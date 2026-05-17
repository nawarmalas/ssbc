@extends('layouts.admin')

@section('title', $submission->name . ' — ' . __('admin.join'))

@section('content')
    <a href="{{ route('admin.join.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">{{ __('admin.back') }}</a>

    <div class="mt-4 w-12 h-px bg-ssbc-gold mb-4"></div>
    <h1 class="text-2xl font-display font-bold text-ssbc-green mb-2">{{ $submission->name }}</h1>
    <p class="text-sm text-ssbc-sage mb-8">{{ $submission->created_at->format('d M Y H:i') }}</p>

    <div class="grid lg:grid-cols-3 gap-6">
        <dl class="ssbc-admin-card p-6 space-y-4 lg:col-span-2">
            @foreach([
                'organization' => __('admin.organization'),
                'role'         => __('admin.role'),
                'country'      => __('admin.country'),
                'email'        => __('admin.email_field'),
                'phone'        => __('admin.phone'),
            ] as $field => $label)
                <div>
                    <dt class="ssbc-eyebrow mb-1">{{ $label }}</dt>
                    <dd class="text-sm text-ssbc-dark">{{ $submission->$field ?: '—' }}</dd>
                </div>
            @endforeach

            @if($submission->message)
                <div>
                    <dt class="ssbc-eyebrow mb-1">{{ __('admin.message') }}</dt>
                    <dd class="text-sm text-ssbc-dark whitespace-pre-wrap">{{ $submission->message }}</dd>
                </div>
            @endif
        </dl>

        <div class="ssbc-admin-card p-6">
            <form method="POST" action="{{ route('admin.join.update', $submission) }}" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="ssbc-label" for="status">{{ __('admin.status') }}</label>
                    <select id="status" name="status" class="ssbc-input">
                        @foreach(['new','reviewed','contacted'] as $s)
                            <option value="{{ $s }}" @selected($submission->status === $s)>{{ __('admin.status_'.$s) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full ssbc-btn-primary">{{ __('admin.save') }}</button>
            </form>

            <form method="POST" action="{{ route('admin.join.destroy', $submission) }}"
                  onsubmit="return confirm('{{ __('admin.confirm_delete') }}');"
                  class="mt-6 border-t border-ssbc-green/10 pt-4">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-red-700 hover:underline">{{ __('admin.delete') }}</button>
            </form>
        </div>
    </div>
@endsection
