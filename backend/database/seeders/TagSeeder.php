<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = ['寝る前', 'お気に入り', 'リクエスト', '図書館', '新しい絵本', 'シリーズ', '季節もの'];

        foreach ($tags as $tag) {
            Tag::create(['name' => $tag]);
        }
    }
}
