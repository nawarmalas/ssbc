@extends('layouts.admin')

@section('title', 'Sectors - ' . __('admin.title'))
@section('page_title', 'القطاعات / Sectors')

@section('content')
    <div class="flex items-center justify-end mb-6">
        <a href="{{ route('admin.sectors.create') }}" class="ssbc-admin-btn-primary">+ Add Sector</a>
    </div>

    <div class="ssbc-admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="ssbc-admin-thead">
                <tr>
                    <th class="text-left px-4 py-3">Name</th>
                    <th class="text-left px-4 py-3">Description</th>
                    <th class="text-left px-4 py-3">Order</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-right px-4 py-3">Edit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sectors as $sector)
                    <tr class="ssbc-admin-row">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-ssbc-dark">{{ $sector->name_en }}</p>
                            <p class="text-xs text-ssbc-sage" dir="rtl" lang="ar">{{ $sector->name_ar }}</p>
                        </td>
                        <td class="px-4 py-3 text-ssbc-dark/70 max-w-xs">
                            <p class="truncate">{{ Str::limit($sector->description_en, 80) }}</p>
                        </td>
                        <td class="px-4 py-3 text-ssbc-dark/70">{{ $sector->sort_order }}</td>
                        <td class="px-4 py-3">
                            <span class="ssbc-status-badge {{ $sector->is_active ? 'ssbc-status-published' : 'ssbc-status-draft' }}">
                                {{ $sector->is_active ? 'Active' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.sectors.edit', $sector) }}" class="ssbc-link-gold">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-ssbc-sage">No sectors yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
