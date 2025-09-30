<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->only(['name', 'email', 'password', 'password_confirmation']);

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Создаём персональный access token для пользователя (bypass password grant issues)
        $tokenResult = $user->createToken('api-token');
        $token = $tokenResult->accessToken ?? $tokenResult->plainTextToken ?? null;

        if (! $token) {
            return response()->json(['error' => 'token_error', 'message' => 'failed_to_create_token'], 500);
        }

        return response()->json(array_merge(['user' => $user->toArray()], [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => null,
        ]));
    }

    protected function issuePasswordToken(string $email, string $password)
    {
        // Legacy method kept for reference. Prefer direct createToken in current environment.
        return ['error' => 'not_supported', 'message' => 'password grant issuance disabled in this environment'];
    }
}
