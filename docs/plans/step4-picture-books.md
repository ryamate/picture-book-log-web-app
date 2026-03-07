# Step 4: 絵本登録 (Picture Books) — 詳細プラン

## ゴール

Google Books API から絵本を検索し、家族の本棚に登録・管理できる状態にする。
Bookshelf コンテキストを DDD + Clean Architecture + CQRS の構成で実装する。

## 完了条件

- [ ] `GET /api/v1/books/search?q={query}` で Google Books API の検索結果が返る
- [ ] `POST /api/v1/families/{family}/books` で絵本を本棚に登録できる
- [ ] `GET /api/v1/families/{family}/books` で本棚一覧がページネーション付きで取得できる
- [ ] `GET /api/v1/families/{family}/books/{book}` で絵本詳細が取得できる
- [ ] `PUT /api/v1/families/{family}/books/{book}` で評価・ステータス・レビューを更新できる
- [ ] `DELETE /api/v1/families/{family}/books/{book}` で絵本を本棚から削除できる
- [ ] 他家族の本棚にアクセスすると 403 が返る
- [ ] React SPA から絵本検索・登録・本棚管理の一連のフローが動作する
- [ ] Feature テストが全て通る

---

## 4-1. マイグレーション

### `create_picture_books_table`

```php
Schema::create('picture_books', function (Blueprint $table) {
    $table->id();
    $table->foreignId('family_id')->constrained()->cascadeOnDelete();
    $table->foreignId('registered_by')->constrained('users')->nullOnDelete();
    $table->string('google_books_id')->nullable();
    $table->string('isbn')->nullable();
    $table->string('title');
    $table->json('authors');              // ["著者A", "著者B"]
    $table->string('thumbnail_url')->nullable();
    $table->unsignedTinyInteger('rating')->nullable();  // 1-5
    $table->string('read_status')->default('unread');    // unread, reading, read
    $table->text('review')->nullable();
    $table->timestamps();

    $table->index(['family_id', 'read_status']);
    $table->index(['family_id', 'created_at']);
});
```

### 設計判断: authors を JSON 型にする理由

| 方式 | 構造 | メリット | デメリット |
|---|---|---|---|
| JSON カラム | `authors JSON` | シンプル、Google Books API の配列をそのまま保存 | 著者での検索が非効率 |
| 正規化（中間テーブル） | `authors` + `author_picture_book` | 著者マスタで検索・集計可能 | 設計が複雑、API レスポンスからの変換が必要 |

→ **JSON 型を採用**。理由:
- Google Books API が `authors: ["著者A", "著者B"]` の配列で返すため、変換なしで保存できる
- 本アプリで著者別検索・集計の要件がない（絵本タイトルや読み聞かせ記録が主な関心事）
- MySQL 8.0 の JSON 関数で必要に応じてクエリ可能

### 設計判断: read_status の型

| 方式 | 説明 |
|---|---|
| ENUM | DB レベルで値を制約。マイグレーションなしに値の追加不可 |
| string + アプリバリデーション | 柔軟、マイグレーション不要で値追加可能 |

→ **string + Value Object で制約** を採用。Domain 層の Value Object で有効な値を管理し、DB は string で保存。

### 設計判断: google_books_id と isbn の nullable

- Google Books API の検索結果から登録する場合は `google_books_id` が設定される
- 手動登録（Google Books にない絵本）も許容するため、両方 nullable
- 重複チェック: `family_id` + `google_books_id` の組み合わせで既登録を判定（同じ本を同家族で2回登録しない）

### 確認ポイント

- `task migrate` で `picture_books` テーブルが作成される

---

## 4-2. Bookshelf コンテキスト — Domain 層

### ディレクトリ構成

```
backend/packages/Bookshelf/
├── Domain/
│   ├── Entity/
│   │   └── PictureBook.php
│   ├── ValueObject/
│   │   ├── Isbn.php
│   │   ├── BookTitle.php
│   │   ├── Authors.php
│   │   ├── Rating.php
│   │   └── ReadStatus.php
│   └── Repository/
│       └── PictureBookRepositoryInterface.php
```

> `PictureBookId`, `FamilyId`, `UserId` は共有カーネル（`packages/Shared/ValueObject/`）に定義。詳細は rebuild-plan の「コンテキスト間の Value Object 共有方針」を参照。

### Domain Entity: `PictureBook`

```php
namespace Packages\Bookshelf\Domain\Entity;

final class PictureBook
{
    public function __construct(
        private readonly ?PictureBookId $id,
        private readonly FamilyId $familyId,
        private readonly UserId $registeredBy,
        private readonly ?string $googleBooksId,
        private readonly ?Isbn $isbn,
        private readonly BookTitle $title,
        private readonly Authors $authors,
        private readonly ?string $thumbnailUrl,
        private ?Rating $rating,
        private ReadStatus $readStatus,
        private ?string $review,
    ) {}

    public static function register(
        FamilyId $familyId,
        UserId $registeredBy,
        ?string $googleBooksId,
        ?Isbn $isbn,
        BookTitle $title,
        Authors $authors,
        ?string $thumbnailUrl,
    ): self {
        return new self(
            id: null,
            familyId: $familyId,
            registeredBy: $registeredBy,
            googleBooksId: $googleBooksId,
            isbn: $isbn,
            title: $title,
            authors: $authors,
            thumbnailUrl: $thumbnailUrl,
            rating: null,
            readStatus: ReadStatus::Unread,
            review: null,
        );
    }

    public function updateReview(?Rating $rating, ReadStatus $readStatus, ?string $review): void
    {
        $this->rating = $rating;
        $this->readStatus = $readStatus;
        $this->review = $review;
    }

    // Getter, reconstruct メソッド
}
```

### Value Objects

| クラス | 内容 |
|---|---|
| `PictureBookId` | 正の整数 |
| `Isbn` | ISBN-10 or ISBN-13 の形式検証（ハイフンなし数字列） |
| `BookTitle` | 1〜500文字 |
| `Authors` | `string[]` のラッパー。空配列を許容（著者不明） |
| `Rating` | 1〜5 の整数 |
| `ReadStatus` | Enum: `Unread`, `Reading`, `Read` |

### ReadStatus（Enum）

```php
namespace Packages\Bookshelf\Domain\ValueObject;

enum ReadStatus: string
{
    case Unread = 'unread';
    case Reading = 'reading';
    case Read = 'read';
}
```

### Repository Interface

```php
namespace Packages\Bookshelf\Domain\Repository;

interface PictureBookRepositoryInterface
{
    public function findById(PictureBookId $id): ?PictureBook;
    public function findByFamilyIdAndGoogleBooksId(FamilyId $familyId, string $googleBooksId): ?PictureBook;
    public function save(PictureBook $book): PictureBook;
    public function delete(PictureBookId $id): void;
}
```

---

## 4-3. Bookshelf コンテキスト — Application 層

### ディレクトリ構成

```
backend/packages/Bookshelf/
├── Application/
│   ├── Command/
│   │   ├── AddBook/
│   │   │   ├── AddBookCommand.php
│   │   │   └── AddBookHandler.php
│   │   ├── UpdateBook/
│   │   │   ├── UpdateBookCommand.php
│   │   │   └── UpdateBookHandler.php
│   │   └── RemoveBook/
│   │       ├── RemoveBookCommand.php
│   │       └── RemoveBookHandler.php
│   └── Query/
│       ├── SearchGoogleBooks/
│       │   ├── SearchGoogleBooksQuery.php
│       │   └── SearchGoogleBooksHandler.php
│       ├── ListBooks/
│       │   ├── ListBooksQuery.php
│       │   └── ListBooksHandler.php
│       └── GetBook/
│           ├── GetBookQuery.php
│           └── GetBookHandler.php
```

### AddBook

**`AddBookCommand`** (DTO):
```php
final class AddBookCommand
{
    public function __construct(
        public readonly int $familyId,
        public readonly int $userId,
        public readonly ?string $googleBooksId,
        public readonly ?string $isbn,
        public readonly string $title,
        public readonly array $authors,
        public readonly ?string $thumbnailUrl,
    ) {}
}
```

**`AddBookHandler`**:
1. `google_books_id` が指定されている場合、同家族で既に登録済みかチェック（重複防止）
2. `PictureBook::register()` でエンティティ生成
3. `PictureBookRepository::save()` で永続化
4. 登録された PictureBook を返却

### UpdateBook

**`UpdateBookCommand`**:
```php
final class UpdateBookCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly ?int $rating,        // 1-5 or null
        public readonly string $readStatus,  // unread, reading, read
        public readonly ?string $review,
    ) {}
}
```

**`UpdateBookHandler`**:
1. `PictureBookRepository::findById()` で取得
2. `PictureBook::updateReview()` で更新
3. `PictureBookRepository::save()` で永続化

### RemoveBook

**`RemoveBookHandler`**:
1. `PictureBookRepository::delete()` で削除

### SearchGoogleBooks（Query）

**`SearchGoogleBooksQuery`**:
```php
final class SearchGoogleBooksQuery
{
    public function __construct(
        public readonly string $keyword,
        public readonly int $startIndex = 0,
        public readonly int $maxResults = 20,
    ) {}
}
```

**`SearchGoogleBooksHandler`**:
- Google Books API クライアントを呼び出し、結果を DTO に変換して返す
- CQRS の Query 側だが、外部 API 呼び出しのため Domain 層は経由しない

### ListBooks（Query）

**`ListBooksHandler`**:
- Eloquent を直接使って家族の本棚を取得
- フィルター: `read_status`
- ソート: `created_at` desc（デフォルト）
- ページネーション: Laravel の `paginate()`

### GetBook（Query）

**`GetBookHandler`**:
- Eloquent を直接使って絵本詳細を取得

---

## 4-4. Bookshelf コンテキスト — Infrastructure 層

### ディレクトリ構成

```
backend/packages/Bookshelf/
└── Infrastructure/
    ├── Repository/
    │   └── EloquentPictureBookRepository.php
    └── External/
        └── GoogleBooksApiClient.php
```

### EloquentPictureBookRepository

```php
namespace Packages\Bookshelf\Infrastructure\Repository;

final class EloquentPictureBookRepository implements PictureBookRepositoryInterface
{
    public function findById(PictureBookId $id): ?PictureBook
    {
        $model = EloquentPictureBook::find($id->value());
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByFamilyIdAndGoogleBooksId(FamilyId $familyId, string $googleBooksId): ?PictureBook
    {
        $model = EloquentPictureBook::where('family_id', $familyId->value())
            ->where('google_books_id', $googleBooksId)
            ->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(PictureBook $book): PictureBook
    {
        if ($book->id() === null) {
            $model = EloquentPictureBook::create([
                'family_id' => $book->familyId()->value(),
                'registered_by' => $book->registeredBy()->value(),
                'google_books_id' => $book->googleBooksId(),
                'isbn' => $book->isbn()?->value(),
                'title' => $book->title()->value(),
                'authors' => $book->authors()->toArray(),
                'thumbnail_url' => $book->thumbnailUrl(),
                'rating' => $book->rating()?->value(),
                'read_status' => $book->readStatus()->value,
                'review' => $book->review(),
            ]);
        } else {
            $model = EloquentPictureBook::findOrFail($book->id()->value());
            $model->update([
                'rating' => $book->rating()?->value(),
                'read_status' => $book->readStatus()->value,
                'review' => $book->review(),
            ]);
        }
        return $this->toDomainEntity($model);
    }

    public function delete(PictureBookId $id): void
    {
        EloquentPictureBook::destroy($id->value());
    }

    private function toDomainEntity(EloquentPictureBook $model): PictureBook { /* ... */ }
}
```

### GoogleBooksApiClient

```php
namespace Packages\Bookshelf\Infrastructure\External;

use Illuminate\Support\Facades\Http;

final class GoogleBooksApiClient
{
    private const BASE_URL = 'https://www.googleapis.com/books/v1/volumes';

    public function search(string $keyword, int $startIndex = 0, int $maxResults = 20): array
    {
        $response = Http::get(self::BASE_URL, [
            'q' => $keyword,
            'startIndex' => $startIndex,
            'maxResults' => $maxResults,
            'langRestrict' => 'ja',
            'printType' => 'books',
        ]);

        $response->throw();

        return $this->transformResponse($response->json());
    }

    private function transformResponse(array $data): array
    {
        $totalItems = $data['totalItems'] ?? 0;
        $items = array_map(fn (array $item) => $this->transformItem($item), $data['items'] ?? []);

        return [
            'total_items' => $totalItems,
            'items' => $items,
        ];
    }

    private function transformItem(array $item): array
    {
        $volumeInfo = $item['volumeInfo'] ?? [];
        $isbn13 = $this->extractIsbn($volumeInfo['industryIdentifiers'] ?? [], 'ISBN_13');
        $isbn10 = $this->extractIsbn($volumeInfo['industryIdentifiers'] ?? [], 'ISBN_10');

        return [
            'google_books_id' => $item['id'],
            'title' => $volumeInfo['title'] ?? '',
            'authors' => $volumeInfo['authors'] ?? [],
            'isbn' => $isbn13 ?? $isbn10,
            'thumbnail_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
            'published_date' => $volumeInfo['publishedDate'] ?? null,
            'description' => $volumeInfo['description'] ?? null,
            'page_count' => $volumeInfo['pageCount'] ?? null,
        ];
    }

    private function extractIsbn(array $identifiers, string $type): ?string
    {
        foreach ($identifiers as $identifier) {
            if ($identifier['type'] === $type) {
                return $identifier['identifier'];
            }
        }
        return null;
    }
}
```

### 設計判断: Google Books API キーの要否

- Google Books API は API キーなしでもリクエスト可能（レート制限あり）
- 個人開発アプリで利用量が少ないため、Phase 1 では API キーなしで開始
- レート制限に引っかかった場合に API キーを設定する（`.env` に `GOOGLE_BOOKS_API_KEY` を追加）

### 設計判断: サムネイル URL の扱い

Google Books API が返す `thumbnail_url` は `http://` の場合がある。

| 方式 | 説明 |
|---|---|
| そのまま保存 | シンプルだが mixed content 警告の可能性 |
| `https://` に書き換えて保存 | Google Books は https でもアクセス可能 |
| プロキシ経由で配信 | 確実だが実装コスト高 |

→ **`https://` に書き換えて保存**。`GoogleBooksApiClient` 内で `str_replace('http://', 'https://', $url)` する。

### ServiceProvider でのバインド

```php
$this->app->bind(PictureBookRepositoryInterface::class, EloquentPictureBookRepository::class);
$this->app->bind(GoogleBooksApiClient::class, GoogleBooksApiClient::class);
```

---

## 4-5. Eloquent Model

### `app/Models/PictureBook.php`（新規）

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PictureBook extends Model
{
    protected $fillable = [
        'family_id',
        'registered_by',
        'google_books_id',
        'isbn',
        'title',
        'authors',
        'thumbnail_url',
        'rating',
        'read_status',
        'review',
    ];

    protected $casts = [
        'authors' => 'array',
        'rating' => 'integer',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
```

### `app/Models/Family.php`（リレーション追加）

```php
// 既存の Family モデルに追加
public function pictureBooks(): HasMany
{
    return $this->hasMany(PictureBook::class);
}
```

---

## 4-6. Interface 層 — Controller, Request, Resource, Routes

### ディレクトリ構成

```
backend/app/Http/
├── Controllers/Api/
│   └── PictureBookController.php
├── Requests/
│   ├── StoreBookRequest.php
│   └── UpdateBookRequest.php
└── Resources/
    ├── PictureBookResource.php
    ├── PictureBookCollection.php
    └── GoogleBookResource.php
```

### PictureBookController

```php
class PictureBookController extends Controller
{
    // GET /api/v1/books/search?q={query}
    public function search(Request $request, SearchGoogleBooksHandler $handler)

    // GET /api/v1/families/{family}/books
    public function index(Request $request, Family $family, ListBooksHandler $handler)

    // POST /api/v1/families/{family}/books
    public function store(StoreBookRequest $request, Family $family, AddBookHandler $handler)

    // GET /api/v1/families/{family}/books/{pictureBook}
    public function show(Family $family, PictureBook $pictureBook, GetBookHandler $handler)

    // PUT /api/v1/families/{family}/books/{pictureBook}
    public function update(UpdateBookRequest $request, Family $family, PictureBook $pictureBook, UpdateBookHandler $handler)

    // DELETE /api/v1/families/{family}/books/{pictureBook}
    public function destroy(Family $family, PictureBook $pictureBook, RemoveBookHandler $handler)
}
```

### FormRequest バリデーション

**`StoreBookRequest`**:
| フィールド | ルール |
|---|---|
| `google_books_id` | `nullable`, `string`, `max:255` |
| `isbn` | `nullable`, `string`, `max:13` |
| `title` | `required`, `string`, `max:500` |
| `authors` | `required`, `array` |
| `authors.*` | `string`, `max:255` |
| `thumbnail_url` | `nullable`, `url`, `max:2048` |

**`UpdateBookRequest`**:
| フィールド | ルール |
|---|---|
| `rating` | `nullable`, `integer`, `min:1`, `max:5` |
| `read_status` | `required`, `string`, `in:unread,reading,read` |
| `review` | `nullable`, `string`, `max:5000` |

### API Resource

**`GoogleBookResource`**（検索結果用）:
```json
{
  "google_books_id": "xxxxx",
  "title": "ぐりとぐら",
  "authors": ["中川李枝子"],
  "isbn": "9784834000825",
  "thumbnail_url": "https://...",
  "published_date": "1967-01-20",
  "description": "...",
  "page_count": 28
}
```

**`PictureBookResource`**（本棚の絵本）:
```json
{
  "id": 1,
  "google_books_id": "xxxxx",
  "isbn": "9784834000825",
  "title": "ぐりとぐら",
  "authors": ["中川李枝子"],
  "thumbnail_url": "https://...",
  "rating": 5,
  "read_status": "read",
  "review": "子どもが大好きな一冊",
  "registered_by": {
    "id": 1,
    "name": "太郎"
  },
  "created_at": "2026-03-08T00:00:00.000000Z"
}
```

**`PictureBookCollection`**（ページネーション付き一覧）:
```json
{
  "data": [ /* PictureBookResource の配列 */ ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 52
  }
}
```

### ルート定義 (`routes/api.php`)

```php
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // ... 既存ルート (auth, families, children) ...

    // Google Books 検索（家族に紐付かない）
    Route::get('/books/search', [PictureBookController::class, 'search']);

    // 本棚（家族に紐付く）
    Route::prefix('/families/{family}/books')->group(function () {
        Route::get('/', [PictureBookController::class, 'index']);
        Route::post('/', [PictureBookController::class, 'store']);
        Route::get('/{pictureBook}', [PictureBookController::class, 'show']);
        Route::put('/{pictureBook}', [PictureBookController::class, 'update']);
        Route::delete('/{pictureBook}', [PictureBookController::class, 'destroy']);
    });
});
```

### クエリパラメータ（一覧取得）

`GET /api/v1/families/{family}/books`:
| パラメータ | 型 | デフォルト | 説明 |
|---|---|---|---|
| `status` | string | (全件) | `unread`, `reading`, `read` でフィルター |
| `sort` | string | `created_at` | ソート対象 (`created_at`, `title`, `rating`) |
| `order` | string | `desc` | `asc` or `desc` |
| `per_page` | integer | `20` | 1ページあたりの件数（最大100） |
| `page` | integer | `1` | ページ番号 |

---

## 4-7. 認可 — PictureBookPolicy

### 認可ロジック

Step 3 の FamilyPolicy を再利用し、本棚操作は家族所属チェックで保護:

```php
namespace App\Policies;

class PictureBookPolicy
{
    // 本棚の閲覧・操作: 家族のメンバーか
    public function manage(User $user, PictureBook $pictureBook): bool
    {
        return $user->family_id === $pictureBook->family_id;
    }
}
```

Controller では:
- `index`, `store`: `FamilyPolicy::view` で家族所属チェック
- `show`, `update`, `destroy`: `PictureBookPolicy::manage` で個別チェック

### URL パラメータの整合性チェック

ルートが `/families/{family}/books/{pictureBook}` のため、`pictureBook.family_id === family.id` の検証が必要。方法:

| 方式 | 説明 |
|---|---|
| Route Model Binding のスコープ | `Route::scopeBindings()` で自動チェック |
| Controller 内で手動チェック | 明示的だが冗長 |
| Policy 内でチェック | 認可と同時に検証 |

→ **Route Model Binding のスコープ**を採用。Laravel はネストされたルートモデルバインディングで自動的にスコープを適用できる:

```php
// routes/api.php — scopeBindings を使用
Route::prefix('/families/{family}/books')->scopeBindings()->group(function () {
    // {pictureBook} は自動的に {family} にスコープされる
});
```

ただし `PictureBook` の `family()` リレーションが定義されている必要がある。

---

## 4-8. Feature テスト

### テストファイル

```
backend/tests/Feature/
└── Bookshelf/
    ├── SearchGoogleBooksTest.php
    ├── AddBookTest.php
    ├── ListBooksTest.php
    ├── GetBookTest.php
    ├── UpdateBookTest.php
    └── RemoveBookTest.php
```

### テストケース一覧

**SearchGoogleBooksTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | キーワードで検索 | 200, Google Books の結果が返る |
| 2 | クエリパラメータ `q` が空 | 422 |
| 3 | 未認証でアクセス | 401 |

> Google Books API はテスト時に Http::fake() でモックする

**AddBookTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に絵本を登録 | 201, 登録された絵本情報 |
| 2 | 同じ google_books_id で重複登録 | 409 or 422（既に登録済み） |
| 3 | 必須フィールド欠落 | 422 |
| 4 | 他家族の本棚に登録 | 403 |
| 5 | google_books_id なしで手動登録 | 201 |

**ListBooksTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 本棚一覧を取得 | 200, ページネーション付き |
| 2 | read_status でフィルター | 200, 該当ステータスのみ |
| 3 | 他家族の本棚を取得 | 403 |
| 4 | 本棚が空 | 200, 空配列 |

**GetBookTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 絵本詳細を取得 | 200, 絵本情報 |
| 2 | 他家族の絵本を取得 | 403 |
| 3 | 存在しない ID | 404 |

**UpdateBookTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 評価・ステータス・レビューを更新 | 200 |
| 2 | rating が範囲外（0 or 6） | 422 |
| 3 | 無効な read_status | 422 |
| 4 | 他家族の絵本を更新 | 403 |

**RemoveBookTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に削除 | 200 or 204 |
| 2 | 他家族の絵本を削除 | 403 |

### Google Books API のモック

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'www.googleapis.com/books/v1/volumes*' => Http::response([
        'totalItems' => 1,
        'items' => [
            [
                'id' => 'test_google_id',
                'volumeInfo' => [
                    'title' => 'ぐりとぐら',
                    'authors' => ['中川李枝子'],
                    'industryIdentifiers' => [
                        ['type' => 'ISBN_13', 'identifier' => '9784834000825'],
                    ],
                    'imageLinks' => [
                        'thumbnail' => 'https://example.com/thumb.jpg',
                    ],
                ],
            ],
        ],
    ]),
]);
```

---

## 4-9. フロントエンド — API 関数・型定義

### `src/types/book.ts`

```typescript
export interface GoogleBook {
  google_books_id: string;
  title: string;
  authors: string[];
  isbn: string | null;
  thumbnail_url: string | null;
  published_date: string | null;
  description: string | null;
  page_count: number | null;
}

export interface PictureBook {
  id: number;
  google_books_id: string | null;
  isbn: string | null;
  title: string;
  authors: string[];
  thumbnail_url: string | null;
  rating: number | null;
  read_status: 'unread' | 'reading' | 'read';
  review: string | null;
  registered_by: { id: number; name: string };
  created_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
```

### `src/api/books.ts`

```typescript
import apiClient from './client';

export const searchGoogleBooks = (query: string) =>
  apiClient.get('/books/search', { params: { q: query } });

export const getBooks = (familyId: number, params?: {
  status?: string;
  sort?: string;
  order?: string;
  page?: number;
  per_page?: number;
}) =>
  apiClient.get(`/families/${familyId}/books`, { params });

export const addBook = (familyId: number, data: {
  google_books_id?: string;
  isbn?: string;
  title: string;
  authors: string[];
  thumbnail_url?: string;
}) =>
  apiClient.post(`/families/${familyId}/books`, data);

export const getBook = (familyId: number, bookId: number) =>
  apiClient.get(`/families/${familyId}/books/${bookId}`);

export const updateBook = (familyId: number, bookId: number, data: {
  rating?: number | null;
  read_status: string;
  review?: string | null;
}) =>
  apiClient.put(`/families/${familyId}/books/${bookId}`, data);

export const removeBook = (familyId: number, bookId: number) =>
  apiClient.delete(`/families/${familyId}/books/${bookId}`);
```

---

## 4-10. フロントエンド — カスタムフック

### `src/hooks/useBooks.ts`

```typescript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

export const useSearchGoogleBooks = (query: string) => {
  return useQuery({
    queryKey: ['googleBooks', query],
    queryFn: () => searchGoogleBooks(query),
    enabled: query.length >= 2,  // 2文字以上で検索
  });
};

export const useBooks = (familyId: number, params?: { status?: string; page?: number }) => {
  return useQuery({
    queryKey: ['books', familyId, params],
    queryFn: () => getBooks(familyId, params),
  });
};

export const useBook = (familyId: number, bookId: number) => {
  return useQuery({
    queryKey: ['book', familyId, bookId],
    queryFn: () => getBook(familyId, bookId),
  });
};

export const useAddBook = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data) => addBook(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['books', familyId] });
    },
  });
};

export const useUpdateBook = (familyId: number, bookId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data) => updateBook(familyId, bookId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['book', familyId, bookId] });
      queryClient.invalidateQueries({ queryKey: ['books', familyId] });
    },
  });
};

export const useRemoveBook = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (bookId: number) => removeBook(familyId, bookId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['books', familyId] });
    },
  });
};
```

---

## 4-11. フロントエンド — ページコンポーネント

### ルーティング構成（Step 3 からの追加）

```typescript
<Route element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
  <Route path="/" element={<DashboardPage />} />
  <Route path="/family/create" element={<CreateFamilyPage />} />
  <Route path="/family/settings" element={<FamilySettingsPage />} />

  {/* Step 4 で追加 */}
  <Route path="/books/search" element={<BookSearchPage />} />
  <Route path="/books" element={<BookshelfPage />} />
  <Route path="/books/:bookId" element={<BookDetailPage />} />
</Route>
```

### BookSearchPage

- 検索キーワード入力フォーム（デバウンス 300ms）
- 検索結果を `GoogleBookCard` コンポーネントでリスト表示
- 各カードに「本棚に追加」ボタン
- 追加済みの本は「登録済み」と表示（重複防止の UX）
- 追加成功時にトースト通知
- 「手動で追加」リンク → 手動登録フォーム（Google Books にない絵本用）

### BookManualAddForm（手動登録）

- BookSearchPage 内、または別モーダルとして表示
- フィールド: `title`（必須）, `authors`（必須、カンマ区切りまたは複数入力）, `isbn`（任意）
- Google Books 検索で見つからない絵本を手動で本棚に追加する導線

**デバウンスの実装**:
```typescript
const [query, setQuery] = useState('');
const debouncedQuery = useDebounce(query, 300);
const { data, isLoading } = useSearchGoogleBooks(debouncedQuery);
```

> `useDebounce` はカスタムフックとして実装（useState + useEffect）

### BookshelfPage

- ステータスフィルタータブ: 全て / 未読 / 読書中 / 読了
- 本棚一覧を `BookCard` コンポーネントのグリッド表示
- ページネーション（ページ番号 or 無限スクロール）
- 絵本検索ページへのリンクボタン
- 空状態: 「まだ絵本が登録されていません。検索して追加しましょう」

### BookCard コンポーネント

- サムネイル画像（fallback あり: 画像なし時のプレースホルダー）
- タイトル、著者
- 読書ステータスバッジ
- 評価（星表示）
- クリックで BookDetailPage へ遷移

### BookDetailPage

- 絵本の詳細情報表示
- 評価の設定（星をクリック）
- 読書ステータスの変更（セレクト or ボタン群）
- レビューの編集（テキストエリア）
- 本棚から削除ボタン（確認ダイアログ付き）
- Step 5 で読み聞かせ記録セクションを追加予定

---

## 4-12. 疎通確認チェックリスト

| # | 確認項目 | 方法 | 期待結果 |
|---|---|---|---|
| 1 | マイグレーション | `task migrate` | picture_books テーブル作成 |
| 2 | Google Books 検索 | `curl .../books/search?q=ぐりとぐら` | 200, 検索結果 |
| 3 | 絵本登録 | `curl -X POST .../families/{id}/books` | 201 |
| 4 | 重複登録チェック | 同じ google_books_id で再度 POST | 409 or 422 |
| 5 | 本棚一覧 | `curl .../families/{id}/books` | 200, ページネーション付き |
| 6 | ステータスフィルター | `curl .../families/{id}/books?status=read` | 200, フィルター済み |
| 7 | 絵本詳細 | `curl .../families/{id}/books/{bookId}` | 200 |
| 8 | 絵本更新 | `curl -X PUT .../families/{id}/books/{bookId}` | 200 |
| 9 | 絵本削除 | `curl -X DELETE .../families/{id}/books/{bookId}` | 200 or 204 |
| 10 | 認可: 他家族 | 所属していない family_id でアクセス | 403 |
| 11 | React 検索フロー | BookSearchPage でキーワード入力 → 結果表示 → 「本棚に追加」 | 本棚に反映 |
| 12 | React 本棚一覧 | BookshelfPage でフィルター・ページ遷移 | 正しく表示 |
| 13 | React 絵本詳細 | BookDetailPage で評価・ステータス・レビュー更新 | 保存される |
| 14 | Feature テスト | `task test` | 全テスト通過 |

---

## 作業順序まとめ

```
4-1.  マイグレーション (picture_books)
         ↓
4-2.  Domain 層 (Entity, ValueObject, RepositoryInterface)
         ↓
4-3.  Application 層 (Command/Query Handler)
         ↓
4-4.  Infrastructure 層 (EloquentRepository, GoogleBooksApiClient, ServiceProvider バインド)
         ↓
4-5.  Eloquent Model (PictureBook, Family リレーション追加)
         ↓
4-6.  Interface 層 (Controller, Request, Resource, Routes)
         ↓
4-7.  認可 (PictureBookPolicy, scopeBindings)
         ↓
4-8.  Feature テスト作成・実行
         ↓
4-9.  フロントエンド 型定義 + API 関数
         ↓
4-10. カスタムフック (useBooks, useSearchGoogleBooks)
         ↓
4-11. ページコンポーネント (BookSearch, Bookshelf, BookDetail)
         ↓
4-12. 疎通確認チェックリスト実行
```

---

## 注意事項・判断メモ

- **Value Object の共有**: `FamilyId`, `UserId`, `PictureBookId` は共有カーネル（`packages/Shared/ValueObject/`）から参照。rebuild-plan の「コンテキスト間の Value Object 共有方針」を参照
- **registered_by の外部キー**: `nullOnDelete` を使用。ユーザーが削除されても絵本データは残る（`registered_by` が null になる）
- **Google Books API キー**: Phase 1 では API キーなしで利用。レート制限に引っかかったら追加
- **サムネイル URL**: `http://` → `https://` に書き換えて保存。mixed content 回避
- **authors の JSON 型**: 正規化せず配列をそのまま保存。著者別検索の要件がないため
- **read_status**: DB は string、Domain は PHP Enum で管理
- **重複登録チェック**: `family_id` + `google_books_id` の組み合わせで判定。手動登録（google_books_id なし）は重複チェックなし
- **Route Model Binding スコープ**: `scopeBindings()` で `{pictureBook}` が `{family}` に自動スコープされるため、URL パラメータの整合性を手動チェックする必要がない
- **絵本削除時の影響**: Step 5 で `read_records` テーブルが追加された後は、絵本削除時の関連レコードの扱い（cascade delete or soft delete）を検討する必要がある。Step 4 時点では単純削除で問題ない
- **ページネーション**: Laravel 標準の `paginate()` を使用。フロントエンドではページ番号方式を先に実装し、必要に応じて無限スクロールに変更
