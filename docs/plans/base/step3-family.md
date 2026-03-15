# Step 3: 家族管理 (Family) — 詳細プラン

## ゴール

家族の作成・編集、子どもの登録・編集・削除、家族メンバー一覧の取得ができる状態にする。
Family コンテキストを DDD + Clean Architecture + CQRS の構成で実装し、ログイン後に家族未所属のユーザーには家族作成を促す導線を用意する。

## 完了条件

- [ ] `POST /api/v1/families` で家族を作成でき、作成者が自動的にメンバーになる
- [ ] `GET /api/v1/families/{family}` で家族情報を取得できる
- [ ] `PUT /api/v1/families/{family}` で家族名を編集できる
- [ ] `GET /api/v1/families/{family}/members` で家族メンバー一覧を取得できる
- [ ] `GET /api/v1/families/{family}/children` で子ども一覧を取得できる
- [ ] `POST /api/v1/families/{family}/children` で子どもを登録できる
- [ ] `PUT /api/v1/families/{family}/children/{child}` で子ども情報を編集できる
- [ ] `DELETE /api/v1/families/{family}/children/{child}` で子どもを削除できる
- [ ] 家族に所属していないユーザーが他家族のリソースにアクセスすると 403 が返る
- [ ] React SPA から家族作成・子ども管理の一連のフローが動作する
- [ ] ログイン後に家族未所属のユーザーには家族作成画面が表示される
- [ ] Feature テストが全て通る

---

## 3-1. マイグレーション

### 作成するマイグレーション

#### `create_families_table`

```php
Schema::create('families', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

#### `add_family_id_to_users_table`

```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('family_id')->nullable()->after('id')->constrained()->nullOnDelete();
});
```

#### `create_children_table`

```php
Schema::create('children', function (Blueprint $table) {
    $table->id();
    $table->foreignId('family_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->date('birthday')->nullable();
    $table->timestamps();
});
```

### 設計判断: users.family_id の nullable

- ユーザー登録時点では家族未所属（`family_id = null`）
- 家族作成 or 招待受理で `family_id` が設定される
- `nullOnDelete`: 家族が削除された場合、ユーザーは未所属状態に戻る（ユーザー自体は削除しない）

### 設計判断: ユーザーと家族の関係（1対1 vs 多対多）

| 方式 | 構造 | メリット | デメリット |
|---|---|---|---|
| 1対1（`users.family_id`） | ユーザーは 1 つの家族にのみ所属 | シンプル、クエリが簡単 | 複数家族への所属不可 |
| 多対多（中間テーブル） | `family_user` ピボットテーブル | 柔軟、ロール管理も可能 | 複雑、現時点で不要 |

→ **1対1 を採用**。理由:
- 絵本読み聞かせアプリにおいて、ユーザーが複数家族に所属するユースケースが Phase 1 では想定されない
- rebuild-plan の DB スキーマ設計（`users.family_id`）に準拠
- 必要になった時点で中間テーブルへの移行は可能

### 確認ポイント

- `task migrate` で 3 つのマイグレーションが成功する
- `users` テーブルに `family_id` カラムが追加される

---

## 3-2. Family コンテキスト — Domain 層

### ディレクトリ構成

```
backend/packages/Family/
├── Domain/
│   ├── Entity/
│   │   ├── Family.php
│   │   └── Child.php
│   ├── ValueObject/
│   │   ├── FamilyId.php
│   │   ├── FamilyName.php
│   │   ├── ChildId.php
│   │   ├── ChildName.php
│   │   └── Birthday.php
│   └── Repository/
│       ├── FamilyRepositoryInterface.php
│       └── ChildRepositoryInterface.php
```

> **注記**: 既存スケルトンに `Domain/Service/` と `Infrastructure/Mail/` ディレクトリが存在するが、Step 3 では使用しない。不要であれば作業中に削除する。

### Domain Entity: `Family`

```php
namespace Packages\Family\Domain\Entity;

final class Family
{
    public function __construct(
        private readonly ?FamilyId $id,
        private FamilyName $name,
    ) {}

    public static function create(FamilyName $name): self
    {
        return new self(null, $name);
    }

    public static function reconstruct(FamilyId $id, FamilyName $name): self
    {
        return new self($id, $name);
    }

    public function rename(FamilyName $name): void
    {
        $this->name = $name;
    }

    // Getter メソッド
}
```

### Domain Entity: `Child`

```php
namespace Packages\Family\Domain\Entity;

final class Child
{
    public function __construct(
        private readonly ?ChildId $id,
        private readonly FamilyId $familyId,
        private ChildName $name,
        private ?Birthday $birthday,
    ) {}

    public static function create(
        FamilyId $familyId,
        ChildName $name,
        ?Birthday $birthday,
    ): self {
        return new self(null, $familyId, $name, $birthday);
    }

    public function update(ChildName $name, ?Birthday $birthday): void
    {
        $this->name = $name;
        $this->birthday = $birthday;
    }

    // Getter, reconstruct メソッド
}
```

### Value Objects

| クラス | バリデーション |
|---|---|
| `FamilyId` | 正の整数 |
| `FamilyName` | 1〜255文字 |
| `ChildId` | 正の整数 |
| `ChildName` | 1〜255文字 |
| `Birthday` | 過去の日付であること（未来日を拒否） |

### Repository Interfaces

**`FamilyRepositoryInterface`**:
```php
interface FamilyRepositoryInterface
{
    public function findById(FamilyId $id): ?Family;
    public function save(Family $family): Family;
}
```

**`ChildRepositoryInterface`**:
```php
interface ChildRepositoryInterface
{
    public function findById(ChildId $id): ?Child;
    public function findByFamilyId(FamilyId $familyId): array;
    public function save(Child $child): Child;
    public function delete(ChildId $id): void;
}
```

---

## 3-3. Family コンテキスト — Application 層

### ディレクトリ構成

```
backend/packages/Family/
├── Application/
│   ├── Command/
│   │   ├── CreateFamily/
│   │   │   ├── CreateFamilyCommand.php
│   │   │   └── CreateFamilyHandler.php
│   │   ├── UpdateFamily/
│   │   │   ├── UpdateFamilyCommand.php
│   │   │   └── UpdateFamilyHandler.php
│   │   ├── AddChild/
│   │   │   ├── AddChildCommand.php
│   │   │   └── AddChildHandler.php
│   │   ├── UpdateChild/
│   │   │   ├── UpdateChildCommand.php
│   │   │   └── UpdateChildHandler.php
│   │   └── RemoveChild/
│   │       ├── RemoveChildCommand.php
│   │       └── RemoveChildHandler.php
│   └── Query/
│       ├── GetFamily/
│       │   ├── GetFamilyQuery.php
│       │   └── GetFamilyHandler.php
│       ├── ListMembers/
│       │   ├── ListMembersQuery.php
│       │   └── ListMembersHandler.php
│       └── ListChildren/
│           ├── ListChildrenQuery.php
│           └── ListChildrenHandler.php
```

### CreateFamily

**`CreateFamilyCommand`** (DTO):
```php
final class CreateFamilyCommand
{
    public function __construct(
        public readonly string $name,
        public readonly int $userId,  // 作成者
    ) {}
}
```

**`CreateFamilyHandler`**:
1. `Family::create()` でエンティティ生成
2. `FamilyRepository::save()` で永続化
3. 作成者の `family_id` を更新（User の所属を設定）
4. 作成された Family を返却

### UpdateFamily

**`UpdateFamilyHandler`**:
1. `FamilyRepository::findById()` で取得
2. `Family::rename()` で名前更新
3. `FamilyRepository::save()` で永続化

### AddChild

**`AddChildHandler`**:
1. `Child::create()` でエンティティ生成
2. `ChildRepository::save()` で永続化

### UpdateChild

**`UpdateChildHandler`**:
1. `ChildRepository::findById()` で取得
2. `Child::update()` で更新
3. `ChildRepository::save()` で永続化

### RemoveChild

**`RemoveChildHandler`**:
1. `ChildRepository::delete()` で削除

### Query ハンドラ（CQRS Query 側）

Query ハンドラは Eloquent を直接使い、Domain 層を経由しない（FQCN は `App\Models` 名前空間の Eloquent Model を使用）:

- **GetFamilyHandler**: `\App\Models\Family::find($id)` → Eloquent Model
- **ListMembersHandler**: `\App\Models\User::where('family_id', $id)->get()` → Collection
- **ListChildrenHandler**: `\App\Models\Child::where('family_id', $id)->get()` → Collection

### 設計判断: CreateFamily 時の User.family_id 更新

家族作成時に作成者を自動的にメンバーにする必要がある。これは Family コンテキストから Auth コンテキスト（User）を更新するコンテキスト横断の操作。

| 方式 | 説明 |
|---|---|
| Handler 内で直接 User を更新 | シンプルだが、コンテキスト間の結合が生まれる |
| ドメインイベント発行 | 疎結合だが、Phase 1 では過剰 |

→ **Handler 内で直接更新を採用**。`CreateFamilyHandler` のコンストラクタで `FamilyRepositoryInterface` に加えて `\App\Models\User`（Eloquent Model）を直接利用し、`\App\Models\User::findOrFail($userId)->update(['family_id' => $family->id()->value()])` で更新する。Auth コンテキストの `UserRepositoryInterface` は経由しない（family_id 更新メソッドが存在しないため）。コンテキスト間の依存は意識しつつ、Phase 1 ではシンプルさを優先する。

---

## 3-4. Family コンテキスト — Infrastructure 層

### ディレクトリ構成

```
backend/packages/Family/
└── Infrastructure/
    └── Repository/
        ├── EloquentFamilyRepository.php
        └── EloquentChildRepository.php
```

### EloquentFamilyRepository

```php
namespace Packages\Family\Infrastructure\Repository;

use App\Models\Family as EloquentFamily;
use Packages\Family\Domain\Entity\Family;
use Packages\Family\Domain\Repository\FamilyRepositoryInterface;

final class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function findById(FamilyId $id): ?Family
    {
        $model = EloquentFamily::find($id->value());
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Family $family): Family
    {
        if ($family->id() === null) {
            $model = EloquentFamily::create(['name' => $family->name()->value()]);
        } else {
            $model = EloquentFamily::findOrFail($family->id()->value());
            $model->update(['name' => $family->name()->value()]);
        }
        return $this->toDomainEntity($model);
    }

    private function toDomainEntity(EloquentFamily $model): Family
    {
        return Family::reconstruct(
            new FamilyId($model->id),
            new FamilyName($model->name),
        );
    }
}
```

### EloquentChildRepository

同様のパターンで実装。`save` は ID の有無で create / update を切り替え。

### ServiceProvider でのバインド

```php
// AppServiceProvider または FamilyServiceProvider
$this->app->bind(FamilyRepositoryInterface::class, EloquentFamilyRepository::class);
$this->app->bind(ChildRepositoryInterface::class, EloquentChildRepository::class);
```

---

## 3-5. Eloquent Model

### `app/Models/Family.php`（新規）

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = ['name'];

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }
}
```

### `app/Models/Child.php`（新規）

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Child extends Model
{
    protected $fillable = ['family_id', 'name', 'birthday'];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
```

### `app/Models/User.php`（リレーション追加）

```php
// 既存の User モデルに追加
public function family(): BelongsTo
{
    return $this->belongsTo(Family::class);
}
```

---

## 3-6. Interface 層 — Controller, Request, Resource, Routes, UserResource 拡張

### ディレクトリ構成

```
backend/app/Http/
├── Controllers/Api/
│   ├── FamilyController.php
│   └── ChildController.php
├── Requests/
│   ├── StoreFamilyRequest.php
│   ├── UpdateFamilyRequest.php
│   ├── StoreChildRequest.php
│   └── UpdateChildRequest.php
└── Resources/
    ├── UserResource.php      ← 既存（family_id 追加）
    ├── FamilyResource.php
    ├── MemberResource.php
    └── ChildResource.php
```

### FamilyController

```php
class FamilyController extends Controller
{
    // POST /api/v1/families
    public function store(StoreFamilyRequest $request, CreateFamilyHandler $handler)

    // GET /api/v1/families/{family}
    public function show(Family $family, GetFamilyHandler $handler)

    // PUT /api/v1/families/{family}
    public function update(UpdateFamilyRequest $request, Family $family, UpdateFamilyHandler $handler)

    // GET /api/v1/families/{family}/members
    public function members(Family $family, ListMembersHandler $handler)
}
```

### ChildController

```php
class ChildController extends Controller
{
    // GET /api/v1/families/{family}/children
    public function index(Family $family, ListChildrenHandler $handler)

    // POST /api/v1/families/{family}/children
    public function store(StoreChildRequest $request, Family $family, AddChildHandler $handler)

    // PUT /api/v1/families/{family}/children/{child}
    public function update(UpdateChildRequest $request, Family $family, Child $child, UpdateChildHandler $handler)

    // DELETE /api/v1/families/{family}/children/{child}
    public function destroy(Family $family, Child $child, RemoveChildHandler $handler)
}
```

### FormRequest バリデーション

**`StoreFamilyRequest`**:
| フィールド | ルール |
|---|---|
| `name` | `required`, `string`, `max:255` |

`authorize()` メソッドで `return $this->user()->family_id === null;` を返す。既に家族に所属しているユーザーの場合は 403 を返却する。

**`UpdateFamilyRequest`**:
| フィールド | ルール |
|---|---|
| `name` | `required`, `string`, `max:255` |

**`StoreChildRequest`** / **`UpdateChildRequest`**:
| フィールド | ルール |
|---|---|
| `name` | `required`, `string`, `max:255` |
| `birthday` | `nullable`, `date`, `before_or_equal:today` |

### API Resource

**`FamilyResource`**:
```json
{
  "id": 1,
  "name": "山田家",
  "created_at": "2026-03-08T00:00:00.000000Z"
}
```

**`MemberResource`**:
```json
{
  "id": 1,
  "name": "太郎",
  "email": "taro@example.com"
}
```

**`ChildResource`**:
```json
{
  "id": 1,
  "name": "はなこ",
  "birthday": "2022-05-15",
  "age": 3
}
```

> `age` は `birthday` から算出する Accessor（Eloquent Model 側で計算 or Resource 内で計算）

### ルート定義 (`routes/api.php`)

```php
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Family
    Route::post('/families', [FamilyController::class, 'store']);
    Route::get('/families/{family}', [FamilyController::class, 'show']);
    Route::put('/families/{family}', [FamilyController::class, 'update']);
    Route::get('/families/{family}/members', [FamilyController::class, 'members']);

    // Children
    Route::get('/families/{family}/children', [ChildController::class, 'index']);
    Route::post('/families/{family}/children', [ChildController::class, 'store']);
    Route::put('/families/{family}/children/{child}', [ChildController::class, 'update']);
    Route::delete('/families/{family}/children/{child}', [ChildController::class, 'destroy']);
});
```

全エンドポイントに `auth:sanctum` ミドルウェアを適用。

### UserResource の拡張（Step 2 からの変更）

Step 2 で作成した `UserResource` に `family_id` を追加する。フロントエンドで `user.family_id` の有無により家族未所属判定を行うため、このタイミングで対応する。

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        'family_id' => $this->family_id,
        'created_at' => $this->created_at->toISOString(),
    ];
}
```

---

## 3-7. 認可 — FamilyPolicy

### 認可ロジック

ユーザーが指定された家族に所属しているかを検証する Policy:

```php
namespace App\Policies;

class FamilyPolicy
{
    // 家族に所属しているか
    public function view(User $user, Family $family): bool
    {
        return $user->family_id === $family->id;
    }

    // 家族情報を更新できるか（所属 = 更新可能）
    public function update(User $user, Family $family): bool
    {
        return $user->family_id === $family->id;
    }
}
```

### ChildPolicy

子どもが指定された家族に属しているか + ユーザーがその家族のメンバーか:

```php
class ChildPolicy
{
    public function manage(User $user, Child $child): bool
    {
        return $user->family_id === $child->family_id;
    }
}
```

### Controller での適用

```php
// FamilyController
public function show(Family $family)
{
    $this->authorize('view', $family);
    // ...
}
```

### 設計判断: ロール（role）による権限分離

`users.role`, `users.avatar_path` は Phase 1 のスキーマから除外済み（rebuild-plan 参照）。Phase 1 では:
- ロールによる権限差は設けない（全メンバーが等しく操作可能）
- 将来的に「管理者のみ家族削除可能」等の権限が必要になった時点で `role` カラムを導入

---

## 3-8. Feature テスト

### テストファイル

```
backend/tests/Feature/
└── Family/
    ├── CreateFamilyTest.php
    ├── UpdateFamilyTest.php
    ├── GetFamilyTest.php
    ├── ListMembersTest.php
    ├── AddChildTest.php
    ├── UpdateChildTest.php
    └── RemoveChildTest.php
```

### テストケース一覧

**CreateFamilyTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に家族を作成 | 201, 作成者の family_id が設定される |
| 2 | 未認証でアクセス | 401 |
| 3 | name が空 | 422 |
| 4 | すでに家族に所属しているユーザーが作成 | 403（StoreFamilyRequest の authorize で拒否） |

**UpdateFamilyTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に家族名を更新 | 200 |
| 2 | 所属していない家族を更新 | 403 |
| 3 | name が空 | 422 |

**GetFamilyTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 所属する家族の情報取得 | 200, 家族情報が返る |
| 2 | 所属していない家族の情報取得 | 403 |

**ListMembersTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 家族メンバー一覧取得 | 200, メンバーリスト |
| 2 | 所属していない家族のメンバー取得 | 403 |

**AddChildTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に子どもを登録 | 201 |
| 2 | birthday が未来日 | 422 |
| 3 | 所属していない家族に追加 | 403 |

**UpdateChildTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に子ども情報を更新 | 200 |
| 2 | 他家族の子どもを更新 | 403 |

**RemoveChildTest**:
| # | テストケース | 期待結果 |
|---|---|---|
| 1 | 正常に子どもを削除 | 200 or 204 |
| 2 | 他家族の子どもを削除 | 403 |

---

## 3-9. フロントエンド — 追加パッケージ

### インストール

```bash
docker compose exec frontend npm install @tanstack/react-query
```

| パッケージ | 用途 |
|---|---|
| `@tanstack/react-query` | サーバー状態管理、キャッシュ、自動再取得 |

### TanStack Query 初期セットアップ

`src/main.tsx` に `QueryClientProvider` を追加:

```typescript
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      staleTime: 5 * 60 * 1000,  // 5分
    },
  },
});

// <QueryClientProvider client={queryClient}> で App をラップ
```

---

## 3-10. フロントエンド — API 関数

### `src/api/family.ts`

```typescript
import apiClient from './client';

export const createFamily = (data: { name: string }) =>
  apiClient.post('/families', data);

export const getFamily = (familyId: number) =>
  apiClient.get(`/families/${familyId}`);

export const updateFamily = (familyId: number, data: { name: string }) =>
  apiClient.put(`/families/${familyId}`, data);

export const getMembers = (familyId: number) =>
  apiClient.get(`/families/${familyId}/members`);

export const getChildren = (familyId: number) =>
  apiClient.get(`/families/${familyId}/children`);

export const addChild = (familyId: number, data: { name: string; birthday?: string }) =>
  apiClient.post(`/families/${familyId}/children`, data);

export const updateChild = (familyId: number, childId: number, data: { name: string; birthday?: string }) =>
  apiClient.put(`/families/${familyId}/children/${childId}`, data);

export const removeChild = (familyId: number, childId: number) =>
  apiClient.delete(`/families/${familyId}/children/${childId}`);
```

---

## 3-11. フロントエンド — カスタムフック

### `src/hooks/useFamily.ts`

```typescript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

export const useFamily = (familyId: number) => {
  return useQuery({
    queryKey: ['family', familyId],
    queryFn: () => getFamily(familyId),
  });
};

export const useCreateFamily = () => {
  const queryClient = useQueryClient();
  const { refreshUser } = useAuth();  // AuthContext から refreshUser を取得
  return useMutation({
    mutationFn: createFamily,
    onSuccess: async () => {
      await refreshUser();  // family_id が更新されるため user 情報を再取得
    },
  });
};

export const useUpdateFamily = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: { name: string }) => updateFamily(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['family', familyId] });
    },
  });
};
```

### `src/hooks/useChildren.ts`

```typescript
export const useChildren = (familyId: number) => {
  return useQuery({
    queryKey: ['children', familyId],
    queryFn: () => getChildren(familyId),
  });
};

export const useAddChild = (familyId: number) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: { name: string; birthday?: string }) => addChild(familyId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['children', familyId] });
    },
  });
};

// useUpdateChild, useRemoveChild も同様のパターン
```

---

## 3-12. フロントエンド — ページコンポーネント

### 前提: useAuth への refreshUser 追加

Step 2 の `useAuth` フックには user 情報の再取得手段がない。家族作成後に `family_id` を反映するため、`refreshUser` メソッドを追加する:

```typescript
// src/hooks/useAuth.tsx に追加
const refreshUser = useCallback(async () => {
  const res = await authApi.getUser();
  setUser(res.data.user);
}, []);

// Provider の value に refreshUser を追加
<AuthContext.Provider value={{ user, isLoading, login, register, logout, refreshUser }}>
```

### 前提: フロントエンド User 型に family_id を追加

`src/api/auth.ts` の `User` 型に `family_id` フィールドを追加する:

```typescript
export interface User {
  id: number;
  name: string;
  email: string;
  family_id: number | null;  // 追加
  created_at: string;
}
```

### ルーティング構成（Step 2 からの追加）

`ProtectedRoute`（認証チェック）と `RequireFamilyRoute`（家族所属チェック）を分離する:

```
src/components/
├── ProtectedRoute.tsx        ← 既存（認証のみ、変更なし）
└── RequireFamilyRoute.tsx    ← 新規（家族所属チェック）
```

**`RequireFamilyRoute`**:
```typescript
export default function RequireFamilyRoute({ children }: { children: ReactNode }) {
  const { user } = useAuth();

  if (!user?.family_id) {
    return <Navigate to="/family/create" replace />;
  }

  return <>{children}</>;
}
```

**ルーティング定義（`App.tsx`）**:
```typescript
<Routes>
  {/* 公開ルート */}
  <Route path="/login" element={<LoginPage />} />
  <Route path="/register" element={<RegisterPage />} />

  {/* 認証必須・家族未所属でもアクセス可 */}
  <Route element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
    <Route path="/family/create" element={<CreateFamilyPage />} />

    {/* 認証必須 + 家族所属必須 */}
    <Route element={<RequireFamilyRoute><Outlet /></RequireFamilyRoute>}>
      <Route path="/" element={<DashboardPage />} />
      <Route path="/family/settings" element={<FamilySettingsPage />} />
    </Route>
  </Route>
</Routes>
```

### 家族未所属時の導線

ログイン後のフロー:

```
ログイン → ProtectedRoute（認証チェック）
  └─ RequireFamilyRoute（家族所属チェック）
       ├─ family_id あり → DashboardPage（通常表示）
       └─ family_id なし → /family/create にリダイレクト
```

`/family/create` は `ProtectedRoute` の内側・`RequireFamilyRoute` の外側に配置するため、無限ループは発生しない。

### CreateFamilyPage

- 家族名入力フォーム（React Hook Form）
- 作成成功後にダッシュボードへリダイレクト
- AuthContext の user 情報を再取得（family_id を反映）

### FamilySettingsPage

- 家族名の編集フォーム
- メンバー一覧表示
- 子ども管理セクション:
  - 子どもリスト（`ChildCard` コンポーネント）
  - 子ども追加フォーム（`ChildForm` コンポーネント）
  - 各子どもの編集・削除

### ChildCard コンポーネント

- 子どもの名前、年齢（birthday から算出）を表示
- 編集ボタン → インラインフォームまたはモーダル
- 削除ボタン → 確認ダイアログ後に削除

### ChildForm コンポーネント

- フィールド: `name`（必須）, `birthday`（任意）
- React Hook Form でバリデーション
- 追加 / 編集 の両方で再利用

### DashboardPage（更新）

Step 2 のプレースホルダーから拡張:
- 家族名を表示
- 子ども一覧を簡易表示
- 家族設定ページへのリンク
- Step 4 以降で本棚・記録の情報を追加予定

---

## 3-13. 疎通確認チェックリスト

| # | 確認項目 | 方法 | 期待結果 |
|---|---|---|---|
| 1 | マイグレーション | `task migrate` | families, children テーブル作成 + users.family_id 追加 |
| 2 | 家族作成 API | `curl -X POST .../families` | 201, 作成者の family_id が設定 |
| 3 | 家族情報取得 | `curl .../families/{id}` | 200, 家族情報 |
| 4 | 家族名更新 | `curl -X PUT .../families/{id}` | 200 |
| 5 | メンバー一覧 | `curl .../families/{id}/members` | 200, メンバーリスト |
| 6 | 子ども一覧 | `curl .../families/{id}/children` | 200, 子どもリスト |
| 7 | 子ども登録 | `curl -X POST .../families/{id}/children` | 201 |
| 8 | 子ども更新 | `curl -X PUT .../families/{id}/children/{childId}` | 200 |
| 9 | 子ども削除 | `curl -X DELETE .../families/{id}/children/{childId}` | 200 or 204 |
| 10 | 認可: 他家族へのアクセス | 所属していない family_id でアクセス | 403 |
| 11 | 既所属ユーザーの家族作成 | 既に家族に所属しているユーザーで POST /families | 403（StoreFamilyRequest の authorize で拒否） |
| 12 | React 家族作成フロー | 未所属ユーザーでログイン → 家族作成ページ表示 → 作成 | ダッシュボードに遷移 |
| 13 | React 子ども管理 | 家族設定ページで追加・編集・削除 | 各操作が反映される |
| 14 | Feature テスト | `task test` | 全テスト通過 |

---

## 作業順序まとめ

```
3-1.  マイグレーション (families, users.family_id, children)
         ↓
3-2.  Domain 層 (Entity, ValueObject, RepositoryInterface)
         ↓
3-3.  Application 層 (Command/Query Handler)
         ↓
3-4.  Infrastructure 層 (EloquentRepository, ServiceProvider バインド)
         ↓
3-5.  Eloquent Model (Family, Child, User リレーション追加)
         ↓
3-6.  Interface 層 (Controller, Request, Resource, Routes, UserResource 拡張)
         ↓
3-7.  認可 (FamilyPolicy, ChildPolicy)
         ↓
3-8.  Feature テスト作成・実行
         ↓
3-9.  フロントエンド TanStack Query セットアップ
         ↓
3-10. API 関数 (family.ts)
         ↓
3-11. カスタムフック (useFamily, useChildren)
         ↓
3-12. ページコンポーネント (useAuth 拡張, User 型更新, RequireFamilyRoute,
      CreateFamily, FamilySettings, Dashboard更新)
         ↓
3-13. 疎通確認チェックリスト実行
```

---

## 注意事項・判断メモ

- **ユーザーと家族の関係**: 1対1（`users.family_id`）を採用。Phase 1 では複数家族所属のユースケースがない
- **role カラム**: Step 3 では追加しない。認可は「家族に所属しているか」のみで判定
- **すでに家族に所属しているユーザーの家族作成**: `StoreFamilyRequest` の `authorize()` で `$this->user()->family_id === null` をチェックし、所属済みなら 403 を返す。Phase 1 では家族脱退機能は提供しない
- **CreateFamily 時の User 更新**: Handler 内で `\App\Models\User` Eloquent Model を直接利用して更新。Auth コンテキストの `UserRepositoryInterface` は経由しない。ドメインイベントは Phase 1 では採用しない
- **useAuth の拡張**: `refreshUser` メソッドを追加し、家族作成後の `family_id` 反映に使用する
- **ProtectedRoute と RequireFamilyRoute の分離**: 認証チェック（ProtectedRoute）と家族所属チェック（RequireFamilyRoute）を別コンポーネントとし、`/family/create` は認証のみ必須・家族未所属でもアクセス可能にする
- **フロントエンド User 型**: `api/auth.ts` の `User` インターフェースに `family_id: number | null` を追加する
- **TanStack Query の導入**: Step 3 から導入。Step 2 の認証系フック（useAuth）は既存の useEffect ベースを維持し、Step 3 以降の CRUD 操作で TanStack Query を活用
- **子ども削除時の影響**: Step 5 で `child_read_record` テーブルが追加された後は、子ども削除時に関連する読み聞かせ記録への影響を考慮する必要がある。Step 3 時点では単純削除で問題ない
