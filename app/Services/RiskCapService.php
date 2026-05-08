<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Models\RiskCapGroup;
use Illuminate\Support\Collection;

class RiskCapService
{
    /**
     * Apply active Risk Cap rules to a collection of already-matched winner rows.
     *
     * The prize amount itself is NEVER changed. For capped fraud groups, this method
     * keeps only the winning rows that fit inside the configured group budget.
     * Normal agents/customers are returned unchanged.
     */
    public function applyToWinnerRows(Collection $winnerRows, Product $product): Collection
    {
        if ($winnerRows->isEmpty()) {
            return $winnerRows;
        }

        $ticketIds = $winnerRows->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($ticketIds->isEmpty()) {
            return $winnerRows;
        }

        $tickets = OrderTicket::with('order')
            ->whereIn('id', $ticketIds)
            ->get()
            ->keyBy('id');

        $groupKeys = $tickets
            ->map(fn (OrderTicket $ticket) => $this->groupKeyFromOrder($ticket->order))
            ->filter()
            ->unique()
            ->values();

        $caps = $this->activeCapsForKeys($groupKeys)->keyBy(fn (RiskCapGroup $cap) => $this->groupKey($cap->product_id, $cap->draw_number, $cap->user_id));

        if ($caps->isEmpty()) {
            return $winnerRows;
        }

        $normalRows = collect();
        $cappedRowsByGroup = collect();

        foreach ($winnerRows as $row) {
            $ticket = $tickets->get((int) ($row['id'] ?? 0));
            $key = $ticket ? $this->groupKeyFromOrder($ticket->order) : null;

            if (!$key || !$caps->has($key)) {
                $normalRows->push($row);
                continue;
            }

            $cappedRowsByGroup->put($key, $cappedRowsByGroup->get($key, collect())->push($row));
        }

        $allowedCappedRows = collect();

        foreach ($cappedRowsByGroup as $key => $rows) {
            /** @var RiskCapGroup $cap */
            $cap = $caps->get($key);
            $allowedCappedRows = $allowedCappedRows->merge($this->allowedRowsWithinCap($rows, $product, (float) $cap->max_payable_amount));
        }

        return $normalRows->merge($allowedCappedRows)->values();
    }

    /**
     * For Check Win / Claim screens after a draw is saved, respect the winner state
     * already calculated by DrawService. This prevents a capped ticket that was not
     * allowed by DrawService from being paid later during claim.
     */
    public function applyStoredWinnerGateForInvoice(Collection $winnerRows, Order $invoice): Collection
    {
        if ($winnerRows->isEmpty()) {
            return $winnerRows;
        }

        $cap = $this->activeCapForOrder($invoice);

        if (!$cap) {
            return $winnerRows;
        }

        $winnerTicketIds = OrderTicket::query()
            ->whereIn('id', $winnerRows->pluck('id')->filter()->map(fn ($id) => (int) $id)->values())
            ->where('is_winner', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $winnerRows
            ->filter(fn ($row) => in_array((int) ($row['id'] ?? 0), $winnerTicketIds, true))
            ->values();
    }

    public function activeCapForOrder(?Order $order): ?RiskCapGroup
    {
        if (!$order) {
            return null;
        }

        return RiskCapGroup::query()
            ->active()
            ->where('product_id', $order->product_id)
            ->where('draw_number', $order->draw_number)
            ->where('user_id', $order->user_id)
            ->first();
    }

    public function matchAmount(array $row, Product $product): float
    {
        $match = $this->matchType($row, $product);

        return (float) ($match['amount'] ?? 0);
    }

    public function matchType(array $row, Product $product): ?array
    {
        foreach ($product->prizes as $prize) {
            if (is_numeric($prize->name)) {
                $key = 'Number ' . $prize->name;
            } elseif ($prize->name === 'Chance') {
                $key = 'Chance ' . $prize->chance_number;
            } else {
                $key = $prize->name;
            }

            if (!empty($row[$key]) && $row[$key] === true) {
                return [
                    'key' => $key,
                    'amount' => (float) $prize->prize,
                ];
            }
        }

        return null;
    }

    private function allowedRowsWithinCap(Collection $rows, Product $product, float $maxPayableAmount): Collection
    {
        if ($maxPayableAmount <= 0) {
            return collect();
        }

        $candidates = $rows
            ->map(function ($row) use ($product) {
                $match = $this->matchType($row, $product);

                return [
                    'row' => $row,
                    'ticket_id' => (int) ($row['id'] ?? 0),
                    'match_type' => $match['key'] ?? null,
                    'amount' => (float) ($match['amount'] ?? 0),
                ];
            })
            ->filter(fn ($candidate) => $candidate['amount'] > 0)
            ->sort(function ($a, $b) {
                if ($a['amount'] == $b['amount']) {
                    return $a['ticket_id'] <=> $b['ticket_id'];
                }

                return $a['amount'] <=> $b['amount'];
            })
            ->values();

        $allowed = collect();
        $runningTotal = 0.0;

        foreach ($candidates as $candidate) {
            if (($runningTotal + $candidate['amount']) <= $maxPayableAmount) {
                $allowed->push($candidate['row']);
                $runningTotal += $candidate['amount'];
            }
        }

        return $allowed->values();
    }

    private function activeCapsForKeys(Collection $keys): Collection
    {
        if ($keys->isEmpty()) {
            return collect();
        }

        $parts = $keys->map(function ($key) {
            [$productId, $drawNumber, $userId] = explode('|', $key);

            return [
                'product_id' => (int) $productId,
                'draw_number' => $drawNumber === 'null' ? null : (int) $drawNumber,
                'user_id' => (int) $userId,
            ];
        });

        return RiskCapGroup::query()
            ->active()
            ->where(function ($query) use ($parts) {
                foreach ($parts as $part) {
                    $query->orWhere(function ($subQuery) use ($part) {
                        $subQuery->where('product_id', $part['product_id'])
                            ->where('user_id', $part['user_id']);

                        if ($part['draw_number'] === null) {
                            $subQuery->whereNull('draw_number');
                        } else {
                            $subQuery->where('draw_number', $part['draw_number']);
                        }
                    });
                }
            })
            ->get();
    }

    public function groupKeyFromOrder(?Order $order): ?string
    {
        if (!$order) {
            return null;
        }

        return $this->groupKey($order->product_id, $order->draw_number, $order->user_id);
    }

    public function groupKey(int|string|null $productId, int|string|null $drawNumber, int|string|null $userId): string
    {
        return implode('|', [
            (int) $productId,
            $drawNumber === null ? 'null' : (int) $drawNumber,
            (int) $userId,
        ]);
    }
}
