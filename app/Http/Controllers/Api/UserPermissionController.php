<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;

class UserPermissionController extends Controller
{
    public function index(){ //get all data user (no pagination)
        $user = DB::table('users')
        ->select('*') ->get();

        if(count($user) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $user
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function getAllUsers(Request $request){ //get all data users
        // Validate request inputs
        $request->validate([
            'search' => 'string|nullable',
            'sort' => 'string|nullable',
            'order' => 'in:asc,desc|nullable',
            'per_page' => 'integer|nullable|min:1'
        ]);

        $query = DB::table('users')
            ->join('user_roles', 'user_roles.id', '=', 'users.position')
            ->select('users.*', 'user_roles.name as position')
            ->where('user_roles.name', '!=', 'Administrator');

        // Apply search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        if ($sort = $request->input('sort')) {
            $order = $request->input('order', 'asc');
            $query->orderBy($sort, $order);
        }

        // Paginate results
        $perPage = $request->input('per_page', 10);
        $users = $query->paginate($perPage);

        // Return response
        if ($users->count() > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $users
            ], 200);
        }

        return response([
            'message' => 'No users found',
            'data' => null
        ], 404);
    }

    public function showUser($id){ //get user by id
        $user = DB::table('users')
        ->join('user_roles', 'user_roles.id', '=', 'users.position')
        ->select('users.name', 'users.username', 'user_roles.name as position')
        ->whereRaw('users.id = "'.$id.'"')
        ->get();

        if(count($user) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $user
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function updateUser(Request $request, $id){

        $user = User::find($id);

        if (!$user) {
            return response([
                'message' => 'User not found',
                'data' => null,
            ], 404);
        }

        // Validate input data
        $validate = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'username' => 'sometimes|required|string|max:255|unique:users,username,' . $id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'position' => 'sometimes|required|integer',
        ]);

        if ($validate->fails()) {
            return response([
                'message' => 'Validation errors',
                'errors' => $validate->errors(),
            ], 400);
        }

        $fieldsToUpdate = $request->only(['first_name', 'last_name', 'username', 'email', 'position']);

        $user->update($fieldsToUpdate);

        return response([
            'message' => 'User updated successfully',
            'data' => $user,
        ], 200);
    }

    public function deleteUser($id){
        $user = User::find($id);

        if (!$user) {
            return response([
                'message' => 'User not found',
                'data' => null,
            ], 404);
        }

        if ($user->delete()) {
            return response([
                'message' => 'User deleted successfully',
            ], 200);
        }

        return response([
            'message' => 'Failed to delete user',
            'data' => null,
        ], 500);
    }


    //user roles
    public function getRoles(Request $request){
        $request->validate([
            'search' => 'string|nullable',
            'sort' => 'string|nullable',
            'order' => 'in:asc,desc|nullable',
            'per_page' => 'integer|nullable|min:1'
        ]);

        $query = DB::table('user_roles')
        ->select('*')
        ->whereNotIn('name', ['Administrator', 'User']);


        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($sort = $request->input('sort')) {
            $order = $request->input('order', 'asc');
            $query->orderBy($sort, $order);
        }

        $perPage = $request->input('per_page', 10);
        $users = $query->paginate($perPage);


        if ($users->count() > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $users
            ], 200);
        }

        return response([
            'message' => 'No users found',
            'data' => null
        ], 404);
    }

    public function getRolesByid($id){
        $role = DB::table('user_roles')
            ->select('*')
            ->where('id', $id)
            ->get();

        // Return response
        if ($role->count() > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $role
            ], 200);
        }

        return response([
            'message' => 'No users found',
            'data' => null
        ], 404);
    }

    public function createRoles(Request $request){
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:user_roles,name',
        ]);

        if ($validate->fails()) {
            return response([
                'message' => 'Validation errors',
                'errors' => $validate->errors(),
            ], 400);
        }

        try {

            $slug = strtolower(preg_replace('/\s+/', '', $request->name));

            $insertData = $request->all();
            $insertData['slug'] = $slug;

            $role = DB::table('user_roles') -> insert($insertData);

            return response([
                'message' => 'create role successful',
                'role' => $role,
            ], 201);
        } catch (\Exception $e) {
            return response([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function updateRoles(Request $request, $id){

        $role = DB::select('SELECT * FROM user_roles WHERE id = ?', [$id]);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found',
                'data' => null,
            ], 404);
        }

        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validate->errors(),
            ], 400);
        }

        $slug = strtolower(preg_replace('/\s+/', '', $request->name));

        $updated = DB::update(
            'UPDATE user_roles SET name = ?, slug = ? WHERE id = ?',
            [$request->input('name'), $slug, $id]
        );

        if ($updated) {

            $updatedRole = DB::select('SELECT * FROM user_roles WHERE id = ?', [$id]);

            return response()->json([
                'message' => 'Role updated successfully',
                'data' => $updatedRole[0],
            ], 200);
        }

        return response()->json([
            'message' => 'Failed to update role',
            'data' => null,
        ], 500);
    }

    public function deleteRoles($id){

        if (!is_numeric($id)) {
            return response()->json([
                'message' => 'Invalid role ID',
                'data' => null,
            ], 400);
        }

        $role = DB::table('user_roles')->find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found',
                'data' => null,
            ], 404);
        }


        DB::beginTransaction();

        try {

            $deleted = DB::table('user_roles')->where('id', $id)->delete();

            if (!$deleted) {
                throw new \Exception('Failed to delete role');
            }

            DB::table('users')
                ->where('position', $id)
                ->update(['position' => config('roles.default_position', 2)]);


            DB::table('user_permissions')->where('role_id', $id)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Role deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }


    public function getSubject(){
        $contexs = DB::table('subjects')
                    ->select('name', 'slug')
                    ->get()
                    ->map(function ($item) {
                        $item->actions = [];//'read', 'create', 'update', 'delete'
                        return $item;
                    });

        if ($contexs->count() > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $contexs,
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null,
        ], 400);
    }





    //permissions
    public function getAllPermission(){ //not use yet
        // $permission = DB::table('permissions')
        // ->join('subjects', 'subjects.id', '=', 'permissions.subject_id')
        // ->join('actions', 'actions.id', '=', 'permissions.action_id')
        // ->select('permissions.id', 'subjects.name as subject', 'actions.name as action')

        // ->get();

        // if(count($permission) > 0) {
        //     return response([
        //         'message' => 'Retrieve All Success',
        //         'data' => $permission
        //     ], 200);
        // }

        // return response([
        //     'message' => 'Empty',
        //     'data' => null
        // ],400);

        $permissions = DB::table('permissions')
        ->join('subjects', 'subjects.id', '=', 'permissions.subject_id')
        ->join('actions', 'actions.id', '=', 'permissions.action_id')
        ->select('subjects.name as subjects', DB::raw('GROUP_CONCAT(actions.name) as actions'))
        ->groupBy('subjects.name')
        ->get();

        if ($permissions->isEmpty()) {
            return response([
                'message' => 'No permissions found',
                'data' => []
            ], 200);
        }

        $permissions->transform(function ($permission) {
            $permission->subjects = explode(',', $permission->subjects);
            $permission->actions = explode(',', $permission->actions);
            return $permission;
        });

        return response([
            'message' => 'Retrieve All Success',
            'data' => $permissions
        ], 200);

    }

    public function getAllUserPermissions(){ //not use yet
        $users = DB::table('users')
            ->join('user_roles', 'user_roles.id', '=', 'users.position')
            ->join('user_permissions', 'user_permissions.role_id', '=', 'users.position')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->join('subjects', 'subjects.id', '=', 'permissions.subject_id')
            ->join('actions', 'actions.id', '=', 'permissions.action_id')
            ->select('users.id', 'users.first_name', 'users.last_name', 'user_roles.name as position', 'subjects.name as subject', DB::raw('GROUP_CONCAT(actions.name) as actions'))
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.position','subjects.name')
            ->get();


        $users->transform(function ($user) {
            $user->subject = explode(',', $user->subject);
            $user->actions = explode(',', $user->actions);
            return $user;
        });

        if(count($users) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $users
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function getUserPermissionByid($id){
        $permissions = DB::table('user_permissions')
            ->join('users', 'users.position', '=', 'user_permissions.role_id')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->join('subjects', 'subjects.id', '=', 'permissions.subject_id')
            ->join('actions', 'actions.id', '=', 'permissions.action_id')
            ->select('subjects.name as subjects', DB::raw('GROUP_CONCAT(actions.name) as actions'))
            ->where('users.id', $id)
            ->groupBy('subjects.name')
            ->get();

        if ($permissions->isEmpty()) {
            return response([
                'message' => 'No permissions found',
                'data' => []
            ], 200);
        }

        $permissions->transform(function ($permission) {
            $permission->subjects = explode(',', $permission->subjects);
            $permission->actions = explode(',', $permission->actions);
            return $permission;
        });

        return response([
            'message' => 'Retrieve All Success',
            'data' => $permissions
        ], 200);
    }

    public function getRolePermissionByslug($slug){
        $slugPermission = DB::table('user_permissions')
            ->join('user_roles', 'user_roles.id', '=', 'user_permissions.role_id')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->join('subjects', 'subjects.id', '=', 'permissions.subject_id')
            ->join('actions', 'actions.id', '=', 'permissions.action_id')
            ->select(DB::raw('CONCAT_WS(".",subjects.slug, actions.name) as permissions'))
            ->where('user_roles.slug', $slug)
            // ->get()
            ->pluck('permissions')
            ->toArray();


        $roleName = DB::table('user_roles')
        ->select('name')
        ->where('name', $slug)
        // ->whereNotIn('id',['1','2'])
        ->first();

        // Check if the array is empty using empty()
        if (empty($roleName)) {
            return response([
                'message' => 'roles no found',
                'data' => $slug
            ], 200);
        }

        if (empty($slugPermission)) {
            return response([
                'message' => 'No permissions found',
                'data' => ['name'=>$roleName->name,'permissions' => null]
            ], 200);
        }

        return response([
            'message' => 'Retrieve All Success',
            'data' => ['name'=>$roleName->name,'permissions' => $slugPermission]
        ], 200);
    }

    public function storePermission(Request $request, $slug)
    {
        $validated = $request->validate([
            // 'slug' => 'required|string|exists:user_roles,slug',
            'permissions' => 'required|array',
            'permissions.*.subject' => 'required|string',
            'permissions.*.actions' => 'required|array',
        ]);

        // $roleSlug = $validated['slug'];
        $roleId = DB::table('user_roles')->where('slug', $slug)->value('id');

        if (!$roleId) {
            return response(['error' => 'Invalid role slug'], 400);
        }

        DB::beginTransaction();

        try {
            DB::table('user_permissions')->where('role_id', $roleId)->delete();

            $existingSubjects = DB::table('subjects')->pluck('id', 'slug');
            $existingActions = DB::table('actions')->pluck('id', 'name');
            $newSubjects = [];
            $newActions = [];

            foreach ($validated['permissions'] as $permission) {
                $subjectName = $permission['subject'];
                $slug_name = strtolower(preg_replace('/\s+/', '', $subjectName));
                $subjectId = $existingSubjects[$subjectName] ?? $newSubjects[$subjectName] ?? null;

                if (!$subjectId) {
                    // $subjectId = DB::table('subjects')->insertGetId(['name' => $subjectName, 'slug' => $slug_name]);
                    // $newSubjects[$subjectName] = $subjectId;
                    return response(['error' => 'Failed to update permissions no such data in the database check your subject name'], 404);
                    // continue;
                }

                foreach (array_unique($permission['actions']) as $actionName) {
                    $actionId = $existingActions[$actionName] ?? $newActions[$actionName] ?? null;

                    if (!$actionId) {
                        // $actionId = DB::table('actions')->insertGetId(['name' => $actionName]);
                        // $newActions[$actionName] = $actionId;
                        return response(['error' => 'Failed to update permissions no such data in the database check your action name'], 404);
                        // continue;
                    }

                    $permissionId = DB::table('permissions')->where([
                        'subject_id' => $subjectId,
                        'action_id' => $actionId,
                    ])->value('id');

                    if (!$permissionId) {
                        $permissionId = DB::table('permissions')->insertGetId([
                            'subject_id' => $subjectId,
                            'action_id' => $actionId,
                        ]);
                    }

                    DB::table('user_permissions')->insertOrIgnore([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            DB::commit();

            return response(['message' => 'Create Permissions successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['error' => 'Failed to update permissions', 'details' => $e->getMessage()], 500);
        }
    }


    public function storePermissionByIndex(Request $request, $slug){
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.subject' => 'required|string',
            'permissions.*.actions' => 'required|array',
            'permissions.*.actions.*' => 'boolean', // Ensure actions are binary values
        ]);

        $roleId = DB::table('user_roles')->where('slug', $slug)->value('id');

        if (!$roleId) {
            return response(['error' => 'Invalid role slug'], 400);
        }

        DB::beginTransaction();

        try {
            DB::table('user_permissions')->where('role_id', $roleId)->delete();

            $existingSubjects = DB::table('subjects')->pluck('id', 'name');
            $existingActions = DB::table('actions')->pluck('id', 'name');
            $newSubjects = [];
            $newActions = [];

            $actionOrder = ['create', 'update', 'delete', 'read']; //ubah urutan

            foreach ($validated['permissions'] as $permission) {
                $subjectName = $permission['subject'];
                $slugName = strtolower(preg_replace('/\s+/', '', $subjectName));
                $subjectId = $existingSubjects[$subjectName] ?? $newSubjects[$subjectName] ?? null;

                if (!$subjectId) {
                    $subjectId = DB::table('subjects')->insertGetId(['name' => $subjectName, 'slug' => $slugName]);
                    $newSubjects[$subjectName] = $subjectId;
                }

                foreach ($permission['actions'] as $index => $allowed) {
                    // if ($allowed === 1) { //allowed actions by number
                    if ($allowed === true) { // allowed actions by true false
                        $actionName = $actionOrder[$index] ?? null;

                        if (!$actionName) {
                            continue;
                        }

                        $actionId = $existingActions[$actionName] ?? $newActions[$actionName] ?? null;

                        if (!$actionId) {
                            $actionId = DB::table('actions')->insertGetId(['name' => $actionName]);
                            $newActions[$actionName] = $actionId;
                        }

                        $permissionId = DB::table('permissions')->where([
                            'subject_id' => $subjectId,
                            'action_id' => $actionId,
                        ])->value('id');

                        if (!$permissionId) {
                            $permissionId = DB::table('permissions')->insertGetId([
                                'subject_id' => $subjectId,
                                'action_id' => $actionId,
                            ]);
                        }

                        DB::table('user_permissions')->insertOrIgnore([
                            'role_id' => $roleId,
                            'permission_id' => $permissionId,
                        ]);
                    }
                }
            }

            DB::commit();

            return response(['message' => 'Permissions updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['error' => 'Failed to update permissions', 'details' => $e->getMessage()], 500);
        }
    }


}
