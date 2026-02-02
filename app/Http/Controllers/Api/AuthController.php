<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Otp;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * SEND OTP
     */
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required']);

        if (app()->environment('local')) {
            $otp = '123456';
        } else {
            $otp = rand(100000, 999999);
            // 🔴 Call Fast2SMS here
        }

        Otp::updateOrCreate(
            ['phone' => $request->phone],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5)
            ]
        );

        Log::info("OTP for {$request->phone}: {$otp}");

        return response()->json(['message' => 'OTP sent']);
    }


    /**
     * VERIFY OTP & LOGIN / REGISTER
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string'
        ]);

        // Dev shortcut
        if (app()->environment('local') && $request->otp === '123456') {
            return $this->loginUser($request->phone);
        }

        $otpRecord = Otp::where('phone', $request->phone)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (! $otpRecord) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $otpRecord->delete();

        return $this->loginUser($request->phone);
    }

    /**
     * COMMON LOGIN METHOD
     */
    private function loginUser(string $phone)
    {
        $user = User::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => 'Student',
                'role' => 'student'
            ]
        );

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * FAST2SMS SENDER
     */
    private function sendFast2Sms(string $phone, string $otp)
    {
        Http::withHeaders([
            'authorization' => config('services.fast2sms.key'),
            'content-type' => 'application/json',
        ])->post('https://www.fast2sms.com/dev/bulkV2', [
            'route' => 'otp',
            'variables_values' => $otp,
            'numbers' => $phone,
        ]);
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    /**
     * ME
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
 /* ---------- Complete profile code ---------- */
    public function completeProfile(Request $request)
    {
        $user = $request->user(); // token-based user

        /* ---------- Validation ---------- */
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'gender'        => 'required|in:Male,Female,Others',
            'address'       => 'required|string',
            'pincode'       => 'required|digits:6',
            'qualification' => 'required|string',
            'dob' => [
                'required',
                'regex:/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        /* ---------- DOB Conversion ---------- */
        $dob = Carbon::createFromFormat('d/m/Y', $request->dob)->format('Y-m-d');

        DB::beginTransaction();

        try {
            /* ---------- Update USERS ---------- */
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'profile_completed' => 1
            ]);

            /* ---------- USER DETAILS ---------- */
            $userDetail = UserDetail::where('user_id', $user->id)->first();

            if (!$userDetail) {
                $regNo = $this->generateRegNo();

                $userDetail = UserDetail::create([
                    'user_id'        => $user->id,
                    'reg_no'         => $regNo,
                    'gender'         => $request->gender,
                    'address'        => $request->address,
                    'pincode'        => $request->pincode,
                    'qualification'  => $request->qualification,
                    'dob'            => $dob,
                    'created_by'     => $user->id,
                ]);
            } else {
                $userDetail->update([
                    'gender'        => $request->gender,
                    'address'       => $request->address,
                    'pincode'       => $request->pincode,
                    'qualification' => $request->qualification,
                    'dob'           => $dob,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Profile completed successfully',
                'reg_no'  => $userDetail->reg_no,
                'user'    => $user,
                'details' => $userDetail
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Profile completion failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to complete profile'
            ], 500);
        }
    }

    private function generateRegNo(): string
    {
        $year = date('Y');

        // Get last reg_no for current year
        $lastRecord = \App\Models\UserDetail::where('reg_no', 'like', 'SDP/' . $year . '/%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRecord && $lastRecord->reg_no) {
            // Extract last 4-digit number
            preg_match('/SDP\/' . $year . '\/(\d{4})$/', $lastRecord->reg_no, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
        } else {
            $lastNumber = 0;
        }

        $nextNumber = $lastNumber + 1;

        return 'SDP/' . $year . '/' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

}
