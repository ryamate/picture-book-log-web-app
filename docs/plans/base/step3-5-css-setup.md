# 3.5. Tailwind CSS + Shadcn/ui 導入 — 詳細プラン

## ゴール

Tailwind CSS と Shadcn/ui をフロントエンドに導入し、既存ページのインラインスタイルを置き換える。

## 完了条件

- [ ] Tailwind CSS がインストール・設定されている
- [ ] Shadcn/ui が初期セットアップされている（Button, Input, Card, Label 等の基本コンポーネント）
- [ ] 既存の全ページ（Login, Register, Dashboard, CreateFamily, FamilySettings）が Tailwind + Shadcn/ui でスタイリングされている
- [ ] App.css の不要スタイルが削除されている
- [ ] index.css が Tailwind ベースに置き換わっている
- [ ] 既存機能が壊れていないこと（手動確認）

---

## 技術選定

| 項目 | 選定 | 理由 |
|---|---|---|
| CSS | Tailwind CSS v4 | Vite + React 19 との相性、ユーティリティファーストで開発速度向上 |
| UIコンポーネント | Shadcn/ui | ソースコードが手元に残り学習効果が高い、Tailwind 必須、軽量 |
| デザインシステム | Tailwind デフォルト | 個人開発に十分、過剰設計を避ける |

---

## 作業手順

### 1. Tailwind CSS インストール

```bash
npm install tailwindcss @tailwindcss/vite
```

`vite.config.ts` に Tailwind プラグインを追加:

```typescript
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  // ...
})
```

`src/index.css` を Tailwind ベースに置き換え:

```css
@import "tailwindcss";
```

### 2. Shadcn/ui セットアップ

```bash
npx shadcn@latest init
```

設定内容:
- Style: New York
- Base color: Neutral
- CSS variables: Yes

基本コンポーネントを追加:

```bash
npx shadcn@latest add button input label card
```

### 3. 既存ページのスタイル適用

対象ページ（インラインスタイル → Tailwind + Shadcn/ui に置き換え）:

| ページ | 主な変更 |
|---|---|
| LoginPage | Card + Input + Button + Label で認証フォーム |
| RegisterPage | 同上 |
| DashboardPage | Card でセクション表示 |
| CreateFamilyPage | Card + Input + Button で家族作成フォーム |
| FamilySettingsPage | Card でセクション分割、子ども一覧 |

対象コンポーネント:

| コンポーネント | 主な変更 |
|---|---|
| AppLayout | Tailwind でヘッダー・レイアウト |
| ChildCard | Card コンポーネント適用 |
| ChildForm | Input + Button + Label 適用 |

### 4. 不要ファイル整理

- `src/App.css` — 削除（未使用の .logo, .card, .read-the-docs クラス）
- `src/index.css` — Tailwind インポートのみに置き換え

---

## 確認ポイント

| # | 確認項目 | 期待結果 |
|---|---|---|
| 1 | `npm run dev` 起動 | Vite dev server がエラーなく起動 |
| 2 | ログインページ表示 | Tailwind + Shadcn/ui でスタイリングされている |
| 3 | 登録ページ表示 | 同上 |
| 4 | ダッシュボード表示 | カード表示でセクションが整理されている |
| 5 | 家族作成ページ | フォームが整ったUIで表示 |
| 6 | 家族設定ページ | セクション分割、子ども一覧が整理されている |
| 7 | 認証フロー | ログイン → ダッシュボード → ログアウトが正常動作 |

---

## 注意事項

- Tailwind CSS v4 は `tailwind.config.js` 不要（CSS ファイル内で設定）
- Shadcn/ui のコンポーネントは `src/components/ui/` に配置される
- 既存のビジネスロジック（hooks, api）は変更しない
