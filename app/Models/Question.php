<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'type',
        'question',
        'subject',
        'tag',
        'difficulty',
        'created_by',
    ];

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function questionSets()
    {
        return $this->belongsToMany(
            QuestionSet::class,
            'question_set_questions'
        )->withPivot('marks_override', 'negative_marks_override');
    }
}
