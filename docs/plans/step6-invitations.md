# Step 6: 家族招待 (Invitations) — 詳細プラン

## ゴール

家族メンバーが他のユーザーをメールアドレスで招待し、招待メール内のリンクから受理することで家族に参加できる状態にする。
招待機能は Family コンテキスト内に実装し、InvitationDomainService でドメインロジックを管理する。Mailpit で開発中のメール送受信を確認する。

## 完了条件

- [ ] `POST /api/v1/families/{family}/invitations` で招待メールが送信される
- [ ] `GET /api/v1/families/{family}/invitations` で招待一覧（ステータス付き）が取得できる
- [ ] `POST /api/v1/invitations/{token}/accept` で招待を受理し、家族に参加できる
- [ ] `DELETE /api/v1/families/{family}/invitations/{invitation}` で招待をキャンセルできる
- [ ] 招待トークンに有効期限があり、期限切れの招待は受理できない
- [ ] すでに家族に所属しているユーザーを招待するとエラーが返る
- [ ] 招待メールが Mailpit で確認できる
- [ ] React SPA から招待送信・招待受理の一連のフローが動作する
- [ ] Feature テストが全て通る

---

## 6-1. マイグレーション

### `create_family_invitations_table`

```php
Schema::create('family_invitations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('family_id')->constrained()->cascadeOnDelete();
    $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
    $table->string('email');
    $table->string('token', 64)->unique();
    $table->timestamp('accepted_at')->nullable();
    $table->timestamp('expires_at');
    $table->timestamps();

    $table->index(['family_id', 'email']);
    $table->index('token');
});
```

### 設計判断: トークンの生成方法と長さ

| 方式 | 説明 |
|---|---|
| UUID v4 | 36文字（ハイフン含む）。衝突リスクが極めて低い |
| `Str::random(64)` | 64文字のランダム英数字。URL に使いやすい |
| 署名付き URL（Laravel Signed URL） | Laravel 組み込み機能。改ざん防止付き |

→ **`Str::random(64)` を採用**。理由:
- 招待トークンは一時的なもので、署名付き URL ほどの厳密さは不要
- DB に保存して照合するシンプルなフロー
- URL に含めやすい文字列（英数字のみ）

### 設計判断: 有効期限のデフォルト値

→ **7日間**。短すぎるとメール確認が間に合わない場合があり、長すぎるとセキュリティリスク。個人・家族向けアプリとして妥当な期間。

### 確認ポイント

- `task migrate` で `family_invitations` テーブルが作成される

---

## 6-2. Family コンテキスト — Domain 層（招待関連の追加）

### ディレクトリ構成（Step 3 からの追加分）

```
backend/packages/Family/
├── Domain/
│   ├── Entity/
│   │   ├── Family.php          # 既存
│   │   ├── Child.php           # 既存
│   │   └── Invitation.php      # 新規
│   ├── ValueObject/
│   │   ├── ...                 # 既存
│   │   ├── InvitationId.php    # 新規
│   │   └── InvitationToken.php # 新規
│   ├── Repository/
│   │   ├── ...                 # 既存
│   │   └── InvitationRepositoryInterface.php  # 新規
│   └── Service/
│       └── InvitationDomainService.php        # 新規
```

> `FamilyId`, `UserId`, `Email` は共有カーネル（`packages/Shared/ValueObject/`）から参照。詳細は rebuild-plan の「コンテキスト間の Value Object 共有方針」を参照。

### Domain Entity: `Invitation`

```php
namespace Packages\Family\Domain\Entity;

final class Invitation
{
    public function __construct(
        private readonly ?InvitationId $id,
        private readonly FamilyId $familyId,
        private readonly UserId $invitedBy,
        private readonly Email $email,
        private readonly InvitationToken $token,
        private ?DateTimeImmutable $acceptedAt,
        private readonly DateTimeImmutable $expiresAt,
    ) {}

    public static function create(
        FamilyId $familyId,
        UserId $invitedBy,
        Email $email,
        InvitationToken $token,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            null, $familyId, $invitedBy, $email, $token, null, $expiresAt,
        );
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable() > $this->expiresAt;
    }

    public function isAccepted(): bool
    {
        return $this->acceptedAt !== null;
    }

    public function isPending(): bool
    {
        return !$this->isAccepted() && !$this->isExpired();
    }

    public function accept(): void
    {
        if ($this->isExpired()) {
            throw new InvitationExpiredException();
        }
        if ($this->isAccepted()) {
            throw new InvitationAlreadyAcceptedException();
        }
        $this->acceptedAt = new DateTimeImmutable();
    }

    // Getter, reconstruct メソッド
}
```

### Value Objects

| クラス | 内容 |
|---|---|
| `InvitationId` | 正の整数 |
| `InvitationToken` | 64文字の英数字文字列。生成用ファクトリメソッド付き |

### InvitationToken

```php
namespace Packages\Family\Domain\ValueObject;

use Illuminate\Support\Str;

final class InvitationToken
{
    private function __construct(
        private readonly string $value,
    ) {
        if (strlen($value) !== 64) {
            throw new InvalidArgumentException('Token must be 64 characters.');
        }
    }

    public static function generate(): self
    {
        return new self(Str::random(64));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
```

### Domain Exceptions

```
backend/packages/Family/
└── Domain/
    └── Exception/
        ├── InvitationExpiredException.php
        ├── InvitationAlreadyAcceptedException.php
        └── UserAlreadyInFamilyException.php
```

### Repository Interface

```php
namespace Packages\Family\Domain\Repository;

interface InvitationRepositoryInterface
{
    public function findById(InvitationId $id): ?Invitation;
    public function findByToken(InvitationToken $token): ?Invitation;
    public function findPendingByFamilyIdAndEmail(FamilyId $familyId, Email $email): ?Invitation;
    public function findByFamilyId(FamilyId $familyId): array;
    public function save(Invitation $invitation): Invitation;
    public function delete(InvitationId $id): void;
}
```

### InvitationDomainService

招待に関するドメインロジックを集約:

```php
namespace Packages\Family\Domain\Service;

final class InvitationDomainService
{
    public function __construct(
        private readonly InvitationRepositoryInterface $invitationRepository,
    ) {}

    /**
     * 招待を作成できるか検証し、Invitation エンティティを生成する
     */
    public function createInvitation(
        FamilyId $familyId,
        UserId $invitedBy,
        Email $email,
    ): Invitation {
        // 同じ家族・同じメールアドレスで未処理の招待が既にあるか
        $existing = $this->invitationRepository->findPendingByFamilyIdAndEmail($familyId, $email);
        if ($existing !== null) {
            throw new DuplicateInvitationException();
        }

        $token = InvitationToken::generate();
        $expiresAt = new DateTimeImmutable('+7 days');

        return Invitation::create($familyId, $invitedBy, $email, $token, $expiresAt);
    }
}
```

### 設計判断: ドメインサービスを使う理由

招待作成は以下の複合的な検証が必要:
- 同じメールへの重複招待チェック（Repository 参照）
- トークン生成
- 有効期限の決定

これらは Invitation Entity 単体では完結しないため、DomainService に切り出す。

---

## 6-3. Family コンテキスト — Application 層（招待関連の追加）

### ディレクトリ構成

```
backend/packages/Family/
├── Application/
│   ├── Command/
│   │   ├── ...                        # 既存 (CreateFamily, AddChild 等)
│   │   ├── SendInvitation/
│   │   │   ├── SendInvitationCommand.php
│   │   │   └── SendInvitationHandler.php
│   │   ├── AcceptInvitation/
│   │   │   ├── AcceptInvitationCommand.php
│   │   │   └── AcceptInvitationHandler.php
│   │   └── CancelInvitation/
│   │       ├── CancelInvitationCommand.php
│   │       └── CancelInvitationHandler.php
│   └── Query/
│       ├── ...                        # 既存
│       └── ListInvitations/
│           ├── ListInvitationsQuery.php
│           └── ListInvitationsHandler.php
```

### SendInvitation

**`SendInvitationCommand`** (DTO):
```php
final class SendInvitationCommand
{
    public function __construct(
        public readonly int $familyId,
        public readonly int $invitedByUserId,
        public readonly string $email,
    ) {}
}
```

**`SendInvitationHandler`**:
1. 招待先メールアドレスのユーザーが既に同家族に所属していないか確認
2. `InvitationDomainService::createInvitation()` で招待エンティティ生成（重複チェック含む）
3. `InvitationRepository::save()` で永続化
4. `InvitationMail` を送信
5. 作成された Invitation を返却

### AcceptInvitation

**`AcceptInvitationCommand`**:
```php
final class AcceptInvitationCommand
{
    public function __construct(
        public readonly string $token,
        public readonly int $userId,
    ) {}
}
```

**`AcceptInvitationHandler`**:
1. `InvitationRepository::findByToken()` でトークンから招待を取得
2. 招待が存在しない → 404 相当の例外
3. `Invitation::accept()` を呼ぶ（期限切れ・受理済みチェックは Entity 内）
4. 受理ユーザーが既に別の家族に所属していないか確認
5. ユーザーの `family_id` を招待された家族に更新
6. `InvitationRepository::save()` で `accepted_at` を永続化
7. 受理結果を返却

### CancelInvitation

**`CancelInvitationHandler`**:
1. `InvitationRepository::findById()` で取得
2. 受理済みでないことを確認
3. `InvitationRepository::delete()` で削除

### ListInvitations（Query）

**`ListInvitationsHandler`**:
- Eloquent を直接使って家族の招待一覧を取得
- ステータス情報（pending / accepted / expired）を付与

### 設計判断: AcceptInvitation 時の User.family_id 更新

Step 3 の CreateFamily と同様、コンテキスト横断の操作。Handler 内で直接 Eloquent User Model 経由で更新する。

### 設計判断: 招待受理にログインは必要か

| 方式 | 説明 |
|---|---|
| ログイン必須 | 受理時に認証済みユーザーの family_id を更新 |
| ログイン不要 + 新規登録導線 | トークンだけで受理。未登録ユーザーには登録画面を表示 |

→ **ログイン必須を採用**。理由:
- 認証済みユーザーと招待を紐付ける必要がある（誰が受理したか明確）
- 未登録ユーザーには「先にアカウント登録してからこのリンクを開いてください」と案内
- Phase 1 ではシンプルなフローを優先

**フロー**:
```
メール内リンク → /invitations/{token}/accept
  ├─ ログイン済み → 受理 API 呼び出し → ダッシュボードへ
  └─ 未ログイン → ログインページへリダイレクト（リターン URL 付き）
```

---

## 6-4. Family コンテキスト — Infrastructure 層（招待関連の追加）

### ディレクトリ構成

```
backend/packages/Family/
└── Infrastructure/
    ├── Repository/
    │   ├── ...                            # 既存
    │   └── EloquentInvitationRepository.php  # 新規
    └── Mail/
        └── InvitationMail.php             # 新規
```

### EloquentInvitationRepository

```php
namespace Packages\Family\Infrastructure\Repository;

final class EloquentInvitationRepository implements InvitationRepositoryInterface
{
    public function findByToken(InvitationToken $token): ?Invitation
    {
        $model = EloquentFamilyInvitation::where('token', $token->value())->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findPendingByFamilyIdAndEmail(FamilyId $familyId, Email $email): ?Invitation
    {
        $model = EloquentFamilyInvitation::where('family_id', $familyId->value())
            ->where('email', $email->value())
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByFamilyId(FamilyId $familyId): array
    {
        return EloquentFamilyInvitation::where('family_id', $familyId->value())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($model) => $this->toDomainEntity($model))
            ->toArray();
    }

    public function save(Invitation $invitation): Invitation
    {
        if ($invitation->id() === null) {
            $model = EloquentFamilyInvitation::create([
                'family_id' => $invitation->familyId()->value(),
                'invited_by' => $invitation->invitedBy()->value(),
                'email' => $invitation->email()->value(),
                'token' => $invitation->token()->value(),
                'accepted_at' => $invitation->acceptedAt(),
                'expires_at' => $invitation->expiresAt(),
            ]);
        } else {
            $model = EloquentFamilyInvitation::findOrFail($invitation->id()->value());
            $model->update([
                'accepted_at' => $invitation->acceptedAt(),
            ]);
        }
        return $this->toDomainEntity($model);
    }

    public function delete(InvitationId $id): void
    {
        EloquentFamilyInvitation::destroy($id->value());
    }

    private function toDomainEntity(EloquentFamilyInvitation $model): Invitation { /* ... */ }
}
```

### InvitationMail

```php
namespace Packages\Family\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $familyName,
        private readonly string $inviterName,
        private readonly string $acceptUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->familyName} への招待",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invitation',
            with: [
                'familyName' => $this->familyName,
                'inviterName' => $this->inviterName,
                'acceptUrl' => $this->acceptUrl,
            ],
        );
    }
}
```

### メールテンプレート

`backend/resources/views/mail/invitation.blade.php`:

```blade
<x-mail::message>
# {{ $familyName }} に招待されました

{{ $inviterName }} さんから「{{ $familyName }}」への招待が届きました。

以下のボタンをクリックして招待を受け入れてください。

<x-mail::button :url="$acceptUrl">
招待を受け入れる
</x-mail::button>

このリンクは7日間有効です。

心当たりがない場合は、このメールを無視してください。

{{ config('app.name') }}
</x-mail::message>
```

### 招待受理 URL の構成

```
{FRONTEND_URL}/invitations/{token}/accept
```

- フロントエンドの URL。メールリンクは React SPA のルートを指す
- React 側でトークンを取得し、API に受理リクエストを送る

### ServiceProvider でのバインド

```php
$this->app->bind(InvitationRepositoryInterface::class, EloquentInvitationRepository::class);
```

---

## 6-5. Eloquent Model

### `app/Models/FamilyInvitation.php`（新規）

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyInvitation extends Model
{
    protected $fillable = [
        'family_id',
        'invited_by',
        'email',
        'token',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
```

### `app/Models/Family.php`（リレーション追加）

```php
public function invitations(): HasMany
{
    return $this->hasMany(FamilyInvitation::class);
}
```

---

## 6-6. Interface 層 — Controller, Request, Resource, Routes

### ディレクトリ構成

```
backend/app/Http/
├── Controllers/Api/
│   └── InvitationController.php
├── Requests/
│   └── SendInvitationRequest.php
└── Resources/
    └── InvitationResource.php
```

### InvitationController

```php
class InvitationController extends Controller
{
    // POST /api/v1/families/{family}/invitations
    public function store(SendInvitationRequest $request, Family $family, SendInvitationHandler $handler)

    // GET /api/v1/families/{family}/invitations
    public function index(Family $family, ListInvitationsHandler $handler)

    // DELETE /api/v1/families/{family}/invitations/{invitation}
    public function destroy(Family $family, FamilyInvitation $invitation, CancelInvitationHandler $handler)

    // POST /api/v1/invitations/{token}/accept
    public function accept(string $token, AcceptInvitationHandler $handler)
}
```

### FormRequest バリデーション

**`SendInvitationRequest`**:
| フィールド | ルール |
|---|---|
| `email` | `required`, `string`, `email`, `max:255` |

**追加バリデーション**:
- 招待先メールが自分自身ではないこと
- 招待先ユーザーが既に同じ家族に所属していないこと（ユーザーが存在する場合）

```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // 自分自身への招待を防止
        if ($this->email === $this->user()->email) {
            $validator->errors()->add('email', '自分自身を招待することはできません。');
        }

        // 既に同家族のメンバーか確認
        $family = $this->route('family');
        $existingUser = User::where('email', $this->email)->first();
        if ($existingUser && $existingUser->family_id === $family->id) {
            $validator->errors()->add('email', 'このユーザーは既にこの家族のメンバーです。');
        }
    });
}
```

### API Resource

**`InvitationResource`**:
```json
{
  "id": 1,
  "email": "hanako@example.com",
  "status": "pending",
  "invited_by": {
    "id": 1,
    "name": "太郎"
  },
  "expires_at": "2026-03-15T00:00:00.000000Z",
  "accepted_at": null,
  "created_at": "2026-03-08T00:00:00.000000Z"
}
```

`status` フィールドは Resource 内で算出:
```php
public function toArray($request): array
{
    return [
        // ...
        'status' => $this->getStatus(),
    ];
}

private function getStatus(): string
{
    if ($this->accepted_at !== null) {
        return 'accepted';
    }
    if ($this->expires_at->isPast()) {
        return 'expired';
    }
    return 'pending';
}
```

### API レスポンス形式

**招待送信成功 (201)**:
```json
{
  "message": "招待メールを送信しました。",
  "invitation": { /* InvitationResource */ }
}
```

**招待受理成功 (200)**:
```json
{
  "message": "家族に参加しました。",
  "family": { /* FamilyResource */ }
}
```

**招待期限切れ (410 Gone)**:
```json
{
  "message": "この招待は期限切れです。"
}
```

**招待受理済み (409 Conflict)**:
```json
{
  "message": "この招待は既に受理されています。"
}
```

### ルート定義 (`routes/api.php`)

```php
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // ... 既存ルート ...

    // 招待（家族に紐付く操作）
    Route::prefix('/families/{family}/invitations')->group(function () {
        Route::post('/', [InvitationController::class, 'store']);
        Route::get('/', [InvitationController::class, 'index']);
        Route::delete('/{invitation}', [InvitationController::class, 'destroy']);
    });

    // 招待受理（家族に紐付かない。トークンで特定）
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);
});
```

---

## 6-7. 認可

招待関連の認可は既存の `FamilyPolicy` を再利用:

- `store`, `index`, `destroy`: `FamilyPolicy::view` で家族所属チェック
- `accept`: 認証済みであれば誰でも可（トークンが正しければ受理可能）

`destroy` の追加チェック:
- 招待が指定された家族に属しているか（`scopeBindings` or 手動チェック）
- 受理済みの招待は削除不可

---

## 6-8. 例外ハンドリング

Domain 例外を HTTP レスポンスにマッピング:

```php
// app/Exceptions/Handler.php or bootstrap/app.php (Laravel 11)
use Packages\Family\Domain\Exception\InvitationExpiredException;
use Packages\Family\Domain\Exception\InvitationAlreadyAcceptedException;
use Packages\Family\Domain\Exception\UserAlreadyInFamilyException;
use Packages\Family\Domain\Exception\DuplicateInvitationException;

$exceptions->render(function (InvitationExpiredException $e) {
    return response()->json(['message' => 'この招待は期限切れです。'], 410);
});

$exceptions->render(function (InvitationAlreadyAcceptedException $e) {
    return response()->json(['message' => 'この招待は既に受理されています。'], 409);
});

$exceptions->render(function (UserAlreadyInFamilyException $e) {
    return response()->json(['message' => 'このユーザーは既に家族に所属しています。'], 409);
});

$exceptions->render(function (DuplicateInvitationException $e) {
    return response()->json(['message' => 'このメールアドレスへの招待は既に送信されています。'], 409);
});
```

### 設計判断: 例外マッピングの場所

| 方式 | 説明 |
|---|---|
| Handler / bootstrap | 全例外を一箇所で管理 |
| Controller 内で try-catch | 各 Controller で個別にハンドリング |

→ **Handler / bootstrap で一括マッピング**。Domain 例外は複数の Controller から投げられる可能性があり、一箇所で管理した方が一貫性がある。

---

## 6-9. Feature テスト

### テストファイル

```
backend/tests/Feature/
└── Family/
    ├── ...                         # 既存 (Step 3)
    ├── SendInvitationTest.php      # 新規
    ├── ListInvitationsTest.php     # 新規
    ├── AcceptInvitationTest.php    # 新規
    └── CancelInvitationTest.php   # 新規
```

### テストケース一覧

**SendInvitationTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に招待を送信 | 201, メール送信、DB に招待レコード |
| 2 | 自分自身のメールアドレスを指定 | 422 |
| 3 | 既にメンバーのユーザーを招待 | 422 |
| 4 | 同じメールに重複招待 | 409 |
| 5 | 所属していない家族から招待 | 403 |
| 6 | 未登録メールアドレスへの招待 | 201（メールは送信される。受理時に登録が必要） |

> メール送信のアサーション: `Mail::fake()` + `Mail::assertSent(InvitationMail::class)`

**ListInvitationsTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 招待一覧を取得 | 200, status 付き |
| 2 | pending / accepted / expired が正しく返る | ステータス算出の検証 |
| 3 | 所属していない家族の招待一覧 | 403 |

**AcceptInvitationTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に招待を受理 | 200, ユーザーの family_id が更新 |
| 2 | 期限切れトークン | 410 |
| 3 | 既に受理済みのトークン | 409 |
| 4 | 存在しないトークン | 404 |
| 5 | 既に別の家族に所属しているユーザーが受理 | 409 |
| 6 | 未認証でアクセス | 401 |

**CancelInvitationTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に招待をキャンセル | 200 or 204 |
| 2 | 受理済みの招待をキャンセル | 409 or 422 |
| 3 | 所属していない家族の招待をキャンセル | 403 |

### メール送信のテスト

```php
use Illuminate\Support\Facades\Mail;

Mail::fake();

// 招待 API を呼び出し
$response = $this->postJson("/api/v1/families/{$family->id}/invitations", [
    'email' => 'hanako@example.com',
]);

$response->assertCreated();

Mail::assertSent(InvitationMail::class, function ($mail) {
    return $mail->hasTo('hanako@example.com');
});
```

---

## 6-10. フロントエンド — API 関数・型定義

### `src/types/invitation.ts`

```typescript
export interface Invitation {
  id: number;
  email: string;
  status: 'pending' | 'accepted' | 'expired';
  invited_by: { id: number; name: string };
  expires_at: string;
  accepted_at: string | null;
  created_at: string;
}
```

### `src/api/invitations.ts`

```typescript
import apiClient from './client';

export const sendInvitation = (familyId: number, data: { email: string }) =>
  apiClient.post(`/families/${familyId}/invitations`, data);

export const getInvitations = (familyId: number) =>
  apiClient.get(`/families/${familyId}/invitations`);

export const cancelInvitation = (familyId: number, invitationId: number) =>
  apiClient.delete(`/families/${familyId}/invitations/${invitationId}`);

export const acceptInvitation = (token: string) =>
  apiClient.post(`/invitations/${token}/accept`);
```

---

## 6-11. フロントエンド — カスタムフック

### `src/hooks/useInvitations.ts`

```typescript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

export const useInvitations = (familyId: number) => {
  return useQuery({
    queryKey: ['invitations', familyId],
    queryFn: () => getInvitations(familyId),
  });
};

export const useSendInvitation = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: { email: string }) => sendInvitation(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invitations', familyId] });
    },
  });
};

export const useCancelInvitation = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (invitationId: number) => cancelInvitation(familyId, invitationId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invitations', familyId] });
    },
  });
};

export const useAcceptInvitation = () => {
  return useMutation({
    mutationFn: (token: string) => acceptInvitation(token),
    // onSuccess で AuthContext の user を再取得（family_id が変わるため）
  });
};
```

---

## 6-12. フロントエンド — ページコンポーネント

### ルーティング構成（Step 5 からの追加）

```typescript
<Routes>
  {/* 公開ルート */}
  <Route path="/login" element={<LoginPage />} />
  <Route path="/register" element={<RegisterPage />} />

  {/* 保護ルート */}
  <Route element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
    <Route path="/" element={<DashboardPage />} />
    {/* ... 既存ルート ... */}

    {/* Step 6 で追加 */}
    <Route path="/invitations/:token/accept" element={<AcceptInvitationPage />} />
  </Route>
</Routes>
```

### FamilySettingsPage への統合（InviteMemberForm + InvitationList）

Step 3 で作成した FamilySettingsPage に招待セクションを追加:

```
FamilySettingsPage
├── 家族名編集フォーム        (既存)
├── メンバー一覧              (既存)
├── 子ども管理セクション       (既存)
└── 招待セクション             (Step 6 追加)
    ├── InviteMemberForm      メールアドレス入力 + 送信ボタン
    └── InvitationList        招待一覧（ステータスバッジ + キャンセルボタン）
```

### InviteMemberForm

- メールアドレス入力フィールド（React Hook Form）
- 送信ボタン
- 送信成功時にトースト通知「招待メールを送信しました」
- エラー表示: 重複招待、既にメンバー、自分自身等

### InvitationList

- 招待一覧を表示
- 各招待のステータスバッジ:
  - `pending`: 黄色「招待中」
  - `accepted`: 緑「受理済み」
  - `expired`: グレー「期限切れ」
- `pending` の招待にキャンセルボタン（確認ダイアログ付き）
- 招待先メールアドレス、招待日、有効期限を表示

### AcceptInvitationPage

メールリンクからアクセスされるページ:

```typescript
const AcceptInvitationPage = () => {
  const { token } = useParams<{ token: string }>();
  const { mutate: accept, isPending, isSuccess, isError, error } = useAcceptInvitation();
  const { refreshUser } = useAuth();

  useEffect(() => {
    if (token) {
      accept(token, {
        onSuccess: async (data) => {
          await refreshUser();  // family_id を反映
          // 成功メッセージ表示後、ダッシュボードへ遷移
        },
      });
    }
  }, [token]);

  if (isPending) return <p>招待を受理しています...</p>;
  if (isSuccess) return <p>家族に参加しました！リダイレクトしています...</p>;
  if (isError) return <ErrorDisplay error={error} />;

  return null;
};
```

**エラー表示**:
| ステータスコード | 表示メッセージ |
|---|---|
| 404 | 「この招待は見つかりませんでした。」 |
| 410 | 「この招待は期限切れです。家族のメンバーに再度招待を依頼してください。」 |
| 409 (already accepted) | 「この招待は既に受理されています。」 |
| 409 (already in family) | 「あなたは既に家族に所属しています。」 |

### 設計判断: 招待受理ページのアクセスフロー

```
メールリンク: {FRONTEND_URL}/invitations/{token}/accept
  │
  ├─ ログイン済み
  │   └─ AcceptInvitationPage が表示 → 自動的に受理 API 呼び出し
  │
  └─ 未ログイン
      └─ ProtectedRoute により /login にリダイレクト
         └─ ログイン後、元の URL に戻る（リターン URL）
```

**リターン URL の実装**:
ProtectedRoute でリダイレクト時に現在の URL を state に含める:

```typescript
// ProtectedRoute
if (!user) {
  return <Navigate to="/login" state={{ from: location }} replace />;
}

// LoginPage - ログイン成功後
const from = location.state?.from?.pathname || '/';
navigate(from, { replace: true });
```

---

## 6-13. Mailpit での確認

### 確認手順

1. `task up` でコンテナ起動
2. API で招待を送信
3. ブラウザで `http://localhost:8025` を開く
4. 受信メール一覧に招待メールが表示される
5. メール本文の「招待を受け入れる」リンクを確認
6. リンク URL が `{FRONTEND_URL}/invitations/{token}/accept` 形式であること

### Laravel メール設定の確認

`backend/.env` が Mailpit を指していること（Step 1 で設定済み）:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="noreply@picturebooklog.local"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 6-14. 疎通確認チェックリスト

| # | 確認項目 | 方法 | 期待結果 |
|---|---|---|---|
| 1 | マイグレーション | `task migrate` | family_invitations テーブル作成 |
| 2 | 招待送信 API | `curl -X POST .../families/{id}/invitations` | 201, DB にレコード |
| 3 | メール送信 | Mailpit UI (localhost:8025) | 招待メールが受信されている |
| 4 | メール内リンク | メール本文を確認 | 正しい URL 形式 |
| 5 | 招待一覧 | `curl .../families/{id}/invitations` | 200, ステータス付き |
| 6 | 重複招待チェック | 同じメールで再度 POST | 409 |
| 7 | 自分自身への招待 | 自分のメールで POST | 422 |
| 8 | 既にメンバーへの招待 | 同家族ユーザーのメールで POST | 422 |
| 9 | 招待受理 | `curl -X POST .../invitations/{token}/accept` | 200, family_id 更新 |
| 10 | 期限切れ受理 | 期限切れトークンで受理 | 410 |
| 11 | 二重受理 | 受理済みトークンで再度受理 | 409 |
| 12 | 別家族所属ユーザーの受理 | 既に別の家族に所属しているユーザーで受理 | 409 |
| 13 | 招待キャンセル | `curl -X DELETE .../families/{id}/invitations/{invId}` | 200 or 204 |
| 13 | 認可: 他家族 | 所属していない family_id でアクセス | 403 |
| 14 | React 招待送信 | FamilySettingsPage で招待送信 | 招待一覧に反映 + メール送信 |
| 15 | React 招待受理 | メールリンクから AcceptInvitationPage | 家族に参加、ダッシュボードへ |
| 16 | React 未ログイン受理 | 未ログインでリンクアクセス | ログイン後にリダイレクト |
| 17 | Feature テスト | `task test` | 全テスト通過 |

---

## 作業順序まとめ

```
6-1.  マイグレーション (family_invitations)
         ↓
6-2.  Domain 層 (Entity, ValueObject, RepositoryInterface, DomainService, Exceptions)
         ↓
6-3.  Application 層 (Command/Query Handler)
         ↓
6-4.  Infrastructure 層 (EloquentRepository, InvitationMail, Blade テンプレート)
         ↓
6-5.  Eloquent Model (FamilyInvitation, Family リレーション追加)
         ↓
6-6.  Interface 層 (Controller, Request, Resource, Routes)
         ↓
6-7.  認可 (FamilyPolicy 再利用)
         ↓
6-8.  例外ハンドリング (Domain 例外 → HTTP レスポンスマッピング)
         ↓
6-9.  Feature テスト作成・実行 (Mail::fake 含む)
         ↓
6-10. フロントエンド 型定義 + API 関数
         ↓
6-11. カスタムフック (useInvitations, useAcceptInvitation)
         ↓
6-12. ページコンポーネント (FamilySettingsPage 統合, AcceptInvitationPage)
         ↓
6-13. Mailpit での E2E 確認
         ↓
6-14. 疎通確認チェックリスト実行
```

---

## 注意事項・判断メモ

- **Value Object の共有**: `FamilyId`, `UserId`, `Email` は共有カーネル（`packages/Shared/ValueObject/`）から参照。rebuild-plan の「コンテキスト間の Value Object 共有方針」を参照
- **トークン生成**: `Str::random(64)` でシンプルに。署名付き URL は不採用
- **有効期限**: 7日間。`Invitation::isExpired()` で Entity 内でチェック
- **招待受理にはログインが必須**: 未登録ユーザーは先にアカウント登録が必要。メール内に案内を記載
- **リターン URL**: ProtectedRoute の `state.from` でログイン後に招待受理ページへ戻す
- **既に家族に所属しているユーザーの受理**: 409 を返す。Phase 1 では家族脱退機能がないため、別家族への移動は不可
- **DomainService の導入**: 招待作成は重複チェック + トークン生成 + 有効期限設定の複合ロジックのため、Entity 単体ではなく DomainService で管理
- **例外マッピング**: Domain 例外を Handler/bootstrap で HTTP ステータスに一括変換。Controller の try-catch ではなく宣言的に管理
- **メールの非同期送信**: Phase 1 では `QUEUE_CONNECTION=sync` のため同期送信。将来的にキュー化する場合は `ShouldQueue` インターフェースを追加するだけで対応可能
- **期限切れ招待の削除**: 自動削除（スケジュールタスク）は Phase 1 では実装しない。一覧で `expired` ステータスとして表示するのみ
