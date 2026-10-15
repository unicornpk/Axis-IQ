<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IqConversation extends Model
{
    protected $fillable = [
        'product',
        'query',
        'answer',
        'tools_called',
        'user_fingerprint',
    ];

    protected $casts = [
        'tools_called' => 'array',
    ];
}
