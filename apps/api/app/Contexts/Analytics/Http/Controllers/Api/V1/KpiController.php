<?php

namespace App\Contexts\Analytics\Http\Controllers\Api\V1;

use App\Contexts\Analytics\Http\Requests\KpiQueryRequest;
use App\Contexts\Analytics\Services\KpiEngine;
use App\Contexts\Analytics\Services\MetricsCatalog;
use App\Platform\Shared\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class KpiController extends Controller
{
    public function index(KpiQueryRequest $request, KpiEngine $kpi, MetricsCatalog $catalog): JsonResponse
    {
        $data = $request->validated();
        $from = isset($data['from']) ? CarbonImmutable::parse($data['from']) : CarbonImmutable::now()->subDays(30);
        $to = isset($data['to']) ? CarbonImmutable::parse($data['to']) : CarbonImmutable::now();

        $kpis = [];
        foreach ($data['metrics'] as $key) {
            if (! $catalog->has($key)) {
                continue;
            }
            $kpis[] = [
                'metric' => $key,
                'unit' => $catalog->definition($key)['unit'],
                'total' => $kpi->total($key, $from->startOfDay(), $to->endOfDay()),
                'series' => $kpi->series($key, $from->startOfDay(), $to->endOfDay()),
            ];
        }

        return ApiResponse::success(['from' => $from->toDateString(), 'to' => $to->toDateString(), 'kpis' => $kpis]);
    }
}
