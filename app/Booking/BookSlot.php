<?php

namespace App\Booking;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class BookSlot
{
    public function create(int $slotId, string $name, string $email): int
    {
        // Read the clock after SQLite acquires the write lock.
        try {
            $booking = DB::selectOne(
                'INSERT INTO bookings (slot_id, customer_name, customer_email)
                 SELECT id, ?, ? FROM slots WHERE id = ? AND starts_at > CURRENT_TIMESTAMP
                 RETURNING id',
                [$name, $email, $slotId],
                useReadPdo: false,
            );
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_ends_with($exception->getPrevious()->getMessage(), 'UNIQUE constraint failed: bookings.slot_id')) {
                throw $exception;
            }

            throw new BookingConflict('Этот слот уже забронирован. Выберите другое время.');
        }

        if ($booking === null) {
            throw new BookingConflict('Этот слот уже начался. Выберите время в будущем.');
        }

        return $booking->id;
    }

    public function cancel(string $id): bool
    {
        return DB::table('bookings')->where('id', $id)->delete() > 0;
    }
}
