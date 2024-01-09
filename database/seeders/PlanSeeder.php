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
            'interval' => 'month',
            'stripe_plan_id' => 'price_1OWGFwEEcid2xo4Inz2kapQ5',
            'lookup_key' => 'standard-month'
        ]);

        Plan::create([
            'name' => 'Optimal',
            'price' => 39,
            'interval' => 'month',
            'stripe_plan_id' => 'price_1OWGG9EEcid2xo4I4k6Wfq1k',
            'lookup_key' => 'optimal-month'
        ]);

        Plan::create([
            'name' => 'Maximum',
            'price' => 49,
            'interval' => 'month',
            'stripe_plan_id' => 'price_1OWGGLEEcid2xo4IHRDW5NPF',
            'lookup_key' => 'maximum-month'
        ]);

        Plan::create([
            'name' => 'Standard',
            'price' => 299,
            'interval' => 'year',
            'stripe_plan_id' => 'price_1OWGGvEEcid2xo4IpVY17DWG',
            'lookup_key' => 'standard-year'
        ]);

        Plan::create([
            'name' => 'Optimal',
            'price' => 399,
            'interval' => 'year',
            'stripe_plan_id' => 'price_1OWGGiEEcid2xo4IobGVBr5p',
            'lookup_key' => 'optimal-year'
        ]);

        Plan::create([
            'name' => 'Maximum',
            'price' => 499,
            'interval' => 'year',
            'stripe_plan_id' => 'price_1OWGGYEEcid2xo4IgWyuKGP4',
            'lookup_key' => 'maximum-year'
        ]);
    }
}
