<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserDetailController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'        => 'required|exists:users,id',
            'gender'         => 'required|in:Male,Female,Others',
            'alt_mobile'     => 'nullable|digits:10',
            'alt_email'      => 'nullable|email',
            'address'        => 'required|string',
            'pincode'        => 'required|digits:6',
            'qualification'  => 'required|string',
            'dob'            => [
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

        // Convert DOB: dd/mm/yyyy → Y-m-d
        $dob = Carbon::createFromFormat('d/m/Y', $request->dob)->format('Y-m-d');

       $userDetail = UserDetail::where('user_id', $request->user_id)->first();

        if (!$userDetail) {
            // First time insert → generate reg_no
            $regNo = $this->generateRegNo();

            $userDetail = UserDetail::create([
                'user_id'           => $request->user_id,
                'reg_no'            => $regNo,
                'gender'            => $request->gender,
                'alt_mobile'        => $request->alt_mobile,
                'alt_email'         => $request->alt_email,
                'address'           => $request->address,
                'pincode'           => $request->pincode,
                'qualification'     => $request->qualification,
                'dob'               => $dob,
                'additional_field'  => $request->additional_field,
                'created_by'        => $request->user_id,
            ]);
        } else {
            // Update existing record → DO NOT change reg_no
            $userDetail->update([
                'gender'           => $request->gender,
                'alt_mobile'       => $request->alt_mobile,
                'alt_email'        => $request->alt_email,
                'address'          => $request->address,
                'pincode'          => $request->pincode,
                'qualification'    => $request->qualification,
                'dob'              => $dob,
                'additional_field' => $request->additional_field,
            ]);
        }


        return response()->json([
            'status'  => true,
            'message' => 'User details saved successfully',
            'reg_no'  => $userDetail->reg_no,
            'data'    => $userDetail,
        ], 200);
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
