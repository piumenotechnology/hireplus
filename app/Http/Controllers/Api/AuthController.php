<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;



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
            $token = $user->createToken('Authentication Token')->accessToken;

            // Fetch user permissions
            $permissions = DB::table('user_permissions')
                ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
                ->join('subjects', 'subjects.id', '=', 'permissions.subject_id')
                ->join('actions', 'actions.id', '=', 'permissions.action_id')
                ->select(
                    'subjects.name as subject',
                    DB::raw('GROUP_CONCAT(actions.name) as actions')
                )
                ->where('user_permissions.role_id', $user->position)
                ->groupBy('subjects.name')
                ->get();


            $permissions->transform(function ($permission) {
                $permission->subject = explode(',', $permission->subject);
                $permission->actions = explode(',', $permission->actions);
                return $permission;
            });

            $posti = DB::table('users')
            ->join('user_roles', 'user_roles.id', '=', 'users.position')
            ->select('user_roles.name')
            ->where('user_roles.id', $user->position)
            ->first();

            $user->position = $posti->name;

            return response([
                'message' => 'Authenticated',
                'user' => $user,
                'capability' => $permissions,
                'token_type' => 'Bearer',
                'access_token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response([
                'message' => 'An error occurred during login',
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
}
