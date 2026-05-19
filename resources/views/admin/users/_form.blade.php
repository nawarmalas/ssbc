@php
    $isEdit = $adminUser->exists;
    $currentRole = old('role', $adminUser->role ?: App\Models\User::ROLE_SUBADMIN);
    $currentPermissions = old('permissions', $adminUser->permissions ?? []);
    // Normalize: old() returns whatever was POSTed (associative `{key:1}`),
    // model state returns flat array. Reduce both to a flat list of enabled keys.
    if (is_array($currentPermissions) && array_keys($currentPermissions) !== range(0, count($currentPermissions) - 1)) {
        $currentPermissions = array_keys(array_filter($currentPermissions, fn ($v) => $v == '1' || $v === true));
    }
@endphp

@if ($errors->any())
    <div class="mb-6 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="ssbc-admin-card p-6 space-y-6 max-w-3xl">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <label class="ssbc-admin-label" for="name">Name</label>
            <input id="name" name="name" class="ssbc-admin-input"
                   value="{{ old('name', $adminUser->name) }}">
        </div>
        <div>
            <label class="ssbc-admin-label" for="email">Email</label>
            <input id="email" name="email" type="email" required class="ssbc-admin-input"
                   value="{{ old('email', $adminUser->email) }}">
        </div>
    </div>

    <div x-data="{ role: '{{ $currentRole }}' }">
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="ssbc-admin-label" for="role">Role</label>
                <select id="role" name="role" required x-model="role" class="ssbc-admin-input">
                    @foreach($roles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-3 text-sm text-ssbc-dark">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $adminUser->is_active))
                           class="rounded-none border-ssbc-green/40 text-ssbc-gold focus:ring-ssbc-gold">
                    Active account
                </label>
            </div>
        </div>

        {{-- Permissions block: only meaningful for subadmins. Admins implicitly
             have every permission so the checkboxes are hidden in that case. --}}
        <div x-show="role === '{{ App\Models\User::ROLE_SUBADMIN }}'" x-cloak class="mt-6">
            <p class="ssbc-admin-label">{{ __('admin.permissions') }}</p>
            <p class="text-xs text-ssbc-sage mb-3">Tick each area this subadmin can access. Leave all unticked to revoke access.</p>
            <div class="space-y-2">
                @foreach(App\Models\User::PERMISSIONS as $permKey => $permLabel)
                    <label class="flex items-center gap-2 text-sm text-ssbc-dark">
                        <input type="hidden" name="permissions[{{ $permKey }}]" value="0">
                        <input type="checkbox" name="permissions[{{ $permKey }}]" value="1"
                               @checked(in_array($permKey, (array) $currentPermissions, true))
                               class="rounded-none border-ssbc-green/40 text-ssbc-gold focus:ring-ssbc-gold">
                        {{ $permLabel }}
                    </label>
                @endforeach
            </div>
            @error('permissions')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <label class="ssbc-admin-label" for="password">{{ $isEdit ? 'New Password' : 'Password' }}</label>
            <input id="password" name="password" type="password" @required(! $isEdit) class="ssbc-admin-input">
            @if($isEdit)
                <p class="text-xs text-ssbc-sage mt-1">Leave blank to keep the current password.</p>
            @endif
        </div>
        <div>
            <label class="ssbc-admin-label" for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEdit) class="ssbc-admin-input">
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">Back to users</a>

        <button type="submit" class="ssbc-admin-btn-primary">{{ $isEdit ? 'Save User' : 'Create User' }}</button>
    </div>
</form>

{{-- Delete sits OUTSIDE the edit form so the modal's own POST form isn't
     nested inside this one (a nested <form> is collapsed by the HTML parser,
     which caused the modal Delete to submit the outer update form). --}}
@if($isEdit && ! $adminUser->is(auth()->user()))
    <div class="mt-6 flex justify-end max-w-3xl">
        @include('partials.admin.confirm-delete', [
            'action'  => route('admin.users.destroy', $adminUser),
            'title'   => 'Delete admin user?',
            'message' => 'This permanently removes this admin login. Existing news audit history will remain linked only where the database keeps it.',
            'button'  => __('admin.delete'),
        ])
    </div>
@endif
