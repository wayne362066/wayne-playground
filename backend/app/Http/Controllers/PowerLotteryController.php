<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePowerLotteryRequest;
use App\Http\Requests\SimulateSelectedPowerLotteryRequest;
use App\Http\Requests\SimulatePowerLotteryRequest;
use App\Http\Responses\ApiResponse;
use App\Services\PowerLotteryGenerator;
use App\Services\PowerLotterySimulator;
use Illuminate\Http\JsonResponse;

final class PowerLotteryController extends Controller
{
    public function generate(
        GeneratePowerLotteryRequest $request,
        PowerLotteryGenerator $generator,
    ): JsonResponse {
        return ApiResponse::success([
            'draws' => $generator->generate($request->integer('count')),
            'generated_at' => now()->toIso8601String(),
        ], '號碼產生成功');
    }

    public function simulate(
        SimulatePowerLotteryRequest $request,
        PowerLotterySimulator $simulator,
    ): JsonResponse {
        return ApiResponse::success([
            'simulation' => $simulator->simulate(
                $request->integer('ticket_count'),
                $request->string('mode')->toString(),
            ),
            'simulated_at' => now()->toIso8601String(),
        ], '模擬完成');
    }

    public function simulateSelected(
        SimulateSelectedPowerLotteryRequest $request,
        PowerLotterySimulator $simulator,
    ): JsonResponse {
        return ApiResponse::success([
            'simulation' => $simulator->simulateSelectedTicket(
                $request->array('zone_one'),
                $request->integer('zone_two'),
                $request->integer('period_count'),
            ),
            'simulated_at' => now()->toIso8601String(),
        ], '自選號碼模擬完成');
    }
}
