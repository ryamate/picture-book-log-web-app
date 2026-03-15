<?php

namespace Database\Seeders;

use App\Models\Child;
use Illuminate\Database\Seeder;

class ChildSeeder extends Seeder
{
    public function run(): void
    {
        // 山田家
        Child::create([
            'id' => 1,
            'family_id' => 1,
            'name' => 'ゆうき',
            'birthday' => '2021-04-15',
        ]);

        Child::create([
            'id' => 2,
            'family_id' => 1,
            'name' => 'あおい',
            'birthday' => '2023-09-20',
        ]);

        // 佐藤家
        Child::create([
            'id' => 3,
            'family_id' => 2,
            'name' => 'さくら',
            'birthday' => '2022-01-10',
        ]);
    }
}
