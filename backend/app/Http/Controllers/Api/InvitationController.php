<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendInvitationRequest;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\InvitationResource;
use App\Models\Family;
use App\Models\FamilyInvitation;
use Illuminate\Support\Facades\DB;
use Packages\Family\Application\Command\AcceptInvitation\AcceptInvitationCommand;
use Packages\Family\Application\Command\AcceptInvitation\AcceptInvitationHandler;
use Packages\Family\Application\Command\CancelInvitation\CancelInvitationCommand;
use Packages\Family\Application\Command\CancelInvitation\CancelInvitationHandler;
use Packages\Family\Application\Command\SendInvitation\SendInvitationCommand;
use Packages\Family\Application\Command\SendInvitation\SendInvitationHandler;
use Packages\Family\Application\Query\ListInvitations\ListInvitationsHandler;
use Packages\Family\Application\Query\ListInvitations\ListInvitationsQuery;

class InvitationController extends Controller
{
    public function store(SendInvitationRequest $request, Family $family, SendInvitationHandler $handler)
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

    public function index(Family $family, ListInvitationsHandler $handler)
    {
        $this->authorize('view', $family);

        $invitations = $handler->handle(new ListInvitationsQuery($family->id));

        return InvitationResource::collection($invitations);
    }

    public function destroy(Family $family, FamilyInvitation $invitation, CancelInvitationHandler $handler)
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

    public function show(string $token)
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

    public function accept(string $token, AcceptInvitationHandler $handler)
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
