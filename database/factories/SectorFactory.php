<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SectorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_ar'        => $this->faker->randomElement([
                'قطاع الزراعة',
                'قطاع الصناعة',
                'قطاع الخدمات',
                'قطاع التقنية',
            ]),
            'name_en'        => $this->faker->words(3, true),
            'description_ar' => $this->faker->randomElement([
                'يدعم هذا القطاع فرص الاستثمار والتنمية المستدامة وبناء الشراكات الاقتصادية.',
                'يركز هذا القطاع على تطوير القدرات المحلية وتعزيز كفاءة سلاسل القيمة.',
                'يساهم هذا القطاع في خلق فرص اقتصادية جديدة وتحسين جودة الخدمات.',
            ]),
            'description_en' => $this->faker->paragraph(),
            'sort_order'     => $this->faker->numberBetween(0, 20),
            'is_active'      => true,
        ];
    }
}
