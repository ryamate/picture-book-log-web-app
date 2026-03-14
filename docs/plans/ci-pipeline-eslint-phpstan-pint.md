# CI パイプライン作成プラン

## Context

現在このプロジェクトにはCIが未設定。コード品質を自動チェックするため、GitHub Actions で ESLint / PHPStan / Laravel Pint を PR 時に実行するパイプラインを構築する。

## 方針

- **Docker不使用**: 静的解析にDB等は不要。GitHub Actions ランナー上で直接実行し、高速化する
- **3ジョブ並列実行**: ESLint / PHPStan / Pint を独立したジョブとして並列実行
- **Larastan導入**: Laravel のファサード・Eloquent等を正しく解析するために必須

## 変更ファイル一覧

| ファイル | 操作 | 内容 |
|---------|------|------|
| `.github/workflows/ci.yml` | 新規作成 | GitHub Actions ワークフロー定義 |
| `backend/phpstan.neon` | 新規作成 | PHPStan 設定（Level 5） |
| `backend/composer.json` | 修正 | `larastan/larastan` を require-dev に追加 |

## Step 1: PHPStan / Larastan のインストール

`backend/composer.json` の `require-dev` に追加:

```json
"larastan/larastan": "^3.0"
```

※ `phpstan/phpstan` は larastan の依存として自動インストールされる

```bash
cd backend && composer require --dev larastan/larastan
```

## Step 2: PHPStan 設定ファイル作成

`backend/phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 5
    paths:
        - app
        - packages/Auth
        - packages/Family
        - packages/Bookshelf
        - packages/ReadLog
        - packages/Shared
```

- `tests/` は意図的に除外（テスト固有のパターンで誤検知が多いため）
- Level 5 で大量エラーが出る場合は `--generate-baseline` でベースラインを作成し、段階的に対応可能

## Step 3: PHPStan をローカルで実行・エラー修正

```bash
cd backend && vendor/bin/phpstan analyse
```

Level 5 で想定されるエラー:
- メソッドの戻り値型宣言の欠落
- Eloquent モデルのプロパティ未定義（`@property` PHPDoc で対応）
- クロージャのパラメータ型不足

エラーが多い場合はベースラインで対応:
```bash
vendor/bin/phpstan analyse --generate-baseline
```
→ `phpstan.neon` に `includes: [phpstan-baseline.neon]` を追加

## Step 4: GitHub Actions ワークフロー作成

`.github/workflows/ci.yml`:

```yaml
name: CI

on:
  pull_request:
    branches: [main]

concurrency:
  group: ci-${{ github.event.pull_request.number }}
  cancel-in-progress: true

jobs:
  eslint:
    name: ESLint
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: frontend
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: npm
          cache-dependency-path: frontend/package-lock.json
      - run: npm ci
      - run: npm run lint

  phpstan:
    name: PHPStan
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_mysql, bcmath
          tools: composer:v2
      - uses: actions/cache@v4
        with:
          path: backend/vendor
          key: composer-${{ hashFiles('backend/composer.lock') }}
          restore-keys: composer-
      - run: composer install --no-interaction --no-progress --prefer-dist
      - run: vendor/bin/phpstan analyse

  pint:
    name: Laravel Pint
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          tools: composer:v2
      - uses: actions/cache@v4
        with:
          path: backend/vendor
          key: composer-${{ hashFiles('backend/composer.lock') }}
          restore-keys: composer-
      - run: composer install --no-interaction --no-progress --prefer-dist
      - run: vendor/bin/pint --test
```

## Step 5: ローカル検証

全ツールがエラー0で通ることを確認:

```bash
# Backend
cd backend
vendor/bin/phpstan analyse
vendor/bin/pint --test

# Frontend
cd frontend
npm run lint
```

## Step 6: PR を作成して CI 動作確認

ブランチを作成・プッシュし、main への PR を開いて3ジョブが全て pass することを確認する。
