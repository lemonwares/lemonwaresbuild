<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'email_order_id',
    'local_part',
    'address',
    'trekmail_mailbox_id',
    'trekmail_invite_id',
    'status',
    'error_message',
])]
class EmailMailbox extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(EmailOrder::class, 'email_order_id');
    }

    public function statusLabel(): string
    {
        return __('account.mailbox_status.' . ($this->status ?: 'pending'));
    }
}
