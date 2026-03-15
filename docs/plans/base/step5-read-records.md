# Step 5: 読み聞かせ記録 (Read Records) — 詳細プラン

## ゴール

絵本ごとに読み聞かせ記録を作成・管理できる状態にする。記録には日付、対象の子ども（複数可）、子どもごとのリアクション、タグ、メモを含む。
ReadLog コンテキストを DDD + Clean Architecture + CQRS の構成で実装する。

## 完了条件

- [ ] `POST /api/v1/families/{family}/records` で読み聞かせ記録を作成できる
- [ ] `GET /api/v1/families/{family}/records` でフィルター・ページネーション付き一覧を取得できる
- [ ] `GET /api/v1/families/{family}/records/{record}` で記録詳細を取得できる
- [ ] `PUT /api/v1/families/{family}/records/{record}` で記録を更新できる
- [ ] `DELETE /api/v1/families/{family}/records/{record}` で記録を削除できる
- [ ] `GET /api/v1/tags?q={query}` でタグのオートコンプリート候補が返る
- [ ] 1つの記録に複数の子ども + 子どもごとのリアクションを紐付けられる
- [ ] タグの新規作成と既存タグの紐付けが同時にできる
- [ ] 他家族の記録にアクセスすると 403 が返る
- [ ] React SPA から記録の作成・一覧・詳細・編集・削除が動作する
- [ ] BookDetailPage 内に読み聞かせ記録セクションが統合される
- [ ] Feature テストが全て通る

---

## 5-1. マイグレーション

### `create_read_records_table`

```php
Schema::create('read_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('picture_book_id')->constrained()->cascadeOnDelete();
    $table->foreignId('family_id')->constrained()->cascadeOnDelete();
    $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
    $table->date('read_date');
    $table->text('memo')->nullable();
    $table->timestamps();

    $table->index(['family_id', 'read_date']);
    $table->index(['picture_book_id', 'read_date']);
});
```

### `create_child_read_record_table`（ピボットテーブル）

```php
Schema::create('child_read_record', function (Blueprint $table) {
    $table->foreignId('child_id')->constrained()->cascadeOnDelete();
    $table->foreignId('read_record_id')->constrained()->cascadeOnDelete();
    $table->string('reaction')->nullable();
    $table->primary(['child_id', 'read_record_id']);
});
```

### `create_tags_table`

```php
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->timestamps();
});
```

### `create_read_record_tag_table`（ピボットテーブル）

```php
Schema::create('read_record_tag', function (Blueprint $table) {
    $table->foreignId('read_record_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
    $table->primary(['read_record_id', 'tag_id']);
});
```

### 設計判断: child_read_record の reaction カラム

旧アプリでは読み聞かせ記録に 1 つのリアクションだった。新アプリでは子どもごとにリアクションを記録する:

| 旧 | 新 |
|---|---|
| `read_records.reaction` | `child_read_record.reaction`（ピボットテーブル上） |

→ 同じ絵本を読んでも、長男は「大喜び」、次男は「途中で飽きた」など、子どもごとに異なるリアクションを記録できる。

### 設計判断: reaction の型

| 方式 | 説明 |
|---|---|
| 定義済み選択肢（Enum） | `loved`, `enjoyed`, `bored`, `cried` 等の固定値 |
| 自由テキスト | ユーザーが自由に入力 |

→ **自由テキスト（string）を採用**。理由:
- リアクションの表現は多様で、定義済み選択肢では表現しきれない
- 個人開発アプリのため、自由度を優先
- フロントエンドでよく使うリアクションをサジェストとして表示する程度の補助は可能

### 設計判断: tags のスコープ

| 方式 | 説明 |
|---|---|
| グローバル（全ユーザー共有） | タグマスタが 1 つ。他家族が作ったタグも検索候補に出る |
| 家族スコープ（`tags.family_id`） | 家族ごとにタグを管理。他家族のタグは見えない |

→ **グローバルを採用**。理由:
- rebuild-plan のスキーマに `tags.family_id` がない（`id, name(unique)` のみ）
- タグ名自体は「寝る前」「お気に入り」等の汎用的な語句で、家族間で共有しても問題ない
- 実装がシンプル
- タグの表示はあくまで紐付けた記録に対してなので、他家族の記録が見えるわけではない

### 確認ポイント

- `task migrate` で 4 テーブルが作成される

---

## 5-2. ReadLog コンテキスト — Domain 層

### ディレクトリ構成

```
backend/packages/ReadLog/
├── Domain/
│   ├── Entity/
│   │   ├── ReadRecord.php
│   │   └── Tag.php
│   ├── ValueObject/
│   │   ├── ReadRecordId.php
│   │   ├── ReadDate.php
│   │   ├── Reaction.php
│   │   ├── ChildReaction.php     # child_id + reaction のペア
│   │   └── TagId.php
│   └── Repository/
│       ├── ReadRecordRepositoryInterface.php
│       └── TagRepositoryInterface.php
```

> `ReadRecordId` は共有カーネルには含めず、ReadLog ドメイン内（`packages/ReadLog/Domain/ValueObject/ReadRecordId.php`）で定義する。他コンテキストから参照されることがないため。
> `FamilyId`, `UserId`, `ChildId`, `PictureBookId` は共有カーネル（`packages/Shared/ValueObject/`）から参照。詳細は rebuild-plan の「コンテキスト間の Value Object 共有方針」を参照。
>
> **前提条件**: `FamilyId` と `ChildId` は現在 `packages/Family/Domain/ValueObject/` に存在する。Step 5 の実装前に `packages/Shared/ValueObject/` への移動が完了していること（`refactor/move-shared-value-objects` ブランチで対応中）。移動が未完了の場合、ReadLog コンテキストから Family コンテキストの ValueObject を直接参照することになり、コンテキスト間の依存方向が不適切になる。

### Domain Entity: `ReadRecord`

```php
namespace Packages\ReadLog\Domain\Entity;

final class ReadRecord
{
    /**
     * @param ChildReaction[] $childReactions
     * @param TagId[] $tagIds
     */
    public function __construct(
        private readonly ?ReadRecordId $id,
        private readonly PictureBookId $pictureBookId,
        private readonly FamilyId $familyId,
        private readonly UserId $recordedBy,
        private ReadDate $readDate,
        private ?string $memo,
        private array $childReactions,
        private array $tagIds,
    ) {}

    public static function create(
        PictureBookId $pictureBookId,
        FamilyId $familyId,
        UserId $recordedBy,
        ReadDate $readDate,
        ?string $memo,
        array $childReactions,
        array $tagIds,
    ): self {
        return new self(
            null, $pictureBookId, $familyId, $recordedBy,
            $readDate, $memo, $childReactions, $tagIds,
        );
    }

    public function update(
        ReadDate $readDate,
        ?string $memo,
        array $childReactions,
        array $tagIds,
    ): void {
        $this->readDate = $readDate;
        $this->memo = $memo;
        $this->childReactions = $childReactions;
        $this->tagIds = $tagIds;
    }

    // Getter, reconstruct メソッド
}
```

### Domain Entity: `Tag`

```php
namespace Packages\ReadLog\Domain\Entity;

final class Tag
{
    public function __construct(
        private readonly ?TagId $id,
        private readonly string $name,
    ) {}

    public static function create(string $name): self
    {
        return new self(null, $name);
    }

    // Getter, reconstruct メソッド
}
```

### Value Objects

| クラス | 内容 |
|---|---|
| `ReadRecordId` | 正の整数 |
| `ReadDate` | 過去または今日の日付。未来日を拒否 |
| `Reaction` | 0〜255文字の自由テキスト |
| `ChildReaction` | `ChildId` + `Reaction` のペア（Value Object） |
| `TagId` | 正の整数 |

### ChildReaction（Value Object）

```php
namespace Packages\ReadLog\Domain\ValueObject;

final class ChildReaction
{
    public function __construct(
        private readonly ChildId $childId,
        private readonly ?Reaction $reaction,
    ) {}

    // Getter メソッド
}
```

### Repository Interfaces

**`ReadRecordRepositoryInterface`**:
```php
interface ReadRecordRepositoryInterface
{
    public function findById(ReadRecordId $id): ?ReadRecord;
    public function save(ReadRecord $record): ReadRecord;
    public function delete(ReadRecordId $id): void;
}
```

**`TagRepositoryInterface`**:
```php
interface TagRepositoryInterface
{
    public function findByName(string $name): ?Tag;
    public function findOrCreateByNames(array $names): array;  // Tag[]
}
```

### 設計判断: ReadRecord Entity に childReactions と tagIds を含める

読み聞かせ記録は「どの子どもに読んだか（+リアクション）」「どのタグを付けたか」が不可分の情報。集約ルート (Aggregate Root) として ReadRecord が子ども紐付けとタグ紐付けを管理する。

→ 保存時にピボットテーブルの sync を Repository 内で行う。

---

## 5-3. ReadLog コンテキスト — Application 層

### ディレクトリ構成

```
backend/packages/ReadLog/
├── Application/
│   ├── Command/
│   │   ├── CreateRecord/
│   │   │   ├── CreateRecordCommand.php
│   │   │   └── CreateRecordHandler.php
│   │   ├── UpdateRecord/
│   │   │   ├── UpdateRecordCommand.php
│   │   │   └── UpdateRecordHandler.php
│   │   └── DeleteRecord/
│   │       ├── DeleteRecordCommand.php
│   │       └── DeleteRecordHandler.php
│   └── Query/
│       ├── ListRecords/
│       │   ├── ListRecordsQuery.php
│       │   └── ListRecordsHandler.php
│       ├── GetRecord/
│       │   ├── GetRecordQuery.php
│       │   └── GetRecordHandler.php
│       └── SearchTags/
│           ├── SearchTagsQuery.php
│           └── SearchTagsHandler.php
```

### CreateRecord

**`CreateRecordCommand`** (DTO):
```php
final class CreateRecordCommand
{
    /**
     * @param array<int, string|null> $childReactions  // [child_id => reaction]
     * @param string[] $tags                           // タグ名の配列
     */
    public function __construct(
        public readonly int $pictureBookId,
        public readonly int $familyId,
        public readonly int $userId,
        public readonly string $readDate,
        public readonly ?string $memo,
        public readonly array $childReactions,
        public readonly array $tags,
    ) {}
}
```

**`CreateRecordHandler`**:
1. `TagRepository::findOrCreateByNames()` でタグを取得 or 新規作成し、TagId の配列を得る
2. `childReactions` 配列を `ChildReaction` Value Object の配列に変換
3. `ReadRecord::create()` でエンティティ生成
4. `ReadRecordRepository::save()` で永続化（ピボットテーブル含む）
5. 作成された ReadRecord を返却

### UpdateRecord

**`UpdateRecordHandler`**:
1. `ReadRecordRepository::findById()` で取得
2. タグの findOrCreate
3. `ReadRecord::update()` で更新
4. `ReadRecordRepository::save()` で永続化（ピボットテーブルを sync）

### DeleteRecord

**`DeleteRecordHandler`**:
1. `ReadRecordRepository::delete()` で削除（cascade でピボットも削除）

### ListRecords（Query）

**`ListRecordsQuery`**:
```php
final class ListRecordsQuery
{
    public function __construct(
        public readonly int $familyId,
        public readonly ?int $childId = null,
        public readonly ?int $pictureBookId = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
    ) {}
}
```

**`ListRecordsHandler`**:
- Eloquent を直接使い、条件付きクエリを構築
- Eager load: `children`（ピボットの reaction 含む）、`tags`、`pictureBook`、`recordedByUser`
- ソート: `read_date` desc（デフォルト）
- ページネーション

### GetRecord（Query）

**`GetRecordHandler`**:
- Eloquent で記録詳細を取得（リレーション Eager load）

### SearchTags（Query）

**`SearchTagsQuery`**:
```php
final class SearchTagsQuery
{
    public function __construct(
        public readonly string $keyword,
        public readonly int $limit = 10,
    ) {}
}
```

**`SearchTagsHandler`**:
- `Tag::where('name', 'like', "{$keyword}%")->limit($limit)->get()`
- オートコンプリート用。前方一致で高速検索

---

## 5-4. ReadLog コンテキスト — Infrastructure 層

### ディレクトリ構成

```
backend/packages/ReadLog/
└── Infrastructure/
    └── Repository/
        ├── EloquentReadRecordRepository.php
        └── EloquentTagRepository.php
```

### EloquentReadRecordRepository

```php
namespace Packages\ReadLog\Infrastructure\Repository;

final class EloquentReadRecordRepository implements ReadRecordRepositoryInterface
{
    public function findById(ReadRecordId $id): ?ReadRecord
    {
        $model = EloquentReadRecord::with(['children', 'tags'])->find($id->value());
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(ReadRecord $record): ReadRecord
    {
        if ($record->id() === null) {
            $model = EloquentReadRecord::create([
                'picture_book_id' => $record->pictureBookId()->value(),
                'family_id' => $record->familyId()->value(),
                'recorded_by' => $record->recordedBy()->value(),
                'read_date' => $record->readDate()->value(),
                'memo' => $record->memo(),
            ]);
        } else {
            $model = EloquentReadRecord::findOrFail($record->id()->value());
            $model->update([
                'read_date' => $record->readDate()->value(),
                'memo' => $record->memo(),
            ]);
        }

        // ピボットテーブル sync: children + reaction
        $childrenSync = [];
        foreach ($record->childReactions() as $cr) {
            $childrenSync[$cr->childId()->value()] = ['reaction' => $cr->reaction()?->value()];
        }
        $model->children()->sync($childrenSync);

        // ピボットテーブル sync: tags
        $tagIds = array_map(fn ($tagId) => $tagId->value(), $record->tagIds());
        $model->tags()->sync($tagIds);

        return $this->toDomainEntity($model->fresh(['children', 'tags']));
    }

    public function delete(ReadRecordId $id): void
    {
        EloquentReadRecord::destroy($id->value());
    }

    private function toDomainEntity(EloquentReadRecord $model): ReadRecord { /* ... */ }
}
```

### EloquentTagRepository

```php
namespace Packages\ReadLog\Infrastructure\Repository;

final class EloquentTagRepository implements TagRepositoryInterface
{
    public function findByName(string $name): ?Tag
    {
        $model = EloquentTag::where('name', $name)->first();
        return $model ? Tag::reconstruct(new TagId($model->id), $model->name) : null;
    }

    public function findOrCreateByNames(array $names): array
    {
        return array_map(function (string $name) {
            $model = EloquentTag::firstOrCreate(['name' => trim($name)]);
            return Tag::reconstruct(new TagId($model->id), $model->name);
        }, $names);
    }
}
```

### ServiceProvider でのバインド

```php
$this->app->bind(ReadRecordRepositoryInterface::class, EloquentReadRecordRepository::class);
$this->app->bind(TagRepositoryInterface::class, EloquentTagRepository::class);
```

---

## 5-5. Eloquent Model

### `app/Models/ReadRecord.php`（新規）

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReadRecord extends Model
{
    protected $fillable = [
        'picture_book_id',
        'family_id',
        'recorded_by',
        'read_date',
        'memo',
    ];

    protected $casts = [
        'read_date' => 'date',
    ];

    public function pictureBook(): BelongsTo
    {
        return $this->belongsTo(PictureBook::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_read_record')
            ->withPivot('reaction');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'read_record_tag');
    }
}
```

### `app/Models/Tag.php`（新規）

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name'];

    public function readRecords(): BelongsToMany
    {
        return $this->belongsToMany(ReadRecord::class, 'read_record_tag');
    }
}
```

### 既存モデルへのリレーション追加

**`app/Models/PictureBook.php`**:
```php
public function readRecords(): HasMany
{
    return $this->hasMany(ReadRecord::class);
}
```

**`app/Models/Child.php`**:
```php
public function readRecords(): BelongsToMany
{
    return $this->belongsToMany(ReadRecord::class, 'child_read_record')
        ->withPivot('reaction');
}
```

**`app/Models/Family.php`**:
```php
public function readRecords(): HasMany
{
    return $this->hasMany(ReadRecord::class);
}
```

---

## 5-6. Interface 層 — Controller, Request, Resource, Routes

### ディレクトリ構成

```
backend/app/Http/
├── Controllers/Api/
│   ├── ReadRecordController.php
│   └── TagController.php
├── Requests/
│   ├── StoreReadRecordRequest.php
│   └── UpdateReadRecordRequest.php
└── Resources/
    ├── ReadRecordResource.php
    ├── ReadRecordCollection.php
    └── TagResource.php
```

### ReadRecordController

```php
class ReadRecordController extends Controller
{
    // GET /api/v1/families/{family}/records
    public function index(Request $request, Family $family, ListRecordsHandler $handler)

    // POST /api/v1/families/{family}/records
    public function store(StoreReadRecordRequest $request, Family $family, CreateRecordHandler $handler)

    // GET /api/v1/families/{family}/records/{readRecord}
    public function show(Family $family, ReadRecord $readRecord, GetRecordHandler $handler)

    // PUT /api/v1/families/{family}/records/{readRecord}
    public function update(UpdateReadRecordRequest $request, Family $family, ReadRecord $readRecord, UpdateRecordHandler $handler)

    // DELETE /api/v1/families/{family}/records/{readRecord}
    public function destroy(Family $family, ReadRecord $readRecord, DeleteRecordHandler $handler)
}
```

### TagController

```php
class TagController extends Controller
{
    // GET /api/v1/tags?q={query}
    public function index(Request $request, SearchTagsHandler $handler)
}
```

### FormRequest バリデーション

**`StoreReadRecordRequest`** / **`UpdateReadRecordRequest`**:
| フィールド | ルール |
|---|---|
| `picture_book_id` | `required` (store のみ), `integer`, `exists:picture_books,id` |
| `read_date` | `required`, `date`, `before_or_equal:today` |
| `memo` | `nullable`, `string`, `max:5000` |
| `children` | `required`, `array`, `min:1` |
| `children.*.child_id` | `required`, `integer`, `exists:children,id` |
| `children.*.reaction` | `nullable`, `string`, `max:255` |
| `tags` | `nullable`, `array` |
| `tags.*` | `string`, `max:50` |

**追加バリデーション（カスタムルール）**:
- `children.*.child_id` が指定された `family` に属していること
- `picture_book_id` が指定された `family` に属していること

```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $family = $this->route('family');

        // 子どもが家族に属しているか
        $childIds = collect($this->children)->pluck('child_id');
        $validChildCount = Child::where('family_id', $family->id)
            ->whereIn('id', $childIds)
            ->count();
        if ($validChildCount !== $childIds->count()) {
            $validator->errors()->add('children', 'Invalid child specified.');
        }

        // 絵本が家族に属しているか (store のみ)
        if ($this->picture_book_id) {
            $bookExists = PictureBook::where('id', $this->picture_book_id)
                ->where('family_id', $family->id)
                ->exists();
            if (!$bookExists) {
                $validator->errors()->add('picture_book_id', 'Invalid picture book specified.');
            }
        }
    });
}
```

### API Resource

**`ReadRecordResource`**:
```json
{
  "id": 1,
  "picture_book": {
    "id": 1,
    "title": "ぐりとぐら",
    "thumbnail_url": "https://..."
  },
  "read_date": "2026-03-08",
  "memo": "寝る前に読んだ",
  "children": [
    {
      "id": 1,
      "name": "はなこ",
      "reaction": "大喜びで何度もリクエスト"
    },
    {
      "id": 2,
      "name": "たろう",
      "reaction": "途中で寝た"
    }
  ],
  "tags": [
    { "id": 1, "name": "寝る前" },
    { "id": 2, "name": "お気に入り" }
  ],
  "recorded_by": {
    "id": 1,
    "name": "パパ"
  },
  "created_at": "2026-03-08T00:00:00.000000Z"
}
```

**`TagResource`**:
```json
{
  "id": 1,
  "name": "寝る前"
}
```

### ルート定義 (`routes/api.php`)

```php
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // ... 既存ルート (auth, families, children, books) ...

    // 読み聞かせ記録
    Route::prefix('/families/{family}/records')->scopeBindings()->group(function () {
        Route::get('/', [ReadRecordController::class, 'index']);
        Route::post('/', [ReadRecordController::class, 'store']);
        Route::get('/{readRecord}', [ReadRecordController::class, 'show']);
        Route::put('/{readRecord}', [ReadRecordController::class, 'update']);
        Route::delete('/{readRecord}', [ReadRecordController::class, 'destroy']);
    });

    // タグ検索（家族に紐付かない）
    Route::get('/tags', [TagController::class, 'index']);
});
```

### クエリパラメータ（一覧取得）

`GET /api/v1/families/{family}/records`:
| パラメータ | 型 | デフォルト | 説明 |
|---|---|---|---|
| `child_id` | integer | (全件) | 子どもでフィルター |
| `picture_book_id` | integer | (全件) | 絵本でフィルター |
| `date_from` | date | (制限なし) | 読んだ日の開始 |
| `date_to` | date | (制限なし) | 読んだ日の終了 |
| `per_page` | integer | `20` | 1ページあたりの件数（最大100） |
| `page` | integer | `1` | ページ番号 |

---

## 5-7. 認可 — ReadRecordPolicy

```php
namespace App\Policies;

class ReadRecordPolicy
{
    public function manage(User $user, ReadRecord $readRecord): bool
    {
        return $user->family_id === $readRecord->family_id;
    }
}
```

Controller では Step 4 と同様に:
- `index`: `FamilyPolicy::view` で家族所属チェック（読み取り操作）
- `store`: `FamilyPolicy::update` で家族所属チェック（書き込み操作。Step 4 の `PictureBookController::store` と同じパターン）
- `show`, `update`, `destroy`: `ReadRecordPolicy::manage` + `scopeBindings` で検証

---

## 5-8. Feature テスト

### テストファイル

```
backend/tests/Feature/
└── ReadLog/
    ├── CreateRecordTest.php
    ├── ListRecordsTest.php
    ├── GetRecordTest.php
    ├── UpdateRecordTest.php
    ├── DeleteRecordTest.php
    └── SearchTagsTest.php
```

### テストケース一覧

**CreateRecordTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に記録を作成（子ども1人、タグなし） | 201 |
| 2 | 複数の子ども + リアクション付きで作成 | 201, 各子どものリアクションが保存 |
| 3 | 新規タグ + 既存タグを同時に指定 | 201, タグが正しく紐付け |
| 4 | children が空配列 | 422（最低1人必要） |
| 5 | 他家族の絵本を指定 | 422 |
| 6 | 他家族の子どもを指定 | 422 |
| 7 | read_date が未来日 | 422 |
| 8 | 他家族の記録として作成 | 403 |

**ListRecordsTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 一覧取得（ページネーション） | 200 |
| 2 | child_id でフィルター | 200, 該当子どもの記録のみ |
| 3 | picture_book_id でフィルター | 200, 該当絵本の記録のみ |
| 4 | 日付範囲でフィルター | 200, 範囲内の記録のみ |
| 5 | 他家族の記録一覧 | 403 |

**GetRecordTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 記録詳細を取得（children, tags, pictureBook 含む） | 200 |
| 2 | 他家族の記録 | 403 |

**UpdateRecordTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 日付・メモ・子ども・タグを更新 | 200 |
| 2 | 子どもの追加・削除・リアクション変更 | 200, ピボットが正しく sync |
| 3 | タグの追加・削除 | 200, ピボットが正しく sync |
| 4 | 他家族の記録を更新 | 403 |

**DeleteRecordTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に削除 | 200 or 204, ピボットも削除 |
| 2 | 他家族の記録を削除 | 403 |
| 3 | 削除後にタグ自体は残る | タグマスタは削除されない |

**SearchTagsTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | キーワードでタグ検索 | 200, 前方一致の候補 |
| 2 | 該当なし | 200, 空配列 |
| 3 | q パラメータなし | 422 or 200（最近使われたタグを返す等） |

---

## 5-9. フロントエンド — 型定義・API 関数

### `src/api/records.ts`

> **既存パターンとの整合**: 現在のプロジェクトでは型定義を API ファイル内にインラインで定義している（例: `src/api/books.ts` に `PictureBook`, `GoogleBook` 等）。`src/types/` ディレクトリは存在しない。そのため、型定義も `src/api/records.ts` 内に定義する。

```typescript
import apiClient from './client';
import { PaginatedResponse } from './books';

export interface ChildReaction {
  child_id: number;
  name: string;         // 表示用
  reaction: string | null;
}

export interface ReadRecord {
  id: number;
  picture_book: {
    id: number;
    title: string;
    thumbnail_url: string | null;
  };
  read_date: string;    // "2026-03-08"
  memo: string | null;
  children: ChildReaction[];
  tags: { id: number; name: string }[];
  recorded_by: { id: number; name: string };
  created_at: string;
}

export interface CreateRecordData {
  picture_book_id: number;
  read_date: string;
  memo?: string;
  children: { child_id: number; reaction?: string }[];
  tags?: string[];
}

export const getRecords = (familyId: number, params?: {
  child_id?: number;
  picture_book_id?: number;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
}) =>
  apiClient.get(`/families/${familyId}/records`, { params });

export const createRecord = (familyId: number, data: CreateRecordData) =>
  apiClient.post(`/families/${familyId}/records`, data);

export const getRecord = (familyId: number, recordId: number) =>
  apiClient.get(`/families/${familyId}/records/${recordId}`);

export const updateRecord = (familyId: number, recordId: number, data: Partial<CreateRecordData>) =>
  apiClient.put(`/families/${familyId}/records/${recordId}`, data);

export const deleteRecord = (familyId: number, recordId: number) =>
  apiClient.delete(`/families/${familyId}/records/${recordId}`);

export const searchTags = (query: string) =>
  apiClient.get('/tags', { params: { q: query } });
```

---

## 5-10. フロントエンド — カスタムフック

### `src/hooks/useRecords.ts`

```typescript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

export const useRecords = (familyId: number, params?: {
  child_id?: number;
  picture_book_id?: number;
  date_from?: string;
  date_to?: string;
  page?: number;
}) => {
  return useQuery({
    queryKey: ['records', familyId, params],
    queryFn: () => getRecords(familyId, params),
  });
};

export const useRecord = (familyId: number, recordId: number) => {
  return useQuery({
    queryKey: ['record', familyId, recordId],
    queryFn: () => getRecord(familyId, recordId),
  });
};

export const useCreateRecord = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateRecordData) => createRecord(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['records', familyId] });
    },
  });
};

export const useUpdateRecord = (familyId: number, recordId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data) => updateRecord(familyId, recordId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['record', familyId, recordId] });
      queryClient.invalidateQueries({ queryKey: ['records', familyId] });
    },
  });
};

export const useDeleteRecord = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (recordId: number) => deleteRecord(familyId, recordId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['records', familyId] });
    },
  });
};

export const useSearchTags = (query: string) => {
  return useQuery({
    queryKey: ['tags', query],
    queryFn: () => searchTags(query),
    enabled: query.length >= 1,
  });
};
```

---

## 5-11. フロントエンド — ページコンポーネント

### ルーティング構成（Step 4 からの追加）

```typescript
<Route element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
  <Route path="/family/create" element={<CreateFamilyPage />} />

  <Route element={<RequireFamilyRoute><Outlet /></RequireFamilyRoute>}>
    <Route path="/" element={<DashboardPage />} />
    <Route path="/family/settings" element={<FamilySettingsPage />} />
    <Route path="/books/search" element={<BookSearchPage />} />
    <Route path="/books" element={<BookshelfPage />} />
    <Route path="/books/:bookId" element={<BookDetailPage />} />

    {/* Step 5 で追加 */}
    <Route path="/records" element={<RecordListPage />} />
    <Route path="/records/new" element={<RecordCreatePage />} />
    <Route path="/records/:recordId" element={<RecordDetailPage />} />
    <Route path="/records/:recordId/edit" element={<RecordEditPage />} />
  </Route>
</Route>
```

> **注意**: 現在の `App.tsx` では `RequireFamilyRoute` で家族所属チェックをラップしている。records は `familyId` が必須の家族スコープ機能のため、`RequireFamilyRoute` の内側に配置する必要がある。`/family/create` のみ `RequireFamilyRoute` の外に配置される（家族未作成ユーザー用）。

### RecordCreatePage + RecordForm

記録作成フォーム。React Hook Form で管理:

**フォームフィールド**:

| フィールド | UI | 説明 |
|---|---|---|
| 絵本選択 | セレクト or 検索フィールド | 本棚の絵本から選択 |
| 日付 | date input | デフォルト: 今日 |
| 子ども | チェックボックス群 | 家族の子ども一覧から複数選択 |
| リアクション | テキスト入力（子どもごと） | 選択した子どもの横に表示 |
| タグ | タグ入力（オートコンプリート） | 入力中にサジェスト、Enter で追加 |
| メモ | テキストエリア | 任意 |

**絵本選択の UX**:
- 本棚にある絵本をドロップダウンまたはモーダル検索で選択
- BookDetailPage からの導線: 「読み聞かせを記録」ボタンで `picture_book_id` をプリセット

**タグ入力の UX**:
- テキスト入力中に `useSearchTags` でサジェスト表示
- サジェストをクリック or Enter でタグ追加
- 追加済みタグはチップ（badge）で表示、x ボタンで削除
- 存在しないタグ名も入力可能（バックエンドで自動作成）

### RecordListPage

- タイムライン形式で記録を表示（日付降順）
- フィルターパネル:
  - 子ども（ドロップダウン）
  - 絵本（ドロップダウンまたは検索）
  - 期間（date_from, date_to）
- ページネーション
- 空状態: 「まだ読み聞かせの記録がありません」

### RecordCard コンポーネント

- 絵本サムネイル + タイトル
- 読んだ日
- 子どもの名前 + リアクション（アイコン or テキスト）
- タグ（チップ表示）
- メモ（省略表示）
- クリックで詳細ページへ

### RecordDetailPage

- 記録の全情報を表示
- 編集ボタン → RecordEditPage へ
- 削除ボタン（確認ダイアログ付き）

### RecordEditPage

- RecordForm を編集モードで再利用
- 既存データをフォームにプリセット
- 更新成功時に詳細ページへリダイレクト

### BookDetailPage への統合

Step 4 で作成した BookDetailPage に読み聞かせ記録セクションを追加:

```typescript
// BookDetailPage 内
const { data: records } = useRecords(familyId, { picture_book_id: bookId });

// 「読み聞かせを記録する」ボタン → /records/new?book_id={bookId}
// この絵本の記録一覧（最新数件）
// 「すべての記録を見る」リンク → /records?picture_book_id={bookId}
```

---

## 5-12. PictureBook の read_status 自動更新（検討事項）

読み聞かせ記録の作成時に、紐付く絵本の `read_status` を自動更新するか:

| 方式 | 説明 |
|---|---|
| 自動更新 | 記録が作成されたら `read_status` を `read` に変更 |
| 手動のみ | ユーザーが明示的にステータスを変更 |

→ **Phase 1 では手動のみ**。理由:
- 「読み聞かせを記録した ≠ 読了」のケースがある（途中まで読んだ、読み聞かせ中のメモ等）
- 自動更新のロジックが曖昧（最初の記録で `reading`、N回目で `read` 等のルールが不明確）
- シンプルさを優先し、Phase 2 以降で検討

---

## 5-13. 疎通確認チェックリスト

| # | 確認項目 | 方法 | 期待結果 |
|---|---|---|---|
| 1 | マイグレーション | `task migrate` | 4テーブル作成 |
| 2 | 記録作成 | `curl -X POST .../families/{id}/records` | 201, 子ども・タグ紐付け |
| 3 | 複数子ども + リアクション | 作成時に children 配列で指定 | 各子どもの reaction が保存 |
| 4 | 新規タグ作成 | 存在しないタグ名で記録作成 | タグが自動作成され紐付け |
| 5 | 記録一覧 | `curl .../families/{id}/records` | 200, ページネーション付き |
| 6 | child_id フィルター | `?child_id=1` | 該当子どもの記録のみ |
| 7 | picture_book_id フィルター | `?picture_book_id=1` | 該当絵本の記録のみ |
| 8 | 日付範囲フィルター | `?date_from=...&date_to=...` | 範囲内の記録のみ |
| 9 | 記録詳細 | `curl .../families/{id}/records/{recordId}` | 200, 全リレーション含む |
| 10 | 記録更新 | `curl -X PUT ...` | 200, ピボット sync |
| 11 | 記録削除 | `curl -X DELETE ...` | 200 or 204, ピボットも削除 |
| 12 | タグ検索 | `curl /api/v1/tags?q=寝` | 200, 前方一致の候補 |
| 13 | 認可: 他家族 | 所属していない family_id でアクセス | 403 |
| 14 | React 記録作成フロー | RecordCreatePage で全フィールド入力 → 送信 | 一覧に反映 |
| 15 | React 記録編集フロー | RecordEditPage で既存データ表示 → 更新 → 詳細ページへリダイレクト | 更新が反映 |
| 16 | React タグ入力 | タグ入力でサジェスト表示 → 選択 | 正しく紐付け |
| 17 | React BookDetailPage | 絵本詳細に記録セクション表示 | 記録一覧・追加ボタン |
| 18 | Feature テスト | `task test` | 全テスト通過 |

---

## 作業順序まとめ

```
5-1.  マイグレーション (read_records, child_read_record, tags, read_record_tag)
         ↓
5-2.  Domain 層 (Entity, ValueObject, RepositoryInterface)
         ↓
5-3.  Application 層 (Command/Query Handler)
         ↓
5-4.  Infrastructure 層 (EloquentRepository, ServiceProvider バインド)
         ↓
5-5.  Eloquent Model (ReadRecord, Tag, 既存モデルへのリレーション追加)
         ↓
5-6.  Interface 層 (Controller, Request, Resource, Routes)
         ↓
5-7.  認可 (ReadRecordPolicy)
         ↓
5-8.  Feature テスト作成・実行
         ↓
5-9.  フロントエンド 型定義 + API 関数
         ↓
5-10. カスタムフック (useRecords, useSearchTags)
         ↓
5-11. ページコンポーネント (RecordList, RecordCreate, RecordDetail, BookDetailPage 統合)
         ↓
5-12. read_status 自動更新の検討（Phase 1 では見送り）
         ↓
5-13. 疎通確認チェックリスト実行
```

---

## 注意事項・判断メモ

- **Value Object の共有**: `FamilyId`, `UserId`, `ChildId`, `PictureBookId` は共有カーネル（`packages/Shared/ValueObject/`）から参照。rebuild-plan の「コンテキスト間の Value Object 共有方針」を参照。**前提**: `FamilyId`, `ChildId` の Shared への移動（`refactor/move-shared-value-objects` ブランチ）が完了していること
- **ReadRecordId の配置**: 共有カーネルではなく ReadLog ドメイン内（`packages/ReadLog/Domain/ValueObject/`）に定義。他コンテキストから参照されないため
- **reaction は自由テキスト**: 定義済み選択肢ではなく string で保存。フロントエンドでのサジェストは Phase 2 で検討
- **tags はグローバルスコープ**: rebuild-plan のスキーマに準拠。家族スコープではない
- **children は最低1人必須**: 「誰に読んだか」が記録の核心情報。子どもなしの記録は許可しない
- **タグの自動作成**: `firstOrCreate` で存在しなければ作成。ユーザーが自由にタグを作れる
- **ピボットテーブルの sync**: 更新時に `sync()` を使い、追加・削除・変更を一括処理
- **read_status の自動更新は見送り**: Phase 1 ではユーザーの手動操作のみ
- **絵本削除時の cascade**: `picture_books` 削除で `read_records` も cascade 削除される設計。Step 4 の注意事項で言及した点はこの Step で確定
- **RecordForm の再利用**: 作成（RecordCreatePage）と編集（RecordEditPage）で同一フォームコンポーネントを使う
- **BookDetailPage からの導線**: クエリパラメータ `?book_id=` で絵本をプリセットし、記録作成への導線をスムーズにする
