<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password'),
            'family_id' => 1,
        ]);

        User::create([
            'name' => '山田花子',
            'email' => 'hanako@example.com',
            'password' => Hash::make('password'),
            'family_id' => 1,
        ]);

        User::create([
            'name' => '佐藤一郎',
            'email' => 'ichiro@example.com',
            'password' => Hash::make('password'),
            'family_id' => 2,
        ]);

        User::create([
            'name' => '鈴木次郎',
            'email' => 'jiro@example.com',
            'password' => Hash::make('password'),
            'family_id' => null,
        ]);
    }
}
