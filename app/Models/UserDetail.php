<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDetail extends Model
{
   use SoftDeletes;
    protected $guarded = ['_token'];
    public $casts = [
        'additional_field' => 'array',
    ];
    // App\Models\UserDetail.php
protected $fillable = [
    'user_id',
    'reg_no',
    'gender',
    'address',
    'pincode',
    'qualification',
    'dob',
    'created_by',
];

    public function user(){
        return $this->belongsTo(User::class,'user_id')->withDefault();
    }

}
