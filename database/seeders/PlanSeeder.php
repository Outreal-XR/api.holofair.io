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
            'stripe_plan_id' => 'price_1OVCtxEEcid2xo4Iu51LudiH',
        ]);

        Plan::create([
            'name' => 'Optimal',
            'price' => 39,
            'duration_type' => 'monthly',
            'stripe_plan_id' => 'price_1OVCw5EEcid2xo4I02ZHVsYm'
        ]);

        Plan::create([
            'name' => 'Maximum',
            'price' => 49,
            'duration_type' => 'monthly',
            'stripe_plan_id' => 'price_1OVCy5EEcid2xo4Ira2HIhNg'
        ]);

        Plan::create([
            'name' => 'Standard',
            'price' => 299,
            'duration_type' => 'yearly',
            'stripe_plan_id' => 'price_1OVCtxEEcid2xo4ICSY9gn34'
        ]);

        Plan::create([
            'name' => 'Optimal',
            'price' => 399,
            'duration_type' => 'yearly',
            'stripe_plan_id' => 'price_1OVCwnEEcid2xo4IL7BSdMJq'
        ]);

        Plan::create([
            'name' => 'Maximum',
            'price' => 499,
            'duration_type' => 'yearly',
            'stripe_plan_id' => 'price_1OVCyLEEcid2xo4I8LcP1Fx6'
        ]);
    }
}
