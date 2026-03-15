# リファクタリング: RequestファイルにGETメソッドを追加 (Issue #12)

## Context

現在、GETエンドポイント（一覧取得・検索）のコントローラーは汎用の `Illuminate\Http\Request` を使い、`$request->query()` やインライン `$request->validate()` でパラメータを取得している。POST/PUT用には専用のFormRequestクラスがあるが、GET用にはない。

このリファクタリングで、GET用にも専用FormRequestクラスを作成し、バリデーションとアクセサメソッドをRequestに集約する。

## 対象エンドポイント (4箇所)

| コントローラー | メソッド | クエリパラメータ |
|---|---|---|
| ReadRecordController | index() | child_id, picture_book_id, date_from, date_to, per_page, page |
| PictureBookController | index() | status, sort, order, per_page |
| PictureBookController | search() | q |
| TagController | index() | q |

## 実装ステップ

### Step 1: FormRequestクラスの作成 (4ファイル)

**1-1. `backend/app/Http/Requests/IndexReadRecordRequest.php`**
- rules: child_id(nullable|integer), picture_book_id(nullable|integer), date_from(nullable|date), date_to(nullable|date|after_or_equal:date_from), per_page(nullable|integer|min:1|max:100), page(nullable|integer|min:1)
- アクセサ: `childId(): ?int`, `pictureBookId(): ?int`, `dateFrom(): ?string`, `dateTo(): ?string`, `perPage(): int` (default 20), `page(): int` (default 1)

**1-2. `backend/app/Http/Requests/IndexBookRequest.php`**
- rules: status(nullable|string|in:unread,reading,read), sort(nullable|string|in:created_at,title,rating), order(nullable|string|in:asc,desc), per_page(nullable|integer|min:1|max:100)
- アクセサ: `status(): ?string`, `sort(): string` (default 'created_at'), `order(): string` (default 'desc'), `perPage(): int` (default 20)
- 参考: sort許可値は `ListBooksHandler` (L26) の `in_array` チェックに合わせる

**1-3. `backend/app/Http/Requests/SearchGoogleBookRequest.php`**
- rules: q(required|string|min:1)
- アクセサ: `keyword(): string`

**1-4. `backend/app/Http/Requests/SearchTagRequest.php`**
- rules: q(required|string|min:1)
- アクセサ: `keyword(): string`

### Step 2: コントローラーの更新 (3ファイル)

**2-1. `backend/app/Http/Controllers/Api/ReadRecordController.php`**
- `index()` の型ヒントを `IndexReadRecordRequest` に変更
- `$request->query(...)` をアクセサメソッド呼び出しに置換
- 不要になった `use Illuminate\Http\Request` を削除

**2-2. `backend/app/Http/Controllers/Api/PictureBookController.php`**
- `index()` の型ヒントを `IndexBookRequest` に変更
- `search()` の型ヒントを `SearchGoogleBookRequest` に変更
- `$request->query(...)` / `$request->validate(...)` をアクセサメソッドに置換
- 不要になった `use Illuminate\Http\Request` を削除

**2-3. `backend/app/Http/Controllers/Api/TagController.php`**
- `index()` の型ヒントを `SearchTagRequest` に変更
- インライン `$request->validate(...)` 削除、`$request->keyword()` に置換
- 不要になった `use Illuminate\Http\Request` を削除

### Step 3: 動作確認

- `cd backend && php artisan test` で全テスト通過を確認
- 既存テスト（ListBooksTest, ListRecordsTest, SearchGoogleBooksTest）はGETリクエストでクエリパラメータを送っているため、FormRequest化しても変更不要

## 注意点

- **挙動の変化**: `per_page=999` のような値は、現在はサイレントにクランプ（min関数）されるが、FormRequest導入後は `max:100` ルールにより422エラーになる。API として明示的にエラーを返す方が適切
- **sort/orderのバリデーション追加**: 現在は `ListBooksHandler` 内でフォールバックしているが、FormRequestで `in:` ルールを追加することで不正値を早期に弾ける
- **date_toのバリデーション追加**: `after_or_equal:date_from` を追加し、不正な日付範囲を弾く
