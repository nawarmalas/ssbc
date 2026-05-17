<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ssbc.org'],
            [
                'name' => 'SSBC Admin',
                'password' => Hash::make('Admin1234!'),
            ]
        );

        if (! SiteSetting::query()->exists()) {
            SiteSetting::create([
                'contact_email' => 'info@ssbc.org',
                'contact_phone' => '+966 11 000 0000',
                'address_en' => 'Riyadh, Kingdom of Saudi Arabia',
                'address_ar' => 'الرياض، المملكة العربية السعودية',
                'linkedin_url' => null,
                'twitter_url' => null,
                'footer_desc_en' => 'The Syrian Saudi Business Council is a formal bilateral institution dedicated to building enduring economic ties between the Syrian and Saudi business communities.',
                'footer_desc_ar' => 'مجلس الأعمال السوري السعودي هو مؤسسة ثنائية رسمية مكرسة لبناء روابط اقتصادية دائمة بين مجتمعَي الأعمال السوري والسعودي.',
            ]);
        }
    }
}
