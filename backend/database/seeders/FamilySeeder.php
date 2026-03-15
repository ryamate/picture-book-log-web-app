<?php

namespace Database\Seeders;

use App\Models\Family;
use Illuminate\Database\Seeder;

class FamilySeeder extends Seeder
{
    public function run(): void
    {
        Family::create(['id' => 1, 'name' => '山田家']);
        Family::create(['id' => 2, 'name' => '佐藤家']);
    }
}
