<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Get all the plans in the application
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlans()
    {
        $plans = Plan::all();

        return response()->json([
            'message' => 'Plans fetched successfully',
            'data' => $plans
        ]);
    }

    /**
     * Get the current plan of the authenticated user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserCurrentPlan()
    {
        $user = auth()->user();

        $userCurrentPlan = $user->subscription()->with('plan')->first();

        return response()->json([
            'message' => 'User current plan fetched successfully',
            'data' => $userCurrentPlan
        ]);
    }

    /**
     * add free plan to all the users that don't have a plan
     * @todo remove this function after adding the free plan to all the users
     */
    public function addFreePlanToUsers()
    {
        $users = User::doesntHave('subscription')->get();

        $freePlan = Plan::where('name', 'free')->first();
        foreach ($users as $user) {
            $user->subscription()->create([
                'plan_id' => $freePlan->id,
                'end_at' => now()->addMonth(),
            ]);
        }

        return response()->json([
            'message' => 'Free plan added to all the users that don\'t have a plan'
        ]);
    }

    /**
     * Subscribe the authenticated user to a plan
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe($plan_id)
    {
        $user = auth()->user();

        $plan = Plan::findOrFail($plan_id);

        $userPlan = $user->subscription()->with('plan')->first();

        //if the user already has a plan, check if it's the same plan
        if ($userPlan) {
            if ($userPlan->plan_id == $plan->id) {
                return response()->json([
                    'message' => 'You\'re already subscribed to the ' . $plan->name . ' plan',
                ]);
            }
        }

        //create or update the subscription
        $user->subscription()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan_id' => $plan->id,
                'start_at' => now(),
                'end_at' => $plan->duration_type ? ($plan->duration_type == 'monthly' ? now()->addMonth() : now()->addYear()) : null,
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'You\'ve been subscribed to the ' . $plan->name . ' plan successfully',
        ]);
    }
}
