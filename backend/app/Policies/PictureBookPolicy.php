<?php

namespace App\Policies;

use App\Models\PictureBook;
use App\Models\User;

/**
 * 絵本アクションの認可ポリシー。
 *
 * ユーザーが自分の家族に属する絵本のみ操作できることを保証する。
 */
class PictureBookPolicy
{
    /**
     * ユーザーが絵本の閲覧・更新・削除を行えるかを判定する。
     *
     * ユーザーが絵本と同じ家族に属している場合に認可される。
     *
     * @param  User  $user  ユーザーモデル
     * @param  PictureBook  $pictureBook  絵本モデル
     */
    public function manage(User $user, PictureBook $pictureBook): bool
    {
        return $user->family_id === $pictureBook->family_id;
    }
}
