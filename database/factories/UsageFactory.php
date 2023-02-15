<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'port' => random_int(59001, 59005),
            'usage' => random_int(1, 100),
            'created_at' => Carbon::now()->subDays(random_int(0, 10))
        ];
    }
}
