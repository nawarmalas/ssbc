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
            $fields = FormField::where('options_source', 'sectors')->with('section')->get();
            if ($fields->isEmpty()) return;

            $options = Sector::activeFieldOptions();
            $formIds = [];

            foreach ($fields as $field) {
                $field->forceFill(['options' => $options])->saveQuietly();
                $formIds[$field->section?->form_id ?? 'join-us'] = true;
            }

            foreach (array_keys($formIds) as $formId) {
                FormService::invalidateCache($formId);
            }
        });
    }
}
