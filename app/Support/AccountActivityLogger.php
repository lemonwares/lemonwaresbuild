<?php

namespace App\Support;

use App\Models\AccountActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AccountActivityLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function log(
        User $owner,
        string $type,
        string $title,
        ?string $body = null,
        string $actorType = 'system',
        ?Model $related = null,
        array $meta = [],
    ): void {
        $owner = $owner->accountOwner();

        try {
            AccountActivity::query()->create([
                'user_id' => $owner->id,
                'actor_type' => $actorType,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'meta' => $meta === [] ? null : $meta,
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
