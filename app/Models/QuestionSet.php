<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionSet extends Model
{
    protected $fillable = [
        'name',
        'default_marks',
        'default_negative_marks'
    ];

    public function questions()
    {
        return $this->belongsToMany(
            Question::class,
            'question_set_questions'
        )->withPivot('marks_override','negative_marks_override');
    }

    public function tests()
    {
        return $this->hasMany(Test::class);
    }
}

