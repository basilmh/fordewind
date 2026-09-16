<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function canRunMigration(): bool
    {
        return !Schema::hasTable('votes');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!$this->canRunMigration()) {
            return;
        }

        Schema::create('votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('winner_car_id')
                ->comment('Автомобиль, получивший голос')
                ->constrained('cars')
                ->restrictOnDelete();
            $table->foreignId('loser_car_id')
                ->comment('Автомобиль, проигравший в паре')
                ->constrained('cars')
                ->restrictOnDelete();
            $table->char('voter_session_hash', 64)->comment('HMAC идентификатора анонимной сессии');
            $table->char('pair_token_hash', 64)->unique('votes_pair_token_hash_unique')->comment('Хеш одноразового токена пары');
            $table->char('pair_hash', 64)->comment('Хеш неупорядоченной пары автомобилей');
            $table->timestamps();

            $table->index(['winner_car_id', 'created_at']);
            $table->index(['loser_car_id', 'created_at']);
            $table->index(['voter_session_hash', 'created_at']);
            $table->unique(['voter_session_hash', 'pair_hash'], 'votes_voter_session_pair_unique');
        });

        DB::statement('ALTER TABLE votes ADD CONSTRAINT votes_different_cars CHECK (winner_car_id <> loser_car_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
