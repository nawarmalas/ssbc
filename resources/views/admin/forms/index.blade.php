@extends('layouts.admin')

@section('title', 'Forms - ' . __('admin.title'))
@section('page_title', 'Forms')

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="ssbc-admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="ssbc-admin-thead">
                    <tr>
                        <th class="text-left px-4 py-3">Form</th>
                        <th class="text-left px-4 py-3">Visibility</th>
                        <th class="text-left px-4 py-3">Submissions</th>
                        <th class="text-right px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($forms as $form)
                        <tr class="ssbc-admin-row align-top">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ssbc-dark">{{ $form->title_en }}</p>
                                <p class="text-xs text-ssbc-sage" dir="rtl">{{ $form->title_ar }}</p>
                                @if($form->isPrivate() && $form->publicUrl('en'))
                                    <input readonly value="{{ $form->publicUrl('en') }}"
                                           class="mt-2 ssbc-admin-input text-xs bg-white"
                                           onclick="this.select()">
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="ssbc-status-badge {{ $form->is_active ? 'ssbc-status-approved' : 'ssbc-status-rejected' }}">
                                    {{ $form->visibility }}{{ $form->is_active ? '' : ' disabled' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $form->submissions_count }}</td>
                            <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                <a href="{{ route('admin.forms.builder', $form) }}" class="ssbc-link-gold">Builder</a>
                                <a href="{{ route('admin.submissions.index', ['form_id' => $form->form_id]) }}" class="ssbc-link-gold">Submissions</a>
                                @if($form->isPrivate())
                                    <form method="POST" action="{{ route('admin.forms.update', $form) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="title_en" value="{{ $form->title_en }}">
                                        <input type="hidden" name="title_ar" value="{{ $form->title_ar }}">
                                        <input type="hidden" name="description_en" value="{{ $form->description_en }}">
                                        <input type="hidden" name="description_ar" value="{{ $form->description_ar }}">
                                        <input type="hidden" name="is_active" value="{{ $form->is_active ? 0 : 1 }}">
                                        <button type="submit" class="ssbc-link-gold">
                                            {{ $form->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.forms.store') }}" class="ssbc-admin-card p-6 space-y-4">
        @csrf
        <h2 class="font-display font-bold text-ssbc-green">New Private Form</h2>
        <div>
            <label class="ssbc-admin-label" for="title_en">Title English</label>
            <input id="title_en" name="title_en" required class="ssbc-admin-input" value="{{ old('title_en') }}">
        </div>
        <div>
            <label class="ssbc-admin-label" for="title_ar">Title Arabic</label>
            <input id="title_ar" name="title_ar" required dir="rtl" class="ssbc-admin-input" value="{{ old('title_ar') }}">
        </div>
        <div>
            <label class="ssbc-admin-label" for="description_en">Description English</label>
            <textarea id="description_en" name="description_en" rows="3" class="ssbc-admin-input">{{ old('description_en') }}</textarea>
        </div>
        <div>
            <label class="ssbc-admin-label" for="description_ar">Description Arabic</label>
            <textarea id="description_ar" name="description_ar" rows="3" dir="rtl" class="ssbc-admin-input">{{ old('description_ar') }}</textarea>
        </div>
        <button type="submit" class="ssbc-admin-btn-primary w-full">Create Private Form</button>
    </form>
</div>
@endsection
