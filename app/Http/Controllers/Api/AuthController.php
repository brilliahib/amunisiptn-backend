<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => 'user',
        ]);

        $tokenRaw = $user->createToken('auth-token', ['access'], now()->addMinutes(120))->plainTextToken;
        $token = explode('|', $tokenRaw, 2)[1];

        $refreshTokenRaw = $user->createToken('refresh-token', ['refresh'], now()->addDays(7))->plainTextToken;
        $refreshToken = explode('|', $refreshTokenRaw, 2)[1];

        AuditLogger::log('Auth', 'register', "Pengguna baru mendaftar: {$user->name} ({$user->email})", $user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => 7200,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        
        if ($user && !$user->password) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini terdaftar menggunakan Google. Silakan login menggunakan Google OAuth.'],
            ]);
        }
        
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // --- SINGLE DEVICE LOGIN ---
        $user->tokens()->delete();

        $tokenRaw = $user->createToken('auth-token', ['access'], now()->addMinutes(120))->plainTextToken;
        $token = explode('|', $tokenRaw, 2)[1];

        $refreshTokenRaw = $user->createToken('refresh-token', ['refresh'], now()->addDays(7))->plainTextToken;
        $refreshToken = explode('|', $refreshTokenRaw, 2)[1];

        AuditLogger::log('Auth', 'login', "Login berhasil: {$user->name} ({$user->email})", $user);

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => 7200,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        AuditLogger::log('Auth', 'logout', "Logout: {$user->name} ({$user->email})", $user);
        
        // Delete all tokens for this device/user
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->bearerToken();

        if (!$refreshToken) {
            return response()->json(['message' => 'Token required'], 401);
        }

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($refreshToken);

        if (!$accessToken || !$accessToken->can('refresh') || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $user = $accessToken->tokenable;

        // Revoke old access tokens
        $user->tokens()->where('abilities', 'LIKE', '%"access"%')->delete();

        // Create new access token
        $tokenRaw = $user->createToken('auth-token', ['access'], now()->addMinutes(120))->plainTextToken;
        $token = explode('|', $tokenRaw, 2)[1];

        return response()->json([
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => 7200,
        ]);
    }

    // --- GOOGLE OAUTH METHODS ---

    public function redirectToGoogle(): JsonResponse
    {
        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => null, 
                    'google_id' => $googleUser->getId(),
                    'role' => 'user',
                ]);
            } else {
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId()
                    ]);
                }
            }

            // --- SINGLE DEVICE LOGIN ---
            $user->tokens()->delete();

            $tokenRaw = $user->createToken('auth-token', ['access'], now()->addMinutes(120))->plainTextToken;
            $token = explode('|', $tokenRaw, 2)[1];

            $refreshTokenRaw = $user->createToken('refresh-token', ['refresh'], now()->addDays(7))->plainTextToken;
            $refreshToken = explode('|', $refreshTokenRaw, 2)[1];

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            
            // Note: Google callback might need to pass the refresh token to frontend if it uses it.
            // But since frontend just sets it as URL param, we can append it.
            return redirect()->away($frontendUrl . '/auth/callback?token=' . $token . '&refresh_token=' . $refreshToken . '&expires_in=7200');

        } catch (\Exception $e) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect()->away($frontendUrl . '/login?error=google_auth_failed');
        }
    }
}