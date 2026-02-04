<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;


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

    /* ================= Add USER ================= */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // users table
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|unique:users,email',
            'phone'  => 'required|digits:10|unique:users,phone',

            // user_details table
            'gender'        => 'required|in:Male,Female,Others',
            'address'       => 'required|string',
            'pincode'       => 'required|digits:6',
            'qualification' => 'required|string',
            'dob'           => [
                'required',
                'regex:/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Convert DOB
            $dob = Carbon::createFromFormat('d/m/Y', $request->dob)->format('Y-m-d');

            // 1️⃣ Create User
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'phone'     => $request->phone,
                'password'  => Hash::make('123456'), // default password
                'is_active' => 1,
            ]);

            // 2️⃣ Generate Registration No
            $regNo = $this->generateRegNo();

            // 3️⃣ Create User Detail
            $userDetail = UserDetail::create([
                'user_id'       => $user->id,
                'reg_no'        => $regNo,
                'gender'        => $request->gender,
                'address'       => $request->address,
                'pincode'       => $request->pincode,
                'qualification' => $request->qualification,
                'dob'           => $dob,
                'created_by'    => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'User created successfully',
                'data'    => [
                    'user'        => $user,
                    'user_detail' => $userDetail,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Failed to create user',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function generateRegNo(): string
    {
        $year = date('Y');

        $last = UserDetail::where('reg_no', 'like', 'SDP/' . $year . '/%')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;

        if ($last && preg_match('/SDP\/' . $year . '\/(\d{4})$/', $last->reg_no, $m)) {
            $lastNumber = (int) $m[1];
        }

        return 'SDP/' . $year . '/' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
