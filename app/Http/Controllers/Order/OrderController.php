<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\CompannySetting;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Models\ProductPrize;
use App\Models\Win;
use App\Models\RiskCapGroup;
use App\Services\AgentAccountService;
use App\Services\CategoryService;
use App\Services\ProductService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected $categoryService;
    protected $productService;
    public function __construct(CategoryService $categoryService, ProductService $productService)
    {
        $this->categoryService = $categoryService;
        $this->productService = $productService;
    }
    public function index(Request $request)
    {
        $match_type = ProductPrize::find($request->match_type);
        $orders = Order::when(!Auth::user()->hasAnyRole(['Super Admin', 'Moderator']), function ($query) {
            $query->where('user_id', Auth::id());
        });

        if (!$request->date_from && !$request->date_to && !$request->invoice_no) {
            $orders = $orders->whereDate('created_at', today());
        }
        if ($request->btn === 'search') {
            $orders = $orders->when($request->user_id, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
                ->when($request->invoice_no, function ($query, $invoice) {
                    $query->where('invoice_no', $invoice);
                })
                ->when($request->date_from, function ($query, $dateFrom) use ($request) {
                    $timeFrom = $request->time_from ?? '00:00:00';
                    $query->where('created_at', '>=', "$dateFrom $timeFrom");
                })
                ->when($request->date_to, function ($query, $dateTo) use ($request) {
                    $timeTo = $request->time_to ?? '23:59:59';
                    $query->where('created_at', '<=', "$dateTo $timeTo");
                })
                ->when($request->category_id && $request->product_id, function ($query) use ($request) {
                    $query->whereHas('product', function ($q) use ($request) {
                        $q->where('category_id', $request->category_id)
                            ->where('id', $request->product_id);
                    });
                })
                ->when($match_type, function ($query, $matchType) {
                    $query->whereHas('tickets', function ($q) use ($matchType) {
                        $q->whereJsonContains(
                            'selected_play_types',
                            ucfirst(strtolower($matchType->name))
                        );
                    });
                });
        }
        $totalPriceSum = (clone $orders)->sum('total_price');
        $orders = $orders->with(['user', 'product', 'user.agent', 'tickets'])->latest()->paginate(10)->through(function ($order) {
            $order->formatted_date = $order->created_at->format('d M, Y h:i:s A');
            return $order;
        });
        $users = User::where('user_type', 'agent')->select('id', 'name')->get();
        $company = CompannySetting::firstOrFail();
        $categories = $this->categoryService->activeCategories();
        $products = Product::where('category_id', $request->category_id)->active()->orderBy('title')->get();

        $product_prizes = [];
        if ($request->product_id) {
            $product = Product::find($request->product_id);
            if ($product->prize_type == 'bet') {
                foreach ($product->prizes as $prize) {
                    $product_prizes[] = [
                        'id' => $prize->id,
                        'name' => $prize->name,
                        'chance_number' => $prize->chance_number
                    ];
                }
            }
        }

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'users' => $users,
            'company' => $company,
            'categories' => $categories,
            'products' => $products,
            'product_prizes' => $product_prizes,
            'totalPriceSum' => $totalPriceSum,
            'filters' => request()->only([
                'user_id',
                'date_from',
                'time_from',
                'date_to',
                'time_to',
                'match_type',
                'category_id',
                'product_id',
                'invoice_no',
            ]),
        ]);
    }

    public function print(Request $request)
    {
        $match_type = ProductPrize::find($request->match_type);
        $orders = Order::when(!Auth::user()->hasAnyRole(['Super Admin', 'Moderator']), function ($query) {
            $query->where('user_id', Auth::id());
        });

        if (!$request->date_from && !$request->date_to) {
            $orders = $orders->whereDate('created_at', today());
        }
        if ($request->btn === 'search') {
            $orders = $orders->when($request->user_id, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
                ->when($request->invoice_no, function ($query, $invoice) {
                    $query->where('invoice_no', $invoice);
                })
                ->when($request->date_from, function ($query, $dateFrom) use ($request) {
                    $timeFrom = $request->time_from ?? '00:00:00';
                    $query->where('created_at', '>=', "$dateFrom $timeFrom");
                })
                ->when($request->date_to, function ($query, $dateTo) use ($request) {
                    $timeTo = $request->time_to ?? '23:59:59';
                    $query->where('created_at', '<=', "$dateTo $timeTo");
                })
                ->when($request->category_id && $request->product_id, function ($query) use ($request) {
                    $query->whereHas('product', function ($q) use ($request) {
                        $q->where('category_id', $request->category_id)
                            ->where('id', $request->product_id);
                    });
                })
                ->when($match_type, function ($query, $matchType) {
                    $query->whereHas('tickets', function ($q) use ($matchType) {
                        $q->whereJsonContains(
                            'selected_play_types',
                            ucfirst(strtolower($matchType->name))
                        );
                    });
                });
        }
        $totalPriceSum = (clone $orders)->sum('total_price');
        $orders = $orders->with(['user', 'product', 'user.agent', 'tickets'])->latest()->get();
        $company = CompannySetting::firstOrFail();

        $pdf = Pdf::loadView('pdf.daily_sales', [
            'orders' => $orders,
            'company' => $company,
            'totalPriceSum' => $totalPriceSum,
            'filters' => $request->only(['date_from', 'date_to', 'time_from', 'time_to'])
        ]);

        return $pdf->download('daily_sales_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    protected function product_prizes($product_id)
    {
        $product_prizes = [];
        $product = Product::find($product_id);

        if ($product->prize_type == 'bet') {
            foreach ($product->prizes as $prize) {
                $product_prizes[] = [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'chance_number' => $prize->chance_number
                ];
            }
        }

        return $product_prizes;
    }


    public function probableWinSuggestions(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'percentage' => ['required', 'numeric', 'min:1', 'max:100'],
            'match_type' => ['nullable'],
            'date_from' => ['required', 'date'],
            'time_from' => ['required', 'string'],
            'date_to' => ['required', 'date'],
            'time_to' => ['required', 'string'],
        ]);

        [$from, $to] = $this->probableWinsDateRange($request);

        if ($from && $to && $from->gt($to)) {
            return response()->json([
                'status' => false,
                'message' => 'From date/time must be before To date/time.',
                'errors' => [
                    'date_to' => ['From date/time must be before To date/time.'],
                ],
            ], 422);
        }

        $product = Product::with('prizes')->findOrFail($validated['product_id']);
        $percentage = (float) $validated['percentage'];

        $orderTotals = (clone $this->probableWinsOrderQuery($request, $from, $to))
            ->selectRaw('COALESCE(SUM(total_price), 0) as total_sell, COALESCE(SUM(commission), 0) as total_commission')
            ->first();

        $totalSell = (float) ($orderTotals->total_sell ?? 0);
        $totalCommission = (float) ($orderTotals->total_commission ?? 0);
        $netSell = max(0, round($totalSell - $totalCommission, 2));
        $targetAmount = round(($netSell * $percentage) / 100, 2);

        // Suggestion must be accurate: only Printed tickets from selected product/date/time,
        // plus minimal order fields needed to apply Risk Cap exactly.
        $tickets = $this->probableWinsTicketQuery($request, $from, $to)
            ->with(['order:id,product_id,draw_number,user_id'])
            ->get(['id', 'order_id', 'selected_numbers', 'selected_play_types']);

        if ($tickets->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No printed tickets found for this product/filter.',
                'meta' => [
                    'total_sell' => round($totalSell, 2),
                    'total_commission' => round($totalCommission, 2),
                    'net_sell' => $netSell,
                    'percentage' => $percentage,
                    'target_amount' => $targetAmount,
                    'ticket_count' => 0,
                    'candidate_count' => 0,
                    'exact_checked_count' => 0,
                    'risk_cap_count' => 0,
                    'risk_cap_scope' => 'Selected product and selected date/time printed tickets only',
                ],
                'suggestions' => [],
            ]);
        }

        $types = $this->probableWinsTypes($product, $request->match_type);
        $preparedTickets = $this->prepareFastProbableSuggestionTickets($product, $tickets);

        // Accuracy rule: suggestions are generated only from numbers that exist in
        // Printed tickets for this product/date/time filter. No random outside number
        // is used, so "Use Number" + normal Probable Wins Search will match.
        $candidateNumbers = $this->probableWinSuggestionPrintedCandidateNumbers($product, $preparedTickets);
        $riskCaps = $this->fastProbableRiskCaps($preparedTickets['group_keys'] ?? [], $product->id);

        $suggestions = $candidateNumbers
            ->map(function (array $pickNumbers) use ($product, $preparedTickets, $types, $targetAmount, $riskCaps) {
                $calculation = $this->calculateFastProbableSuggestion(
                    $product,
                    $preparedTickets['tickets'],
                    $pickNumbers,
                    $types,
                    true,
                    $riskCaps
                );

                $totalAmount = (float) $calculation['total_amount'];

                return [
                    'pick_number' => $pickNumbers,
                    'number_text' => implode('', $pickNumbers),
                    'summary' => $calculation['summary'],
                    'winner_count' => (int) $calculation['winner_count'],
                    'total_amount' => round($totalAmount, 2),
                    'difference' => round(abs($targetAmount - $totalAmount), 2),
                    'is_over_budget' => $targetAmount > 0 && $totalAmount > $targetAmount,
                ];
            })
            ->sort($this->probableSuggestionSorter($targetAmount))
            ->take(2)
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Suggestion generated successfully.',
            'meta' => [
                'total_sell' => round($totalSell, 2),
                'total_commission' => round($totalCommission, 2),
                'net_sell' => $netSell,
                'percentage' => $percentage,
                'target_amount' => $targetAmount,
                'ticket_count' => count($preparedTickets['tickets']),
                'candidate_count' => $candidateNumbers->count(),
                'exact_checked_count' => $candidateNumbers->count(),
                'risk_cap_count' => count($riskCaps),
                'risk_cap_scope' => 'Selected product and selected date/time printed tickets only',
            ],
            'suggestions' => $suggestions,
        ]);
    }

    private function probableSuggestionSorter(float $targetAmount): callable
    {
        return function ($a, $b) use ($targetAmount) {
            $aOver = $targetAmount > 0 && $a['total_amount'] > $targetAmount;
            $bOver = $targetAmount > 0 && $b['total_amount'] > $targetAmount;

            // Prefer numbers that stay inside the target budget. If all numbers are over
            // budget, the closest over-budget number will still be shown.
            if ($aOver !== $bOver) {
                return $aOver <=> $bOver;
            }

            if ($a['difference'] == $b['difference']) {
                if ($a['total_amount'] == $b['total_amount']) {
                    return $b['winner_count'] <=> $a['winner_count'];
                }

                return $b['total_amount'] <=> $a['total_amount'];
            }

            return $a['difference'] <=> $b['difference'];
        };
    }

    private function probableSuggestionCandidateLimit(int $ticketCount): int
    {
        // The old suggestion waited because it tested too many candidate numbers.
        // These limits are enough for a helper suggestion and keep the request fast.
        if ($ticketCount >= 10000) {
            return 45;
        }

        if ($ticketCount >= 5000) {
            return 55;
        }

        if ($ticketCount >= 1500) {
            return 70;
        }

        return 90;
    }

    private function probableWinCandidateNumbers(Product $product, Collection $tickets): Collection
    {
        $product->loadMissing('prizes');

        $pickNumber = max(1, (int) $product->pick_number);
        $typeNumber = max(1, (int) $product->type_number);
        $maxCandidates = 25000;
        $candidates = collect();
        $seen = [];

        $addCandidate = function ($numbers) use (&$candidates, &$seen, $pickNumber, $typeNumber, $maxCandidates) {
            if ($candidates->count() >= $maxCandidates) {
                return;
            }

            $numbers = collect($numbers)
                ->take($pickNumber)
                ->map(fn ($number) => $this->formatProbableNumberValue($number, $typeNumber))
                ->values()
                ->all();

            if (count($numbers) !== $pickNumber) {
                return;
            }

            $key = implode('|', $numbers);
            if (isset($seen[$key])) {
                return;
            }

            $seen[$key] = true;
            $candidates->push($numbers);
        };

        $soldValues = collect();
        $positionWiseValues = [];

        foreach ($tickets as $ticket) {
            $ticketNumbers = collect($ticket->selected_numbers ?? [])->values();
            $addCandidate($ticketNumbers);

            foreach ($ticketNumbers as $index => $number) {
                $formatted = $this->formatProbableNumberValue($number, $typeNumber);
                $soldValues->push($formatted);
                $positionWiseValues[$index] ??= collect();
                $positionWiseValues[$index]->push($formatted);
            }
        }

        $drawValues = $this->probableProductDrawValues($product, $soldValues);
        $possibleCount = pow(max(1, $drawValues->count()), $pickNumber);

        if ($possibleCount <= $maxCandidates) {
            $this->buildProbableNumberTree($drawValues->all(), $pickNumber, [], $addCandidate);
            return $candidates->values();
        }

        $positionTopValues = collect($positionWiseValues)
            ->map(fn (Collection $values) => $values->countBy()->sortDesc()->keys()->values())
            ->values();

        $maxRows = min(1000, max(100, $tickets->count() * 2));
        for ($row = 0; $row < $maxRows; $row++) {
            $numbers = [];
            for ($position = 0; $position < $pickNumber; $position++) {
                $values = $positionTopValues[$position] ?? $drawValues;
                if ($values->isEmpty()) {
                    $values = $drawValues;
                }
                $numbers[] = $values[$row % $values->count()];
            }
            $addCandidate($numbers);
        }

        $topOverallValues = $soldValues->countBy()->sortDesc()->keys()->take(18)->values();
        if ($topOverallValues->isEmpty()) {
            $topOverallValues = $drawValues->take(18)->values();
        }

        for ($offset = 0; $offset < $topOverallValues->count(); $offset++) {
            $numbers = [];
            for ($position = 0; $position < $pickNumber; $position++) {
                $numbers[] = $topOverallValues[($offset + $position) % $topOverallValues->count()];
            }
            $addCandidate($numbers);
        }

        if ($product->prize_type !== 'bet' && $topOverallValues->count() >= $pickNumber) {
            $this->buildProbableCombinationTree(
                $topOverallValues->all(),
                $pickNumber,
                0,
                [],
                $addCandidate
            );
        }

        return $candidates->values();
    }

    private function probableWinSuggestionPrintedCandidateNumbers(Product $product, array $preparedTickets): Collection
    {
        $pickNumber = max(1, (int) $product->pick_number);
        $typeNumber = max(1, (int) $product->type_number);
        $candidates = collect();
        $seen = [];

        $addCandidate = function ($numbers) use (&$candidates, &$seen, $pickNumber, $typeNumber) {
            $numbers = collect($numbers)
                ->take($pickNumber)
                ->map(fn ($number) => $this->formatProbableNumberValue($number, $typeNumber))
                ->values()
                ->all();

            if (count($numbers) !== $pickNumber) {
                return;
            }

            $key = implode('|', $numbers);
            if (isset($seen[$key])) {
                return;
            }

            $seen[$key] = true;
            $candidates->push($numbers);
        };

        foreach (($preparedTickets['tickets'] ?? []) as $ticket) {
            $numbers = $ticket['numbers'] ?? [];
            if (empty($numbers)) {
                continue;
            }

            // Main candidate: exactly what was sold/printed.
            $addCandidate($numbers);

            // For bet products, Rumble can win on permutations. Add reverse/sorted
            // candidates from the same printed ticket numbers only; no outside random.
            if ($product->prize_type === 'bet') {
                $addCandidate(array_reverse($numbers));
                $sorted = $numbers;
                sort($sorted);
                $addCandidate($sorted);
            }
        }

        return $candidates->values();
    }

    private function probableWinSuggestionCandidateNumbers(Product $product, array $preparedTickets, int $maxCandidates = 90): Collection
    {
        $pickNumber = max(1, (int) $product->pick_number);
        $typeNumber = max(1, (int) $product->type_number);
        $drawValues = collect($preparedTickets['draw_values'] ?? []);
        $candidates = collect();
        $seen = [];

        $addCandidate = function ($numbers) use (&$candidates, &$seen, $pickNumber, $typeNumber, $maxCandidates) {
            if ($candidates->count() >= $maxCandidates) {
                return;
            }

            $numbers = collect($numbers)
                ->take($pickNumber)
                ->map(fn ($number) => $this->formatProbableNumberValue($number, $typeNumber))
                ->values()
                ->all();

            if (count($numbers) !== $pickNumber) {
                return;
            }

            $key = implode('|', $numbers);
            if (isset($seen[$key])) {
                return;
            }

            $seen[$key] = true;
            $candidates->push($numbers);
        };

        foreach (($preparedTickets['tickets'] ?? []) as $index => $ticket) {
            if ($index >= 25) {
                break;
            }

            $numbers = $ticket['numbers'];
            $addCandidate($numbers);
            $addCandidate(array_reverse($numbers));
            $sorted = $numbers;
            sort($sorted);
            $addCandidate($sorted);
        }

        $positionTopValues = [];
        $positionLowValues = [];
        for ($position = 0; $position < $pickNumber; $position++) {
            $values = $preparedTickets['position_values'][$position] ?? [];
            arsort($values);
            $positionTopValues[$position] = array_keys($values) ?: $drawValues->all();
            asort($values);
            $positionLowValues[$position] = array_keys($values) ?: array_reverse($drawValues->all());
        }

        $addRows = function (array $positionValues, int $rows, int $offset = 0) use ($pickNumber, $addCandidate) {
            for ($row = 0; $row < $rows; $row++) {
                $numbers = [];
                for ($position = 0; $position < $pickNumber; $position++) {
                    $values = $positionValues[$position] ?? [];
                    if (empty($values)) {
                        continue 2;
                    }
                    $numbers[] = $values[($row + $offset + $position) % count($values)];
                }
                $addCandidate($numbers);
            }
        };

        $addRows($positionTopValues, 10);
        $addRows($positionLowValues, 10);
        $addRows($positionTopValues, 8, 3);
        $addRows($positionLowValues, 8, 5);

        $valueFrequency = $preparedTickets['value_frequency'] ?? [];
        arsort($valueFrequency);
        $topOverallValues = array_slice(array_keys($valueFrequency), 0, 20);
        asort($valueFrequency);
        $lowOverallValues = array_slice(array_keys($valueFrequency), 0, 20);

        foreach ([$topOverallValues, $lowOverallValues, $drawValues->all(), array_reverse($drawValues->all())] as $values) {
            if (empty($values)) {
                continue;
            }

            $limit = min(12, count($values));
            for ($offset = 0; $offset < $limit; $offset++) {
                $numbers = [];
                for ($position = 0; $position < $pickNumber; $position++) {
                    $numbers[] = $values[($offset + $position) % count($values)];
                }
                $addCandidate($numbers);
            }
        }

        $values = $drawValues->all();
        if (!empty($values)) {
            $sampleSpace = $this->boundedPossibleCount(max(1, count($values)), $pickNumber, 2147483647);
            $remaining = max(0, $maxCandidates - $candidates->count());
            $step = $remaining > 0 ? max(1, (int) floor($sampleSpace / $remaining)) : 1;

            for ($i = 0; $i < $remaining; $i++) {
                $index = (int) (($i * $step + ($i * 37)) % max(1, $sampleSpace));
                $addCandidate($this->candidateFromOrdinal($values, $pickNumber, $index));
            }
        }

        return $candidates->values();
    }

    private function prepareFastProbableSuggestionTickets(Product $product, Collection $tickets): array
    {
        $typeNumber = max(1, (int) $product->type_number);
        $prepared = [];
        $soldValues = [];
        $positionValues = [];
        $valueFrequency = [];

        foreach ($tickets as $ticket) {
            $numbers = $this->normalizeProbableNumbers($ticket->selected_numbers ?? [], $typeNumber);
            if (empty($numbers)) {
                continue;
            }

            $types = $this->normalizeProbablePlayTypes($ticket->selected_play_types ?? []);
            $sortedNumbers = $numbers;
            sort($sortedNumbers);

            $groupKey = $ticket->order
                ? $this->fastProbableRiskCapKey($ticket->order->product_id, $ticket->order->draw_number, $ticket->order->user_id)
                : null;

            $prepared[] = [
                'id' => (int) $ticket->id,
                'numbers' => $numbers,
                'numbers_sorted' => $sortedNumbers,
                'numbers_reverse' => array_reverse($numbers),
                'types' => $types,
                'types_lookup' => array_fill_keys($types, true),
                'group_key' => $groupKey,
            ];

            foreach ($numbers as $index => $number) {
                $soldValues[] = $number;
                $positionValues[$index][$number] = ($positionValues[$index][$number] ?? 0) + 1;
                $valueFrequency[$number] = ($valueFrequency[$number] ?? 0) + 1;
            }
        }

        $drawValues = $this->probableProductDrawValues($product, collect($soldValues))->all();

        return [
            'tickets' => $prepared,
            'draw_values' => $drawValues,
            'position_values' => $positionValues,
            'value_frequency' => $valueFrequency,
            'group_keys' => collect($prepared)->pluck('group_key')->filter()->unique()->values()->all(),
        ];
    }

    private function fastProbableRiskCaps(array $groupKeys, int|string|null $productId = null): array
    {
        $groupKeys = collect($groupKeys)->filter()->unique()->values();

        if ($groupKeys->isEmpty()) {
            return [];
        }

        $parts = $groupKeys->map(function ($key) {
            [$keyProductId, $drawNumber, $userId] = explode('|', $key);

            return [
                'product_id' => (int) $keyProductId,
                'draw_number' => $drawNumber === 'null' ? null : (string) $drawNumber,
                'user_id' => (int) $userId,
            ];
        });

        $query = RiskCapGroup::query()
            ->active();

        if ($productId !== null && $productId !== '') {
            $query->where('product_id', (int) $productId);
        }

        return $query
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
            ->get()
            ->mapWithKeys(fn (RiskCapGroup $cap) => [
                $this->fastProbableRiskCapKey($cap->product_id, $cap->draw_number, $cap->user_id) => (float) $cap->max_payable_amount,
            ])
            ->all();
    }

    private function fastProbableRiskCapKey($productId, $drawNumber, $userId): string
    {
        return implode('|', [
            (int) $productId,
            ($drawNumber === null || $drawNumber === '') ? 'null' : (string) $drawNumber,
            (int) $userId,
        ]);
    }

    private function applyFastProbableRiskCapToRows(Collection $winnerRows, array $riskCaps): Collection
    {
        if ($winnerRows->isEmpty() || empty($riskCaps)) {
            return $winnerRows;
        }

        $normalRows = collect();
        $cappedRowsByGroup = collect();

        foreach ($winnerRows as $row) {
            $groupKey = $row['group_key'] ?? null;

            if (!$groupKey || !array_key_exists($groupKey, $riskCaps)) {
                $normalRows->push($row);
                continue;
            }

            $cappedRowsByGroup->put($groupKey, $cappedRowsByGroup->get($groupKey, collect())->push($row));
        }

        $allowedCappedRows = collect();

        foreach ($cappedRowsByGroup as $groupKey => $rows) {
            $maxPayableAmount = (float) ($riskCaps[$groupKey] ?? 0);

            if ($maxPayableAmount <= 0) {
                continue;
            }

            $runningTotal = 0.0;
            $rows = $rows
                ->sort(function ($a, $b) {
                    if ((float) ($a['amount'] ?? 0) == (float) ($b['amount'] ?? 0)) {
                        return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
                    }

                    return ((float) ($a['amount'] ?? 0)) <=> ((float) ($b['amount'] ?? 0));
                })
                ->values();

            foreach ($rows as $row) {
                $amount = (float) ($row['amount'] ?? 0);

                if ($amount > 0 && ($runningTotal + $amount) <= $maxPayableAmount) {
                    $allowedCappedRows->push($row);
                    $runningTotal += $amount;
                }
            }
        }

        return $normalRows->merge($allowedCappedRows)->values();
    }

    private function calculateFastProbableSuggestion(Product $product, array $tickets, array $pickNumbers, Collection $types, bool $applyRiskCap, array $riskCaps = []): array
    {
        $typeNumber = max(1, (int) $product->type_number);
        $pickNumbers = $this->normalizeProbableNumbers($pickNumbers, $typeNumber);
        $allowedKeys = [];
        $summary = [];
        $winnerRows = collect();
        $chancePrizes = $product->prizes->where('name', 'Chance')->sortByDesc('chance_number')->values();
        $numberPrizes = $product->prizes->sortByDesc('name')->values();

        foreach ($types as $type) {
            $key = $this->probableWinPrizeKey($type);
            $allowedKeys[$key] = true;
            $summary[$key] = [
                'match_type' => $key,
                'winners' => 0,
                'prize_per_winner' => (float) $type->prize,
                'tickets' => [],
                'total_amount' => 0,
            ];
        }

        foreach ($tickets as $ticket) {
            $winnerKey = $this->fastProbableWinningKeyForTicket($product, $ticket, $pickNumbers, $chancePrizes, $numberPrizes);

            if (!$winnerKey || !isset($allowedKeys[$winnerKey])) {
                continue;
            }

            if ($applyRiskCap) {
                $winnerRows->push([
                    'id' => $ticket['id'],
                    'group_key' => $ticket['group_key'] ?? null,
                    'match_key' => $winnerKey,
                    'amount' => (float) ($summary[$winnerKey]['prize_per_winner'] ?? 0),
                    $winnerKey => true,
                ]);
                continue;
            }

            $summary[$winnerKey]['winners']++;
        }

        if ($applyRiskCap && $winnerRows->isNotEmpty()) {
            $winnerRows = $this->applyFastProbableRiskCapToRows($winnerRows, $riskCaps);

            foreach ($winnerRows as $row) {
                $key = $row['match_key'] ?? null;
                if ($key && isset($summary[$key])) {
                    $summary[$key]['winners']++;
                }
            }
        }

        foreach ($summary as $key => $row) {
            $summary[$key]['total_amount'] = round($row['winners'] * $row['prize_per_winner'], 2);
        }

        $summary = collect($summary)
            ->filter(fn ($row) => $row['winners'] > 0)
            ->sortByDesc('total_amount')
            ->values();

        return [
            'summary' => $summary,
            'winner_count' => (int) $summary->sum('winners'),
            'total_amount' => (float) $summary->sum('total_amount'),
        ];
    }

    private function fastProbableWinningKeyForTicket(Product $product, array $ticket, array $pickNumbers, Collection $chancePrizes, Collection $numberPrizes): ?string
    {
        $ticketNumbers = $ticket['numbers'];
        $ticketTypes = $ticket['types_lookup'];
        $numbersStraight = array_values($pickNumbers);
        $numbersChance = array_reverse($numbersStraight);
        $numbersSorted = $numbersStraight;
        sort($numbersSorted);
        $len = count($numbersStraight);

        if ($product->prize_type === 'bet') {
            $isStraightWinner = false;
            $isRumbleWinner = false;

            if (isset($ticketTypes['Straight'])) {
                $isStraightWinner = count($ticketNumbers) === $len && $ticketNumbers == $numbersStraight;
                if ($isStraightWinner) {
                    return 'Straight';
                }
            }

            if (isset($ticketTypes['Rumble'])) {
                $isRumbleWinner = count($ticketNumbers) === $len && $ticket['numbers_sorted'] == $numbersSorted;
                if ($isRumbleWinner) {
                    return 'Rumble';
                }
            }

            if (isset($ticketTypes['Chance'])) {
                $ticketReverse = $ticket['numbers_reverse'];
                $matchCount = 0;
                for ($index = 0; $index < min(count($ticketReverse), count($numbersChance)); $index++) {
                    if ((string) $ticketReverse[$index] != (string) $numbersChance[$index]) {
                        break;
                    }
                    $matchCount++;
                }

                foreach ($chancePrizes as $chanceType) {
                    if ($matchCount == (int) $chanceType->chance_number) {
                        return $this->probableWinPrizeKey($chanceType);
                    }
                }
            }

            return null;
        }

        if ((int) $product->product_number === 9) {
            $matchCount = 0;
            $matchedNumbers = [];
            for ($index = 0; $index < min(count($ticketNumbers), count($numbersStraight)); $index++) {
                if ((string) $ticketNumbers[$index] !== (string) $numbersStraight[$index]) {
                    break;
                }
                $matchedNumbers[] = $ticketNumbers[$index];
                $matchCount++;
            }
        } elseif ((int) $product->product_number === 2) {
            $ticketReverse = $ticket['numbers_reverse'];
            $drawReverse = array_reverse($numbersStraight);
            $matchCount = 0;
            $matchedNumbers = [];
            for ($index = 0; $index < min(count($ticketReverse), count($drawReverse)); $index++) {
                if ((string) $ticketReverse[$index] !== (string) $drawReverse[$index]) {
                    break;
                }
                $matchedNumbers[] = $ticketReverse[$index];
                $matchCount++;
            }
        } else {
            $matchedNumbers = array_values(array_intersect($ticketNumbers, $numbersStraight));
            $matchCount = count($matchedNumbers);
        }

        foreach ($numberPrizes as $prize) {
            $prizeNameLower = strtolower($prize->name);
            $firstMatchedNumber = $matchedNumbers[0] ?? null;

            if ($prizeNameLower === 'platinum') {
                if ($matchCount === 1 && in_array($firstMatchedNumber, [25, 26, 27])) {
                    return $this->probableWinPrizeKey($prize);
                }
            } elseif ($prizeNameLower === 'golden') {
                if ($matchCount === 1 && in_array($firstMatchedNumber, [5, 6, 7])) {
                    return $this->probableWinPrizeKey($prize);
                }
            } elseif ($prizeNameLower === 'normal') {
                if ($matchCount === 1 && !in_array($firstMatchedNumber, [25, 26, 27, 5, 6, 7])) {
                    return $this->probableWinPrizeKey($prize);
                }
            } elseif ($matchCount === (int) $prize->name) {
                return $this->probableWinPrizeKey($prize);
            }
        }

        return null;
    }

    private function normalizeProbableNumbers($numbers, int $typeNumber): array
    {
        if (!is_array($numbers)) {
            $numbers = (array) $numbers;
        }

        return collect($numbers)
            ->filter(fn ($number) => $number !== null && $number !== '')
            ->map(fn ($number) => $this->formatProbableNumberValue($number, $typeNumber))
            ->values()
            ->all();
    }

    private function normalizeProbablePlayTypes($types): array
    {
        if (is_string($types)) {
            $decoded = json_decode($types, true);
            $types = json_last_error() === JSON_ERROR_NONE ? $decoded : [$types];
        }

        return collect((array) $types)
            ->filter(fn ($type) => $type !== null && $type !== '')
            ->map(fn ($type) => (string) $type)
            ->values()
            ->all();
    }

    private function boundedPossibleCount(int $base, int $power, int $limit): int
    {
        $count = 1;

        for ($i = 0; $i < $power; $i++) {
            if ($base > 0 && $count > intdiv($limit, $base)) {
                return $limit + 1;
            }

            $count *= $base;
        }

        return $count;
    }

    private function candidateFromOrdinal(array $values, int $pickNumber, int $ordinal): array
    {
        $base = max(1, count($values));
        $numbers = [];

        for ($position = 0; $position < $pickNumber; $position++) {
            $numbers[] = $values[$ordinal % $base];
            $ordinal = intdiv($ordinal, $base);
        }

        return $numbers;
    }

    private function estimateProbableWinsFromTickets(Product $product, Collection $tickets, array $pickNumbers, Collection $types): array
    {
        $product->loadMissing('prizes');

        $numbersStraight = collect($pickNumbers)->values();
        $numbersChance = collect($pickNumbers)->reverse()->values();
        $numbersSorted = collect($pickNumbers)->sort()->values();
        $len = $numbersStraight->count();

        $summary = [];
        foreach ($types as $type) {
            $key = $this->probableWinPrizeKey($type);
            $summary[$key] = [
                'match_type' => $key,
                'winners' => 0,
                'prize_per_winner' => (float) $type->prize,
                'tickets' => [],
                'total_amount' => 0,
            ];
        }

        foreach ($tickets as $ticket) {
            $winnerKey = $this->probableWinningKeyForTicket(
                $product,
                $ticket,
                $numbersStraight,
                $numbersSorted,
                $numbersChance,
                $len
            );

            if ($winnerKey && isset($summary[$winnerKey])) {
                $summary[$winnerKey]['winners']++;
            }
        }

        foreach ($summary as $key => $row) {
            $summary[$key]['total_amount'] = round($row['winners'] * $row['prize_per_winner'], 2);
        }

        $summary = collect($summary)
            ->filter(fn ($row) => $row['winners'] > 0)
            ->sortByDesc('total_amount')
            ->values();

        return [
            'summary' => $summary,
            'winner_count' => (int) $summary->sum('winners'),
            'total_amount' => (float) $summary->sum('total_amount'),
        ];
    }

    private function probableWinningKeyForTicket(
        Product $product,
        OrderTicket $ticket,
        Collection $numbersStraight,
        Collection $numbersSorted,
        Collection $numbersChance,
        int $len
    ): ?string {
        $ticketTypes = is_array($ticket->selected_play_types)
            ? $ticket->selected_play_types
            : (array) $ticket->selected_play_types;
        $ticketNumbers = collect($ticket->selected_numbers)->values();

        if ($product->prize_type === 'bet') {
            if (isset($ticketTypes['Straight'])) {
                $isStraightWinner =
                    $ticketNumbers->count() === $len &&
                    $ticketNumbers->all() == $numbersStraight->all();

                if ($isStraightWinner) {
                    return 'Straight';
                }
            }

            if (isset($ticketTypes['Rumble'])) {
                $isRumbleWinner =
                    $ticketNumbers->count() === $len &&
                    $ticketNumbers->sort()->values()->all() == $numbersSorted->all();

                if ($isRumbleWinner) {
                    return 'Rumble';
                }
            }

            if (isset($ticketTypes['Chance'])) {
                $matchCount = $ticketNumbers->reverse()
                    ->values()
                    ->zip($numbersChance)
                    ->takeWhile(fn ($pair) => (string) $pair[0] == (string) $pair[1])
                    ->count();

                foreach ($chancePrizes as $chanceType) {
                    if ($matchCount == (int) $chanceType->chance_number) {
                        return $this->probableWinPrizeKey($chanceType);
                    }
                }
            }

            return null;
        }

        if ((int) $product->product_number === 9) {
            $matchCount = $ticketNumbers->zip($numbersStraight)
                ->takeWhile(fn ($pair) => (string) $pair[0] === (string) $pair[1])
                ->count();
            $matchedNumbers = $ticketNumbers->take($matchCount);
        } elseif ((int) $product->product_number === 2) {
            $matchCount = $ticketNumbers->reverse()->values()
                ->zip($numbersStraight->reverse()->values())
                ->takeWhile(fn ($pair) => (string) $pair[0] === (string) $pair[1])
                ->count();
            $matchedNumbers = $ticketNumbers->reverse()->take($matchCount);
        } else {
            $matchedNumbers = $ticketNumbers->intersect($numbersStraight);
            $matchCount = $matchedNumbers->count();
        }

        foreach ($numberPrizes as $prize) {
            $prizeNameLower = strtolower($prize->name);

            if ($prizeNameLower === 'platinum') {
                if ($matchCount === 1 && in_array($matchedNumbers->first(), [25, 26, 27])) {
                    return $this->probableWinPrizeKey($prize);
                }
            } elseif ($prizeNameLower === 'golden') {
                if ($matchCount === 1 && in_array($matchedNumbers->first(), [5, 6, 7])) {
                    return $this->probableWinPrizeKey($prize);
                }
            } elseif ($prizeNameLower === 'normal') {
                if ($matchCount === 1 && !in_array($matchedNumbers->first(), [25, 26, 27, 5, 6, 7])) {
                    return $this->probableWinPrizeKey($prize);
                }
            } elseif ($matchCount === (int) $prize->name) {
                return $this->probableWinPrizeKey($prize);
            }
        }

        return null;
    }

    private function probableWinPrizeKey(ProductPrize $type): string
    {
        if (is_numeric($type->name)) {
            return 'Number ' . $type->name;
        }

        if ($type->name === 'Chance') {
            return $type->name . ' ' . $type->chance_number;
        }

        return $type->name;
    }

    private function probableProductDrawValues(Product $product, Collection $soldValues): Collection
    {
        $typeNumber = max(1, (int) $product->type_number);
        $start = $typeNumber <= 9 ? 0 : 1;

        $values = collect(range($start, $typeNumber))
            ->map(fn ($number) => $this->formatProbableNumberValue($number, $typeNumber));

        return $values
            ->merge($soldValues)
            ->unique()
            ->values();
    }

    private function formatProbableNumberValue($number, int $typeNumber): string
    {
        $value = (int) $number;

        return $typeNumber > 9
            ? str_pad((string) $value, 2, '0', STR_PAD_LEFT)
            : (string) $value;
    }

    private function buildProbableNumberTree(array $values, int $length, array $current, callable $addCandidate): void
    {
        if (count($current) === $length) {
            $addCandidate($current);
            return;
        }

        foreach ($values as $value) {
            $next = [...$current, $value];
            $this->buildProbableNumberTree($values, $length, $next, $addCandidate);
        }
    }

    private function buildProbableCombinationTree(array $values, int $length, int $start, array $current, callable $addCandidate): void
    {
        if (count($current) === $length) {
            $addCandidate($current);
            return;
        }

        for ($index = $start; $index < count($values); $index++) {
            $this->buildProbableCombinationTree(
                $values,
                $length,
                $index + 1,
                [...$current, $values[$index]],
                $addCandidate
            );
        }
    }

    private function probableWinsDateRange(Request $request): array
    {
        $from = null;
        $to = null;

        if ($request->date_from) {
            $from = Carbon::parse($request->date_from . ' ' . ($request->time_from ?? '00:00:00'));
        }

        if ($request->date_to) {
            $to = Carbon::parse($request->date_to . ' ' . ($request->time_to ?? '23:59:59'));
        }

        return [$from, $to];
    }

    private function probableWinsOrderQuery(Request $request, ?Carbon $from = null, ?Carbon $to = null)
    {
        return Order::query()
            ->where('status', 'Printed')
            ->where('is_claimed', 0)
            ->where('is_winner', 0)
            ->where('product_id', $request->product_id)
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to));
    }

    private function probableWinsTicketQuery(Request $request, ?Carbon $from = null, ?Carbon $to = null)
    {
        $matchType = ProductPrize::find($request->match_type);

        return OrderTicket::query()
            ->withoutRiskHold()
            ->whereHas('order', function ($query) use ($request, $from, $to) {
                $query->where('status', 'Printed')
                    ->where('is_claimed', 0)
                    ->where('is_winner', 0)
                    ->where('product_id', $request->product_id);

                if ($from) {
                    $query->where('created_at', '>=', $from);
                }

                if ($to) {
                    $query->where('created_at', '<=', $to);
                }
            })
            ->when($request->match_type, function ($query) use ($matchType, $request) {
                if ($request->match_type === 'Chance') {
                    $query->whereJsonContains('selected_play_types', 'Chance');
                } elseif ($matchType) {
                    $query->whereJsonContains('selected_play_types', $matchType->name);
                }
            });
    }

    private function probableWinsTypes(Product $product, $matchType): Collection
    {
        $product->loadMissing('prizes');

        if ($matchType === 'Chance') {
            return ProductPrize::where('product_id', $product->id)
                ->where('name', 'Chance')
                ->get();
        }

        if ($matchType) {
            $type = ProductPrize::find($matchType);

            return $type ? collect([$type]) : $product->prizes;
        }

        return $product->prizes;
    }

    private function randomProbablePickNumbers(Product $product): array
    {
        $numbers = [];
        $pickNumber = max(1, (int) $product->pick_number);
        $typeNumber = max(1, (int) $product->type_number);
        $pad = $typeNumber > 9 ? strlen((string) $typeNumber) : 1;

        for ($i = 0; $i < $pickNumber; $i++) {
            $number = random_int(1, $typeNumber);
            $numbers[] = $pad > 1 ? str_pad((string) $number, $pad, '0', STR_PAD_LEFT) : (string) $number;
        }

        return $numbers;
    }

    private function calculateProbableWinsFromTickets(Product $product, Collection $tickets, array $pickNumbers, Collection $types): array
    {
        $product->loadMissing('prizes');

        $numbersStraight = collect($pickNumbers)->values();
        $numbersChance = collect($pickNumbers)->reverse()->values();
        $numbersSorted = collect($pickNumbers)->sort()->values();
        $len = $numbersStraight->count();

        $orders = $tickets
            ->map(function ($ticket) use ($numbersStraight, $numbersSorted, $numbersChance, $len, $product) {
                $data = [
                    'id' => $ticket->id,
                    'created_at' => $ticket->created_at?->format('d M, Y h:i:s A'),
                    'product_name' => $ticket->order?->product?->title . ' ' . $ticket->order?->product?->product_number,
                    'invoice_no' => $ticket->order?->invoice_no,
                    'selected_numbers' => $ticket->selected_numbers,
                    'types' => $ticket->selected_play_types,
                    'vendor_name' => $ticket->order?->user?->name,
                    'vendor_address' => $ticket->order?->user?->address,
                ];

                $isStraightWinner = false;
                $isRumbleWinner = false;
                $isChanceWinner = false;
                $isNumberWinner = false;
                $ticketTypes = is_array($ticket->selected_play_types)
                    ? $ticket->selected_play_types
                    : (array) $ticket->selected_play_types;
                $ticketNumbers = collect($ticket->selected_numbers)->values();

                if ($product->prize_type === 'bet') {
                    foreach ($product->prizes->whereIn('name', ['Straight', 'Rumble']) as $type) {
                        if ($type->name === 'Straight' && in_array('Straight', $ticketTypes, true)) {
                            $isStraightWinner =
                                $ticketNumbers->count() === $len &&
                                $ticketNumbers->all() == $numbersStraight->all();
                            $data[$type->name] = $isStraightWinner;
                        } elseif ($type->name === 'Rumble' && in_array('Rumble', $ticketTypes, true)) {
                            if ($isStraightWinner === false) {
                                $isRumbleWinner =
                                    $ticketNumbers->count() === $len &&
                                    $ticketNumbers->sort()->values()->all() == $numbersSorted->all();
                            }
                            $data[$type->name] = $isRumbleWinner;
                        }
                    }

                    if (isset($ticketTypes['Chance'])) {
                        $matchCount = $ticketNumbers->reverse()
                            ->values()
                            ->zip($numbersChance)
                            ->takeWhile(fn ($pair) => (string) $pair[0] == (string) $pair[1])
                            ->count();

                        $chancePrizes = $product->prizes
                            ->where('name', 'Chance')
                            ->sortByDesc('chance_number')
                            ->values();

                        foreach ($chancePrizes as $chanceType) {
                            $key = $chanceType->name . ' ' . $chanceType->chance_number;
                            $data[$key] = false;

                            if ($isStraightWinner || $isRumbleWinner || $isChanceWinner) {
                                continue;
                            }

                            if ($matchCount == (int) $chanceType->chance_number) {
                                $data[$key] = true;
                                $isChanceWinner = true;
                            }
                        }
                    }
                } else {
                    if ((int) $product->product_number === 9) {
                        $matchCount = $ticketNumbers->zip($numbersStraight)
                            ->takeWhile(fn ($pair) => (string) $pair[0] === (string) $pair[1])
                            ->count();
                        $matchedNumbers = $ticketNumbers->take($matchCount);
                    } elseif ((int) $product->product_number === 2) {
                        $matchCount = $ticketNumbers->reverse()->values()
                            ->zip($numbersStraight->reverse()->values())
                            ->takeWhile(fn ($pair) => (string) $pair[0] === (string) $pair[1])
                            ->count();
                        $matchedNumbers = $ticketNumbers->reverse()->take($matchCount);
                    } else {
                        $matchedNumbers = $ticketNumbers->intersect($numbersStraight);
                        $matchCount = $matchedNumbers->count();
                    }

                    $numberPrizes = $product->prizes->sortByDesc('name');

                    foreach ($numberPrizes as $prize) {
                        $key = is_numeric($prize->name) ? 'Number ' . $prize->name : $prize->name;
                        $data[$key] = false;

                        if ($isNumberWinner) {
                            continue;
                        }

                        $prizeNameLower = strtolower($prize->name);
                        if ($prizeNameLower === 'platinum') {
                            if ($matchCount === 1 && in_array($matchedNumbers->first(), [25, 26, 27])) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        } elseif ($prizeNameLower === 'golden') {
                            if ($matchCount === 1 && in_array($matchedNumbers->first(), [5, 6, 7])) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        } elseif ($prizeNameLower === 'normal') {
                            if ($matchCount === 1 && !in_array($matchedNumbers->first(), [25, 26, 27, 5, 6, 7])) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        } elseif ($matchCount === (int) $prize->name) {
                            $data[$key] = true;
                            $isNumberWinner = true;
                        }
                    }
                }

                $orderHasWon = $isStraightWinner || $isRumbleWinner || $isChanceWinner || $isNumberWinner;

                return $orderHasWon ? $data : null;
            })
            ->filter()
            ->values();

        $orders = app(\App\Services\RiskCapService::class)->applyToWinnerRows($orders, $product);
        $summary = $this->probableWinsSummary($orders, $types);
        $orders = $this->attachProbableWinAmounts($orders, $summary);
        $summary = collect($summary)->sortByDesc('total_amount')->values();

        return [
            'summary' => $summary,
            'orders' => $orders->where('win_amount', '>', 0)->sortByDesc('win_amount')->values(),
            'winner_count' => (int) $summary->sum('winners'),
            'total_amount' => (float) $summary->sum('total_amount'),
        ];
    }

    private function probableWinsSummary(Collection $orders, Collection $types): array
    {
        $summary = [];

        foreach ($types as $type) {
            if (is_numeric($type->name)) {
                $name = 'Number ' . $type->name;
            } elseif ($type->name === 'Chance') {
                $name = $type->name . ' ' . $type->chance_number;
            } else {
                $name = $type->name;
            }

            $winnerCount = $orders->where($name, true)->count();

            if ($winnerCount > 0) {
                $summary[$name] = [
                    'match_type' => $name,
                    'winners' => $winnerCount,
                    'prize_per_winner' => (float) $type->prize,
                    'tickets' => $orders->where($name, true)->values(),
                    'total_amount' => round($winnerCount * (float) $type->prize, 2),
                ];
            }
        }

        return $summary;
    }

    private function attachProbableWinAmounts(Collection $orders, array $summary): Collection
    {
        return $orders->transform(function ($order) use ($summary) {
            $order['win_amount'] = 0;
            $order['match_type'] = null;

            foreach ($summary as $key => $sum) {
                if (!empty($order[$key]) && $order[$key] === true) {
                    $order['win_amount'] = $sum['prize_per_winner'] ?? 0;
                    $order['match_type'] = $key;
                    break;
                }
            }

            return $order;
        });
    }

    public function probableWins(Request $request)
    {
        $from = null;
        $to   = null;

        if ($request->date_from) {
            $from = Carbon::parse(
                $request->date_from . ' ' . ($request->time_from ?? '00:00:00')
            );
        }

        if ($request->date_to) {
            $to = Carbon::parse(
                $request->date_to . ' ' . ($request->time_to ?? '23:59:59')
            );
        }
        $products = Product::orderBy('title')->get();
        $product_prizes = $request->product_id
            ? collect($this->product_prizes($request->product_id))
            : collect();

        $product = Product::find($request->product_id);

        $summery = [];
        $orders = null;
        if ($request->btn === 'search' && $request->pick_number && $request->product_id) {
            $types = $this->probableWinsTypes($product, $request->match_type);
            $tickets = $this->probableWinsTicketQuery($request, $from, $to)
                ->with(['order.user', 'order.product'])
                ->get();

            $calculation = $this->calculateProbableWinsFromTickets(
                $product,
                $tickets,
                collect($request->pick_number)->values()->all(),
                $types
            );

            $summery = $calculation['summary']->values()->all();
            $orders = $calculation['orders']->values();
        }

        // return $summery;
        return Inertia::render('Orders/ProbableWins', [
            'products' => $products,
            'filters' => request()->only([
                'product_id',
                'match_type',
                'pick_number',
                'date_from',
                'time_from',
                'date_to',
                'time_to'
            ]),
            'product_prizes' => $product_prizes,
            'product' => $product,
            'summary' => collect($summery)->sortByDesc('total_amount')->all(),
            'orders' => $orders?->where('win_amount', '>', 0)?->sortByDesc('win_amount')->values()
        ]);
    }

    public function probableWinsPdf(Request $request)
    {
        $from = null;
        $to   = null;

        if ($request->date_from) {
            $from = Carbon::parse(
                $request->date_from . ' ' . ($request->time_from ?? '00:00:00')
            );
        }

        if ($request->date_to) {
            $to = Carbon::parse(
                $request->date_to . ' ' . ($request->time_to ?? '23:59:59')
            );
        }

        $product = Product::find($request->product_id);
        $product_prizes = $request->product_id
            ? collect($this->product_prizes($request->product_id))
            : collect();

        $summery = [];
        $orders = null;
        $groupedOrders = [];

        if ($request->pick_number && $request->product_id) {
            $match_type = ProductPrize::find($request->match_type);

            $types = $product->prizes;

            if ($request->match_type === "Chance") {
                $match_type = ProductPrize::where('product_id', $product->id)->where('name', 'Chance')->get();
                $types = $match_type
                    ? $match_type
                    : $product->prizes;
            } else {
                $match_type = ProductPrize::find($request->match_type);
                $types = $match_type
                    ? [$match_type]
                    : $product->prizes;
            }

            $numbersStraight = collect($request->pick_number)->values();
            $numbersChance = collect($request->pick_number)->reverse()->values();
            $numbersSorted   = collect($request->pick_number)->sort()->values();
            $len             = $numbersStraight->count();

            $orders = OrderTicket::query()
                ->withoutRiskHold()
                ->whereHas('order', function ($o) use ($request, $from, $to) {
                    $o->where('status', 'Printed')->where('is_claimed', 0)->where('is_winner', 0)->where('product_id', $request->product_id);
                    if ($from) {
                        $o->where('created_at', '>=', $from);
                    }
                    if ($to) {
                        $o->where('created_at', '<=', $to);
                    }
                })
                ->with('order.user')
                ->when($request->match_type, function ($q) use ($match_type, $request) {
                    if ($request->match_type === 'Chance') {
                        $q->whereJsonContains('selected_play_types', "Chance");
                    } else {
                        $q->whereJsonContains('selected_play_types', $match_type->name);
                    }
                })
                ->get()
                ->map(function ($order) use ($numbersStraight, $numbersSorted, $len, $types, $product, $numbersChance) {
                    $data = ['id' => $order->id, 'created_at' => $order->created_at?->format('d M, Y h:i:s A'), 'product_name' => $order->order?->product?->title.' '.$order->order?->product?->product_number, 'invoice_no' => $order->order?->invoice_no, 'selected_numbers' => $order->selected_numbers, 'types' => $order->selected_play_types, 'vendor_name' => $order->order?->user?->name, 'vendor_address' => $order->order?->user?->address];
                    $isStraightWinner = false;
                    $isRumbleWinner = false;
                    $isChanceWinner = false;
                    $isNumberWinner = false;
                    $ticketTypes   = is_array($order->selected_play_types)
                        ? $order->selected_play_types
                        : (array) $order->selected_play_types;
                    $ticketNumbers = collect($order->selected_numbers)->values();


                    if ($product->prize_type === 'bet') {
                        foreach ($product->prizes->whereIn('name', ['Straight', 'Rumble']) as $type) {
                            if ($type->name === 'Straight' & in_array('Straight', $ticketTypes, true)) {
                                $isStraightWinner =
                                    $ticketNumbers->count() === $len &&
                                    $ticketNumbers->all() == $numbersStraight->all();
                                $data[$type->name] = $isStraightWinner;
                            } else if ($type->name === 'Rumble' && in_array('Rumble', $ticketTypes, true)) {
                                if ($isStraightWinner == false) {
                                    $isRumbleWinner =
                                        $ticketNumbers->count() === $len &&
                                        $ticketNumbers->sort()->values()->all() == $numbersSorted->all();
                                }
                                $data[$type->name] = $isRumbleWinner;
                            }
                        }
                        if (isset($ticketTypes['Chance'])) {
                            $matchCount = $ticketNumbers->reverse()
                                ->values()
                                ->zip($numbersChance)
                                ->takeWhile(fn($pair) => (string)$pair[0] == (string)$pair[1])
                                ->count();

                            $chancePrizes = $product->prizes
                                ->where('name', 'Chance')
                                ->sortByDesc('chance_number')
                                ->values();


                            foreach ($chancePrizes as $chanceType) {
                                $key = $chanceType->name . ' ' . $chanceType->chance_number;
                                $data[$key] = false;

                                if ($isStraightWinner || $isRumbleWinner || $isChanceWinner) {
                                    continue;
                                }

                                if ($matchCount == (int) $chanceType->chance_number) {
                                    $data[$key] = true;
                                    $isChanceWinner = true;
                                }
                            }
                        }
                    } else {
                        if ((int)$product->product_number === 9) {
                            $matchCount = $ticketNumbers->zip($numbersStraight)
                                ->takeWhile(fn($pair) => (string)$pair[0] === (string)$pair[1])
                                ->count();
                            $matchedNumbers = $ticketNumbers->take($matchCount);
                        } else if ((int)$product->product_number === 2) {
                            $matchCount = $ticketNumbers->reverse()->values()
                                ->zip($numbersStraight->reverse()->values())
                                ->takeWhile(fn($pair) => (string)$pair[0] === (string)$pair[1])
                                ->count();
                            $matchedNumbers = $ticketNumbers->reverse()->take($matchCount);
                        } else {
                            $matchedNumbers = $ticketNumbers->intersect($numbersStraight);
                            $matchCount = $matchedNumbers->count();
                        }
                        $numberPrizes = $product->prizes
                            ->sortByDesc('name');


                        foreach ($numberPrizes as $prize) {
                            $key = is_numeric($prize->name) ? 'Number ' . $prize->name : $prize->name;
                            $data[$key] = false;

                            if ($isNumberWinner) continue;

                            $prizeNameLower = strtolower($prize->name);
                            if ($prizeNameLower === 'platinum') {
                                if ($matchCount === 1 && in_array($matchedNumbers->first(), [25, 26, 27])) {
                                    $data[$key] = true;
                                    $isNumberWinner = true;
                                }
                            } else if ($prizeNameLower === 'golden') {
                                if ($matchCount === 1 && in_array($matchedNumbers->first(), [5, 6, 7])) {
                                    $data[$key] = true;
                                    $isNumberWinner = true;
                                }
                            } else if ($prizeNameLower === 'normal') {
                                if ($matchCount === 1 && !in_array($matchedNumbers->first(), [25, 26, 27, 5, 6, 7])) {
                                    $data[$key] = true;
                                    $isNumberWinner = true;
                                }
                            } else {
                                if ($matchCount === (int) $prize->name) {
                                    $data[$key] = true;
                                    $isNumberWinner = true;
                                }
                            }
                        }
                    }
                    $orderHasWon = $isStraightWinner || $isRumbleWinner || $isChanceWinner || $isNumberWinner;

                    if ($orderHasWon) {
                        return $data;
                    }

                    return null;
                })->filter();

            $orders = app(\App\Services\RiskCapService::class)->applyToWinnerRows($orders, $product);

            foreach ($types as $type) {
                if (is_numeric($type->name)) {
                    $name = 'Number ' . $type->name;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[$name] = [
                            'match_type' => $name,
                            'winners' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->values(),
                            'total_amount' => ($orders->where($name, true)->count() * $type->prize)
                        ];
                    }
                } else if ($type->name === 'Chance') {
                    $name = $type->name . ' ' . $type->chance_number;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[$name] = [
                            'match_type' => $name,
                            'winners' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->values(),
                            'total_amount' => ($orders->where($name, true)->count() * $type->prize)
                        ];
                    }
                } else {
                    if ($orders->where($type->name, true)->count() > 0) {
                        $summery[$type->name] = [
                            'match_type' => $type->name,
                            'winners' =>  $orders->where($type->name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($type->name, true)->values(),
                            'total_amount' => ($orders->where($type->name, true)->count() * $type->prize)
                        ];
                    }
                }
            }

            $orders->transform(function ($order) use ($summery) {
                $order['win_amount'] = 0;
                $order['match_type'] = null;

                if (!empty($order['Straight']) && $order['Straight'] === true) {
                    $order['win_amount'] = $summery['Straight']['prize_per_winner'] ?? 0;
                    $order['match_type'] = 'Straight';
                }

                if (!empty($order['Rumble']) && $order['Rumble'] === true) {
                    $order['win_amount'] = $summery['Rumble']['prize_per_winner'] ?? 0;
                    $order['match_type'] = 'Rumble';
                }

                // Chance types
                foreach ($summery as $key => $sum) {
                    if (str_starts_with($key, 'Chance') && !empty($order[$key]) && $order[$key] === true) {
                        $order['win_amount'] = $sum['prize_per_winner'] ?? 0;
                        $order['match_type'] = $key;
                    }
                }
                // Number types
                foreach ($summery as $key => $sum) {
                    if (str_contains($key, 'Number') && !empty($order[$key]) && $order[$key] === true) {
                        $order['win_amount'] = $sum['prize_per_winner'] ?? 0;
                        $order['match_type'] = $key;
                    }
                }

                return $order;
            });

            // Group orders for vendor list
            $ordersGrouped = $orders->where('win_amount', '>', 0)->sortByDesc('win_amount');
            $groupedMap = [];
            foreach ($ordersGrouped as $order) {
                $invoiceNo = $order['invoice_no'];
                if (!isset($groupedMap[$invoiceNo])) {
                    $groupedMap[$invoiceNo] = [
                        'invoice_no' => $order['invoice_no'],
                        'product_name' => $order['product_name'],
                        'created_at' => $order['created_at'],
                        'vendor_name' => $order['vendor_name'],
                        'vendor_address' => $order['vendor_address'],
                        'items' => [],
                        'total_win_amount' => 0,
                    ];
                }
                $groupedMap[$invoiceNo]['items'][] = $order;
                $groupedMap[$invoiceNo]['total_win_amount'] += $order['win_amount'];
            }
            $groupedOrders = array_values($groupedMap);
        }

        $company = CompannySetting::firstOrFail();

        $fromDateTime = $request->date_from ? ($request->time_from ? $request->date_from . ' ' . $request->time_from : $request->date_from) : '-';
        $toDateTime = $request->date_to ? ($request->time_to ? $request->date_to . ' ' . $request->time_to : $request->date_to) : '-';

        $summaryArray = collect($summery)->sortByDesc('total_amount')->values()->all();
        $totalAmount = collect($summaryArray)->sum('total_amount');
        $totalWinAmount = $orders?->where('win_amount', '>', 0)->sum('win_amount') ?? 0;

        // Build heading title
        $pickNumbers = collect($request->pick_number ?? [])->filter(fn($n) => $n !== null && $n !== '')->implode('');
        $matchTypeLabel = null;
        if ($request->match_type) {
            if ($request->match_type === 'Chance') {
                $matchTypeLabel = 'Chance';
            } else {
                $prize = ProductPrize::find($request->match_type);
                $matchTypeLabel = $prize?->name;
            }
        }

        $pdf = Pdf::loadView('pdf.probable_wins_report', [
            'summary' => $summaryArray,
            'groupedOrders' => $groupedOrders,
            'company' => $company,
            'from_date' => $fromDateTime,
            'to_date' => $toDateTime,
            'product' => $product,
            'totalAmount' => $totalAmount,
            'totalWinAmount' => $totalWinAmount,
            'pickNumbers' => $pickNumbers,
            'matchTypeLabel' => $matchTypeLabel,
        ]);

        return $pdf->stream('probable_wins_report_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    public function updateStatus(Order $order, Request $request)
    {
        $order->status = $request->status;
        if ($request->status === "Printed") {
            $accountService = new AgentAccountService();
            $accountService->store([
                'user_id' => $order->user_id,
                'type' => 'sell',
                'amount' => $order->total_price
            ]);
            $accountService->store([
                'user_id' => $order->user_id,
                'type' => 'commission',
                'amount' => $order->commission
            ]);
        }
        $order->save();

        return back();
    }

    public function orderPdf($invoice)
    {
        $order = Order::where('invoice_no', $invoice)->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }
        $draw_date_time = $this->productService->getDrawTimeFromOrder($order);
        $draw_number = Win::where('product_id', $order->product_id)->max('draw_number');
        $pdf = Pdf::loadView('pdf.order_pdf', ['order' => $order, 'draw_time' => $draw_date_time['draw_time'], 'draw_date' => $draw_date_time['draw_date'], 'draw_number' => $draw_number]);
        $pdf->setPaper([0, 0, 226.77, 600], 'portrait');
        return $pdf->stream($order->invoice_no . '.pdf');
    }
}
