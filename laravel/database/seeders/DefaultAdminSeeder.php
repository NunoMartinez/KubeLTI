<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DefaultAdminSeeder extends Seeder
{
    public function run()
    {
        // Create a default admin user
        $now = Carbon::now();
        
        DB::table('users')->insert([
            'name' => 'Admin User',
            'nickname' => 'admin',
            'email' => 'admin@example.com',
            'email_verified_at' => $now,
            'password' => bcrypt('password'), // default password
            'remember_token' => Str::random(10),
            'created_at' => $now,
            'updated_at' => $now,
            'type' => 'A', // Administrator
            'blocked' => 0,
            'brain_coins_balance' => 0,
            'deleted_at' => null,
        ]);
        
        $this->command->info("Default admin user created successfully");
    }
}