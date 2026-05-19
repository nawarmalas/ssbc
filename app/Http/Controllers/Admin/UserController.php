<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByRaw("role = 'admin' desc")
            ->orderBy('name')
            ->orderBy('email')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'adminUser' => new User([
                'role' => User::ROLE_SUBADMIN,
                'is_active' => true,
            ]),
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);

        User::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'permissions' => $this->resolvePermissions($request, $data['role']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', 'Admin user created.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'adminUser' => $user,
            'roles' => User::ROLES,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user);
        $isActive = $request->boolean('is_active');

        if ($user->is(auth()->user()) && (! $isActive || $data['role'] !== $user->role)) {
            return back()
                ->withInput()
                ->with('error', 'You cannot disable your own account or change your own role.');
        }

        if ($this->wouldRemoveLastActiveAdmin($user, $data['role'], $isActive)) {
            return back()
                ->withInput()
                ->with('error', 'At least one active full admin account must remain.');
        }

        $payload = [
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'role' => $data['role'],
            'permissions' => $this->resolvePermissions($request, $data['role']),
            'is_active' => $isActive,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return redirect()->route('admin.users.index')
            ->with('status', 'Admin user updated.');
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($this->wouldRemoveLastActiveAdmin($user, null, false)) {
            return back()->with('error', 'At least one active full admin account must remain.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'Admin user deleted.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['boolean'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['in:0,1'],
            'password' => $passwordRules,
        ]);
    }

    /**
     * Convert the form's `permissions[news_write]=1` checkbox map into a
     * flat list of enabled permission keys. Admins implicitly hold every
     * permission so we store an empty array — `hasPermission()` short-circuits
     * via `isAdmin()`.
     */
    private function resolvePermissions(Request $request, string $role): array
    {
        if ($role !== User::ROLE_SUBADMIN) {
            return [];
        }

        $submitted = (array) $request->input('permissions', []);
        $valid = array_keys(User::PERMISSIONS);

        $enabled = [];
        foreach ($valid as $key) {
            $value = $submitted[$key] ?? '0';
            if ($value === '1' || $value === 1 || $value === true) {
                $enabled[] = $key;
            }
        }

        return $enabled;
    }

    private function wouldRemoveLastActiveAdmin(User $user, ?string $newRole, bool $newActive): bool
    {
        if (! $user->is_active || $user->role !== User::ROLE_ADMIN) {
            return false;
        }

        $willRemainActiveAdmin = $newRole === User::ROLE_ADMIN && $newActive;

        if ($willRemainActiveAdmin) {
            return false;
        }

        return User::where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->count() === 0;
    }
}
