<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("-----------------------------------------------");
        $this->command->info("START of database seeder");
        $this->command->info("-----------------------------------------------");

        DB::statement("SET foreign_key_checks=0");

        // Clear only the users table
        DB::table('users')->delete();
        DB::statement('ALTER TABLE users AUTO_INCREMENT = 1');

        DB::statement("SET foreign_key_checks=1");

        $this->command->info("-----------------------------------------------");

        // Set timezone
        DB::statement("SET time_zone = '+00:00'");

        // Create a single admin user
        $this->call(UsersSeeder::class);

        $this->command->info("-----------------------------------------------");
        $this->command->info("END of database seeder");
        $this->command->info("-----------------------------------------------");
    }
}
