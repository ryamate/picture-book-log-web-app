# Step 2: 認証 (Authentication) — 詳細プラン

## ゴール

Laravel Sanctum によるトークンベース認証を実装し、React SPA からユーザー登録・ログイン・ログアウト・ユーザー情報取得ができる状態にする。
Auth コンテキストを DDD + Clean Architecture + CQRS の構成で実装する最初のステップ。

## 完了条件

- [ ] `POST /api/v1/auth/register` でユーザー登録でき、トークンが返る
- [ ] `POST /api/v1/auth/login` でログインでき、トークンが返る
- [ ] `POST /api/v1/auth/logout` でトークンが無効化される
- [ ] `GET /api/v1/auth/user` で認証済みユーザー情報が取得できる
- [ ] 未認証で保護エンドポイントにアクセスすると 401 が返る
- [ ] React SPA から登録・ログイン・ログアウトの一連のフローが動作する
- [ ] ProtectedRoute により未認証ユーザーがログインページにリダイレクトされる
- [ ] Feature テストが全て通る

---

## 2-1. Sanctum セットアップ

### 作業内容

```bash
docker compose exec app composer require laravel/sanctum
docker compose exec app php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
docker compose exec app php artisan migrate
```

### 設定ファイル

**`config/sanctum.php`**:
- `stateful` ドメインの設定は不要（SPA Cookie 認証ではなくトークンベースを使用）
- トークンの有効期限: `'expiration' => null`（無期限、ログアウトで明示的に削除）

**`config/cors.php`**:
```php
return [
    'paths' => ['api/*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

**`backend/.env` に追加**:
```env
FRONTEND_URL=http://localhost:5173
```

### 設計判断: SPA Cookie 認証 vs トークンベース

Sanctum には 2 つの認証方式がある:

| 方式 | 仕組み | 適するケース |
|---|---|---|
| SPA Cookie 認証 | CSRF トークン + セッション Cookie | 同一ドメイン / サブドメインの SPA |
| API トークン認証 | `Authorization: Bearer {token}` ヘッダー | 完全分離の SPA、モバイルアプリ |

**トークンベースを選択する理由**:
- フロントエンド (Cloudflare Pages) とバックエンド (ConoHa VPS) が異なるドメインになる本番構成
- CSRF トークン取得の `/sanctum/csrf-cookie` エンドポイントが不要でシンプル
- 将来的にモバイルアプリ対応も可能

### 確認ポイント

- `personal_access_tokens` マイグレーションが実行される
- `config/sanctum.php` が存在する

---

## 2-2. Auth コンテキスト — Domain 層

### ディレクトリ構成

```
backend/packages/Auth/
├── Domain/
│   ├── Entity/
│   │   └── User.php              # ドメインエンティティ
│   ├── ValueObject/
│   │   ├── UserId.php
│   │   ├── Email.php
│   │   ├── UserName.php
│   │   └── HashedPassword.php
│   └── Repository/
│       └── UserRepositoryInterface.php
```

### Domain Entity: `User`

```php
namespace Packages\Auth\Domain\Entity;

final class User
{
    public function __construct(
        private readonly UserId $id,
        private readonly UserName $name,
        private readonly Email $email,
        private readonly HashedPassword $password,
    ) {}

    // Getter メソッド
    // ファクトリメソッド: createNew (登録時), reconstruct (DB復元時)
}
```

### Value Objects

| クラス | バリデーション |
|---|---|
| `UserId` | 正の整数 |
| `Email` | メール形式（`filter_var`） |
| `UserName` | 1〜255文字 |
| `HashedPassword` | bcrypt ハッシュ済み文字列をラップ |

### Repository Interface

```php
namespace Packages\Auth\Domain\Repository;

interface UserRepositoryInterface
{
    public function findByEmail(Email $email): ?User;
    public function save(User $user): User;
}
```

### 設計判断メモ

- **Value Object のバリデーション**: コンストラクタで不変条件を検証し、不正な値は `InvalidArgumentException` をスロー。Laravel の FormRequest バリデーションとは別に、ドメイン層でも最低限の検証を行う
- **HashedPassword**: 平文パスワードは Domain 層に持ち込まない。ハッシュ化は Application 層（Command Handler）で行い、Domain には HashedPassword として渡す
- **User Entity の ID**: 登録時は DB 採番前なので `UserId` は nullable にするか、`createNew` では ID なしで生成し `save` 後に ID 付きで返す設計にする

---

## 2-3. Auth コンテキスト — Application 層

### ディレクトリ構成

```
backend/packages/Auth/
├── Application/
│   ├── Command/
│   │   ├── RegisterUser/
│   │   │   ├── RegisterUserCommand.php    # 入力DTO
│   │   │   └── RegisterUserHandler.php    # ユースケース
│   │   ├── Login/
│   │   │   ├── LoginCommand.php
│   │   │   └── LoginHandler.php
│   │   └── Logout/
│   │       ├── LogoutCommand.php
│   │       └── LogoutHandler.php
│   └── Query/
│       └── GetCurrentUser/
│           ├── GetCurrentUserQuery.php
│           └── GetCurrentUserHandler.php
```

### RegisterUser

**`RegisterUserCommand`** (DTO):
```php
final class RegisterUserCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
```

**`RegisterUserHandler`**:
1. Email の重複チェック（Repository 経由）
2. パスワードをハッシュ化
3. Domain Entity `User::createNew()` 生成
4. Repository で永続化
5. トークン生成（Sanctum）して返却

### Login

**`LoginHandler`**:
1. Email でユーザー検索
2. パスワード照合（`Hash::check`）
3. 失敗時は例外スロー
4. 成功時にトークン生成して返却

### Logout

**`LogoutHandler`**:
1. 現在のアクセストークンを削除（`$user->currentAccessToken()->delete()`）

### GetCurrentUser (Query)

**`GetCurrentUserHandler`**:
- CQRS の Query 側。Eloquent を直接使って現在のユーザー情報を取得
- Domain Entity を経由せず、DTO またはそのまま Eloquent Model を返す

### 設計判断: トークン生成の責務

トークン生成（`createToken`）は Sanctum（Infrastructure 層）に依存する。選択肢:

1. **Handler 内で Eloquent Model 経由で直接呼ぶ** — シンプルだが Application 層が Infrastructure に依存
2. **TokenServiceInterface を Domain/Application に定義し、Infrastructure で実装** — 純粋だが過剰

→ **選択肢 1 を採用**。認証トークン生成は Auth コンテキスト固有の関心事であり、他コンテキストに波及しない。過度な抽象化を避け、Handler 内で Eloquent User Model の `createToken` を呼ぶ。

---

## 2-4. Auth コンテキスト — Infrastructure 層

### ディレクトリ構成

```
backend/packages/Auth/
└── Infrastructure/
    └── Repository/
        └── EloquentUserRepository.php
```

### EloquentUserRepository

```php
namespace Packages\Auth\Infrastructure\Repository;

use App\Models\User as EloquentUser;
use Packages\Auth\Domain\Entity\User;
use Packages\Auth\Domain\Repository\UserRepositoryInterface;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findByEmail(Email $email): ?User
    {
        $eloquentUser = EloquentUser::where('email', $email->value())->first();
        if ($eloquentUser === null) {
            return null;
        }
        return $this->toDomainEntity($eloquentUser);
    }

    public function save(User $user): User
    {
        $eloquentUser = EloquentUser::create([
            'name' => $user->name()->value(),
            'email' => $user->email()->value(),
            'password' => $user->password()->value(),
        ]);
        return $this->toDomainEntity($eloquentUser);
    }

    private function toDomainEntity(EloquentUser $model): User
    {
        return User::reconstruct(
            new UserId($model->id),
            new UserName($model->name),
            new Email($model->email),
            new HashedPassword($model->password),
        );
    }
}
```

### ServiceProvider でのバインド

`App\Providers\AppServiceProvider` または専用の `AuthServiceProvider`:

```php
$this->app->bind(
    UserRepositoryInterface::class,
    EloquentUserRepository::class,
);
```

---

## 2-5. Interface 層 — Controller, Request, Resource, Routes

### ディレクトリ構成

```
backend/app/Http/
├── Controllers/Api/
│   └── AuthController.php
├── Requests/
│   ├── RegisterRequest.php
│   └── LoginRequest.php
└── Resources/
    └── UserResource.php
```

### AuthController

```php
namespace App\Http\Controllers\Api;

class AuthController extends Controller
{
    // POST /api/v1/auth/register
    public function register(RegisterRequest $request, RegisterUserHandler $handler)

    // POST /api/v1/auth/login
    public function login(LoginRequest $request, LoginHandler $handler)

    // POST /api/v1/auth/logout (auth:sanctum)
    public function logout(Request $request, LogoutHandler $handler)

    // GET /api/v1/auth/user (auth:sanctum)
    public function user(Request $request, GetCurrentUserHandler $handler)
}
```

### FormRequest バリデーション

**`RegisterRequest`**:
| フィールド | ルール |
|---|---|
| `name` | `required`, `string`, `max:255` |
| `email` | `required`, `string`, `email`, `max:255`, `unique:users` |
| `password` | `required`, `string`, `min:8`, `confirmed` |

**`LoginRequest`**:
| フィールド | ルール |
|---|---|
| `email` | `required`, `string`, `email` |
| `password` | `required`, `string` |

### UserResource

```php
namespace App\Http\Resources;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### API レスポンス形式

**登録・ログイン成功**:
```json
{
  "user": {
    "id": 1,
    "name": "John",
    "email": "john@example.com",
    "created_at": "2026-03-07T00:00:00.000000Z"
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**ログアウト成功**:
```json
{
  "message": "Logged out successfully"
}
```

**バリデーションエラー (422)**:
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

**認証エラー (401)**:
```json
{
  "message": "Unauthenticated."
}
```

### ルート定義 (`routes/api.php`)

```php
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});
```

---

## 2-6. Eloquent Model の調整

### `app/Models/User.php`

Laravel デフォルトの User モデルに Sanctum の `HasApiTokens` トレイトを追加:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
```

### 設計判断: Eloquent Model の配置

rebuild-plan では `database/Eloquent/Models/` への移動を検討していたが、Step 2 では Laravel デフォルトの `app/Models/` をそのまま使う。理由:
- Sanctum の `HasApiTokens` など Laravel 標準機能との統合が容易
- `auth.php` の `providers.users.model` 設定変更が不要
- 移動のメリットがまだ明確でないため、必要性が出てから判断する

---

## 2-7. Feature テスト

### テストファイル

```
backend/tests/Feature/
└── Auth/
    ├── RegisterTest.php
    ├── LoginTest.php
    ├── LogoutTest.php
    └── GetCurrentUserTest.php
```

### テストケース一覧

**RegisterTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常な入力で登録 | 201, user + token 返却 |
| 2 | email 重複 | 422, バリデーションエラー |
| 3 | password が 8 文字未満 | 422 |
| 4 | password_confirmation 不一致 | 422 |
| 5 | 必須フィールド欠落 | 422 |

**LoginTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正しい認証情報でログイン | 200, user + token 返却 |
| 2 | メールアドレスが存在しない | 401 or 422 |
| 3 | パスワード不一致 | 401 or 422 |

**LogoutTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 認証済みユーザーがログアウト | 200, トークン無効化 |
| 2 | 未認証でログアウト試行 | 401 |

**GetCurrentUserTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 認証済みでユーザー情報取得 | 200, user 情報返却 |
| 2 | 未認証でアクセス | 401 |

### テスト環境

- `phpunit.xml` で SQLite in-memory または MySQL テスト用 DB を使用
- `RefreshDatabase` トレイトでテスト毎に DB リセット

---

## 2-8. フロントエンド — 追加パッケージ

### インストール

```bash
docker compose exec frontend npm install axios react-router-dom react-hook-form
docker compose exec frontend npm install -D @types/react-router-dom
```

| パッケージ | 用途 |
|---|---|
| `axios` | API クライアント |
| `react-router-dom` | SPA ルーティング |
| `react-hook-form` | フォーム管理 |

> TanStack Query は Step 3 以降で導入。Step 2 では Axios + useEffect でシンプルに実装する。

---

## 2-9. フロントエンド — Axios クライアント

### `src/api/client.ts`

```typescript
import axios from 'axios';

const apiClient = axios.create({
  baseURL: '/api/v1',  // Vite proxy 経由
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// リクエストインターセプター: トークン付与
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// レスポンスインターセプター: 401 でトークン削除 + リダイレクト
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  },
);

export default apiClient;
```

### `src/api/auth.ts`

```typescript
import apiClient from './client';

export const register = (data: RegisterData) =>
  apiClient.post('/auth/register', data);

export const login = (data: LoginData) =>
  apiClient.post('/auth/login', data);

export const logout = () =>
  apiClient.post('/auth/logout');

export const getUser = () =>
  apiClient.get('/auth/user');
```

### 設計判断: トークン保存場所

| 方式 | メリット | デメリット |
|---|---|---|
| `localStorage` | 実装がシンプル、リロードで消えない | XSS 脆弱性でトークン漏洩のリスク |
| `httpOnly Cookie` | XSS でアクセス不可 | CSRF 対策が必要、Sanctum SPA 認証が前提 |
| メモリ (React state) | XSS でもアクセス困難 | リロードで消える |

→ **`localStorage` を採用**。理由:
- トークンベース認証（2-1 で決定済み）との整合性
- 個人開発アプリでユーザー入力由来の HTML レンダリングが少なく XSS リスクが低い
- リロード時の UX を優先

---

## 2-10. フロントエンド — AuthContext + useAuth

### `src/hooks/useAuth.tsx`

```typescript
interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  login: (data: LoginData) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
}
```

**処理フロー**:
1. アプリ起動時に `localStorage` にトークンがあれば `getUser()` を呼んでユーザー情報を復元
2. `login` / `register` 成功時にトークンを `localStorage` に保存し、`user` state を更新
3. `logout` 成功時にトークンを削除し、`user` state を `null` に

### `src/components/ProtectedRoute.tsx`

```typescript
const ProtectedRoute = ({ children }: { children: ReactNode }) => {
  const { user, isLoading } = useAuth();

  if (isLoading) return <LoadingSpinner />;
  if (!user) return <Navigate to="/login" replace />;

  return <>{children}</>;
};
```

---

## 2-11. フロントエンド — ページコンポーネント

### ルーティング構成

```typescript
<Routes>
  {/* 公開ルート */}
  <Route path="/login" element={<LoginPage />} />
  <Route path="/register" element={<RegisterPage />} />

  {/* 保護ルート */}
  <Route element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
    <Route path="/" element={<DashboardPage />} />
    {/* Step 3 以降で追加 */}
  </Route>
</Routes>
```

### LoginPage

- React Hook Form でフォーム管理
- フィールド: `email`, `password`
- エラー表示: バリデーションエラー、認証エラー
- 登録ページへのリンク
- ログイン成功時に `/` へリダイレクト

### RegisterPage

- フィールド: `name`, `email`, `password`, `password_confirmation`
- エラー表示: バリデーションエラー（email 重複含む）
- ログインページへのリンク
- 登録成功時に `/` へリダイレクト

### AppLayout + Header

- `Header`: ユーザー名表示、ログアウトボタン
- `Outlet` で子ルートを描画
- ログアウト時に `/login` へリダイレクト

### DashboardPage（仮）

- Step 2 時点では「ログイン済みです」程度のプレースホルダー
- Step 3 以降で家族・本棚情報を表示

---

## 2-12. 疎通確認チェックリスト

| # | 確認項目 | 方法 | 期待結果 |
|---|---|---|---|
| 1 | ユーザー登録 API | `curl -X POST .../auth/register` | 201 + token |
| 2 | ログイン API | `curl -X POST .../auth/login` | 200 + token |
| 3 | ユーザー情報取得 | `curl -H "Authorization: Bearer {token}" .../auth/user` | 200 + user |
| 4 | 未認証アクセス | `curl .../auth/user` (トークンなし) | 401 |
| 5 | ログアウト API | `curl -X POST -H "Authorization: Bearer {token}" .../auth/logout` | 200 |
| 6 | ログアウト後アクセス | 同じトークンで `/auth/user` | 401 |
| 7 | React 登録フロー | ブラウザで `/register` → フォーム入力 → 送信 | ダッシュボードに遷移 |
| 8 | React ログインフロー | ブラウザで `/login` → フォーム入力 → 送信 | ダッシュボードに遷移 |
| 9 | React ログアウト | Header のログアウトボタン | ログインページに遷移 |
| 10 | 保護ルート | 未ログインで `/` にアクセス | `/login` にリダイレクト |
| 11 | リロード後のセッション維持 | ログイン後にブラウザリロード | ユーザー情報が復元され、ログイン状態が維持される |
| 12 | Feature テスト | `task test` | 全テスト通過 |

---

## 作業順序まとめ

```
2-1.  Sanctum セットアップ (composer, config, migrate)
         ↓
2-2.  Domain 層 (Entity, ValueObject, RepositoryInterface)
         ↓
2-3.  Application 層 (Command/Query Handler)
         ↓
2-4.  Infrastructure 層 (EloquentUserRepository, ServiceProvider バインド)
         ↓
2-5.  Interface 層 (Controller, Request, Resource, Routes)
         ↓
2-6.  Eloquent Model 調整 (HasApiTokens)
         ↓
2-7.  Feature テスト作成・実行
         ↓
2-8.  フロントエンド パッケージ追加
         ↓
2-9.  Axios クライアント (client.ts, auth.ts)
         ↓
2-10. AuthContext + useAuth + ProtectedRoute
         ↓
2-11. ページコンポーネント (Login, Register, AppLayout, Dashboard)
         ↓
2-12. 疎通確認チェックリスト実行
```

---

## 注意事項・判断メモ

- **Sanctum の認証方式**: トークンベース（Bearer トークン）を採用。本番で FE/BE が異なるドメインになるため
- **Domain 層の純粋性 vs 実用性**: トークン生成は Sanctum 依存のため Handler 内で Eloquent Model 経由で呼ぶ。過度な抽象化は避ける
- **Eloquent Model の配置**: Step 2 では `app/Models/` のまま。移動は必要性が出てから判断
- **TanStack Query**: Step 2 では未導入。Axios + useEffect でシンプルに実装し、Step 3 以降で導入
- **パスワードリセット**: Phase 1 のスコープ外。必要になった時点で追加
- **メール認証**: Phase 1 のスコープ外。Mailpit は Step 6（招待機能）で活用
- **エラーハンドリング**: Laravel デフォルトの例外ハンドリングをベースに、API 用の JSON レスポンスが返ることを確認。カスタム例外は必要に応じて追加
