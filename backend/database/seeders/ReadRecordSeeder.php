<?php

namespace Database\Seeders;

use App\Models\PictureBook;
use App\Models\ReadRecord;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadRecordSeeder extends Seeder
{
    public function run(): void
    {
        $tags = Tag::all()->keyBy('name');
        $today = Carbon::today();

        // 山田家の記録
        $yamadaBooks = PictureBook::where('family_id', 1)->get()->keyBy('title');
        $yamadaRecords = [
            ['book' => 'ぐりとぐら', 'days_ago' => 1, 'children' => [1 => '😊', 2 => '😊'], 'tags' => ['寝る前', 'お気に入り'], 'memo' => 'カステラのシーンで二人とも大喜びでした'],
            ['book' => 'ぐりとぐら', 'days_ago' => 8, 'children' => [1 => '😊'], 'tags' => ['リクエスト'], 'memo' => 'ゆうきのリクエストで読みました'],
            ['book' => 'はらぺこあおむし', 'days_ago' => 2, 'children' => [2 => '😆'], 'tags' => ['寝る前'], 'memo' => '穴に指を入れて遊んでいました'],
            ['book' => 'はらぺこあおむし', 'days_ago' => 12, 'children' => [1 => '😊', 2 => '😆'], 'tags' => ['お気に入り'], 'memo' => null],
            ['book' => 'おおきなかぶ', 'days_ago' => 3, 'children' => [1 => '😄'], 'tags' => ['図書館'], 'memo' => '「うんとこしょ」を一緒に言ってくれた'],
            ['book' => 'いないいないばあ', 'days_ago' => 4, 'children' => [2 => '😆'], 'tags' => ['寝る前'], 'memo' => 'あおいがとても喜んでいた'],
            ['book' => 'いないいないばあ', 'days_ago' => 15, 'children' => [2 => '😊'], 'tags' => [], 'memo' => null],
            ['book' => 'だるまさんが', 'days_ago' => 5, 'children' => [1 => '😆', 2 => '😆'], 'tags' => ['お気に入り', 'リクエスト'], 'memo' => '二人でだるまさんの真似をしていた'],
            ['book' => 'だるまさんが', 'days_ago' => 10, 'children' => [1 => '😊'], 'tags' => [], 'memo' => null],
            ['book' => 'だるまさんが', 'days_ago' => 20, 'children' => [1 => '😊', 2 => '😊'], 'tags' => ['リクエスト'], 'memo' => '何度読んでも飽きないみたい'],
            ['book' => 'ねないこだれだ', 'days_ago' => 6, 'children' => [1 => '😨'], 'tags' => ['寝る前'], 'memo' => 'ちょっと怖がっていたけど楽しそう'],
            ['book' => 'しろくまちゃんのほっとけーき', 'days_ago' => 7, 'children' => [1 => '😊', 2 => '😊'], 'tags' => ['新しい絵本'], 'memo' => 'ホットケーキを作りたいと言い出した'],
            ['book' => 'くだもの', 'days_ago' => 9, 'children' => [2 => '😊'], 'tags' => ['図書館'], 'memo' => '「はい、どうぞ」と果物を渡す真似をしていた'],
            ['book' => 'ノンタンぶらんこのせて', 'days_ago' => 11, 'children' => [1 => '😊'], 'tags' => ['シリーズ'], 'memo' => null],
            ['book' => 'ぐるんぱのようちえん', 'days_ago' => 14, 'children' => [1 => '😊'], 'tags' => ['図書館'], 'memo' => '最後のようちえんのページが好きみたい'],
            ['book' => 'ぐりとぐら', 'days_ago' => 18, 'children' => [1 => '😊', 2 => '😊'], 'tags' => ['お気に入り'], 'memo' => null],
            ['book' => 'はらぺこあおむし', 'days_ago' => 22, 'children' => [1 => '😆'], 'tags' => [], 'memo' => '数を一緒に数えました'],
            ['book' => 'いないいないばあ', 'days_ago' => 25, 'children' => [2 => '😆'], 'tags' => ['寝る前'], 'memo' => null],
            ['book' => 'おおきなかぶ', 'days_ago' => 27, 'children' => [1 => '😊', 2 => '😊'], 'tags' => [], 'memo' => '家族みんなで引っ張る真似をした'],
            ['book' => 'だるまさんが', 'days_ago' => 28, 'children' => [1 => '😆'], 'tags' => ['お気に入り'], 'memo' => null],
        ];

        foreach ($yamadaRecords as $data) {
            $book = $yamadaBooks->get($data['book']);
            if (! $book) {
                continue;
            }

            $record = ReadRecord::create([
                'picture_book_id' => $book->id,
                'family_id' => 1,
                'recorded_by' => 1,
                'read_date' => $today->copy()->subDays($data['days_ago']),
                'memo' => $data['memo'],
            ]);

            foreach ($data['children'] as $childId => $reaction) {
                $record->children()->attach($childId, ['reaction' => $reaction]);
            }

            $tagIds = collect($data['tags'])
                ->map(fn (string $name) => $tags->get($name)?->id)
                ->filter()
                ->all();
            if ($tagIds) {
                $record->tags()->attach($tagIds);
            }
        }

        // 佐藤家の記録
        $satoBooks = PictureBook::where('family_id', 2)->get()->keyBy('title');
        $satoRecords = [
            ['book' => 'ぐりとぐら', 'days_ago' => 2, 'children' => [3 => '😊'], 'tags' => ['寝る前'], 'memo' => null],
            ['book' => 'はらぺこあおむし', 'days_ago' => 5, 'children' => [3 => '😆'], 'tags' => ['お気に入り'], 'memo' => '何度目かな？'],
            ['book' => 'おつきさまこんばんは', 'days_ago' => 7, 'children' => [3 => '😊'], 'tags' => ['寝る前'], 'memo' => '窓からお月さまを探していた'],
            ['book' => 'じゃあじゃあびりびり', 'days_ago' => 10, 'children' => [3 => '😆'], 'tags' => [], 'memo' => null],
            ['book' => 'おつきさまこんばんは', 'days_ago' => 15, 'children' => [3 => '😊'], 'tags' => ['リクエスト'], 'memo' => 'さくらのリクエスト'],
        ];

        foreach ($satoRecords as $data) {
            $book = $satoBooks->get($data['book']);
            if (! $book) {
                continue;
            }

            $record = ReadRecord::create([
                'picture_book_id' => $book->id,
                'family_id' => 2,
                'recorded_by' => 3,
                'read_date' => $today->copy()->subDays($data['days_ago']),
                'memo' => $data['memo'],
            ]);

            foreach ($data['children'] as $childId => $reaction) {
                $record->children()->attach($childId, ['reaction' => $reaction]);
            }

            $tagIds = collect($data['tags'])
                ->map(fn (string $name) => $tags->get($name)?->id)
                ->filter()
                ->all();
            if ($tagIds) {
                $record->tags()->attach($tagIds);
            }
        }
    }
}
