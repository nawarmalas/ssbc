@extends('layouts.admin')

@section('title', 'Board Members - ' . __('admin.title'))
@section('page_title', 'Board Members')

@section('content')
    <div class="flex items-center justify-end mb-6">
        <a href="{{ route('admin.board-members.create') }}" class="ssbc-admin-btn-primary">+ Add Member</a>
    </div>

    <div class="ssbc-admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="ssbc-admin-thead">
                <tr>
                    <th class="text-left px-4 py-3">Photo</th>
                    <th class="text-left px-4 py-3">Name</th>
                    <th class="text-left px-4 py-3">Role (EN)</th>
                    <th class="text-left px-4 py-3">Order</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-right px-4 py-3">Edit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr class="ssbc-admin-row">
                        <td class="px-4 py-3">
                            @if($member->photoUrl())
                                <img src="{{ $member->photoUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            @else
                                <div class="h-10 w-10 rounded-full bg-ssbc-green/10 flex items-center justify-center text-ssbc-sage text-xs">–</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-ssbc-dark">{{ $member->name_en }}</p>
                            <p class="text-xs text-ssbc-sage" dir="rtl" lang="ar">{{ $member->name_ar }}</p>
                        </td>
                        <td class="px-4 py-3 text-ssbc-dark/70">{{ $member->role_en }}</td>
                        <td class="px-4 py-3 text-ssbc-dark/70">{{ $member->sort_order }}</td>
                        <td class="px-4 py-3">
                            <span class="ssbc-status-badge {{ $member->is_active ? 'ssbc-status-published' : 'ssbc-status-draft' }}">
                                {{ $member->is_active ? 'Active' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.board-members.edit', $member) }}" class="ssbc-link-gold">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-ssbc-sage">No board members yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
