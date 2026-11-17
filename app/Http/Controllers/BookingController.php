<?php

namespace App\Http\Controllers;

use App\Booking\BookingConflict;
use App\Booking\BookSlot;
use App\Http\Requests\StoreBookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookingController
{
    public function store(StoreBookingRequest $request, BookSlot $bookings): JsonResponse
    {
        $fields = $request->validated();

        try {
            $id = $bookings->create($fields['slot_id'], $fields['customer_name'], $fields['customer_email']);
        } catch (BookingConflict $conflict) {
            return response()->json(['message' => $conflict->getMessage()], 409);
        }

        return response()->json(['id' => $id], 201);
    }

    public function destroy(string $id, BookSlot $bookings): Response|JsonResponse
    {
        if (! $bookings->cancel($id)) {
            return response()->json(['message' => 'Бронирование не найдено.'], 404);
        }

        return response()->noContent();
    }
}
