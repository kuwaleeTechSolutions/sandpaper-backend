<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'name'             => auth()->user()->name ?? 'Admin',
            'totalStudents'    => 250,     // ← replace with real query later
            'totalTeachers'    => 15,
            'todayAttendance'  => 92,
            'pendingFees'      => 45000,
            'upcomingExams'    => 3,
            'recentAdmissions' => 12,
        ]);
    }
}