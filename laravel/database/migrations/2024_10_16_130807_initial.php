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
        Schema::table('users', function (Blueprint $table) {
            // Administrator, Player
            $table->enum('type', ['A', 'P'])->default('P');

            // Nickname - must be unique
            $table->string('nickname', 20)->unique();

            // User access is blocked
            $table->boolean('blocked')->default(false);

            // User Photo/Avatar
            $table->string('photo_filename')->nullable();

            // Brain Coin Balance - kept for compatibility but not used
            $table->integer('brain_coins_balance')->default(0);

            // custom data
            $table->json('custom')->nullable();

            // Users can be deleted with "soft deletes"
            $table->softDeletes();
        });

        // Removed tables: boards, games, multiplayer_games_played, transactions
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('nickname');
            $table->dropColumn('blocked');
            $table->dropColumn('photo_filename');
            $table->dropColumn('brain_coins_balance');
            $table->dropColumn('custom');
            $table->dropSoftDeletes();
        });
    }
};
