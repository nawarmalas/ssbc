@extends('layouts.admin')

@section('title', 'Admin Users - ' . __('admin.title'))
@section('page_title', 'Admin Users')

@section('content')
<div class="flex items-center justify-end mb-6">
    <a href="{{ route('admin.users.create') }}" class="ssbc-admin-btn-primary">+ Create User</a>
</div>

<div class="ssbc-admin-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="ssbc-admin-thead">
            <tr>
                <th class="text-left px-4 py-3">Name</th>
                <th class="text-left px-4 py-3">Email</th>
                <th class="text-left px-4 py-3">Role</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Updated</th>
                <th class="text-right px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $adminUser)
                <tr class="ssbc-admin-row">
                    <td class="px-4 py-3 font-semibold text-ssbc-dark">{{ $adminUser->name ?: '-' }}</td>
                    <td class="px-4 py-3 text-ssbc-dark">{{ $adminUser->email }}</td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-ssbc-dark">{{ $adminUser->roleLabel() }}</div>
                        @if($adminUser->isSubadmin())
                            <div class="mt-1 flex flex-wrap gap-1">
                                @forelse($adminUser->permissionLabels() as $label)
                                    <span class="inline-block bg-ssbc-beige text-ssbc-green text-xs px-2 py-0.5 border border-ssbc-green/15">{{ $label }}</span>
                                @empty
                                    <span class="text-xs text-ssbc-sage italic">No permissions</span>
                                @endforelse
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="ssbc-status-badge {{ $adminUser->is_active ? 'ssbc-status-approved' : 'ssbc-status-rejected' }}">
                            {{ $adminUser->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-ssbc-dark/70 whitespace-nowrap">{{ $adminUser->updated_at?->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.users.edit', $adminUser) }}" class="ssbc-link-gold">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-ssbc-sage">No users yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
