<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SectorFactory extends Factory
{
    public function definition(): array
    {
        $nameEn = $this->faker->unique()->words(3, true);
        return [
            'name_ar'        => $this->faker->randomElement([
                'قطاع الزراعة', 'قطاع الصناعة', 'قطاع الخدمات', 'قطاع التقنية',
            ]),
            'name_en'        => $nameEn,
            'description_ar' => $this->faker->randomElement([
                'يدعم هذا القطاع فرص الاستثمار والتنمية المستدامة وبناء الشراكات الاقتصادية.',
                'يركز هذا القطاع على تطوير القدرات المحلية وتعزيز كفاءة سلاسل القيمة.',
            ]),
            'description_en' => $this->faker->paragraph(),
            'sort_order'     => $this->faker->numberBetween(0, 20),
            'is_active'      => true,
            'slug'           => Str::slug($nameEn),
        ];
    }
}
