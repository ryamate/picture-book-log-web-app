# 1-1. Laravel プロジェクト作成 — 詳細プラン

## ゴール

Laravel API バックエンドのプロジェクトを作成し、Docker 環境向けの初期設定と DDD 用ディレクトリ構造の準備を完了する。

## 完了条件

- [ ] `backend/` に Laravel プロジェクトが作成されている
- [ ] 不要なフロントエンド関連ファイルが削除されている
- [ ] `/api/health` エンドポイントが定義されている
- [ ] `.env` が Docker 環境用に設定されている
- [ ] DDD 用 `packages/` ディレクトリ構造が作成されている
- [ ] `composer dump-autoload` が成功する

---

## 作業手順

### 1. Laravel プロジェクト作成

ホストに composer がないため、Docker 経由で作成する。

```bash
docker run --rm -v $(pwd):/app -w /app composer:latest create-project laravel/laravel backend
```

### 2. 不要なフロントエンド関連ファイルの削除

フロントエンドは別コンテナ (React) で管理するため、以下を削除する:

- `backend/vite.config.js`
- `backend/package.json`
- `backend/resources/js/` (ディレクトリごと)
- `backend/resources/css/` (ディレクトリごと)

### 3. API ルーティング設定

Laravel 12 ではデフォルトで `routes/api.php` が存在しないため、新規作成し `bootstrap/app.php` に登録する。

- `backend/routes/api.php` を作成し、ヘルスチェックエンドポイントを追加:
  ```php
  Route::get('/health', fn () => response()->json(['status' => 'ok']));
  ```
- `backend/bootstrap/app.php` の `withRouting()` に `api:` パラメータを追加:
  ```php
  ->withRouting(
      web: __DIR__.'/../routes/web.php',
      api: __DIR__.'/../routes/api.php',
      commands: __DIR__.'/../routes/console.php',
      health: '/up',
  )
  ```

### 4. .env の Docker 用調整

`backend/.env` を以下の通り変更する:

| 項目 | デフォルト値 | 変更後 |
|---|---|---|
| APP_NAME | Laravel | "Picture Book Log" |
| APP_URL | http://localhost | http://localhost:8080 |
| DB_CONNECTION | sqlite | mysql |
| DB_HOST | (コメントアウト) | db |
| DB_DATABASE | (コメントアウト) | picture_book_log |
| DB_USERNAME | (コメントアウト) | app_user |
| DB_PASSWORD | (コメントアウト) | secret |
| SESSION_DRIVER | database | cookie |
| QUEUE_CONNECTION | database | sync |
| CACHE_STORE | database | file |
| MAIL_MAILER | log | smtp |
| MAIL_HOST | 127.0.0.1 | mailpit |
| MAIL_PORT | 2525 | 1025 |
| MAIL_FROM_ADDRESS | hello@example.com | noreply@picturebooklog.local |

不要な設定 (AWS, Redis, Memcached, VITE_APP_NAME) は削除する。

初期作成時に自動生成される `database/database.sqlite` も削除する (Docker MySQL を使用するため)。

### 5. DDD packages ディレクトリの作成

`backend/packages/` 配下に4ドメインのディレクトリ構造を作成する。空ディレクトリには `.gitkeep` を配置する。

```
packages/
├── Auth/
│   ├── Application/{Command,Query}
│   ├── Domain/{Entity,ValueObject,Repository}
│   └── Infrastructure/Repository
├── Family/
│   ├── Application/{Command,Query}
│   ├── Domain/{Entity,ValueObject,Repository,Service}
│   └── Infrastructure/{Repository,Mail}
├── Bookshelf/
│   ├── Application/{Command,Query}
│   ├── Domain/{Entity,ValueObject,Repository}
│   └── Infrastructure/{Repository,External}
└── ReadLog/
    ├── Application/{Command,Query}
    ├── Domain/{Entity,ValueObject,Repository}
    └── Infrastructure/Repository
```

### 6. composer.json の autoload 設定

PSR-4 autoload に packages の4ドメインを追加する:

```json
"Packages\\Auth\\": "packages/Auth/",
"Packages\\Family\\": "packages/Family/",
"Packages\\Bookshelf\\": "packages/Bookshelf/",
"Packages\\ReadLog\\": "packages/ReadLog/"
```

---

## 確認ポイント

| # | 確認項目 | コマンド | 期待結果 |
|---|---|---|---|
| 1 | Laravel バージョン | `php artisan --version` | Laravel 12+ |
| 2 | autoload 生成 | `composer dump-autoload` | 成功 |
| 3 | .gitignore に /vendor | `grep vendor backend/.gitignore` | `/vendor` が含まれている |

---

## 注意事項

- Step 1 では `packages/` は空ディレクトリ + `.gitkeep` のみ。実際のコードは Step 2 以降で追加
- Eloquent Models は Laravel デフォルトの `app/Models/User.php` をそのまま残す。Step 2 で認証実装時に配置を検討
