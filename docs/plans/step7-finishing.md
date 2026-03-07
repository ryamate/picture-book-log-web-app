# Step 7: 仕上げ — 詳細プラン

## ゴール

Step 2〜6 で実装した機能を UX・品質・運用の観点で仕上げ、本番リリース可能な状態にする。
エラーハンドリング、ローディング表示、空状態、レスポンシブ対応、デモデータ、ドキュメントを整備する。

## 完了条件

- [ ] React のグローバルエラーバウンダリが機能し、未処理エラーでアプリがクラッシュしない
- [ ] 全ページでローディングスケルトンが表示される
- [ ] データが空の状態でフレンドリーなメッセージと次のアクション導線が表示される
- [ ] モバイル・タブレット・デスクトップでレイアウトが崩れない
- [ ] `task fresh` でデモデータが投入され、一通りの操作を確認できる
- [ ] README に環境構築手順・技術スタック・アーキテクチャ概要が記載されている
- [ ] 全 Feature テストが通る

---

## 7-1. グローバルエラーバウンダリ (React)

### ErrorBoundary コンポーネント

React の `ErrorBoundary` でレンダリング中の未処理エラーをキャッチし、フォールバック UI を表示する。

```typescript
// src/components/ErrorBoundary.tsx
import { Component, ErrorInfo, ReactNode } from 'react';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

class ErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false, error: null };

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
    console.error('Uncaught error:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback ?? <ErrorFallback onRetry={() => this.setState({ hasError: false, error: null })} />;
    }
    return this.props.children;
  }
}
```

### ErrorFallback コンポーネント

```typescript
// src/components/ErrorFallback.tsx
const ErrorFallback = ({ onRetry }: { onRetry: () => void }) => (
  <div className="error-fallback">
    <h2>予期しないエラーが発生しました</h2>
    <p>申し訳ありません。ページの再読み込みをお試しください。</p>
    <button onClick={onRetry}>再試行</button>
    <button onClick={() => window.location.href = '/'}>トップに戻る</button>
  </div>
);
```

### 適用箇所

```typescript
// src/App.tsx
<ErrorBoundary>
  <QueryClientProvider client={queryClient}>
    <AuthProvider>
      <RouterProvider router={router} />
    </AuthProvider>
  </QueryClientProvider>
</ErrorBoundary>
```

### API エラーのグローバルハンドリング

TanStack Query の `QueryClient` でグローバルエラーコールバックを設定:

```typescript
const queryClient = new QueryClient({
  defaultOptions: {
    mutations: {
      onError: (error) => {
        // 汎用エラー通知（トースト等）
        if (isAxiosError(error) && error.response?.status === 500) {
          toast.error('サーバーエラーが発生しました。しばらくしてから再度お試しください。');
        }
      },
    },
  },
});
```

### 設計判断: エラー通知の方式

| 方式 | 説明 |
|---|---|
| トースト通知 | 画面隅に一時的に表示。操作の邪魔にならない |
| インライン表示 | フォーム直下等に表示。バリデーションエラーに適する |
| フルページフォールバック | ErrorBoundary 用。致命的エラーのみ |

→ **3つを併用**:
- **バリデーションエラー (422)**: フォーム内のインライン表示（既に Step 2〜6 で実装済み）
- **サーバーエラー (500)、ネットワークエラー**: トースト通知
- **レンダリングエラー**: ErrorBoundary のフルページフォールバック

トースト通知ライブラリ: `react-hot-toast`（軽量、カスタマイズ容易）

```bash
docker compose exec frontend npm install react-hot-toast
```

---

## 7-2. ローディングスケルトン

### 方針

データ取得中に空白画面やスピナーだけでなく、コンテンツの形状を模したスケルトン UI を表示する。

### 対象ページと表示内容

| ページ | スケルトン内容 |
|---|---|
| BookSearchPage | 検索結果のカード型スケルトン（検索実行中） |
| BookshelfPage | BookCard のグリッド型スケルトン（サムネイル + テキスト行） |
| BookDetailPage | サムネイル + テキストブロック |
| RecordListPage | RecordCard のリスト型スケルトン |
| FamilySettingsPage | テキストブロック + リスト |
| DashboardPage | カード型スケルトン群 |

### 実装方法

CSS アニメーションによるシンプルなスケルトン:

```typescript
// src/components/Skeleton.tsx
interface SkeletonProps {
  width?: string;
  height?: string;
  borderRadius?: string;
}

const Skeleton = ({ width = '100%', height = '1rem', borderRadius = '4px' }: SkeletonProps) => (
  <div
    className="skeleton"
    style={{ width, height, borderRadius }}
  />
);
```

```css
/* src/styles/skeleton.css */
.skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

### 各ページ用スケルトンコンポーネント

```typescript
// src/components/BookCardSkeleton.tsx
const BookCardSkeleton = () => (
  <div className="book-card-skeleton">
    <Skeleton width="100%" height="200px" />        {/* サムネイル */}
    <Skeleton width="80%" height="1rem" />           {/* タイトル */}
    <Skeleton width="60%" height="0.875rem" />       {/* 著者 */}
  </div>
);

// src/components/BookshelfSkeleton.tsx
const BookshelfSkeleton = () => (
  <div className="bookshelf-grid">
    {Array.from({ length: 8 }).map((_, i) => (
      <BookCardSkeleton key={i} />
    ))}
  </div>
);
```

### TanStack Query との統合

```typescript
const { data, isLoading } = useBooks(familyId, params);

if (isLoading) return <BookshelfSkeleton />;
```

### 設計判断: スケルトン vs スピナー

| 方式 | 適するケース |
|---|---|
| スケルトン | 初回読み込み、ページ遷移時 |
| スピナー | ボタンクリック後の短い待機（送信中等） |

→ **データフェッチにはスケルトン、アクション実行にはボタン内スピナー**。
ボタンスピナーは `isPending` を使って表示:

```typescript
<button disabled={isPending}>
  {isPending ? '送信中...' : '保存'}
</button>
```

---

## 7-3. 空状態のフレンドリーメッセージ

### 方針

データが空の状態で「何もありません」ではなく、次のアクションを促すメッセージと導線を表示する。

### 各ページの空状態

| ページ | 空状態メッセージ | アクション導線 |
|---|---|---|
| BookshelfPage | まだ絵本が登録されていません | 「絵本を探す」ボタン → BookSearchPage |
| RecordListPage | まだ読み聞かせの記録がありません | 「記録をつける」ボタン → RecordCreatePage |
| FamilySettings メンバー | あなただけのメンバーです | 「家族を招待する」ボタン → 招待フォームにフォーカス |
| FamilySettings 子ども | まだ子どもが登録されていません | 「子どもを追加する」ボタン → ChildForm |
| RecordListPage (フィルター結果) | 条件に一致する記録がありません | 「フィルターをクリア」ボタン |
| BookshelfPage (フィルター結果) | このステータスの絵本はありません | 「すべて表示」タブに切り替え |
| FamilySettings 招待 | まだ招待を送っていません | 「メンバーを招待する」フォームへのガイド |

### EmptyState コンポーネント

```typescript
// src/components/EmptyState.tsx
interface EmptyStateProps {
  message: string;
  actionLabel?: string;
  onAction?: () => void;
}

const EmptyState = ({ message, actionLabel, onAction }: EmptyStateProps) => (
  <div className="empty-state">
    <p>{message}</p>
    {actionLabel && onAction && (
      <button onClick={onAction}>{actionLabel}</button>
    )}
  </div>
);
```

---

## 7-4. レスポンシブデザイン（モバイルファースト）

### 方針

モバイルファーストで設計し、ブレークポイントで PC 向けレイアウトに拡張する。

### ブレークポイント

| 名前 | 幅 | 対象 |
|---|---|---|
| `sm` | 640px〜 | 大きめのスマートフォン |
| `md` | 768px〜 | タブレット |
| `lg` | 1024px〜 | デスクトップ |

### CSS 方針

| 方式 | 説明 |
|---|---|
| CSS Modules | コンポーネントスコープ。名前衝突なし |
| Tailwind CSS | ユーティリティファースト。高速開発 |
| 素の CSS + BEM | シンプル。ライブラリ不要 |

→ **CSS Modules を採用**。理由:
- Vite が標準サポート（設定不要）
- コンポーネント単位でスタイルを管理でき、スコープが自然に分離
- Tailwind ほどの学習コストがなく、通常の CSS 知識で書ける
- Phase 1 のコンポーネント数なら十分に管理可能

### 主要レイアウトの対応方針

**Header / ナビゲーション**:
- モバイル: ハンバーガーメニュー
- デスクトップ: 横並びナビゲーション

**BookshelfPage（本棚グリッド）**:
- モバイル: 2列グリッド
- タブレット: 3列
- デスクトップ: 4〜5列

```css
.bookshelf-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

@media (min-width: 768px) {
  .bookshelf-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1024px) {
  .bookshelf-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
```

**RecordListPage（記録リスト）**:
- モバイル: カード型の縦並び（フルwidth）
- デスクトップ: カード型 + 左にフィルターサイドバー

**フォーム（RecordForm, ChildForm 等）**:
- モバイル: 1列レイアウト
- デスクトップ: 2列（ラベル左、入力右）or そのまま1列（十分な幅）

**BookDetailPage**:
- モバイル: サムネイル上、情報下の縦並び
- デスクトップ: サムネイル左、情報右の横並び

### タッチ操作の考慮

- タップターゲットは最低 44x44px
- フォーム入力フィールドに十分なパディング
- スワイプ操作は Phase 1 では不要

---

## 7-5. データベースシーダー（デモデータ）

### 方針

`task fresh` で一発でデモ環境を構築できるシーダーを作成する。開発時の動作確認、デモ、スクリーンショット撮影に使用。

### シーダー構成

```
backend/database/seeders/
├── DatabaseSeeder.php          # メインシーダー（各シーダーを呼び出し）
├── FamilySeeder.php
├── UserSeeder.php
├── ChildSeeder.php
├── PictureBookSeeder.php
├── ReadRecordSeeder.php
└── TagSeeder.php
```

### デモデータ内容

**家族 1: 山田家**

| データ | 内容 |
|---|---|
| ユーザー | 山田太郎（パパ）`taro@example.com` / `password` |
| ユーザー | 山田花子（ママ）`hanako@example.com` / `password` |
| 子ども | ゆうき（2021-04-15生まれ） |
| 子ども | あおい（2023-09-20生まれ） |
| 絵本 | 10〜15冊（各ステータス混在） |
| 読み聞かせ記録 | 20〜30件（タグ・リアクション付き） |

**家族 2: 佐藤家**（認可テスト用）

| データ | 内容 |
|---|---|
| ユーザー | 佐藤一郎 `ichiro@example.com` / `password` |
| 子ども | さくら（2022-01-10生まれ） |
| 絵本 | 5冊 |
| 読み聞かせ記録 | 5件 |

**未所属ユーザー**（招待テスト用）

| データ | 内容 |
|---|---|
| ユーザー | 鈴木次郎 `jiro@example.com` / `password` |

### DatabaseSeeder

```php
namespace Database\Seeders;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FamilySeeder::class,
            UserSeeder::class,
            ChildSeeder::class,
            TagSeeder::class,
            PictureBookSeeder::class,
            ReadRecordSeeder::class,
        ]);
    }
}
```

### PictureBookSeeder のデータ例

実在する絵本のデータを使用（Google Books API のレスポンスを参考に手動で用意）:

| タイトル | 著者 | ステータス | 評価 |
|---|---|---|---|
| ぐりとぐら | 中川李枝子 | read | 5 |
| はらぺこあおむし | エリック・カール | read | 5 |
| おおきなかぶ | A.トルストイ | read | 4 |
| ねないこだれだ | せなけいこ | reading | 4 |
| いないいないばあ | 松谷みよ子 | read | 5 |
| だるまさんが | かがくいひろし | read | 5 |
| しろくまちゃんのほっとけーき | わかやまけん | reading | 4 |
| きんぎょがにげた | 五味太郎 | unread | null |
| もこもこもこ | 谷川俊太郎 | unread | null |
| くだもの | 平山和子 | read | 3 |

### TagSeeder のデータ例

```php
$tags = ['寝る前', 'お気に入り', 'リクエスト', '図書館', '新しい絵本', 'シリーズ', '季節もの'];
```

### ReadRecordSeeder のデータ例

- 日付は直近1ヶ月にばらけさせる
- 子どもごとに異なるリアクションを設定
- 複数タグの紐付け

### 確認ポイント

- `task fresh` でエラーなく完了する
- 山田太郎でログイン後、本棚・記録・家族設定にデモデータが表示される
- 佐藤家のデータに山田家からアクセスすると 403 が返る

---

## 7-6. README ドキュメント

### 構成

```markdown
# 絵本読み聞かせ記録アプリ（Picture Book Log）

## 概要
家族で絵本の読み聞かせを記録・共有するWebアプリケーション。

## 技術スタック
（テーブル形式で記載）

## アーキテクチャ
- DDD + Clean Architecture + CQRS
- （レイヤー図）

## 環境構築

### 前提条件
- Docker / Docker Compose
- Task (go-task)

### セットアップ手順
1. リポジトリクローン
2. 環境変数設定（.env.example → .env）
3. `task up`
4. `task composer install`
5. `task artisan key:generate`
6. `task fresh`
7. アクセス確認

### アクセス先
| サービス | URL |
|---|---|
| フロントエンド | http://localhost:5173 |
| API | http://localhost:8080/api/v1 |
| Mailpit | http://localhost:8025 |

### デモアカウント
| ユーザー | メール | パスワード |
|---|---|---|

## 開発コマンド
（Taskfile.yml のコマンド一覧）

## API エンドポイント一覧
（全エンドポイントの概要）

## ディレクトリ構成
（主要ディレクトリの説明）
```

### 設計判断: README のスコープ

- 環境構築手順に重点を置く（初見の人が迷わずセットアップできること）
- API の詳細仕様は別途ドキュメント化しない（Phase 1 では README の概要で十分）
- アーキテクチャの詳細は `docs/plans/rebuild-plan.md` を参照として案内

---

## 7-7. 最終テスト・品質チェック

### バックエンド

```bash
# 全テスト実行
task test

# テストカバレッジ確認（任意）
docker compose exec app php artisan test --coverage
```

### フロントエンド

Step 7 ではフロントエンドのユニットテストは追加しない（Phase 1 スコープ外）。手動での E2E 確認を行う。

### E2E 手動テストシナリオ

以下のシナリオを一通り手動で確認する:

**シナリオ 1: 新規ユーザーの基本フロー（CRUD 一通り）**
```
アカウント登録 → 家族作成 → 子ども追加
→ 絵本検索・登録 → 絵本の評価・ステータス・レビュー更新
→ 読み聞かせ記録作成 → 記録編集 → 記録削除
→ 子ども編集 → 子ども削除
→ 絵本を本棚から削除
→ 本棚・記録一覧確認
```

**シナリオ 2: 招待フロー**
```
山田太郎でログイン → 家族設定で招待送信
→ Mailpit でメール確認
→ 鈴木次郎でログイン → 招待リンクから受理
→ 山田家のデータが見える
```

**シナリオ 3: 認可チェック**
```
佐藤一郎でログイン
→ 山田家の family_id でAPIアクセス → 403
```

**シナリオ 4: エラーハンドリング**
```
未認証で保護ルートにアクセス → ログインページにリダイレクト
→ バリデーションエラーのあるフォーム送信 → エラー表示
→ ネットワーク切断 → エラートースト表示
```

**シナリオ 5: レスポンシブ確認**
```
Chrome DevTools でモバイル表示 → 各ページのレイアウト確認
→ タブレット → デスクトップ
```

---

## 7-8. 疎通確認チェックリスト

| # | 確認項目 | 方法 | 期待結果 |
|---|---|---|---|
| 1 | デモデータ投入 | `task fresh` | エラーなく完了 |
| 2 | デモアカウントログイン | `taro@example.com` / `password` | ダッシュボード表示 |
| 3 | ErrorBoundary | コンポーネントで意図的に例外 throw（開発時のみ） | フォールバック UI 表示 |
| 4 | ローディングスケルトン | 低速ネットワーク (DevTools) でページ遷移 | スケルトン表示 |
| 5 | 空状態 | 佐藤家で絵本全削除 or 新規家族作成 | メッセージ + アクション導線 |
| 6 | モバイル表示 | DevTools でモバイルエミュレーション | レイアウト崩れなし |
| 7 | タブレット表示 | DevTools で 768px 幅 | グリッド列数変化 |
| 8 | サーバーエラー表示 | API を一時停止してアクセス | トーストエラー表示 |
| 9 | 401 リダイレクト | トークン削除してページ遷移 | ログインページへ |
| 10 | 全 Feature テスト | `task test` | 全テスト通過 |
| 11 | E2E シナリオ 1〜5 | 手動実行 | 全シナリオ成功 |
| 12 | README 手順 | README に沿って 0 からセットアップ | 環境構築成功 |

---

## 作業順序まとめ

```
7-1.  グローバルエラーバウンダリ + トースト通知セットアップ
         ↓
7-2.  ローディングスケルトン（Skeleton コンポーネント + 各ページ適用）
         ↓
7-3.  空状態メッセージ（EmptyState コンポーネント + 各ページ適用）
         ↓
7-4.  レスポンシブデザイン（CSS Modules + ブレークポイント対応）
         ↓
7-5.  データベースシーダー作成
         ↓
7-6.  README ドキュメント作成
         ↓
7-7.  最終テスト・品質チェック（Feature テスト + E2E 手動テスト）
         ↓
7-8.  疎通確認チェックリスト実行
```

---

## 注意事項・判断メモ

- **ErrorBoundary はクラスコンポーネント**: React の ErrorBoundary は現時点でクラスコンポーネントでのみ実装可能。関数コンポーネント版は react-error-boundary ライブラリで代替可能だが、Phase 1 では標準 API で十分
- **トースト通知**: `react-hot-toast` を採用。軽量で API がシンプル
- **CSS 方針**: CSS Modules。Tailwind CSS は Phase 2 以降で検討
- **スケルトン**: 自前実装。ライブラリ（react-loading-skeleton 等）は不採用。CSS アニメーションだけで実現できるため
- **フロントエンドテスト**: Phase 1 ではユニットテスト・E2E テスト（Playwright 等）は導入しない。手動テストのみ
- **デモデータ**: 実在する絵本のタイトル・著者を使用。サムネイル URL は Google Books からの実際の URL or null
- **README の維持**: 各 Step 完了時に README を都度更新するのではなく、Step 7 で一括作成する方針。開発中は `docs/plans/` を参照
- **パフォーマンス最適化**: Phase 1 ではデータ量が限定的なため、特別な最適化（仮想スクロール、画像遅延読み込み等）は行わない。必要になった時点で追加
