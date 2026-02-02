<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Test;
use App\Models\Question;
use App\Models\Option;
use App\Models\QuestionSet;
use Illuminate\Support\Facades\DB;

class TestManagementController extends Controller
{
    /**
     * List all tests
     */
    public function index()
    {
        return Test::with('questionSet')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new test
     */
    public function createTest(Request $request)
    {
        $request->validate([
            'title'              => 'required|string',
            'description'        => 'nullable|string',
            'question_set_id'    => 'required|exists:question_sets,id',
            'duration_minutes'   => 'required|integer|min:1',
        ]);

        return Test::create([
            'title'            => $request->title,
            'description'      => $request->description,
            'question_set_id'  => $request->question_set_id,
            'duration_minutes' => $request->duration_minutes,
            'status'           => 'draft',
        ]);
    }

    /**
     * Create a question in Question Bank
     */
    public function createQuestion(Request $request)
    {
        $request->validate([
            'type'       => 'required|in:mcq,descriptive',
            'question'   => 'required|string',
            'subject'    => 'nullable|string',
            'tag'        => 'nullable|string',
            'difficulty' => 'nullable|in:easy,medium,hard',

            'options' => 'required_if:type,mcq|array|min:2',
            'options.*.text' => 'required|string',
            'options.*.is_correct' => 'boolean',
        ]);

        if ($request->type === 'mcq') {
            $correctCount = collect($request->options)
                ->where('is_correct', true)
                ->count();

            if ($correctCount !== 1) {
                return response()->json([
                    'message' => 'Exactly one option must be correct'
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            $question = Question::create([
                'type'       => $request->type,
                'question'   => $request->question,
                'subject'    => $request->subject,
                'tag'        => $request->tag,
                'difficulty' => $request->difficulty,
                'created_by' => auth()->id(),
            ]);

            if ($request->type === 'mcq') {
                foreach ($request->options as $index => $opt) {
                    $question->options()->create([
                        'option_text' => $opt['text'],
                        'is_correct'  => $opt['is_correct'] ?? false,
                    ]);
                }
            }

            DB::commit();

            return response()->json(
                $question->load('options'),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create question',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all questions (Question Bank)
     */
    public function listQuestions()
    {
        return Question::select('id', 'question', 'type', 'subject', 'difficulty')
            ->orderBy('created_at', 'desc')
            ->get();
    }



    /**
     * Create a Question Set
     */
    public function createQuestionSet(Request $request)
    {
        $request->validate([
            'name'                   => 'required|string',
            'default_marks'          => 'required|integer|min:1',
            'default_negative_marks' => 'nullable|integer|min:0',
            'question_ids'           => 'required|array|min:1',
        ]);

        DB::beginTransaction();

        try {
            $questionSet = QuestionSet::create([
                'name'                   => $request->name,
                'default_marks'          => $request->default_marks,
                'default_negative_marks' => $request->default_negative_marks ?? 0,
            ]);

            foreach ($request->question_ids as $questionId) {
                $questionSet->questions()->attach($questionId);
            }

            DB::commit();
            return response()->json($questionSet, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Publish a test
     */
    public function publish($testId)
    {
        $test = Test::findOrFail($testId);

        if (!$test->questionSet || $test->questionSet->questions()->count() === 0) {
            return response()->json([
                'error' => 'Cannot publish test without questions'
            ], 422);
        }

        $test->update(['status' => 'published']);

        return response()->json(['published' => true]);
    }
}
