<?php

namespace App\Policies;

use App\Models\ReadRecord;
use App\Models\User;

/**
 * 読み聞かせ記録アクションの認可ポリシー。
 *
 * ユーザーが自分の家族に属する記録のみ操作できることを保証する。
 */
class ReadRecordPolicy
{
    /**
     * ユーザーが記録の閲覧・更新・削除を行えるかを判定する。
     *
     * ユーザーが記録と同じ家族に属している場合に認可される。
     *
     * @param User $user ユーザーモデル
     * @param ReadRecord $readRecord 読み聞かせ記録モデル
     * @return bool
     */
    public function manage(User $user, ReadRecord $readRecord): bool
    {
        return $user->family_id === $readRecord->family_id;
    }
}
