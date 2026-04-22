<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\OtpCode;
use App\Notifications\SendOtpNotification;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'position' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return response([
                'message' => 'Validation errors',
                'errors' => $validate->errors(),
            ], 400);
        }

        try {

            $username = strtolower(preg_replace('/\s+/', '', $request->first_name . '_' . $request->last_name));

            $originalUsername = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }

            $registrationData = $request->all();
            $registrationData['username'] = $username;
            $registrationData['password'] = bcrypt($request->password);

            $user = User::create($registrationData);

            return response([
                'message' => 'Registration successful',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function login(Request $request){

        $validate = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validate->fails()) {
            return response([
                'message' => 'Validation errors',
                'errors' => $validate->errors(),
            ], 400);
        }


        $credentials = $request->only('username', 'password');
        if (!Auth::attempt($credentials)) {
            return response([
                'message' => 'Invalid credentials',
            ], 401);
        }

        try {
            $user = Auth::user();

            // If user is admin (position = 1), skip OTP and return authenticated response
            if ($user->position == 1) {
                $token = $user->createToken('Authentication Token')->accessToken;

                // Fetch user permissions
                $cekData = DB::table('user_permissions')
                    ->join('user_roles', 'user_roles.id', '=', 'user_permissions.role_id')
                    ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
                    ->join('subjects', 'subjects.id', '=', 'permissions.subject_id')
                    ->join('actions', 'actions.id', '=', 'permissions.action_id')
                    ->select(DB::raw('CONCAT_WS(".",subjects.slug, actions.name) as permissions'))
                    ->where('user_roles.id', $user->position)
                    ->pluck('permissions')
                    ->toArray();

                $posti = DB::table('users')
                    ->join('user_roles', 'user_roles.id', '=', 'users.position')
                    ->select('user_roles.name')
                    ->where('user_roles.id', $user->position)
                    ->first();

                $user->position = $posti->name;

                return response([
                    'message' => 'Authenticated',
                    'user' => $user,
                    'capability' => $cekData,
                    'token_type' => 'Bearer',
                    'access_token' => $token,
                ], 200);
            }

            // Delete any existing OTPs for this user
            OtpCode::where('user_id', $user->id)->delete();

            // Generate 6-digit OTP
            $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Save OTP to database with 10-minute expiration
            OtpCode::create([
                'user_id' => $user->id,
                'otp_code' => Hash::make($otpCode),
                'expires_at' => Carbon::now()->addMinutes(5),
            ]);

            // Send OTP notification to user email
            $user->notify(new SendOtpNotification($otpCode));

            return response([
                'message' => 'OTP sent to your email',
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response([
                'message' => 'An error occurred during login',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validate->fails()) {
            return response([
                'message' => 'Validation errors',
                'errors' => $validate->errors(),
            ], 400);
        }

        try {
            $otpRecord = OtpCode::where('user_id', $request->user_id)
                ->whereNull('verified_at')
                ->first();

            if (!$otpRecord || !Hash::check($request->otp_code, $otpRecord->otp_code)) {
                return response([
                    'message' => 'Invalid OTP code',
                ], 401);
            }

            if ($otpRecord->isExpired()) {
                return response([
                    'message' => 'OTP code has expired',
                ], 401);
            }

            // Mark OTP as verified
            $otpRecord->update(['verified_at' => Carbon::now()]);

            $user = User::find($request->user_id);
            $token = $user->createToken('Authentication Token')->accessToken;

            // Fetch user permissions
            $cekData = DB::table('user_permissions')
                ->join('user_roles', 'user_roles.id', '=', 'user_permissions.role_id')
                ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
                ->join('subjects', 'subjects.id', '=', 'permissions.subject_id')
                ->join('actions', 'actions.id', '=', 'permissions.action_id')
                ->select(DB::raw('CONCAT_WS(".",subjects.slug, actions.name) as permissions'))
                ->where('user_roles.id', $user->position)
                ->pluck('permissions')
                ->toArray();

            $posti = DB::table('users')
                ->join('user_roles', 'user_roles.id', '=', 'users.position')
                ->select('user_roles.name')
                ->where('user_roles.id', $user->position)
                ->first();

            $user->position = $posti->name;

            return response([
                'message' => 'Authenticated',
                'user' => $user,
                'capability' => $cekData,
                'token_type' => 'Bearer',
                'access_token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response([
                'message' => 'An error occurred during OTP verification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function resendOtp(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validate->fails()) {
            return response([
                'message' => 'Validation errors',
                'errors' => $validate->errors(),
            ], 400);
        }

        try {
            $user = User::find($request->user_id);

            // Delete any existing OTPs for this user
            OtpCode::where('user_id', $user->id)->delete();

            // Generate new 6-digit OTP
            $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Save OTP to database with 10-minute expiration
            OtpCode::create([
                'user_id' => $user->id,
                'otp_code' => Hash::make($otpCode),
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);

            // Send OTP notification to user email
            $user->notify(new SendOtpNotification($otpCode));

            return response([
                'message' => 'OTP resent to your email',
            ], 200);
        } catch (\Exception $e) {
            return response([
                'message' => 'An error occurred while resending OTP',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->token()->revoke();
            $request->user()->token()->delete();

            return response([
                'success' => true,
                'message' => 'Logged out successfully',
            ], 200);
        } else {
            return response([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent.'])
            : response()->json(['message' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset successfully.'])
            : response()->json(['message' => __($status)], 400);
    }
}
