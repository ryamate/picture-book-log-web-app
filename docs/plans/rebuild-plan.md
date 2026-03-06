# 絵本読み聞かせ記録アプリ リビルドプラン

## Context

過去に作成した「Yonde」(https://github.com/ryamate/yonde-web-app) をベースに、技術スタックを刷新し、コア機能に絞って作り直す。
旧アプリは Laravel 6 + Vue.js 2 + MySQL + Docker 構成。
新アプリは **Laravel API + React SPA（完全分離）** で再構築する。

## 目的

- **個人開発アプリとしてサービスを継続運用**する
- **学習目的**: DDD, Clean Architecture, CQRS 等のアーキテクチャを実践で学ぶ
- **アウトプット**: 各Stepの作業過程をログに残し、Qiita記事として公開する

## 開発ログ運用

### ディレクトリ

```
docs/
└── devlog/
    ├── step1-project-setup.md
    ├── step2-authentication.md
    ├── step3-family.md
    ├── step4-bookshelf.md
    ├── step5-read-records.md
    ├── step6-invitations.md
    └── step7-finishing.md
```

### 各ログの記録内容

```markdown
# Step N: タイトル

## ゴール
このStepで達成すること

## 技術選定・設計判断
- なぜこの方法を選んだか（比較検討、トレードオフ）
- 参考にした資料・記事

## 作業ログ
### N-1. サブタスク名
- やったこと
- ハマったこと・解決方法
- コードのポイント（抜粋）

## 動作確認
- スクリーンショット / curlの実行結果

## 振り返り
- 学んだこと
- 次にやること
```

### 運用ルール

1. **各Stepの作業開始時**にログファイルを作成し、ゴールを書く
2. **作業中**は判断理由・ハマりポイントをこまめに追記する
3. **Step完了時**に振り返りを書き、Qiita記事のドラフトとして整形する

### `/devlog` スキル作成（TODO）

- Step 1の作業を進めながら、実際の使用感を踏まえてスキルを作成する
- 作業中に `/devlog` を呼ぶだけで、直近の作業内容をログファイルに追記できるようにする
- 実際に手動でログを数回書いてみて、ちょうどいい粒度・フォーマットを確定してからスキル化する

## 技術スタック

| レイヤー | 技術 |
|---|---|
| バックエンド | Laravel 11+ (REST API) |
| アーキテクチャ | DDD + Clean Architecture + CQRS |
| フロントエンド | React 18 + TypeScript + Vite |
| 認証 | Laravel Sanctum (トークンベース) |
| DB | MySQL 8.0 |
| データ取得 | TanStack Query (React Query) |
| フォーム | React Hook Form |
| ルーティング | React Router v6 |
| 開発環境 | Docker Compose |
| メール(dev) | Mailpit |

## アーキテクチャ方針

### レイヤー構成（Clean Architecture）

```
[Interface] → [Application (UseCase)] → [Domain] ← [Infrastructure]
```

| レイヤー | 責務 | 依存方向 |
|---|---|---|
| **Domain** | エンティティ、値オブジェクト、リポジトリインターフェース、ドメインサービス | 依存なし（最内層） |
| **Application** | Command / Query ハンドラ（ユースケース）、DTO | Domain のみに依存 |
| **Infrastructure** | Eloquentリポジトリ実装、外部API連携 | Domain に依存（インターフェース実装） |
| **Interface** | Controller、Request、Resource（Laravel HTTP層） | Application に依存 |

### CQRS

- **Command**: 状態を変更する操作。Domain Entity を経由し、Repository で永続化
- **Query**: 読み取り専用。Eloquent を直接使い、パフォーマンス重視（Domain 層を経由しない）
- イベントソーシングは採用しない（シンプルなCQRS）

### 境界づけられたコンテキスト（Bounded Context）

| コンテキスト | 責務 |
|---|---|
| Auth | ユーザー認証・登録 |
| Family | 家族管理、子ども、招待 |
| Bookshelf | 絵本登録・検索・本棚管理 |
| ReadLog | 読み聞かせ記録、タグ |

## コア機能（Phase 1）

1. ユーザー認証（メール/パスワード）
2. 絵本登録（Google Books API連携、本棚管理）
3. 読み聞かせ記録（日付、子ども、リアクション、タグ、メモ）
4. 家族共有（家族作成、招待、本棚・記録の共有）

---

## Step 1: プロジェクト基盤構築

### ディレクトリ構成

```
picture-book-log-web-app/
├── docker-compose.yml
├── .env.example
├── Taskfile.yml
├── README.md
├── backend/                        # Laravel 11 API
│   ├── Dockerfile
│   ├── app/
│   │   ├── Http/                   # Interface層（Laravel標準）
│   │   │   ├── Controllers/Api/
│   │   │   ├── Requests/
│   │   │   └── Resources/
│   │   └── Providers/
│   ├── packages/                   # DDD + Clean Architecture
│   │   ├── Auth/
│   │   │   ├── Application/
│   │   │   │   ├── Command/       # RegisterUser, Login, Logout
│   │   │   │   └── Query/         # GetCurrentUser
│   │   │   ├── Domain/
│   │   │   │   ├── Entity/
│   │   │   │   ├── ValueObject/   # Email, HashedPassword
│   │   │   │   └── Repository/    # UserRepositoryInterface
│   │   │   └── Infrastructure/
│   │   │       └── Repository/    # EloquentUserRepository
│   │   ├── Family/
│   │   │   ├── Application/
│   │   │   │   ├── Command/       # CreateFamily, AddChild, InviteMember...
│   │   │   │   └── Query/         # GetFamily, ListChildren, ListMembers
│   │   │   ├── Domain/
│   │   │   │   ├── Entity/        # Family, Child, Invitation
│   │   │   │   ├── ValueObject/   # FamilyName, InvitationToken
│   │   │   │   ├── Repository/
│   │   │   │   └── Service/       # InvitationDomainService
│   │   │   └── Infrastructure/
│   │   │       ├── Repository/
│   │   │       └── Mail/          # InvitationMail
│   │   ├── Bookshelf/
│   │   │   ├── Application/
│   │   │   │   ├── Command/       # AddBook, UpdateBook, RemoveBook
│   │   │   │   └── Query/         # SearchGoogleBooks, ListBooks, GetBook
│   │   │   ├── Domain/
│   │   │   │   ├── Entity/        # PictureBook
│   │   │   │   ├── ValueObject/   # Isbn, Rating, ReadStatus
│   │   │   │   └── Repository/
│   │   │   └── Infrastructure/
│   │   │       ├── Repository/
│   │   │       └── External/      # GoogleBooksApiClient
│   │   └── ReadLog/
│   │       ├── Application/
│   │       │   ├── Command/       # CreateRecord, UpdateRecord, DeleteRecord
│   │       │   └── Query/         # ListRecords, GetRecord, SearchTags
│   │       ├── Domain/
│   │       │   ├── Entity/        # ReadRecord, Tag
│   │       │   ├── ValueObject/   # Reaction, ReadDate
│   │       │   └── Repository/
│   │       └── Infrastructure/
│   │           └── Repository/
│   ├── database/
│   │   ├── migrations/
│   │   └── Eloquent/Models/       # Eloquentモデル（Infrastructure用）
│   ├── routes/api.php
│   └── tests/
├── frontend/                       # React SPA
│   ├── Dockerfile
│   ├── src/
│   │   ├── api/                    # Axiosクライアント、API関数
│   │   ├── components/             # UI、レイアウト、機能別コンポーネント
│   │   ├── hooks/                  # カスタムフック (useAuth, useBooks等)
│   │   ├── pages/                  # ページコンポーネント
│   │   ├── types/                  # TypeScript型定義
│   │   └── App.tsx
│   └── vite.config.ts
└── infra/docker/
    ├── php/                        # PHP 8.3 FPM
    ├── nginx/                      # Nginx (APIプロキシ)
    └── mysql/                      # MySQL設定
```

### Docker Compose構成

5サービス:

| サービス | イメージ | 用途 |
|---|---|---|
| `app` | `php:8.3-fpm-alpine` | PHP-FPM (Laravel API) |
| `web` | `nginx:1.27-alpine` | Nginx (APIプロキシ) |
| `db` | `mysql:8.0` | データベース |
| `frontend` | `node:20-alpine` | Vite dev server (React SPA) |
| `mailpit` | `axllent/mailpit:latest` | 開発用メールサーバー |

### やること

1. `composer create-project laravel/laravel backend`
2. `npm create vite@latest frontend -- --template react-ts`
3. Docker Compose / Dockerfile / Nginx設定を作成
4. Taskfile.yml作成 (`task up`, `task down`, `task migrate`, `task seed`, `task test`)
5. 各コンテナの疎通確認

---

## Step 2: 認証 (Authentication)

### バックエンド

- Sanctum設定 (`config/sanctum.php`, `config/cors.php`)
- `AuthController` — register, login, logout, user
- `RegisterRequest`, `LoginRequest` バリデーション
- `UserResource` JSONリソース
- `routes/api.php` にルート定義
- Feature テスト

### API エンドポイント

```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/user
```

### フロントエンド

- Axiosクライアント (`src/api/client.ts`) — ベースURL、トークン管理、401インターセプター
- `AuthContext` + `useAuth` フック
- `LoginPage`, `RegisterPage` (React Hook Form)
- `ProtectedRoute` コンポーネント
- `AppLayout` + `Header` (ユーザー名、ログアウト)

---

## Step 3: 家族管理 (Family)

### バックエンド

- マイグレーション: `families`, `users`への`family_id`追加, `children`
- モデル: `Family`, `Child`, `User`リレーション更新
- `FamilyController`, `ChildController`
- `FamilyPolicy` — ユーザーが家族に所属しているか検証
- APIリソース、テスト

### API エンドポイント

```
POST   /api/v1/families
GET    /api/v1/families/{family}
PUT    /api/v1/families/{family}
GET    /api/v1/families/{family}/members
GET    /api/v1/families/{family}/children
POST   /api/v1/families/{family}/children
PUT    /api/v1/families/{family}/children/{child}
DELETE /api/v1/families/{family}/children/{child}
```

### フロントエンド

- `FamilySettingsPage` (家族作成・編集)
- `ChildForm`, `ChildCard` コンポーネント
- `useFamily`, `useChildren` フック
- ログイン後に家族未所属なら作成/参加を促すフロー

---

## Step 4: 絵本登録 (Picture Books)

### バックエンド

- マイグレーション: `picture_books`
- `PictureBook` モデル (authorsはJSON型)
- `GoogleBooksService` — Google Books APIへのHTTPクライアント
- `PictureBookController` — CRUD + 検索プロキシ
- `PictureBookPolicy`, リクエストバリデーション、テスト

### API エンドポイント

```
GET    /api/v1/books/search?q={query}     # Google Books検索プロキシ
GET    /api/v1/families/{family}/books     # 家族の本棚一覧 (ページネーション)
POST   /api/v1/families/{family}/books     # 本棚に追加
GET    /api/v1/families/{family}/books/{book}
PUT    /api/v1/families/{family}/books/{book}
DELETE /api/v1/families/{family}/books/{book}
```

### フロントエンド

- `BookSearchPage` — Google Books検索、デバウンス付き
- `BookshelfPage` — ステータスフィルター、ページネーション
- `BookCard`, `BookDetailPage` — 評価、ステータス、レビュー編集
- `useBooks` フック

---

## Step 5: 読み聞かせ記録 (Read Records)

### バックエンド

- マイグレーション: `read_records`, `child_read_record`, `tags`, `read_record_tag`
- モデル: `ReadRecord`, `Tag` (リレーション定義)
- `ReadRecordController` — CRUD
- `TagController` — オートコンプリート用検索
- テスト

### API エンドポイント

```
GET    /api/v1/families/{family}/records   # フィルタ: child, book, date range
POST   /api/v1/families/{family}/records
GET    /api/v1/families/{family}/records/{record}
PUT    /api/v1/families/{family}/records/{record}
DELETE /api/v1/families/{family}/records/{record}
GET    /api/v1/tags?q={query}
```

### フロントエンド

- `RecordCreatePage` + `RecordForm` — 本選択、日付、子ども(リアクション付き)、タグ、メモ
- `RecordListPage` — タイムライン表示、フィルター
- `RecordCard` コンポーネント
- `BookDetailPage`内に記録セクション統合
- `useRecords` フック

---

## Step 6: 家族招待 (Invitations)

### バックエンド

- マイグレーション: `family_invitations` (token, expires_at付き)
- `FamilyInvitation` モデル
- 招待送信・受理エンドポイント
- `InvitationMail` Mailable + Mailpit連携
- テスト

### フロントエンド

- `InviteMemberForm` (FamilySettingsPage内)
- 招待受理ページ（メールリンクから）

---

## Step 7: 仕上げ

- グローバルエラーバウンダリ (React)
- ローディングスケルトン
- 空状態のフレンドリーメッセージ
- レスポンシブデザイン（モバイルファースト）
- データベースシーダー（デモデータ）
- READMEドキュメント

---

## DB スキーマ（改善版）

旧アプリからの主な変更点:
- `authors` を JSON型に変更（複数著者対応）
- `child_read_record` ピボットに `reaction` カラム追加（子ども毎のリアクション）
- `family_invitations` に `expires_at` 追加
- Phase 1不要のテーブル削除（contacts, follows, likes）

### テーブル一覧

| テーブル | 主なカラム |
|---|---|
| families | id, name |
| users | id, family_id(nullable), name, email, password, role, avatar_path, soft_delete |
| children | id, family_id, name, birthday |
| picture_books | id, family_id, registered_by, google_books_id, isbn, title, authors(JSON), thumbnail_url, rating(1-5), read_status, review |
| read_records | id, picture_book_id, family_id, recorded_by, read_date, memo |
| child_read_record | child_id, read_record_id, reaction |
| tags | id, name(unique) |
| read_record_tag | read_record_id, tag_id |
| family_invitations | id, family_id, invited_by, email, token, accepted_at, expires_at |

---

## 検証方法

1. **Docker起動確認**: `task up` で全コンテナ起動、`curl localhost:8080/api/v1/auth/user` でAPI応答確認
2. **認証フロー**: フロントエンドから登録→ログイン→ユーザー情報取得
3. **CRUD操作**: 家族作成→子ども追加→本検索・登録→記録作成の一連フロー
4. **バックエンドテスト**: `task test` でPHPUnitテスト実行
5. **招待フロー**: Mailpit (localhost:8025) で招待メール確認、リンクから受理
