<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'contact_email',
        'contact_phone',
        'address_en',
        'address_ar',
        'linkedin_url',
        'twitter_url',
        'footer_desc_en',
        'footer_desc_ar',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? new self([
            'contact_email' => 'info@ssbc.org',
            'contact_phone' => '',
            'address_en' => '',
            'address_ar' => '',
        ]);
    }

    public function address(string $locale): string
    {
        return $locale === 'ar' ? ($this->address_ar ?: $this->address_en) : $this->address_en;
    }

    public function footerDesc(string $locale): ?string
    {
        return $locale === 'ar' ? ($this->footer_desc_ar ?: $this->footer_desc_en) : $this->footer_desc_en;
    }
}
