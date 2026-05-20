<?php

namespace App\Observers;

use App\Models\FormField;
use App\Models\Sector;
use App\Services\FormService;
use Illuminate\Support\Facades\DB;

class SectorObserver
{
    public function saved(Sector $sector): void        { $this->sync(); }
    public function deleted(Sector $sector): void      { $this->sync(); }
    public function restored(Sector $sector): void     { $this->sync(); }
    public function forceDeleted(Sector $sector): void { $this->sync(); }

    private function sync(): void
    {
        DB::afterCommit(function () {
            $field = FormField::where('code', 'sectors_of_operation')->first();
            if (! $field) return;

            $options = Sector::query()
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($s) => [
                    'value'    => $s->slug,
                    'label_en' => $s->name_en,
                    'label_ar' => $s->name_ar,
                ])->values()->all();

            $field->forceFill(['options' => $options])->saveQuietly();

            $formId = $field->section?->form_id ?? 'join-us';
            FormService::invalidateCache($formId);
        });
    }
}
