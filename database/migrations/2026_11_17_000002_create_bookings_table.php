<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('slot_id')->unique()->constrained();
            $table->string('customer_name');
            $table->string('customer_email', 254);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
