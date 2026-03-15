<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\SearchGoogleBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\GoogleBookResource;
use App\Http\Resources\PictureBookCollection;
use App\Http\Resources\PictureBookResource;
use App\Models\Family;
use App\Models\PictureBook;
use DomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Packages\Bookshelf\Application\Command\AddBook\AddBookCommand;
use Packages\Bookshelf\Application\Command\AddBook\AddBookHandler;
use Packages\Bookshelf\Application\Command\RemoveBook\RemoveBookCommand;
use Packages\Bookshelf\Application\Command\RemoveBook\RemoveBookHandler;
use Packages\Bookshelf\Application\Command\UpdateBook\UpdateBookCommand;
use Packages\Bookshelf\Application\Command\UpdateBook\UpdateBookHandler;
use Packages\Bookshelf\Application\Query\ListBooks\ListBooksHandler;
use Packages\Bookshelf\Application\Query\ListBooks\ListBooksQuery;
use Packages\Bookshelf\Application\Query\SearchGoogleBooks\SearchGoogleBooksHandler;
use Packages\Bookshelf\Application\Query\SearchGoogleBooks\SearchGoogleBooksQuery;

/**
 * 絵本のCRUD操作およびGoogle Books検索のAPIコントローラー。
 */
class PictureBookController extends Controller
{
    /**
     * キーワードでGoogle Booksを検索する。 GET /api/google-books/search
     *
     * @param  SearchGoogleBookRequest  $request  Google Books 検索リクエスト
     * @param  SearchGoogleBooksHandler  $handler  検索ハンドラー
     *
     * @throws ConnectionException
     */
    public function search(SearchGoogleBookRequest $request, SearchGoogleBooksHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(new SearchGoogleBooksQuery(
                keyword: $request->keyword(),
            ));
        } catch (RequestException $e) {
            $status = $e->response->status();
            if ($status === 429) {
                return response()->json(['message' => 'Google Books API のリクエスト上限に達しました。しばらく時間をおいて再度お試しください。'], 429);
            }

            return response()->json(['message' => '外部サービスとの通信中にエラーが発生しました。'], 502);
        }

        return response()->json([
            'total_items' => $result['total_items'],
            'items' => GoogleBookResource::collection($result['items']),
        ]);
    }

    /**
     * 家族の絵本一覧を取得する。 GET /api/families/{family}/picture-books
     *
     * @param  IndexBookRequest  $request  絵本一覧取得リクエスト
     * @param  Family  $family  家族モデル
     * @param  ListBooksHandler  $handler  一覧取得ハンドラー
     * @return PictureBookCollection
     */
    public function index(IndexBookRequest $request, Family $family, ListBooksHandler $handler)
    {
        $this->authorize('view', $family);

        $result = $handler->handle(new ListBooksQuery(
            familyId: $family->id,
            status: $request->status(),
            sort: $request->sort(),
            order: $request->order(),
            perPage: $request->perPage(),
        ));

        return new PictureBookCollection($result);
    }

    /**
     * 家族に新しい絵本を追加する。 POST /api/families/{family}/picture-books
     *
     * @param  StoreBookRequest  $request  絵本登録リクエスト
     * @param  Family  $family  家族モデル
     * @param  AddBookHandler  $handler  絵本追加ハンドラー
     * @return PictureBookResource|JsonResponse
     */
    public function store(StoreBookRequest $request, Family $family, AddBookHandler $handler)
    {
        $this->authorize('update', $family);

        try {
            $book = $handler->handle(new AddBookCommand(
                familyId: $family->id,
                userId: $request->user()->id,
                googleBooksId: $request->validated('google_books_id'),
                isbn: $request->validated('isbn'),
                title: $request->validated('title'),
                authors: $request->validated('authors'),
                thumbnailUrl: $request->validated('thumbnail_url'),
            ));

            $eloquentBook = PictureBook::find($book->id()->value());

            return (new PictureBookResource($eloquentBook))->response()->setStatusCode(201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * 絵本の詳細を取得する。 GET /api/families/{family}/picture-books/{pictureBook}
     *
     * @param  Family  $family  家族モデル
     * @param  PictureBook  $pictureBook  絵本モデル
     * @return PictureBookResource
     */
    public function show(Family $family, PictureBook $pictureBook)
    {
        $this->authorize('manage', $pictureBook);

        return new PictureBookResource($pictureBook);
    }

    /**
     * 絵本の読書情報を更新する。 PUT /api/families/{family}/picture-books/{pictureBook}
     *
     * @param  UpdateBookRequest  $request  絵本更新リクエスト
     * @param  Family  $family  家族モデル
     * @param  PictureBook  $pictureBook  絵本モデル
     * @param  UpdateBookHandler  $handler  絵本更新ハンドラー
     * @return PictureBookResource
     */
    public function update(UpdateBookRequest $request, Family $family, PictureBook $pictureBook, UpdateBookHandler $handler)
    {
        $this->authorize('manage', $pictureBook);

        $handler->handle(new UpdateBookCommand(
            bookId: $pictureBook->id,
            rating: $request->validated('rating'),
            readStatus: $request->validated('read_status'),
            review: $request->validated('review'),
        ));

        return new PictureBookResource($pictureBook->fresh());
    }

    /**
     * 絵本を削除する。 DELETE /api/families/{family}/picture-books/{pictureBook}
     *
     * @param  Family  $family  家族モデル
     * @param  PictureBook  $pictureBook  絵本モデル
     * @param  RemoveBookHandler  $handler  絵本削除ハンドラー
     * @return JsonResponse
     */
    public function destroy(Family $family, PictureBook $pictureBook, RemoveBookHandler $handler)
    {
        $this->authorize('manage', $pictureBook);

        $handler->handle(new RemoveBookCommand($pictureBook->id));

        return response()->json(null, 204);
    }
}
