<?php

namespace App\Http\Controllers;

use App\Helpers\UserHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use stdClass;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-users');
    }
    /**
     * Display a listing of users
     */
    public function index()
    {
        $breadcrumb = new stdClass();
        $breadcrumb->title = 'Manajemen User';
        $breadcrumb->list = ['Home', 'User', 'Manajemen User'];

        $rolesForCreate = UserHelper::getAllRoles(true); // Only Ketua & Karyawan
        $rolesForEdit = UserHelper::getAllRoles(false); // All roles including Admin

        return view('user.index', [
            'activemenu' => 'user',
            'breadcrumb' => $breadcrumb,
            'rolesForCreate' => $rolesForCreate,
            'rolesForEdit' => $rolesForEdit
        ]);
    }

    /**
     * Get user data for DataTables
     */
    public function getData()
    {
        $users = UserHelper::getAllUsersWithRole();
        $data = UserHelper::formatForDataTable($users);

        return response()->json([
            'status' => 'success',
            'data' => $data
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Get user details for editing
     *
     * @param int $id
     */
    public function edit($id)
    {
        $user = UserHelper::getUserById($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make(
            $request->all(),
            UserHelper::getValidationRules(),
            UserHelper::getValidationMessages()
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $currentUser = Auth::user();
            $user = UserHelper::createUser(
                $request->all(),
                $currentUser->{User::FIELD_USERNAME}
            );

            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil ditambahkan',
                'data' => $user
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $id)
    {
        $user = UserHelper::getUserById($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make(
            $request->all(),
            UserHelper::getValidationRules($id),
            UserHelper::getValidationMessages()
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $currentUser = Auth::user();
            $user = UserHelper::updateUser(
                $user,
                $request->all(),
                $currentUser->{User::FIELD_USERNAME}
            );

            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil diperbarui',
                'data' => $user
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user (soft delete)
     */
    public function destroy($id)
    {
        $user = UserHelper::getUserById($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Prevent deleting currently logged in user
        if ($user->{User::FIELD_USER_ID} === Auth::user()->{User::FIELD_USER_ID}) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus user yang sedang login'
            ], 403);
        }

        try {
            UserHelper::deleteUser($user);

            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil dihapus'
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus user: ' . $e->getMessage()
            ], 500);
        }
    }
}
