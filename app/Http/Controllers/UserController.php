<?php

namespace App\Http\Controllers;

use App\Helpers\UserHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\DashboardMonitorLogger;
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
            $currentUser = $this->resolveAuthenticatedUser();

            if (!$currentUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sesi tidak valid, silakan login kembali'
                ], 401);
            }

            $user = UserHelper::createUser(
                $request->all(),
                (string) $currentUser->{User::FIELD_USERNAME}
            );

            DashboardMonitorLogger::create('User', "Tambah user {$user->username}", ['username' => $user->username, 'role' => $request->role_id ?? null], $request);

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
            $currentUser = $this->resolveAuthenticatedUser();

            if (!$currentUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sesi tidak valid, silakan login kembali'
                ], 401);
            }

            $oldData = $user->toArray();
            $user = UserHelper::updateUser(
                $user,
                $request->all(),
                (string) $currentUser->{User::FIELD_USERNAME}
            );

            DashboardMonitorLogger::update('User', "Ubah user {$user->username}", $oldData, $user->toArray(), $request);

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

        $currentUser = $this->resolveAuthenticatedUser();
        $currentUserId = $currentUser ? $currentUser->{User::FIELD_USER_ID} : null;

        if ($currentUserId === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi tidak valid, silakan login kembali'
            ], 401);
        }

        // Prevent deleting currently logged in user
        if ((string) $user->{User::FIELD_USER_ID} === (string) $currentUserId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus user yang sedang login'
            ], 403);
        }

        try {
            DashboardMonitorLogger::delete('User', "Hapus user {$user->username}", $user->toArray());

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

    private function resolveAuthenticatedUser(): ?User
    {
        $authIdentifier = Auth::id();

        if ($authIdentifier === null) {
            return null;
        }

        return User::query()
            ->where(User::FIELD_USERNAME, (string) $authIdentifier)
            ->first();
    }
}

