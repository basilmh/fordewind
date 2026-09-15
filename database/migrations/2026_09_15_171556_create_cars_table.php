<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function canRunMigration(): bool
    {
        return !Schema::hasTable('cars');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!$this->canRunMigration()) {
            return;
        }

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('auction_item_id', 32)->unique();
            $table->decimal('current_high_pre_bid', 14, 2)->nullable();
            $table->string('custom_status', 64);
            $table->decimal('my_pre_bid', 14, 2)->nullable();
            $table->unsignedSmallInteger('year')->index();
            $table->string('make', 100)->index();
            $table->string('model', 100);
            $table->unsignedBigInteger('odometer');
            $table->string('units', 16);
            $table->string('vehicle_location')->nullable();
            $table->string('engine', 32);
            $table->string('transmission', 32);
            $table->string('color', 64);
            $table->string('brand', 64);
            $table->decimal('winning_bid_amount', 14, 2)->nullable();
            $table->string('image_filename')->index();
            $table->timestamps();

            $table->index(['model', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
