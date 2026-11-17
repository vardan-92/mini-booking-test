<?php

namespace App\Booking;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class SlotSchedule
{
    public function all(): Collection
    {
        $now = Date::now('UTC');
        $timezone = config('app.timezone');

        return DB::table('slots')
            ->leftJoin('bookings', 'bookings.slot_id', '=', 'slots.id')
            ->orderBy('slots.starts_at')
            ->get(['slots.id', 'slots.starts_at', 'slots.ends_at', 'bookings.id as booking_id'])
            ->map(function (object $slot) use ($now, $timezone): array {
                $start = CarbonImmutable::parse($slot->starts_at, 'UTC');
                $end = CarbonImmutable::parse($slot->ends_at, 'UTC');

                return [
                    'id' => $slot->id,
                    'starts_at' => $start->setTimezone($timezone)->toIso8601String(),
                    'ends_at' => $end->setTimezone($timezone)->toIso8601String(),
                    'status' => match (true) {
                        $start->lessThanOrEqualTo($now) => 'past',
                        $slot->booking_id !== null => 'booked',
                        default => 'available',
                    },
                    'booking_id' => $slot->booking_id,
                ];
            });
    }
}
