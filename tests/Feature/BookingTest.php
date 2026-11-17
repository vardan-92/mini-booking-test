<?php

namespace Tests\Feature;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_future_slot_can_be_booked(): void
    {
        $payload = $this->bookingPayload();

        $response = $this->postJson('/api/bookings', $payload)
            ->assertCreated()
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('bookings', ['id' => $response->json('id')] + $payload);
    }

    public function test_booking_an_occupied_slot_returns_conflict_without_creating_a_second_booking(): void
    {
        $payload = $this->bookingPayload();
        $firstBookingId = $this->postJson('/api/bookings', $payload)->assertCreated()->json('id');

        $this->postJson('/api/bookings', array_replace($payload, [
            'customer_name' => 'Иван Тестов',
            'customer_email' => 'ivan@example.test',
        ]))->assertConflict();

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('bookings', ['id' => $firstBookingId] + $payload);
    }

    public function test_a_slot_that_has_started_is_rejected_using_database_time(): void
    {
        $start = now('UTC')->startOfSecond();
        $this->travelTo($start->copy()->subSecond());

        $this->postJson('/api/bookings', $this->bookingPayload($start))->assertConflict();

        $this->assertDatabaseCount('bookings', 0);
    }

    private function bookingPayload(?CarbonInterface $start = null): array
    {
        $start ??= now('UTC')->addDay()->startOfHour();

        return [
            'slot_id' => DB::table('slots')->insertGetId([
                'starts_at' => $start->toDateTimeString(),
                'ends_at' => $start->copy()->addHour()->toDateTimeString(),
            ]),
            'customer_name' => 'Анна Тестова',
            'customer_email' => 'anna@example.test',
        ];
    }
}
