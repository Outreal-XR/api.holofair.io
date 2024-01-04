<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::create([
            'name' => 'Free',
        ]);

        Plan::create([
            'name' => 'Standard',
            'price' => 29,
            'duration_type' => 'monthly',
        ]);

        Plan::create([
            'name' => 'Optimal',
            'price' => 39,
            'duration_type' => 'monthly',
        ]);

        Plan::create([
            'name' => 'Maximum',
            'price' => 49,
            'duration_type' => 'monthly',
        ]);

        Plan::create([
            'name' => 'Standard',
            'price' => 299,
            'duration_type' => 'yearly',
        ]);

        Plan::create([
            'name' => 'Optimal',
            'price' => 399,
            'duration_type' => 'yearly',
        ]);

        Plan::create([
            'name' => 'Maximum',
            'price' => 499,
            'duration_type' => 'yearly',
        ]);
    }
}
