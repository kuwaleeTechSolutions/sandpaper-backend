<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Option;

class TestController extends Controller {

    public function index() {
        return Test::where('status', 'published')->get();
    }

    public function show($id) {
        return Test::with('questions.options')->findOrFail($id);
    }
}

