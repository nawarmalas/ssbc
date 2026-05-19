@extends('layouts.admin')

@section('title', $submission->name . ' — ' . __('admin.contact'))
@section('page_title', __('admin.contact'))

@section('content')
    <a href="{{ route('admin.contact.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">← Back to list</a>

    <div class="mt-4 mb-8">
        <h1 class="text-2xl font-display font-bold text-ssbc-green">{{ $submission->name }}</h1>
        <p class="text-sm text-ssbc-sage mt-1">{{ $submission->created_at->format('d M Y H:i') }}</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <dl class="ssbc-admin-card p-6 space-y-4 lg:col-span-2">
            <div>
                <dt class="ssbc-admin-label">{{ __('admin.email_field') }}</dt>
                <dd class="text-sm text-ssbc-dark">{{ $submission->email }}</dd>
            </div>
            <div>
                <dt class="ssbc-admin-label">{{ __('admin.phone') }}</dt>
                <dd class="text-sm text-ssbc-dark">{{ $submission->phone ?: '—' }}</dd>
            </div>
            <div>
                <dt class="ssbc-admin-label">{{ __('admin.message') }}</dt>
                <dd class="text-sm text-ssbc-dark whitespace-pre-wrap">{{ $submission->message }}</dd>
            </div>
        </dl>

        <div class="ssbc-admin-card p-6 h-fit">
            <form method="POST" action="{{ route('admin.contact.update', $submission) }}" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="ssbc-admin-label" for="status">{{ __('admin.status') }}</label>
                    <select id="status" name="status" class="ssbc-admin-input">
                        @foreach(['new','reviewed','contacted'] as $s)
                            <option value="{{ $s }}" @selected($submission->status === $s)>{{ __('admin.status_'.$s) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full ssbc-admin-btn-primary">{{ __('admin.save') }}</button>
            </form>

            <div class="mt-6 border-t border-gray-200 pt-4">
                @include('partials.admin.confirm-delete', [
                    'action'     => route('admin.contact.destroy', $submission),
                    'title'      => __('admin.confirm_delete'),
                    'message'    => 'This permanently removes the contact submission.',
                    'button'     => __('admin.delete'),
                    'class'      => 'ssbc-admin-btn-danger w-full',
                    'extraClass' => 'w-full block',
                ])
            </div>
        </div>
    </div>
@endsection
