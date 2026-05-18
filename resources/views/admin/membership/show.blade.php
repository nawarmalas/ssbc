@extends('layouts.admin')

@section('title', $application->full_name_en . ' — ' . __('admin.membership'))
@section('page_title', __('admin.membership'))

@section('content')
    <a href="{{ route('admin.membership.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">← Back to list</a>

    <div class="mt-4 mb-8">
        <h1 class="text-2xl font-display font-bold text-ssbc-green mb-1">{{ $application->full_name_en }}</h1>
        <p class="text-sm text-ssbc-sage" dir="rtl" lang="ar">{{ $application->full_name_ar }}</p>
        <p class="text-sm text-ssbc-sage mt-1">{{ $application->created_at->format('d M Y H:i') }}</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            {{-- Personal info --}}
            <dl class="ssbc-admin-card p-6 grid sm:grid-cols-2 gap-4">
                <div>
                    <dt class="ssbc-eyebrow mb-1">{{ __('join.fields.date_of_birth') }}</dt>
                    <dd class="text-sm text-ssbc-dark">{{ $application->date_of_birth?->format('d M Y') ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="ssbc-eyebrow mb-1">{{ __('join.fields.position') }}</dt>
                    <dd class="text-sm text-ssbc-dark">{{ $application->position }}</dd>
                </div>
                <div>
                    <dt class="ssbc-eyebrow mb-1">{{ __('join.fields.mobile') }}</dt>
                    <dd class="text-sm text-ssbc-dark">{{ $application->mobile }}</dd>
                </div>
                <div>
                    <dt class="ssbc-eyebrow mb-1">{{ __('join.fields.email') }}</dt>
                    <dd class="text-sm text-ssbc-dark">{{ $application->email }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="ssbc-eyebrow mb-1">{{ __('join.fields.home_address') }}</dt>
                    <dd class="text-sm text-ssbc-dark whitespace-pre-wrap">{{ $application->home_address ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="ssbc-eyebrow mb-1">{{ __('join.fields.linked_in') }}</dt>
                    <dd class="text-sm text-ssbc-dark">
                        @if($application->linked_in)
                            <a href="{{ $application->linked_in }}" target="_blank" rel="noopener" class="ssbc-link-gold">{{ $application->linked_in }}</a>
                        @else — @endif
                    </dd>
                </div>
            </dl>

            {{-- Companies --}}
            <div class="ssbc-admin-card p-6">
                <p class="ssbc-eyebrow mb-4">{{ __('admin.companies') }}</p>
                <div class="space-y-4">
                    @foreach($application->companies ?? [] as $i => $c)
                        <div class="border border-ssbc-green/10 p-4">
                            <p class="font-semibold text-ssbc-dark">{{ $c['name'] ?? '' }}</p>
                            <p class="text-xs text-ssbc-sage mt-1">
                                {{ $c['registration_number'] ?? '' }} · {{ $c['country'] ?? '' }}
                                @if(!empty($c['sector']))
                                    · {{ __('join.sectors.'.$c['sector']) }}
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Documents --}}
            <div class="ssbc-admin-card p-6">
                <p class="ssbc-admin-label mb-4">{{ __('admin.documents') }}</p>

                <div class="space-y-4 text-sm">
                    <div>
                        <p class="text-xs text-ssbc-sage mb-2">{{ __('admin.id_document') }}</p>
                        @if($application->id_document_path)
                            <a href="{{ $application->idDocumentUrl() }}" target="_blank" download class="ssbc-file-link">
                                <span aria-hidden="true">📄</span>
                                <span>{{ basename($application->id_document_path) }}</span>
                            </a>
                        @else
                            <p class="text-ssbc-sage italic">Not uploaded</p>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs text-ssbc-sage mb-2">{{ __('admin.company_documents') }}</p>
                        @php $companyDocs = $application->companyDocumentUrls(); @endphp
                        @if(empty($companyDocs))
                            <p class="text-ssbc-sage italic">Not uploaded</p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach($companyDocs as $doc)
                                    <a href="{{ $doc['url'] }}" target="_blank" download class="ssbc-file-link">
                                        <span aria-hidden="true">📄</span>
                                        <span>{{ $doc['name'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs text-ssbc-sage mb-2">{{ __('admin.company_profile') }}</p>
                        @if($application->company_profile_url)
                            <a href="{{ $application->companyProfileUrl() }}" target="_blank" download class="ssbc-file-link">
                                <span aria-hidden="true">📄</span>
                                <span>{{ basename($application->company_profile_url) }}</span>
                            </a>
                        @else
                            <p class="text-ssbc-sage italic">Not uploaded</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Status / notes panel --}}
        <div class="ssbc-admin-card p-6 h-fit">
            <form method="POST" action="{{ route('admin.membership.update', $application) }}" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="ssbc-admin-label" for="status">{{ __('admin.status') }}</label>
                    <select id="status" name="status" class="ssbc-admin-input">
                        @foreach(['new','reviewed','contacted','approved','rejected'] as $s)
                            <option value="{{ $s }}" @selected($application->status === $s)>{{ __('admin.status_'.$s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ssbc-admin-label" for="admin_notes">{{ __('admin.admin_notes') }}</label>
                    <textarea id="admin_notes" name="admin_notes" rows="6" class="ssbc-admin-input">{{ old('admin_notes', $application->admin_notes) }}</textarea>
                </div>
                <button type="submit" class="w-full ssbc-admin-btn-primary">{{ __('admin.save') }}</button>
            </form>

            <form method="POST" action="{{ route('admin.membership.destroy', $application) }}"
                  onsubmit="return confirm('{{ __('admin.confirm_delete') }}');"
                  class="mt-6 border-t border-gray-200 pt-4">
                @csrf @method('DELETE')
                <button type="submit" class="ssbc-admin-btn-danger w-full">{{ __('admin.delete') }}</button>
            </form>
        </div>
    </div>
@endsection
