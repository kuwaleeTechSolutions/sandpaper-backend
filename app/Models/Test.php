<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $fillable = [
        'title',
        'description',
        'question_set_id',
        'duration_minutes',
        'status',
        'start_time',
        'end_time'
    ];

    public function questionSet()
    {
        return $this->belongsTo(QuestionSet::class);
    }
}
