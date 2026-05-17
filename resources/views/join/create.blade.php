@extends('layouts.app')

@php $locale = app()->getLocale(); @endphp

@section('title', __('join.hero.heading') . ' — ' . __('common.site_name'))

@section('content')

@include('partials.page-hero', [
    'eyebrow' => __('join.hero.eyebrow'),
    'heading' => __('join.hero.heading'),
    'body'    => __('join.intro'),
])

<section class="bg-white">
    <div class="ssbc-container py-16">
        <div class="max-w-3xl mx-auto">

            @if ($errors->any())
                <div class="mb-8 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold mb-2">{{ trans_choice('There is :count problem with the submission.|There are :count problems with the submission.', $errors->count(), ['count' => $errors->count()]) }}</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('join.store', ['locale' => $locale]) }}"
                  enctype="multipart/form-data"
                  x-data="joinForm()"
                  @submit="onSubmit($event)">
                @csrf

                {{-- Step indicator --}}
                <div class="flex items-center justify-between mb-10">
                    <p class="ssbc-eyebrow">
                        {{ __('join.steps.step') }}
                        <span x-text="step"></span>
                        {{ __('join.steps.of') }} 4
                    </p>
                    <div class="flex gap-2">
                        <template x-for="i in 4" :key="i">
                            <span class="h-1 w-10"
                                  :class="i <= step ? 'bg-ssbc-gold' : 'bg-ssbc-green/15'"></span>
                        </template>
                    </div>
                </div>

                {{-- STEP 1: Personal --}}
                <div x-show="step === 1" x-cloak>
                    <h2 class="text-2xl font-display font-bold text-ssbc-green mb-2">
                        {{ __('join.steps.1') }}
                    </h2>
                    <div class="w-12 h-px bg-ssbc-gold mb-8"></div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="ssbc-label" for="full_name_en">{{ __('join.fields.full_name_en') }}</label>
                            <input id="full_name_en" name="full_name_en" type="text" required class="ssbc-input" value="{{ old('full_name_en') }}">
                        </div>
                        <div>
                            <label class="ssbc-label" for="full_name_ar">{{ __('join.fields.full_name_ar') }}</label>
                            <input id="full_name_ar" name="full_name_ar" type="text" required class="ssbc-input" dir="rtl" lang="ar" value="{{ old('full_name_ar') }}">
                        </div>
                        <div>
                            <label class="ssbc-label" for="date_of_birth">{{ __('join.fields.date_of_birth') }}</label>
                            <input id="date_of_birth" name="date_of_birth" type="date" required class="ssbc-input" value="{{ old('date_of_birth') }}">
                        </div>
                        <div>
                            <label class="ssbc-label" for="position">{{ __('join.fields.position') }}</label>
                            <input id="position" name="position" type="text" required class="ssbc-input" value="{{ old('position') }}">
                        </div>
                        <div>
                            <label class="ssbc-label" for="mobile">{{ __('join.fields.mobile') }}</label>
                            <input id="mobile" name="mobile" type="tel" required class="ssbc-input" value="{{ old('mobile') }}">
                        </div>
                        <div>
                            <label class="ssbc-label" for="email">{{ __('join.fields.email') }}</label>
                            <input id="email" name="email" type="email" required class="ssbc-input" value="{{ old('email') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="ssbc-label" for="home_address">{{ __('join.fields.home_address') }} <span class="text-ssbc-sage normal-case font-normal">({{ __('common.optional') }})</span></label>
                            <textarea id="home_address" name="home_address" rows="2" class="ssbc-input">{{ old('home_address') }}</textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="ssbc-label" for="linked_in">{{ __('join.fields.linked_in') }} <span class="text-ssbc-sage normal-case font-normal">({{ __('common.optional') }})</span></label>
                            <input id="linked_in" name="linked_in" type="url" class="ssbc-input" placeholder="https://" value="{{ old('linked_in') }}">
                        </div>
                    </div>
                </div>

                {{-- STEP 2: Companies --}}
                <div x-show="step === 2" x-cloak>
                    <h2 class="text-2xl font-display font-bold text-ssbc-green mb-2">{{ __('join.steps.2') }}</h2>
                    <div class="w-12 h-px bg-ssbc-gold mb-8"></div>

                    <template x-for="(company, idx) in companies" :key="idx">
                        <div class="border border-ssbc-green/15 p-6 mb-6 relative">
                            <div class="flex items-center justify-between mb-4">
                                <p class="ssbc-eyebrow">#<span x-text="idx + 1"></span></p>
                                <button type="button" class="text-sm text-red-700 hover:underline"
                                        x-show="companies.length > 1"
                                        @click="companies.splice(idx, 1)">{{ __('join.remove') }}</button>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="ssbc-label">{{ __('join.fields.company_name') }}</label>
                                    <input type="text" required class="ssbc-input"
                                           :name="`companies[${idx}][name]`" x-model="company.name">
                                </div>
                                <div>
                                    <label class="ssbc-label">{{ __('join.fields.registration_number') }}</label>
                                    <input type="text" required class="ssbc-input"
                                           :name="`companies[${idx}][registration_number]`" x-model="company.registration_number">
                                </div>
                                <div>
                                    <label class="ssbc-label">{{ __('join.fields.country') }}</label>
                                    <input type="text" required class="ssbc-input"
                                           :name="`companies[${idx}][country]`" x-model="company.country">
                                </div>
                                <div>
                                    <label class="ssbc-label">{{ __('join.fields.sector') }}</label>
                                    <select required class="ssbc-input"
                                            :name="`companies[${idx}][sector]`" x-model="company.sector">
                                        <option value="">{{ __('join.sectors.select') }}</option>
                                        @foreach($sectors as $s)
                                            <option value="{{ $s }}">{{ __('join.sectors.'.$s) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="button" class="ssbc-link-gold text-sm"
                            @click="companies.push({name:'',registration_number:'',country:'',sector:''})">
                        {{ __('join.add_company') }}
                    </button>
                </div>

                {{-- STEP 3: Documents --}}
                <div x-show="step === 3" x-cloak>
                    <h2 class="text-2xl font-display font-bold text-ssbc-green mb-2">{{ __('join.steps.3') }}</h2>
                    <div class="w-12 h-px bg-ssbc-gold mb-8"></div>

                    <div class="space-y-6">
                        <div>
                            <label class="ssbc-label" for="id_document">{{ __('join.fields.id_document') }}</label>
                            <input id="id_document" name="id_document" type="file" required
                                   accept=".jpg,.jpeg,.png,.pdf" class="ssbc-input bg-ssbc-light">
                        </div>

                        <div>
                            <label class="ssbc-label">{{ __('join.fields.company_documents') }}</label>
                            <input name="company_documents[]" type="file" required multiple
                                   accept=".pdf,.doc,.docx" class="ssbc-input bg-ssbc-light">
                        </div>

                        <div>
                            <label class="ssbc-label" for="company_profile">
                                {{ __('join.fields.company_profile') }}
                                <span class="text-ssbc-sage normal-case font-normal">({{ __('common.optional') }})</span>
                            </label>
                            <input id="company_profile" name="company_profile" type="file"
                                   accept=".pdf" class="ssbc-input bg-ssbc-light">
                        </div>
                    </div>
                </div>

                {{-- STEP 4: Declaration --}}
                <div x-show="step === 4" x-cloak>
                    <h2 class="text-2xl font-display font-bold text-ssbc-green mb-2">{{ __('join.steps.4') }}</h2>
                    <div class="w-12 h-px bg-ssbc-gold mb-8"></div>

                    <div class="border border-ssbc-green/15 bg-ssbc-beige/50 p-6">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="declaration" value="1" required class="mt-1 rounded-none border-ssbc-green/40 text-ssbc-gold focus:ring-ssbc-gold">
                            <span class="text-sm text-ssbc-dark leading-relaxed">{{ __('join.declaration') }}</span>
                        </label>
                    </div>
                </div>

                {{-- Nav buttons --}}
                <div class="mt-12 flex items-center justify-between border-t border-ssbc-green/15 pt-6">
                    <button type="button" class="ssbc-btn-outline-dark"
                            x-show="step > 1"
                            @click="step--">
                        ← {{ __('common.previous') }}
                    </button>
                    <span x-show="step === 1"></span>

                    <button type="button" class="ssbc-btn-primary"
                            x-show="step < 4"
                            @click="step++">
                        {{ __('common.next') }} →
                    </button>

                    <button type="submit" class="ssbc-btn-primary"
                            x-show="step === 4">
                        {{ __('join.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
function joinForm() {
    return {
        step: 1,
        companies: [{name:'', registration_number:'', country:'', sector:''}],
        onSubmit(e) {
            // Allow default form post; Alpine doesn't interfere.
        }
    };
}
</script>

@endsection
