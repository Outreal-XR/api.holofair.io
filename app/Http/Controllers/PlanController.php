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
}
