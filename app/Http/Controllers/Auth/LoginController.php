<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->only(['email', 'password']);

        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Try to authenticate the user using credentials and issue a personal token.
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['error' => 'invalid_credentials'], 401);
        }

        $tokenResult = $user->createToken('api-token');
        $token = $tokenResult->accessToken ?? $tokenResult->plainTextToken ?? null;

        if (! $token) {
            return response()->json(['error' => 'token_error', 'message' => 'failed_to_create_token'], 500);
        }

        return response()->json([
            'user' => $user->toArray(),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => null,
        ]);
    }
}
