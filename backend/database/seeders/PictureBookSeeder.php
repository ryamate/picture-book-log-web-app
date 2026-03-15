<?php

namespace Database\Seeders;

use App\Models\PictureBook;
use Illuminate\Database\Seeder;

class PictureBookSeeder extends Seeder
{
    public function run(): void
    {
        $yamadaBooks = [
            [
                'title' => 'ぐりとぐら',
                'authors' => ['中川李枝子'],
                'isbn' => '9784834000825',
                'read_status' => 'read',
                'rating' => 5,
                'review' => '子どもたちが大好きな定番絵本。カステラのシーンで毎回大喜びします。',
            ],
            [
                'title' => 'はらぺこあおむし',
                'authors' => ['エリック・カール'],
                'isbn' => '9784033280103',
                'read_status' => 'read',
                'rating' => 5,
                'review' => '穴あきのページが楽しい。色彩がとても鮮やかで赤ちゃんの頃から楽しめる。',
            ],
            [
                'title' => 'おおきなかぶ',
                'authors' => ['A.トルストイ'],
                'isbn' => '9784834000627',
                'read_status' => 'read',
                'rating' => 4,
                'review' => '「うんとこしょ、どっこいしょ」のリズムが楽しい。',
            ],
            [
                'title' => 'ねないこだれだ',
                'authors' => ['せなけいこ'],
                'isbn' => '9784834002188',
                'read_status' => 'reading',
                'rating' => 4,
                'review' => null,
            ],
            [
                'title' => 'いないいないばあ',
                'authors' => ['松谷みよ子'],
                'isbn' => '9784494001019',
                'read_status' => 'read',
                'rating' => 5,
                'review' => '赤ちゃんの頃からの定番。シンプルだけど何度読んでも笑ってくれる。',
            ],
            [
                'title' => 'だるまさんが',
                'authors' => ['かがくいひろし'],
                'isbn' => '9784893094315',
                'read_status' => 'read',
                'rating' => 5,
                'review' => '体を動かしながら読めるので盛り上がる。',
            ],
            [
                'title' => 'しろくまちゃんのほっとけーき',
                'authors' => ['わかやまけん'],
                'isbn' => '9784772100311',
                'read_status' => 'reading',
                'rating' => 4,
                'review' => null,
            ],
            [
                'title' => 'きんぎょがにげた',
                'authors' => ['五味太郎'],
                'isbn' => '9784834008999',
                'read_status' => 'unread',
                'rating' => null,
                'review' => null,
            ],
            [
                'title' => 'もこもこもこ',
                'authors' => ['谷川俊太郎'],
                'isbn' => '9784579400294',
                'read_status' => 'unread',
                'rating' => null,
                'review' => null,
            ],
            [
                'title' => 'くだもの',
                'authors' => ['平山和子'],
                'isbn' => '9784834008531',
                'read_status' => 'read',
                'rating' => 3,
                'review' => 'リアルな果物のイラストが美しい。食べ物に興味を持つきっかけになった。',
            ],
            [
                'title' => 'ノンタンぶらんこのせて',
                'authors' => ['キヨノサチコ'],
                'isbn' => '9784032170108',
                'read_status' => 'read',
                'rating' => 4,
                'review' => null,
            ],
            [
                'title' => 'ぐるんぱのようちえん',
                'authors' => ['西内ミナミ'],
                'isbn' => '9784834000832',
                'read_status' => 'read',
                'rating' => 4,
                'review' => '最後のようちえんのシーンが温かい。',
            ],
        ];

        // 山田家の絵本（registered_by: 山田太郎=1）
        foreach ($yamadaBooks as $book) {
            PictureBook::create([
                'family_id' => 1,
                'registered_by' => 1,
                'title' => $book['title'],
                'authors' => $book['authors'],
                'isbn' => $book['isbn'],
                'read_status' => $book['read_status'],
                'rating' => $book['rating'],
                'review' => $book['review'],
            ]);
        }

        // 佐藤家の絵本
        $satoBooks = [
            ['title' => 'ぐりとぐら', 'authors' => ['中川李枝子'], 'isbn' => '9784834000825', 'read_status' => 'read', 'rating' => 5],
            ['title' => 'はらぺこあおむし', 'authors' => ['エリック・カール'], 'isbn' => '9784033280103', 'read_status' => 'reading', 'rating' => 4],
            ['title' => 'おつきさまこんばんは', 'authors' => ['林明子'], 'isbn' => '9784834006872', 'read_status' => 'read', 'rating' => 5],
            ['title' => 'じゃあじゃあびりびり', 'authors' => ['まついのりこ'], 'isbn' => '9784031024402', 'read_status' => 'read', 'rating' => 4],
            ['title' => 'がたんごとんがたんごとん', 'authors' => ['安西水丸'], 'isbn' => '9784834002720', 'read_status' => 'unread', 'rating' => null],
        ];

        foreach ($satoBooks as $book) {
            PictureBook::create([
                'family_id' => 2,
                'registered_by' => 3,
                'title' => $book['title'],
                'authors' => $book['authors'],
                'isbn' => $book['isbn'],
                'read_status' => $book['read_status'],
                'rating' => $book['rating'],
            ]);
        }
    }
}
