<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::with('roles:id,name')->select('id', 'name', 'email', 'is_active');

            if ($request->filled('role')) {
                $users->whereHas('roles', fn ($q) => $q->where('name', $request->role));
            }
            if ($request->filled('status')) {
                $users->where('is_active', $request->status === 'active');
            }

            return DataTables::of($users)
                ->filterColumn('roles', function ($query, $keyword) {
                    $query->whereHas('roles', fn ($q) => $q->where('name', 'like', "%{$keyword}%"));
                })
                ->addColumn('roles', function ($user) {
                    return $user->roles->pluck('name')->sort()->map(fn ($name) => '<span class="badge badge-soft-primary mr-1">' . e($name) . '</span>')->implode('');
                })
                ->addColumn('status', function ($user) {
                    return $user->is_active
                        ? '<span class="badge badge-soft-success">Aktif</span>'
                        : '<span class="badge badge-soft-secondary">Nonaktif</span>';
                })
                ->addColumn('actions', function ($user) {
                    $self = $user->id === auth()->id();
                    $toggle = $user->is_active
                        ? '<button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-user" data-id="' . $user->id . '" data-name="' . e($user->name) . '" data-active="1"' . ($self ? ' disabled title="Tidak bisa menonaktifkan akun sendiri"' : '') . '>Nonaktifkan</button>'
                        : '<button type="button" class="btn btn-sm btn-outline-success btn-toggle-user" data-id="' . $user->id . '" data-name="' . e($user->name) . '" data-active="0">Aktifkan</button>';
                    $delete = '<button type="button" class="btn btn-sm btn-link text-danger btn-delete-user" data-id="' . $user->id . '" data-name="' . e($user->name) . '"' . ($self ? ' disabled' : '') . ' title="Hapus"><i class="fas fa-trash"></i></button>';

                    return '<div class="text-nowrap"><button type="button" class="btn btn-sm btn-warning btn-edit-user" data-id="' . $user->id . '">Edit</button> ' . $toggle . ' ' . $delete . '</div>';
                })
                ->setRowClass(fn ($user) => $user->is_active ? '' : 'text-muted')
                ->rawColumns(['roles', 'status', 'actions'])
                ->make(true);
        }

        $summary = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('summary'));
    }

    public function show($id)
    {
        $user = User::with('roles:id,name')->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('name'),
            'is_self' => $user->id === auth()->id(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);
            $user->syncRoles($data['roles']);
            return $user;
        });

        return response()->json(['success' => true, 'message' => 'User berhasil ditambahkan.', 'id' => $user->id]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $this->validateUser($request, $user);

        if ($error = $this->adminRoleGuard($user, $data['roles'])) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        DB::transaction(function () use ($user, $data) {
            $userData = ['name' => $data['name'], 'email' => $data['email']];
            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }
            $user->update($userData);
            $user->syncRoles($data['roles']);
        });

        return response()->json(['success' => true, 'message' => 'User berhasil diperbarui.']);
    }

    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        if ($user->is_active) {
            if ($user->id === auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak bisa menonaktifkan akun sendiri.'], 422);
            }
            if ($this->isLastActiveAdmin($user)) {
                return response()->json(['success' => false, 'message' => 'Ini adalah Admin aktif terakhir dan tidak bisa dinonaktifkan.'], 422);
            }
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'User diaktifkan kembali.' : 'User dinonaktifkan. User tidak bisa login lagi, datanya tetap tersimpan.',
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak bisa menghapus akun sendiri.'], 422);
        }
        if ($this->isLastActiveAdmin($user)) {
            return response()->json(['success' => false, 'message' => 'Ini adalah Admin aktif terakhir dan tidak bisa dihapus.'], 422);
        }

        // Deleting cascades into dokter/employee/screening/workdoc rows and blanks authors elsewhere,
        // so only accounts without any linked data may be deleted
        $linked = $this->linkedData($user->id);
        if ($linked) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak bisa dihapus karena masih memiliki data terkait (' . implode(', ', array_slice($linked, 0, 6)) . (count($linked) > 6 ? ', ...' : '') . '). Nonaktifkan user ini sebagai gantinya.',
                'can_deactivate' => $user->is_active,
            ], 422);
        }

        DB::transaction(function () use ($user) {
            $user->syncRoles([]);
            $user->delete();
        });

        return response()->json(['success' => true, 'message' => 'User berhasil dihapus.']);
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6'],
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
        ], [
            'roles.required' => 'Pilih minimal satu role.',
            'roles.min' => 'Pilih minimal satu role.',
            'email.unique' => 'Email ini sudah dipakai user lain.',
        ]);
    }

    // Prevent locking everyone out of the Admin Panel
    private function adminRoleGuard(User $user, array $newRoles): ?string
    {
        if (!$user->hasRole('Admin') || in_array('Admin', $newRoles, true)) {
            return null;
        }
        if ($user->id === auth()->id()) {
            return 'Anda tidak bisa menghapus role Admin dari akun sendiri.';
        }
        if ($this->isLastActiveAdmin($user)) {
            return 'Ini adalah Admin aktif terakhir; role Admin tidak bisa dihapus.';
        }
        return null;
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return $user->is_active
            && $user->hasRole('Admin')
            && User::role('Admin')->where('is_active', true)->where('id', '!=', $user->id)->doesntExist();
    }

    // Tables (from the database's foreign keys) that still reference this user
    private function linkedData(int $userId): array
    {
        $references = DB::select(
            "SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.KEY_COLUMN_USAGE
             WHERE REFERENCED_TABLE_NAME = 'users' AND REFERENCED_COLUMN_NAME = 'id' AND TABLE_SCHEMA = DATABASE()"
        );

        $linked = [];
        foreach ($references as $ref) {
            if (DB::table($ref->t)->where($ref->c, $userId)->exists()) {
                $linked[$ref->t] = $ref->t;
            }
        }

        return array_values($linked);
    }
}
