<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;


class AdminUserController extends Controller
{
    /* ================= USER LIST ================= */
    public function index()
    {
        return response()->json(
            User::select('id','name','email','phone','is_active')->whereNotNull('email')
                ->orderBy('id','desc')
                ->paginate(10)
        );
    }

    /* ================= USER DETAILS ================= */
    public function show($id)
    {
        $user = User::with('userDetail')->findOrFail($id);
        return response()->json($user);
    }

    /* ================= STATUS TOGGLE ================= */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'status' => true,
            'is_active' => $user->is_active
        ]);
    }
}
