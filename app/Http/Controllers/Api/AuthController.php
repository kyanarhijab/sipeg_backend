<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'employee_code' => 'required|string',
            'password'      => 'required|string',
        ]);

        
        $user = User::where('employee_code', $request->employee_code)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kode Karyawan atau kata sandi salah. Silakan coba lagi.'
            ], 401);
        }

        
        $user->tokens()->delete();
        $token = $user->createToken('sipeg_auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil',
            'user'    => [
                'id'            => $user->id,
                'employee_code' => $user->employee_code,
                'name'          => $user->name,
                'role'          => 'Admin HRD',
            ],
            'token'   => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil'
        ]);
    }
}