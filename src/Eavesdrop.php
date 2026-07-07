<?php

namespace ModernMcGuire\Eavesdrop;

use Illuminate\Database\Eloquent\Model;

class Eavesdrop extends Model
{
    protected $table = 'eavesdrops';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
