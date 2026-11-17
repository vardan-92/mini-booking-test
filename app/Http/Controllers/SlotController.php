<?php

namespace App\Http\Controllers;

use App\Booking\SlotSchedule;
use Illuminate\Http\JsonResponse;

class SlotController
{
    public function __invoke(SlotSchedule $schedule): JsonResponse
    {
        return response()->json($schedule->all());
    }
}
