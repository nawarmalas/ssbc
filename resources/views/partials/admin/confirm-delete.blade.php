{{--
    Branded confirm-delete button + modal. Replaces the native browser
    confirm() dialog ("localhost says..."). Submits a DELETE form on confirm.

    Usage:
        @include('partials.admin.confirm-delete', [
            'action'  => route('admin.submissions.destroy', $submission),
            'title'   => 'Delete submission?',
            'message' => 'This permanently removes the submission and uploads.',
            'button'  => __('admin.delete'),
            'class'   => 'ssbc-admin-btn-danger',
        ])

    Variables:
        $action   form action URL (required)
        $title    modal heading text (required)
        $message  modal body text (required)
        $button   label on the trigger button + the modal confirm button (required)
        $class    Tailwind classes for the trigger button (optional, defaults to btn-danger)
        $method   form method override, default 'DELETE'
        $confirmLabel  override the modal's confirm button label (defaults to $button)
        $extraClass    additional classes on the wrapping form (e.g. layout helpers)
--}}

@php
    $method = $method ?? 'DELETE';
    $class = $class ?? 'ssbc-admin-btn-danger';
    $confirmLabel = $confirmLabel ?? $button;
    $extraClass = $extraClass ?? '';
@endphp

<div x-data="{ open: false }" class="inline-block {{ $extraClass }}">
    <button type="button" @click="open = true" class="{{ $class }}">{{ $button }}</button>

    <template x-teleport="body">
        <div x-show="open"
             x-cloak
             @keydown.escape.window="open = false"
             x-transition.opacity.duration.150ms
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.outside="open = false"
                 x-transition.scale.duration.150ms
                 class="bg-white max-w-md w-full p-6 shadow-xl border border-gray-200"
                 role="dialog"
                 aria-modal="true">
                <h3 class="font-display font-bold text-ssbc-green text-lg mb-3">{{ $title }}</h3>
                <p class="text-sm text-ssbc-dark/80 leading-relaxed mb-6">{{ $message }}</p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="open = false"
                            class="px-4 py-2 text-sm text-ssbc-sage hover:text-ssbc-green border border-gray-200">
                        {{ __('admin.cancel') }}
                    </button>
                    <form method="POST" action="{{ $action }}">
                        @csrf @method($method)
                        <button type="submit" class="ssbc-admin-btn-danger">{{ $confirmLabel }}</button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
