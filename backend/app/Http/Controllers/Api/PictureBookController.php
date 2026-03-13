<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\GoogleBookResource;
use App\Http\Resources\PictureBookCollection;
use App\Http\Resources\PictureBookResource;
use App\Models\Family;
use App\Models\PictureBook;
use DomainException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
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
 * API controller for picture book CRUD operations and Google Books search.
 */
class PictureBookController extends Controller
{
    /**
     * Search Google Books by keyword. GET /api/google-books/search
     *
     * @param Request $request
     * @param SearchGoogleBooksHandler $handler
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, SearchGoogleBooksHandler $handler)
    {
        $request->validate(['q' => ['required', 'string', 'min:1']]);

        try {
            $result = $handler->handle(new SearchGoogleBooksQuery(
                keyword: $request->query('q'),
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
     * List picture books for a family. GET /api/families/{family}/picture-books
     *
     * @param Request $request
     * @param Family $family
     * @param ListBooksHandler $handler
     * @return PictureBookCollection
     */
    public function index(Request $request, Family $family, ListBooksHandler $handler)
    {
        $this->authorize('view', $family);

        $result = $handler->handle(new ListBooksQuery(
            familyId: $family->id,
            status: $request->query('status'),
            sort: $request->query('sort', 'created_at'),
            order: $request->query('order', 'desc'),
            perPage: min((int) $request->query('per_page', 20), 100),
        ));

        return new PictureBookCollection($result);
    }

    /**
     * Add a new picture book to a family. POST /api/families/{family}/picture-books
     *
     * @param StoreBookRequest $request
     * @param Family $family
     * @param AddBookHandler $handler
     * @return PictureBookResource|\Illuminate\Http\JsonResponse
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
     * Show a single picture book. GET /api/families/{family}/picture-books/{pictureBook}
     *
     * @param Family $family
     * @param PictureBook $pictureBook
     * @return PictureBookResource
     */
    public function show(Family $family, PictureBook $pictureBook)
    {
        $this->authorize('manage', $pictureBook);

        return new PictureBookResource($pictureBook);
    }

    /**
     * Update a picture book's reading info. PUT /api/families/{family}/picture-books/{pictureBook}
     *
     * @param UpdateBookRequest $request
     * @param Family $family
     * @param PictureBook $pictureBook
     * @param UpdateBookHandler $handler
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
     * Delete a picture book. DELETE /api/families/{family}/picture-books/{pictureBook}
     *
     * @param Family $family
     * @param PictureBook $pictureBook
     * @param RemoveBookHandler $handler
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Family $family, PictureBook $pictureBook, RemoveBookHandler $handler)
    {
        $this->authorize('manage', $pictureBook);

        $handler->handle(new RemoveBookCommand($pictureBook->id));

        return response()->json(null, 204);
    }
}
