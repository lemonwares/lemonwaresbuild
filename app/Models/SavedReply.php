<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedReply extends Model
{
    protected $fillable = [
        'title',
        'body',
    ];
}
