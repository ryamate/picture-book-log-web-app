# 絵本読み聞かせ記録アプリ（Picture Book Log）

家族で絵本の読み聞かせを記録・共有する Web アプリケーション。

## 技術スタック

| レイヤー | 技術 |
|---|---|
| フロントエンド | React 19 + TypeScript + Vite |
| UI | Tailwind CSS v4 + shadcn/ui |
| データ取得 | TanStack React Query v5 |
| ルーティング | React Router v7 |
| バックエンド | Laravel 12 (PHP 8.4) |
| 認証 | Laravel Sanctum (トークン認証) |
| データベース | MySQL 8 |
| メール | Mailpit (開発環境) |
| コンテナ | Docker Compose |

## アーキテクチャ

バックエンドは DDD + Clean Architecture + CQRS パターンを採用。

```
backend/packages/
├── Auth/         # 認証ドメイン
├── Family/       # 家族・子ども・招待ドメイン
├── Bookshelf/    # 絵本ドメイン
├── ReadLog/      # 読み聞かせ記録ドメイン
└── Shared/       # 共有カーネル
```

詳細は `docs/plans/rebuild-plan.md` を参照。

## 環境構築

### 前提条件

- Docker / Docker Compose
- [Task](https://taskfile.dev/)（go-task）

### セットアップ手順

```bash
# 1. リポジトリクローン
git clone <repository-url>
cd picture-book-log-web-app

# 2. 環境変数設定
cp backend/.env.example backend/.env

# 3. コンテナ起動
task up

# 4. 依存パッケージインストール
task composer install

# 5. アプリケーションキー生成
task artisan key:generate

# 6. DB セットアップ（マイグレーション + デモデータ投入）
task fresh

# 7. アクセス確認
# フロントエンド: http://localhost:5173
# API: http://localhost:8080/api/health
```

### アクセス先

| サービス | URL |
|---|---|
| フロントエンド | http://localhost:5173 |
| API | http://localhost:8080/api/v1 |
| Mailpit | http://localhost:8025 |

### デモアカウント

| ユーザー | メール | パスワード | 備考 |
|---|---|---|---|
| 山田太郎 | taro@example.com | password | 山田家（パパ） |
| 山田花子 | hanako@example.com | password | 山田家（ママ） |
| 佐藤一郎 | ichiro@example.com | password | 佐藤家（認可テスト用） |
| 鈴木次郎 | jiro@example.com | password | 未所属（招待テスト用） |

## 開発コマンド

| コマンド | 説明 |
|---|---|
| `task up` | 全コンテナ起動 |
| `task down` | 全コンテナ停止 |
| `task fresh` | DB リセット + マイグレーション + シーダー |
| `task test` | PHPUnit テスト実行 |
| `task lint` | 全静的解析（ESLint / PHPStan / Pint） |
| `task lint:fix` | Pint 自動修正 |
| `task artisan <command>` | php artisan コマンド実行 |
| `task npm <command>` | npm コマンド実行 |
| `task shell` | app コンテナに入る |

## API エンドポイント一覧

### 認証

| メソッド | エンドポイント | 説明 |
|---|---|---|
| POST | `/api/v1/auth/register` | ユーザー登録 |
| POST | `/api/v1/auth/login` | ログイン |
| POST | `/api/v1/auth/logout` | ログアウト |
| GET | `/api/v1/auth/user` | 認証ユーザー情報取得 |

### 家族

| メソッド | エンドポイント | 説明 |
|---|---|---|
| POST | `/api/v1/families` | 家族作成 |
| GET | `/api/v1/families/{id}` | 家族情報取得 |
| PUT | `/api/v1/families/{id}` | 家族名更新 |
| GET | `/api/v1/families/{id}/members` | メンバー一覧 |

### 子ども

| メソッド | エンドポイント | 説明 |
|---|---|---|
| GET | `/api/v1/families/{id}/children` | 子ども一覧 |
| POST | `/api/v1/families/{id}/children` | 子ども追加 |
| PUT | `/api/v1/families/{id}/children/{childId}` | 子ども更新 |
| DELETE | `/api/v1/families/{id}/children/{childId}` | 子ども削除 |

### 絵本（本棚）

| メソッド | エンドポイント | 説明 |
|---|---|---|
| GET | `/api/v1/books/search` | Google Books 検索 |
| GET | `/api/v1/families/{id}/books` | 本棚一覧 |
| POST | `/api/v1/families/{id}/books` | 絵本登録 |
| GET | `/api/v1/families/{id}/books/{bookId}` | 絵本詳細 |
| PUT | `/api/v1/families/{id}/books/{bookId}` | 絵本更新 |
| DELETE | `/api/v1/families/{id}/books/{bookId}` | 絵本削除 |

### 読み聞かせ記録

| メソッド | エンドポイント | 説明 |
|---|---|---|
| GET | `/api/v1/families/{id}/records` | 記録一覧 |
| POST | `/api/v1/families/{id}/records` | 記録作成 |
| GET | `/api/v1/families/{id}/records/{recordId}` | 記録詳細 |
| PUT | `/api/v1/families/{id}/records/{recordId}` | 記録更新 |
| DELETE | `/api/v1/families/{id}/records/{recordId}` | 記録削除 |

### 招待

| メソッド | エンドポイント | 説明 |
|---|---|---|
| GET | `/api/v1/invitations/{token}` | 招待情報取得（認証不要） |
| POST | `/api/v1/families/{id}/invitations` | 招待送信 |
| GET | `/api/v1/families/{id}/invitations` | 招待一覧 |
| DELETE | `/api/v1/families/{id}/invitations/{invitationId}` | 招待キャンセル |
| POST | `/api/v1/invitations/{token}/accept` | 招待受理 |

### タグ

| メソッド | エンドポイント | 説明 |
|---|---|---|
| GET | `/api/v1/tags` | タグ検索 |

## ディレクトリ構成

```
picture-book-log-web-app/
├── backend/                  # Laravel バックエンド
│   ├── app/
│   │   ├── Http/Controllers/ # API コントローラー
│   │   └── Models/           # Eloquent モデル
│   ├── packages/             # DDD ドメインパッケージ
│   ├── database/
│   │   ├── migrations/       # マイグレーション
│   │   └── seeders/          # デモデータシーダー
│   ├── routes/api.php        # API ルート定義
│   └── tests/                # Feature テスト
├── frontend/                 # React フロントエンド
│   └── src/
│       ├── api/              # API クライアント
│       ├── components/       # 共通コンポーネント
│       │   └── ui/           # shadcn/ui コンポーネント
│       ├── hooks/            # カスタムフック
│       └── pages/            # ページコンポーネント
├── docs/plans/               # 設計ドキュメント・実装プラン
├── docker-compose.yml
└── Taskfile.yml              # 開発コマンド定義
```
