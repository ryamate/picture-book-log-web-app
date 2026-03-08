# 1-2. React プロジェクト作成 — 詳細プラン

## ゴール

React + TypeScript のフロントエンドプロジェクトを Vite で作成し、Docker コンテナ内で動作するための設定を完了する。

## 完了条件

- [ ] `frontend/` に Vite + React + TypeScript プロジェクトが作成されている
- [ ] `vite.config.ts` が Docker 環境向けに設定されている（host, port, proxy）
- [ ] `npm run dev` で Vite dev server が起動する
- [ ] ブラウザで初期画面が表示される

---

## 作業手順

### 1. Vite プロジェクト作成

ホストに Node.js v22.3.0 / npm 10.8.1 がインストール済みのため、直接実行する。

```bash
npm create vite@latest frontend -- --template react-ts
cd frontend && npm install
```

生成されるディレクトリ構造:

```
frontend/
├── public/
├── src/
│   ├── App.tsx
│   ├── App.css
│   ├── main.tsx
│   └── ...
├── index.html
├── package.json
├── tsconfig.json
├── tsconfig.app.json
├── tsconfig.node.json
└── vite.config.ts
```

### 2. Vite 設定調整

`frontend/vite.config.ts` を以下の内容に変更する:

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

変更ポイント:

| 設定 | 目的 |
|---|---|
| `host: '0.0.0.0'` | Docker コンテナ内からホストへの公開 |
| `port: 5173` | docker-compose.yml のポートマッピングと一致させる |
| `proxy /api` | フロントから `/api/*` へのリクエストを Nginx コンテナに転送 |

### 3. .gitignore の確認

Vite テンプレートが生成する `frontend/.gitignore` に以下が含まれていることを確認する:

- `node_modules`
- `dist`

---

## 確認ポイント

| # | 確認項目 | コマンド | 期待結果 |
|---|---|---|---|
| 1 | プロジェクト作成 | `ls frontend/package.json` | ファイルが存在する |
| 2 | 依存インストール | `ls frontend/node_modules` | ディレクトリが存在する |
| 3 | dev server 起動 | `cd frontend && npm run dev` | Vite dev server が起動する |
| 4 | 初期画面表示 | ブラウザで `http://localhost:5173` | Vite + React 初期画面 |

---

## 注意事項

- Step 1 では追加パッケージ（ルーティング、API通信ライブラリ等）はインストールしない。Vite dev server の起動確認のみ
- Docker 環境では `node_modules` を named volume で管理し、ホスト側の `node_modules` と競合しないようにする（docker-compose.yml で対応、1-3 で設定）
- ホスト側で `npm install` を実行するのはプロジェクト作成時のみ。以降の依存管理は Docker コンテナ経由で行う
