<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->cascadeOnDelete();
            $table->string('from_station_code');
            $table->string('from_station_name');
            $table->string('to_station_code');
            $table->string('to_station_name');
            $table->date('departure_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_searches');
    }
};
