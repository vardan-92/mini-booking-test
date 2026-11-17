<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $today = Date::now(config('app.timezone'))->toImmutable()->startOfDay();
        $slots = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day = $today->addDays($offset);

            for ($hour = 9; $hour < 18; $hour++) {
                $slots[] = [
                    'starts_at' => $day->setTime($hour, 0)->utc()->toDateTimeString(),
                    'ends_at' => $day->setTime($hour + 1, 0)->utc()->toDateTimeString(),
                ];
            }
        }

        DB::table('slots')->insertOrIgnore($slots);
    }
}
