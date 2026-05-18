<?php

namespace App\Services;

use App\Models\FormSection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class FormService
{
    public static function getActiveForm(string $formId): Collection
    {
        return Cache::remember(
            "form:{$formId}:sections",
            300,
            fn () => FormSection::with(['fields' => function ($q) {
                $q->where('is_active', true)->orderBy('order_index');
            }])
                ->where('form_id', $formId)
                ->orderBy('order_index')
                ->get()
        );
    }

    public static function invalidateCache(string $formId = 'join-us'): void
    {
        Cache::forget("form:{$formId}:sections");
    }
}
