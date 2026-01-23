<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * SEND OTP
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        // ✅ OTP logic by environment
        if (app()->environment('local')) {
            $otp = '123456';
        } else {
            $otp = rand(100000, 999999);
            $this->sendFast2Sms($request->phone, $otp);
        }

        Otp::updateOrCreate(
            ['phone' => $request->phone],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]
        );

        // Log OTP in local for dev
        \Log::info("OTP for {$request->phone} is {$otp}");

        return response()->json([
            'message' => 'OTP sent successfully'
        ]);
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
}
