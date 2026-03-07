# Step 1: プロジェクト基盤構築 — 詳細プラン

## ゴール

Docker Compose で Laravel API + React SPA + MySQL + Nginx + Mailpit が起動し、各コンテナ間の疎通が確認できる状態にする。

## 完了条件

- [ ] `task up` で全5コンテナが正常起動する
- [ ] `curl http://localhost:8080/api/health` で Laravel API から JSON レスポンスが返る
- [ ] `http://localhost:5173` で React の初期画面が表示される
- [ ] Laravel から MySQL への接続が成功する（`task artisan migrate` が通る）
- [ ] Mailpit の Web UI (`http://localhost:8025`) にアクセスできる
- [ ] `task test` で PHPUnit のデフォルトテストが通る

---

## 1-1. Laravel プロジェクト作成

### 作業内容

```bash
composer create-project laravel/laravel backend
```

### 作成後の調整

- `backend/.env` を本番用ではなく Docker 用に調整（DB_HOST=db 等）
- 不要なフロントエンド関連を削除:
  - `backend/vite.config.js` — 削除（フロントは別コンテナ）
  - `backend/resources/js/`, `backend/resources/css/` — 削除
  - `backend/package.json` — 削除
- `backend/routes/api.php` にヘルスチェック用エンドポイント追加:
  ```php
  Route::get('/health', fn () => response()->json(['status' => 'ok']));
  ```
- `backend/.gitignore` に `/vendor` が含まれていることを確認

### packages ディレクトリの準備

DDD用のディレクトリ構造を作成（空ディレクトリに `.gitkeep` を配置）:

```
backend/packages/
├── Auth/
│   ├── Application/
│   │   ├── Command/
│   │   └── Query/
│   ├── Domain/
│   │   ├── Entity/
│   │   ├── ValueObject/
│   │   └── Repository/
│   └── Infrastructure/
│       └── Repository/
├── Family/
│   ├── Application/
│   │   ├── Command/
│   │   └── Query/
│   ├── Domain/
│   │   ├── Entity/
│   │   ├── ValueObject/
│   │   ├── Repository/
│   │   └── Service/
│   └── Infrastructure/
│       ├── Repository/
│       └── Mail/
├── Bookshelf/
│   ├── Application/
│   │   ├── Command/
│   │   └── Query/
│   ├── Domain/
│   │   ├── Entity/
│   │   ├── ValueObject/
│   │   └── Repository/
│   └── Infrastructure/
│       ├── Repository/
│       └── External/
└── ReadLog/
    ├── Application/
    │   ├── Command/
    │   └── Query/
    ├── Domain/
    │   ├── Entity/
    │   ├── ValueObject/
    │   └── Repository/
    └── Infrastructure/
        └── Repository/
```

### composer.json の autoload 設定

packages を PSR-4 で読み込めるように設定:

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Packages\\Auth\\": "packages/Auth/",
      "Packages\\Family\\": "packages/Family/",
      "Packages\\Bookshelf\\": "packages/Bookshelf/",
      "Packages\\ReadLog\\": "packages/ReadLog/"
    }
  }
}
```

### 確認ポイント

- `php artisan --version` で Laravel 11+ であること
- `composer dump-autoload` が成功すること

---

## 1-2. React プロジェクト作成

### 作業内容

```bash
npm create vite@latest frontend -- --template react-ts
cd frontend && npm install
```

### 追加パッケージ（最小限）

Step 1 では API 通信やルーティングはまだ不要。Vite の dev server が起動することだけ確認する。

### Vite 設定調整

`frontend/vite.config.ts`:

```typescript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',  // Docker コンテナ外からアクセス可能にする
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://web:80',  // Nginx コンテナへプロキシ
        changeOrigin: true,
      },
    },
  },
})
```

### 確認ポイント

- `npm run dev` でVite dev serverが起動すること
- ブラウザで初期画面が表示されること

---

## 1-3. Docker 環境構築

### ディレクトリ構成

```
infra/docker/
├── php/
│   └── Dockerfile
├── nginx/
│   ├── Dockerfile        # （もしくは docker-compose で直接 image 指定）
│   └── default.conf
└── mysql/
    └── my.cnf            # 文字コード設定等
```

### 1-3a. PHP Dockerfile (`infra/docker/php/Dockerfile`)

```dockerfile
FROM php:8.3-fpm-alpine

# 必要な拡張
RUN apk add --no-cache \
    && docker-php-ext-install pdo_mysql bcmath

# Composer インストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
```

ポイント:
- Alpine ベースで軽量化
- `pdo_mysql`: MySQL接続に必須
- `bcmath`: Laravelの一部機能で使用
- Composer はマルチステージビルドで取得

### 1-3b. Nginx 設定 (`infra/docker/nginx/default.conf`)

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 1-3c. MySQL 設定 (`infra/docker/mysql/my.cnf`)

```ini
[mysqld]
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci

[client]
default-character-set=utf8mb4
```

### 1-3d. docker-compose.yml

```yaml
services:
  app:
    build:
      context: .
      dockerfile: infra/docker/php/Dockerfile
    volumes:
      - ./backend:/var/www/html
    depends_on:
      db:
        condition: service_healthy
    environment:
      - DB_HOST=db
      - DB_DATABASE=${DB_DATABASE:-picture_book_log}
      - DB_USERNAME=${DB_USERNAME:-app_user}
      - DB_PASSWORD=${DB_PASSWORD:-secret}

  web:
    image: nginx:1.27-alpine
    ports:
      - "8080:80"
    volumes:
      - ./backend:/var/www/html
      - ./infra/docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  db:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-rootpass}
      MYSQL_DATABASE: ${DB_DATABASE:-picture_book_log}
      MYSQL_USER: ${DB_USERNAME:-app_user}
      MYSQL_PASSWORD: ${DB_PASSWORD:-secret}
    volumes:
      - db-data:/var/lib/mysql
      - ./infra/docker/mysql/my.cnf:/etc/mysql/conf.d/my.cnf
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      timeout: 5s
      retries: 10

  frontend:
    image: node:20-alpine
    working_dir: /app
    ports:
      - "5173:5173"
    volumes:
      - ./frontend:/app
      - frontend-node-modules:/app/node_modules
    command: sh -c "npm install && npm run dev"

  mailpit:
    image: axllent/mailpit:latest
    ports:
      - "8025:8025"   # Web UI
      - "1025:1025"   # SMTP

volumes:
  db-data:
  frontend-node-modules:
```

ポイント:
- `db` に `healthcheck` を設定し、`app` は DB が ready になってから起動
- `frontend-node-modules` を named volume にして、ホスト側の node_modules と競合しない
- ポートマッピング: API=8080, Frontend=5173, Mailpit UI=8025

### 1-3e. .env.example

```env
# Database
DB_DATABASE=picture_book_log
DB_USERNAME=app_user
DB_PASSWORD=secret
DB_ROOT_PASSWORD=rootpass
```

### 確認ポイント

- `docker compose up -d` で全5コンテナが `running` / `healthy` になる
- `docker compose ps` で状態確認
- `docker compose logs app` でPHP-FPMエラーがないこと

---

## 1-4. Laravel の環境設定

### backend/.env（Docker用）

```env
APP_NAME="Picture Book Log"
APP_ENV=local
APP_KEY=   # php artisan key:generate で生成
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=picture_book_log
DB_USERNAME=app_user
DB_PASSWORD=secret

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="noreply@picturebooklog.local"
MAIL_FROM_NAME="${APP_NAME}"

SESSION_DRIVER=cookie
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

### 作業手順

```bash
# コンテナ内で実行
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 確認ポイント

- `php artisan migrate` が成功する
- `curl http://localhost:8080/api/health` → `{"status":"ok"}`

---

## 1-5. Taskfile.yml 作成

タスクランナーとして [Task](https://taskfile.dev/) を使用。よく使うコマンドをショートカット化する。

```yaml
version: '3'

tasks:
  up:
    desc: 全コンテナを起動
    cmds:
      - docker compose up -d

  down:
    desc: 全コンテナを停止
    cmds:
      - docker compose down

  restart:
    desc: 全コンテナを再起動
    cmds:
      - docker compose restart

  logs:
    desc: ログ表示
    cmds:
      - docker compose logs -f {{.CLI_ARGS}}

  ps:
    desc: コンテナ状態確認
    cmds:
      - docker compose ps

  artisan:
    desc: php artisan コマンド実行
    cmds:
      - docker compose exec app php artisan {{.CLI_ARGS}}

  migrate:
    desc: マイグレーション実行
    cmds:
      - docker compose exec app php artisan migrate

  seed:
    desc: シーダー実行
    cmds:
      - docker compose exec app php artisan db:seed

  fresh:
    desc: DBリセット + マイグレーション + シーダー
    cmds:
      - docker compose exec app php artisan migrate:fresh --seed

  test:
    desc: PHPUnit テスト実行
    cmds:
      - docker compose exec app php artisan test

  composer:
    desc: Composer コマンド実行
    cmds:
      - docker compose exec app composer {{.CLI_ARGS}}

  npm:
    desc: npm コマンド実行（frontend コンテナ）
    cmds:
      - docker compose exec frontend npm {{.CLI_ARGS}}

  shell:
    desc: app コンテナに入る
    cmds:
      - docker compose exec app sh
```

### 確認ポイント

- `task up` → 全コンテナ起動
- `task migrate` → マイグレーション成功
- `task test` → テスト通過

---

## 1-6. 疎通確認チェックリスト

すべての作業完了後、以下を順に確認する:

| # | 確認項目 | コマンド / URL | 期待結果 |
|---|---|---|---|
| 1 | コンテナ起動 | `task up && task ps` | 5コンテナすべて running |
| 2 | API ヘルスチェック | `curl http://localhost:8080/api/health` | `{"status":"ok"}` |
| 3 | DB 接続 | `task migrate` | マイグレーション成功 |
| 4 | React 画面 | ブラウザで `http://localhost:5173` | Vite + React 初期画面 |
| 5 | Mailpit | ブラウザで `http://localhost:8025` | Mailpit Web UI 表示 |
| 6 | テスト | `task test` | PHPUnit テスト通過 |
| 7 | API プロキシ | フロントから `/api/health` fetch | JSON レスポンス取得 |

---

## 作業順序まとめ

```
1-1. Laravel プロジェクト作成 + DDD ディレクトリ準備
         ↓
1-2. React プロジェクト作成 + Vite 設定
         ↓
1-3. Docker 環境構築（Dockerfile, nginx, mysql, docker-compose.yml）
         ↓
1-4. Laravel 環境設定（.env, key:generate, migrate）
         ↓
1-5. Taskfile.yml 作成
         ↓
1-6. 疎通確認チェックリスト実行
```

---

## 注意事項・判断メモ

- **packages ディレクトリ**: Step 1 では空ディレクトリ + `.gitkeep` のみ。実際のコードは Step 2 以降で追加
- **Eloquent Models の配置**: `backend/database/Eloquent/Models/` に配置する方針だが、Step 1 では Laravel デフォルトの `app/Models/User.php` をそのまま残す。Step 2 で認証実装時に移動を検討
- **node_modules**: Docker の named volume で管理し、ホスト側にはインストールしない方針
- **Task (taskfile.dev)**: ホストマシンに `task` コマンドがインストールされている前提。未インストールなら `brew install go-task` で導入
