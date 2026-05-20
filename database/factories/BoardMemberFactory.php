<?php
// database/factories/BoardMemberFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BoardMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_ar' => $this->faker->name(),
            'name_en' => $this->faker->name(),
            'role_ar' => 'عضو المجلس',
            'role_en' => 'Board Member',
            'bio_ar'  => $this->faker->paragraph(),
            'bio_en'  => $this->faker->paragraph(),
            'photo'   => null,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
