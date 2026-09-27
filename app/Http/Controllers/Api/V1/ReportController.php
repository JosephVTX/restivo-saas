<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Reports\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    public function dashboard(Request $request, ReportService $reports): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        [$from, $to] = $this->resolveRange($request);

        return response()->json([
            'data' => $reports->dashboard($from, $to),
        ]);
    }

    /**
     * Resolve the reporting window from `period` (today|week|month) or an
     * explicit `from`/`to` date pair.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveRange(Request $request): array
    {
        $now = CarbonImmutable::now();

        if ($request->filled('from')) {
            $from = CarbonImmutable::parse((string) $request->query('from'))->startOfDay();
            $to = $request->filled('to')
                ? CarbonImmutable::parse((string) $request->query('to'))->endOfDay()
                : $from->endOfDay();

            return [$from, $to];
        }

        return match ((string) $request->query('period', 'today')) {
            'week' => [$now->startOfWeek(), $now->endOfDay()],
            'month' => [$now->startOfMonth(), $now->endOfDay()],
            default => [$now->startOfDay(), $now->endOfDay()],
        };
    }
}
