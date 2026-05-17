<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MembershipApplication extends Model
{
    protected $fillable = [
        'full_name_en',
        'full_name_ar',
        'date_of_birth',
        'position',
        'mobile',
        'email',
        'home_address',
        'linked_in',
        'companies',
        'id_document_path',
        'company_document_paths',
        'company_profile_url',
        'status',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'companies' => 'array',
            'company_document_paths' => 'array',
        ];
    }

    public function idDocumentUrl(): ?string
    {
        return $this->id_document_path ? Storage::disk('public')->url($this->id_document_path) : null;
    }

    public function companyProfileUrl(): ?string
    {
        return $this->company_profile_url ? Storage::disk('public')->url($this->company_profile_url) : null;
    }

    public function companyDocumentUrls(): array
    {
        $paths = $this->company_document_paths ?? [];
        return array_map(fn ($p) => [
            'path' => $p,
            'url' => Storage::disk('public')->url($p),
            'name' => basename($p),
        ], $paths);
    }
}
