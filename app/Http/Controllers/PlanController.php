<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PlanController extends Controller
{

    /**
     * Create a plan in stripe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPlanInStripe(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string',
            'price' => 'required|integer',
            'interval' => 'required|string|in:month,year',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);
        }

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));


        $lookupKey = Str::snake($request->name . '-' . $request->interval);

        //check if the plan already exists
        $plan = Plan::where('lookup_key', $lookupKey)->first();
        if ($plan) {
            return response()->json([
                'message' => 'Plan already exists',
                'data' => $plan
            ]);
        }

        try {
            //create a product in stripe
            $product = \Stripe\Product::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            //create prices in stripe for the product
            $stripePrice = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => $request->price * 100,
                'currency' => 'usd',
                'recurring' => [
                    'interval' => $request->interval,
                ],
                'lookup_key' => $lookupKey,
            ]);

            //add the plan to the database
            $dbPlan = new Plan();

            if ($stripePrice && $stripePrice->active) {
                $dbPlan->name = $request->name;
                $dbPlan->price = $request->price;
                $dbPlan->interval = $request->interval;
                $dbPlan->stripe_plan_id = $stripePrice->id;
                $dbPlan->lookup_key = $lookupKey;
                $dbPlan->save();
            }


            return response()->json([
                'message' => 'Plan created successfully',
                'data' => $dbPlan
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
