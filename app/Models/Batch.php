<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use SoftDeletes;
    protected $guarded = ['_token'];

    public $casts = [
        'additional_field' => 'array',
    ];
}
