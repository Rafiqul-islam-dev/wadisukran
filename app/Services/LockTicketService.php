<?php

namespace App\Services;

use App\Models\OrderTicket;
use App\Models\RiskCapGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LockTicketService
{
    /**
     * Find suspicious ticket groups that can win regardless of the draw number.
     *
     * IMPORTANT: detection is group based: Product + Draw Number + Agent.
     * Held/cancelled risky tickets are ignored, so holding a few selected tickets
     * breaks only that agent's lock. Other agents/customers with the same number
     * remain valid.
     */
    public function detect(Request $request): Collection
    {
        [$from, $to] = $this->dateRange($request);

        $tickets = OrderTicket::query()
            ->withoutRiskHold()
            ->with([
                'order.user:id,name,email,phone,address',
                'order.user.agent:user_id,commission',
                'order.user.agent:user_id,commission',
                'order.customer:id,name,phone,email',
                'order.product' => function ($query) {
                    $query->withTrashed()->with('prizes');
                },
            ])
            ->whereHas('order', function ($query) use ($request, $from, $to) {
                $query->where('status', 'Printed')
                    ->where('is_claimed', 0)
                    ->where('is_winner', 0)
                    ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
                    ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
                    ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
            })
            ->where('is_claimed', 0)
            ->where('is_winner', 0)
            ->latest()
            ->get();

        return $tickets
            ->groupBy(function (OrderTicket $ticket) {
                $order = $ticket->order;

                return implode('|', [
                    $order?->product_id ?? 0,
                    $order?->draw_number ?? 'unknown',
                    $order?->user_id ?? 0,
                ]);
            })
            ->map(fn (Collection $group) => $this->analyseGroup($group))
            ->filter()
            ->sortByDesc('risk_score')
            ->values();
    }

    public function heldTickets(Request $request): Collection
    {
        [$from, $to] = $this->dateRange($request);

        return OrderTicket::query()
            ->riskHeld()
            ->with([
                'order.user:id,name,email,phone,address',
                'order.user.agent:user_id,commission',
                'order.user.agent:user_id,commission',
                'order.customer:id,name,phone,email',
                'order.product' => fn ($query) => $query->withTrashed(),
                'riskHoldBy:id,name',
            ])
            ->whereHas('order', function ($query) use ($request, $from, $to) {
                $query->where('status', 'Printed')
                    ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
                    ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
                    ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
            })
            ->latest('risk_hold_at')
            ->get()
            ->map(fn (OrderTicket $ticket) => $this->ticketPayload($ticket))
            ->values();
    }

    private function analyseGroup(Collection $tickets): ?array
    {
        /** @var OrderTicket|null $firstTicket */
        $firstTicket = $tickets->first();
        $order = $firstTicket?->order;
        $product = $order?->product;

        if (!$product || $product->prize_type !== 'bet') {
            return null;
        }

        $pickNumber = (int) $product->pick_number;
        $base = ((int) $product->type_number) + 1;

        if ($pickNumber <= 0 || $base <= 1) {
            return null;
        }

        $coverage = [
            'Straight' => [],
            'Rumble' => [],
            'Chance' => [],
        ];

        foreach ($tickets as $ticket) {
            $numbers = $this->normalizeNumbers($ticket->selected_numbers);
            $types = $this->normalizePlayTypes($ticket->selected_play_types);

            if (count($numbers) !== $pickNumber) {
                continue;
            }

            if (in_array('Straight', $types, true)) {
                $key = $this->numberKey($numbers);
                $coverage['Straight'][$key][$ticket->id] = $ticket;
            }

            if (in_array('Rumble', $types, true)) {
                $sorted = $numbers;
                sort($sorted, SORT_NUMERIC);
                $key = $this->numberKey($sorted);
                $coverage['Rumble'][$key][$ticket->id] = $ticket;
            }

            if (in_array('Chance', $types, true)) {
                foreach ($this->chanceNumbers($product->prizes) as $chanceNumber) {
                    if ($chanceNumber <= 0 || $chanceNumber > $pickNumber) {
                        continue;
                    }

                    $suffix = array_slice($numbers, -$chanceNumber);
                    $key = $this->numberKey($suffix);
                    $coverage['Chance'][$chanceNumber][$key][$ticket->id] = $ticket;
                }
            }
        }

        $locks = [];

        $straightRequired = $this->safePow($base, $pickNumber);
        $straightCovered = count($coverage['Straight']);
        if ($straightRequired > 0 && $straightCovered >= $straightRequired) {
            $locks[] = $this->lockPayload('Straight', $straightRequired, $straightCovered, 'Exact number coverage');
        }

        $rumbleRequired = $this->combinationWithRepetition($base, $pickNumber);
        $rumbleCovered = count($coverage['Rumble']);
        if ($rumbleRequired > 0 && $rumbleCovered >= $rumbleRequired) {
            $locks[] = $this->lockPayload('Rumble', $rumbleRequired, $rumbleCovered, 'All digit-combination coverage');
        }

        foreach ($coverage['Chance'] as $chanceNumber => $coveredKeys) {
            $required = $this->safePow($base, (int) $chanceNumber);
            $covered = count($coveredKeys);

            if ($required > 0 && $covered >= $required) {
                $locks[] = $this->lockPayload(
                    'Chance ' . $chanceNumber,
                    $required,
                    $covered,
                    'Last ' . $chanceNumber . ' digit coverage'
                );
            }
        }

        if (empty($locks)) {
            return null;
        }

        $orders = $tickets->pluck('order')->filter()->unique('id')->values();
        $firstCreatedAt = $orders->min('created_at');
        $lastCreatedAt = $orders->max('created_at');
        $riskScore = collect($locks)->max(fn ($lock) => $lock['coverage_percent']);
        $breakSuggestions = $this->buildBreakSuggestions($locks, $coverage);
        $riskCap = RiskCapGroup::query()
            ->active()
            ->where('product_id', $product->id)
            ->where('draw_number', $order?->draw_number)
            ->where('user_id', $order?->user?->id)
            ->first();

        return [
            'product_id' => $product->id,
            'product_name' => trim($product->title . ' ' . ($product->product_number ?? '')),
            'draw_number' => $order?->draw_number,
            'agent_id' => $order?->user?->id,
            'agent_name' => $order?->user?->name ?? 'N/A',
            'agent_phone' => $order?->user?->phone,
            'order_count' => $orders->count(),
            'ticket_count' => $tickets->count(),
            'total_price' => (float) $orders->sum('total_price'),
            'commission_percent' => (float) ($order?->commission_percentage ?? $order?->user?->agent?->commission ?? 0),
            'risk_cap' => $riskCap ? [
                'id' => $riskCap->id,
                'status' => $riskCap->status,
                'cap_percent' => (float) $riskCap->cap_percent,
                'total_sale' => (float) $riskCap->total_sale,
                'commission_percent' => (float) $riskCap->commission_percent,
                'commission_amount' => (float) $riskCap->commission_amount,
                'net_sale' => (float) $riskCap->net_sale,
                'max_payable_amount' => (float) $riskCap->max_payable_amount,
                'reason' => $riskCap->reason,
                'applied_at' => $riskCap->applied_at ? Carbon::parse($riskCap->applied_at)->format('d M, Y h:i:s A') : null,
            ] : null,
            'first_ticket_at' => $firstCreatedAt ? Carbon::parse($firstCreatedAt)->format('d M, Y h:i:s A') : null,
            'last_ticket_at' => $lastCreatedAt ? Carbon::parse($lastCreatedAt)->format('d M, Y h:i:s A') : null,
            'tickets' => $this->ticketPayloads($tickets),
            'locks' => collect($locks)->sortBy('type')->values()->all(),
            'risk_score' => $riskScore,
            'break_suggestions' => $breakSuggestions,
        ];
    }

    private function buildBreakSuggestions(array $locks, array $coverage): array
    {
        $lockCandidates = [];

        foreach ($locks as $lock) {
            $type = (string) $lock['type'];
            $map = null;

            if ($type === 'Straight') {
                $map = $coverage['Straight'];
            } elseif ($type === 'Rumble') {
                $map = $coverage['Rumble'];
            } elseif (str_starts_with($type, 'Chance ')) {
                $chanceNumber = (int) str_replace('Chance ', '', $type);
                $map = $coverage['Chance'][$chanceNumber] ?? null;
            }

            if (!$map) {
                continue;
            }

            $candidates = $this->coverageBreakCandidates($type, $map);
            if (empty($candidates)) {
                continue;
            }

            $lockCandidates[] = [
                'lock_type' => $type,
                'min_ticket_count' => min(array_column($candidates, 'ticket_count')),
                'candidates' => $candidates,
            ];
        }

        // Choose larger break groups first, then choose later groups that add the fewest
        // new tickets. This keeps innocent tickets affected as low as possible.
        usort($lockCandidates, fn ($a, $b) => $b['min_ticket_count'] <=> $a['min_ticket_count']);

        $selectedTicketIds = [];
        $selectedTicketMap = [];
        $details = [];

        foreach ($lockCandidates as $lockCandidate) {
            $best = null;
            $bestNewCount = null;

            foreach ($lockCandidate['candidates'] as $candidate) {
                $newIds = array_values(array_diff($candidate['ticket_ids'], $selectedTicketIds));
                $newCount = count($newIds);

                if (
                    $best === null ||
                    $newCount < $bestNewCount ||
                    ($newCount === $bestNewCount && $candidate['ticket_count'] < $best['ticket_count'])
                ) {
                    $best = $candidate;
                    $bestNewCount = $newCount;
                }
            }

            if (!$best) {
                continue;
            }

            $details[] = $best;
            $selectedTicketIds = array_values(array_unique(array_merge($selectedTicketIds, $best['ticket_ids'])));

            foreach ($best['tickets'] as $ticket) {
                $selectedTicketMap[$ticket['id']] = $ticket;
            }
        }

        return [
            'ticket_ids' => array_values(array_map('intval', array_keys($selectedTicketMap))),
            'ticket_count' => count($selectedTicketMap),
            'tickets' => array_values($selectedTicketMap),
            'details' => $details,
            'message' => count($selectedTicketMap) > 0
                ? 'Hold these suggested tickets to break this agent/group lock with minimum customer impact.'
                : null,
        ];
    }

    private function coverageBreakCandidates(string $lockType, array $coverageMap): array
    {
        if (empty($coverageMap)) {
            return [];
        }

        $candidates = [];

        foreach ($coverageMap as $key => $tickets) {
            $payloads = collect($tickets)
                ->map(fn (OrderTicket $ticket) => $this->ticketPayload($ticket))
                ->sortBy('ticket_number')
                ->values()
                ->all();

            $candidates[] = [
                'lock_type' => $lockType,
                'coverage_key' => $this->formatCoverageKey((string) $key, $lockType),
                'ticket_count' => count($payloads),
                'ticket_ids' => collect($payloads)->pluck('id')->values()->all(),
                'ticket_numbers' => collect($payloads)->pluck('ticket_number')->values()->all(),
                'tickets' => $payloads,
            ];
        }

        usort($candidates, function ($a, $b) {
            return $a['ticket_count'] <=> $b['ticket_count'];
        });

        return $candidates;
    }

    private function ticketPayloads(Collection $tickets): array
    {
        return $tickets
            ->sortByDesc(fn (OrderTicket $ticket) => $ticket->order?->created_at ?? $ticket->created_at)
            ->map(fn (OrderTicket $ticket) => $this->ticketPayload($ticket))
            ->values()
            ->all();
    }

    private function ticketPayload(OrderTicket $ticket): array
    {
        $order = $ticket->order;
        $product = $order?->product;
        $numbers = $this->normalizeNumbers($ticket->selected_numbers);
        $playTypes = $this->normalizePlayTypes($ticket->selected_play_types);

        return [
            'id' => $ticket->id,
            'order_id' => $ticket->order_id,
            'invoice_no' => $order?->invoice_no,
            'ticket_number' => implode('', $numbers),
            'selected_numbers' => $numbers,
            'play_types' => $playTypes,
            'play_type_text' => implode(', ', $playTypes),
            'created_at' => $order?->created_at
                ? Carbon::parse($order->created_at)->format('d M, Y h:i:s A')
                : ($ticket->created_at ? Carbon::parse($ticket->created_at)->format('d M, Y h:i:s A') : null),
            'order_total' => (float) ($order?->total_price ?? 0),
            'status' => $order?->status,
            'risk_status' => $ticket->risk_status,
            'risk_reason' => $ticket->risk_reason,
            'risk_hold_at' => $ticket->risk_hold_at ? Carbon::parse($ticket->risk_hold_at)->format('d M, Y h:i:s A') : null,
            'risk_hold_by' => $ticket->riskHoldBy?->name,
            'product_id' => $order?->product_id,
            'product_name' => $product ? trim($product->title . ' ' . ($product->product_number ?? '')) : null,
            'draw_number' => $order?->draw_number,
            'agent_id' => $order?->user?->id,
            'agent_name' => $order?->user?->name,
            'agent_phone' => $order?->user?->phone,
        ];
    }

    private function normalizeNumbers(mixed $numbers): array
    {
        if (is_string($numbers)) {
            $decoded = json_decode($numbers, true);
            $numbers = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $numbers);
        }

        return collect($numbers ?? [])
            ->map(fn ($number) => (string) (int) $number)
            ->values()
            ->all();
    }

    private function normalizePlayTypes(mixed $types): array
    {
        if (is_string($types)) {
            $decoded = json_decode($types, true);
            $types = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $types);
        }

        return collect($types ?? [])
            ->map(function ($type) {
                $type = ucfirst(strtolower(trim((string) $type)));

                // Some old screens/code used "Ramble" spelling. Treat it as Rumble
                // so suspicious Rumble coverage is not missed.
                return $type === 'Ramble' ? 'Rumble' : $type;
            })
            ->values()
            ->all();
    }

    private function chanceNumbers(Collection $prizes): array
    {
        return $prizes
            ->where('type', 'bet')
            ->where('name', 'Chance')
            ->pluck('chance_number')
            ->filter(fn ($number) => (int) $number > 0)
            ->map(fn ($number) => (int) $number)
            ->unique()
            ->values()
            ->all();
    }

    private function numberKey(array $numbers): string
    {
        return implode('-', array_map(fn ($number) => (string) (int) $number, $numbers));
    }

    private function formatCoverageKey(string $key, string $lockType): string
    {
        $digits = explode('-', $key);

        if ($lockType === 'Rumble') {
            return implode(',', $digits);
        }

        return implode('', $digits);
    }

    private function lockPayload(string $type, int $required, int $covered, string $reason): array
    {
        return [
            'type' => $type,
            'reason' => $reason,
            'required_tickets' => $required,
            'covered_tickets' => $covered,
            'coverage_percent' => round(($covered / max($required, 1)) * 100, 2),
        ];
    }

    private function dateRange(Request $request): array
    {
        $from = null;
        $to = null;

        if ($request->filled('date_from')) {
            $from = Carbon::parse($request->date_from . ' ' . ($request->time_from ?: '00:00:00'));
        }

        if ($request->filled('date_to')) {
            $to = Carbon::parse($request->date_to . ' ' . ($request->time_to ?: '23:59:59'));
        }

        if (!$from && !$to) {
            $from = today()->startOfDay();
            $to = today()->endOfDay();
        }

        return [$from, $to];
    }

    private function safePow(int $base, int $power): int
    {
        if ($base <= 0 || $power < 0) {
            return 0;
        }

        return (int) ($base ** $power);
    }

    private function combinationWithRepetition(int $base, int $pickNumber): int
    {
        if ($base <= 0 || $pickNumber <= 0) {
            return 0;
        }

        return $this->combination($base + $pickNumber - 1, $pickNumber);
    }

    private function combination(int $n, int $r): int
    {
        if ($r < 0 || $r > $n) {
            return 0;
        }

        $r = min($r, $n - $r);
        $result = 1;

        for ($i = 1; $i <= $r; $i++) {
            $result = ($result * ($n - $r + $i)) / $i;
        }

        return (int) round($result);
    }
}
