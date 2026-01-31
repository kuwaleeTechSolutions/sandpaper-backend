<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
   use SoftDeletes;
    protected $guarded = ['_token'];
    public $casts = [
        'additional_field' => 'array',
    ];
}
