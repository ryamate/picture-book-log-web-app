<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReadRecordRequest;
use App\Http\Requests\UpdateReadRecordRequest;
use App\Http\Resources\ReadRecordCollection;
use App\Http\Resources\ReadRecordResource;
use App\Models\Family;
use App\Models\ReadRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Packages\ReadLog\Application\Command\CreateRecord\CreateRecordCommand;
use Packages\ReadLog\Application\Command\CreateRecord\CreateRecordHandler;
use Packages\ReadLog\Application\Command\DeleteRecord\DeleteRecordCommand;
use Packages\ReadLog\Application\Command\DeleteRecord\DeleteRecordHandler;
use Packages\ReadLog\Application\Command\UpdateRecord\UpdateRecordCommand;
use Packages\ReadLog\Application\Command\UpdateRecord\UpdateRecordHandler;
use Packages\ReadLog\Application\Exception\InvalidOwnershipException;
use Packages\ReadLog\Application\Query\GetRecord\GetRecordHandler;
use Packages\ReadLog\Application\Query\GetRecord\GetRecordQuery;
use Packages\ReadLog\Application\Query\ListRecords\ListRecordsHandler;
use Packages\ReadLog\Application\Query\ListRecords\ListRecordsQuery;

/**
 * 読み聞かせ記録のCRUD操作のAPIコントローラー。
 */
class ReadRecordController extends Controller
{
    /**
     * 読み聞かせ記録一覧を取得する。 GET /api/v1/families/{family}/records
     *
     * @param  Request  $request  リクエスト
     * @param  Family  $family  家族モデル
     * @param  ListRecordsHandler  $handler  一覧取得ハンドラー
     */
    public function index(Request $request, Family $family, ListRecordsHandler $handler): ReadRecordCollection
    {
        $this->authorize('view', $family);

        $result = $handler->handle(new ListRecordsQuery(
            familyId: $family->id,
            childId: $request->query('child_id') ? (int) $request->query('child_id') : null,
            pictureBookId: $request->query('picture_book_id') ? (int) $request->query('picture_book_id') : null,
            dateFrom: $request->query('date_from'),
            dateTo: $request->query('date_to'),
            perPage: min((int) $request->query('per_page', 20), 100),
            page: (int) $request->query('page', 1),
        ));

        return new ReadRecordCollection($result);
    }

    /**
     * 読み聞かせ記録を作成する。 POST /api/v1/families/{family}/records
     *
     * @param  StoreReadRecordRequest  $request  読み聞かせ記録作成リクエスト
     * @param  Family  $family  家族モデル
     * @param  CreateRecordHandler  $handler  記録作成ハンドラー
     */
    public function store(StoreReadRecordRequest $request, Family $family, CreateRecordHandler $handler): JsonResponse
    {
        $this->authorize('update', $family);

        // children 配列を [child_id => reaction] 形式に変換
        $childReactions = [];
        foreach ($request->validated('children') as $child) {
            $childReactions[$child['child_id']] = $child['reaction'] ?? null;
        }

        try {
            $record = $handler->handle(new CreateRecordCommand(
                pictureBookId: $request->validated('picture_book_id'),
                familyId: $family->id,
                userId: $request->user()->id,
                readDate: $request->validated('read_date'),
                memo: $request->validated('memo'),
                childReactions: $childReactions,
                tags: $request->validated('tags') ?? [],
            ));
        } catch (InvalidOwnershipException $e) {
            throw ValidationException::withMessages([$e->field => $e->getMessage()]);
        }

        $eloquentRecord = ReadRecord::with(['children', 'tags', 'pictureBook', 'recordedByUser'])
            ->find($record->id()->value());

        return (new ReadRecordResource($eloquentRecord))->response()->setStatusCode(201);
    }

    /**
     * 読み聞かせ記録の詳細を取得する。 GET /api/v1/families/{family}/records/{readRecord}
     *
     * @param  Family  $family  家族モデル
     * @param  ReadRecord  $readRecord  読み聞かせ記録モデル
     * @param  GetRecordHandler  $handler  記録取得ハンドラー
     */
    public function show(Family $family, ReadRecord $readRecord, GetRecordHandler $handler): ReadRecordResource
    {
        $this->authorize('manage', $readRecord);

        $record = $handler->handle(new GetRecordQuery($readRecord->id));

        return new ReadRecordResource($record);
    }

    /**
     * 読み聞かせ記録を更新する。 PUT /api/v1/families/{family}/records/{readRecord}
     *
     * @param  UpdateReadRecordRequest  $request  読み聞かせ記録更新リクエスト
     * @param  Family  $family  家族モデル
     * @param  ReadRecord  $readRecord  読み聞かせ記録モデル
     * @param  UpdateRecordHandler  $handler  記録更新ハンドラー
     */
    public function update(UpdateReadRecordRequest $request, Family $family, ReadRecord $readRecord, UpdateRecordHandler $handler): ReadRecordResource
    {
        $this->authorize('manage', $readRecord);

        // children 配列を [child_id => reaction] 形式に変換
        $childReactions = [];
        foreach ($request->validated('children') as $child) {
            $childReactions[$child['child_id']] = $child['reaction'] ?? null;
        }

        try {
            $handler->handle(new UpdateRecordCommand(
                recordId: $readRecord->id,
                readDate: $request->validated('read_date'),
                memo: $request->validated('memo'),
                childReactions: $childReactions,
                tags: $request->validated('tags') ?? [],
            ));
        } catch (InvalidOwnershipException $e) {
            throw ValidationException::withMessages([$e->field => $e->getMessage()]);
        }

        $updatedRecord = ReadRecord::with(['children', 'tags', 'pictureBook', 'recordedByUser'])
            ->find($readRecord->id);

        return new ReadRecordResource($updatedRecord);
    }

    /**
     * 読み聞かせ記録を削除する。 DELETE /api/v1/families/{family}/records/{readRecord}
     *
     * @param  Family  $family  家族モデル
     * @param  ReadRecord  $readRecord  読み聞かせ記録モデル
     * @param  DeleteRecordHandler  $handler  記録削除ハンドラー
     */
    public function destroy(Family $family, ReadRecord $readRecord, DeleteRecordHandler $handler): JsonResponse
    {
        $this->authorize('manage', $readRecord);

        $handler->handle(new DeleteRecordCommand($readRecord->id));

        return response()->json(null, 204);
    }
}
