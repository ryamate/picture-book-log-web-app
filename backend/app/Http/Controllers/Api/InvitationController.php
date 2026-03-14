<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendInvitationRequest;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\InvitationResource;
use App\Models\Family;
use App\Models\FamilyInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Packages\Family\Application\Command\AcceptInvitation\AcceptInvitationCommand;
use Packages\Family\Application\Command\AcceptInvitation\AcceptInvitationHandler;
use Packages\Family\Application\Command\CancelInvitation\CancelInvitationCommand;
use Packages\Family\Application\Command\CancelInvitation\CancelInvitationHandler;
use Packages\Family\Application\Command\SendInvitation\SendInvitationCommand;
use Packages\Family\Application\Command\SendInvitation\SendInvitationHandler;
use Packages\Family\Application\Query\ListInvitations\ListInvitationsHandler;
use Packages\Family\Application\Query\ListInvitations\ListInvitationsQuery;
use Throwable;

/**
 * 家族招待 API コントローラー
 *
 * 家族グループへの招待の送信・一覧取得・キャンセル・詳細表示・承認を提供する。
 */
class InvitationController extends Controller
{
    /**
     * 招待を送信する
     *
     * 指定された家族グループに対して、メールアドレス宛に招待を送信する。
     *
     * @param  SendInvitationRequest  $request  招待送信リクエスト
     * @param  Family  $family  招待先の家族グループ
     * @param  SendInvitationHandler  $handler  招待送信ハンドラー
     *
     * @throws Throwable
     */
    public function store(SendInvitationRequest $request, Family $family, SendInvitationHandler $handler): JsonResponse
    {
        $this->authorize('update', $family);

        $invitation = DB::transaction(fn () => $handler->handle(new SendInvitationCommand(
            familyId: $family->id,
            invitedByUserId: $request->user()->id,
            email: $request->validated('email'),
        )));

        $eloquentInvitation = FamilyInvitation::with('invitedByUser')->find($invitation->id()->value());

        return response()->json([
            'message' => '招待メールを送信しました。',
            'invitation' => new InvitationResource($eloquentInvitation),
        ], 201);
    }

    /**
     * 招待一覧を取得する
     *
     * 指定された家族グループの招待一覧を返す。
     *
     * @param  Family  $family  対象の家族グループ
     * @param  ListInvitationsHandler  $handler  招待一覧取得ハンドラー
     */
    public function index(Family $family, ListInvitationsHandler $handler): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $invitations = $handler->handle(new ListInvitationsQuery($family->id));

        return InvitationResource::collection($invitations);
    }

    /**
     * 招待をキャンセルする
     *
     * 指定された家族グループの招待を取り消す。
     *
     * @param  Family  $family  対象の家族グループ
     * @param  FamilyInvitation  $invitation  キャンセル対象の招待
     * @param  CancelInvitationHandler  $handler  招待キャンセルハンドラー
     */
    public function destroy(Family $family, FamilyInvitation $invitation, CancelInvitationHandler $handler): JsonResponse
    {
        $this->authorize('update', $family);

        if ($invitation->family_id !== $family->id) {
            abort(403);
        }

        $handler->handle(new CancelInvitationCommand(
            invitationId: $invitation->id,
        ));

        return response()->json(['message' => '招待をキャンセルしました。']);
    }

    /**
     * 招待の詳細を取得する
     *
     * トークンに対応する招待情報（メールアドレス・家族名・有効期限・承認状態）を返す。
     *
     * @param  string  $token  招待トークン
     */
    public function show(string $token): JsonResponse
    {
        $invitation = FamilyInvitation::with('family')->where('token', $token)->first();

        if (! $invitation) {
            abort(404, '招待が見つかりません。');
        }

        /** @var Family $family */
        $family = $invitation->family;

        return response()->json([
            'email' => $invitation->email,
            'family_name' => $family->name,
            'is_expired' => $invitation->expires_at->isPast(),
            'is_accepted' => $invitation->accepted_at !== null,
        ]);
    }

    /**
     * 招待を承認する
     *
     * トークンに対応する招待を承認し、認証ユーザーを家族グループに追加する。
     *
     * @param  string  $token  招待トークン
     * @param  AcceptInvitationHandler  $handler  招待承認ハンドラー
     *
     * @throws Throwable
     */
    public function accept(string $token, AcceptInvitationHandler $handler): JsonResponse
    {
        $invitation = DB::transaction(fn () => $handler->handle(new AcceptInvitationCommand(
            token: $token,
            userId: request()->user()->id,
        )));

        $family = Family::find($invitation->familyId()->value());

        return response()->json([
            'message' => '家族に参加しました。',
            'family' => new FamilyResource($family),
        ]);
    }
}
