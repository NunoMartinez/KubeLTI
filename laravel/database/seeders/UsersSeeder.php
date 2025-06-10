<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    private $photoPath;

    public function run()
    {
        $this->photoPath = storage_path('app/public/photos');
        $this->command->table(['Users table seeder notice'], [
            ['Photos will be stored on path ' . $this->photoPath]
        ]);

        // Ensure photos directory exists
        if (!File::exists($this->photoPath)) {
            File::makeDirectory($this->photoPath, 0755, true);
        }

        $now = now();

        // Create a single admin user
        $admin = [
            'name' => 'Admin User',
            'nickname' => 'admin',
            'email' => 'admin@kubelti.com',
            'email_verified_at' => $now,
            'password' => Hash::make('password'), // Default password
            'remember_token' => Str::random(10),
            'created_at' => $now,
            'updated_at' => $now,
            'type' => 'A', // Administrator
            'blocked' => false,
            'brain_coins_balance' => 0, // Default value, not used but required by the schema
            'deleted_at' => null,
            'photo_filename' => null,
            'custom' => null,
        ];

        DB::table('users')->insert($admin);
        
        $this->command->info('Created default admin user:');
        $this->command->info('Email: admin@kubelti.com');
        $this->command->info('Password: password');
    }


}


