<?php

namespace App\Services;

use App\Models\OrderTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LockTicketService
{
    /**
     * Find ticket groups that can win regardless of the draw number.
     *
     * A group becomes a lock when one agent covers every possible winning outcome
     * for a configured play type in the same product and draw number.
     */
    public function detect(Request $request): Collection
    {
        [$from, $to] = $this->dateRange($request);

        $tickets = OrderTicket::query()
            ->with([
                'order.user:id,name,email,phone,address',
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
                $coverage['Straight'][$this->numberKey($numbers)] = true;
            }

            if (in_array('Rumble', $types, true)) {
                $sorted = $numbers;
                sort($sorted, SORT_NUMERIC);
                $coverage['Rumble'][$this->numberKey($sorted)] = true;
            }

            if (in_array('Chance', $types, true)) {
                foreach ($this->chanceNumbers($product->prizes) as $chanceNumber) {
                    if ($chanceNumber <= 0 || $chanceNumber > $pickNumber) {
                        continue;
                    }

                    $suffix = array_slice($numbers, -$chanceNumber);
                    $coverage['Chance'][$chanceNumber][$this->numberKey($suffix)] = true;
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
            'first_ticket_at' => $firstCreatedAt ? Carbon::parse($firstCreatedAt)->format('d M, Y h:i:s A') : null,
            'last_ticket_at' => $lastCreatedAt ? Carbon::parse($lastCreatedAt)->format('d M, Y h:i:s A') : null,
            'tickets' => $this->ticketPayloads($tickets),
            'locks' => $locks,
            'risk_score' => $riskScore,
        ];
    }

    private function ticketPayloads(Collection $tickets): array
    {
        return $tickets
            ->sortByDesc(fn (OrderTicket $ticket) => $ticket->order?->created_at ?? $ticket->created_at)
            ->map(function (OrderTicket $ticket) {
                $order = $ticket->order;
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
                ];
            })
            ->values()
            ->all();
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
