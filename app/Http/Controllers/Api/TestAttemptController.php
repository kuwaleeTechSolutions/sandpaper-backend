<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Question;
use App\Models\Option;

class TestAttemptController extends Controller {

    public function start($testId) {
        return TestAttempt::create([
            'test_id' => $testId,
            'user_id' => auth()->id(),
            'started_at' => now()
        ]);
    }

    public function answer(Request $request, $id) {
        Answer::updateOrCreate(
            [
                'test_attempt_id' => $id,
                'question_id' => $request->question_id
            ],
            [
                'option_id' => $request->option_id,
                'descriptive_answer' => $request->descriptive_answer
            ]
        );
        return response()->json(['saved' => true]);
    }

    public function submit($id) {
        $attempt = TestAttempt::with('answers.question.options')->findOrFail($id);
        $total = 0;

        foreach ($attempt->answers as $answer) {
            if ($answer->question->type === 'mcq') {
                $correct = $answer->question->options
                    ->where('is_correct', true)->first();

                $answer->marks_obtained =
                    ($correct && $answer->option_id == $correct->id)
                    ? $answer->question->marks
                    : 0;

                $total += $answer->marks_obtained;
                $answer->save();
            }
        }

        $attempt->update([
            'submitted_at' => now(),
            'total_marks' => $total,
            'status' => 'submitted'
        ]);

        return response()->json(['total_marks' => $total]);
    }
}

