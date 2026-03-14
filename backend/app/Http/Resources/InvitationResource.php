<?php

namespace App\Http\Resources;

use App\Models\FamilyInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FamilyInvitation */
class InvitationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'status' => $this->getStatus(),
            'invited_by' => [
                'id' => $this->invitedByUser->id,
                'name' => $this->invitedByUser->name,
            ],
            'expires_at' => $this->expires_at->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    private function getStatus(): string
    {
        if ($this->accepted_at !== null) {
            return 'accepted';
        }
        if ($this->expires_at->isPast()) {
            return 'expired';
        }

        return 'pending';
    }
}
