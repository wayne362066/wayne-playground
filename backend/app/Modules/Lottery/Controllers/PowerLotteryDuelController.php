<?php

namespace App\Modules\Lottery\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Lottery\Requests\CreateDuelRequest;
use App\Modules\Lottery\Requests\JoinDuelRequest;
use App\Modules\Lottery\Services\DuelRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PowerLotteryDuelController extends Controller
{
    public function index(DuelRoomService $rooms): JsonResponse
    {
        return ApiResponse::success([
            'rooms' => $rooms->publicRooms(),
        ]);
    }

    public function current(Request $request, DuelRoomService $rooms): JsonResponse
    {
        return ApiResponse::success([
            'room' => $rooms->current($request),
        ]);
    }

    public function store(
        CreateDuelRequest $request,
        DuelRoomService $rooms,
    ): JsonResponse {
        return ApiResponse::success([
            'room' => $rooms->create($request, $request->validated()),
        ], '房間已建立', 201);
    }

    public function show(
        Request $request,
        string $room,
        DuelRoomService $rooms,
    ): JsonResponse {
        return ApiResponse::success([
            'room' => $rooms->show($request, $room),
        ]);
    }

    public function join(
        JoinDuelRequest $request,
        string $room,
        DuelRoomService $rooms,
    ): JsonResponse {
        return ApiResponse::success([
            'room' => $rooms->join($request, $room, $request->validated()),
        ], '已加入房間');
    }

    public function ready(
        Request $request,
        string $room,
        DuelRoomService $rooms,
    ): JsonResponse {
        return ApiResponse::success([
            'room' => $rooms->ready($request, $room),
        ], '已準備');
    }

    public function heartbeat(
        Request $request,
        string $room,
        DuelRoomService $rooms,
    ): JsonResponse {
        return ApiResponse::success([
            'room' => $rooms->heartbeat($request, $room),
        ]);
    }

    public function leave(
        Request $request,
        string $room,
        DuelRoomService $rooms,
    ): JsonResponse {
        return ApiResponse::success([
            'room' => $rooms->leave($request, $room),
        ], '已離開房間');
    }

    public function rematch(
        Request $request,
        string $room,
        DuelRoomService $rooms,
    ): JsonResponse {
        return ApiResponse::success([
            'room' => $rooms->rematch($request, $room),
        ], '再來一局狀態已更新');
    }
}
