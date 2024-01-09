<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function subscribe(Request $request, $plan_id)
    {
        $user = auth()->user();

        if ($request->isFree) {
            $user->stripe_plan_id = null;
            $user->save();

            return response()->json([
                'message' => 'Subscription successful'
            ]);
        }

        $plan = Plan::where('stripe_plan_id', $plan_id)->first();

        if (!$plan) {
            return response()->json([
                'message' => 'Plan not found'
            ], Response::HTTP_NOT_FOUND);
        }

        //check if the user already has a subscription
        $userSubscription = Subscription::where('user_id', $user->id)->where('plan_id', $plan->id)->where('status', 'paid')->latest()->first();
        $paymentEndAt = $userSubscription ? ($userSubscription->payment ? $userSubscription->payment->end_at : null) : null;

        if ((int)$user->stripe_plan_id === $plan->id && $userSubscription && $paymentEndAt > now()->timestamp) {
            return response()->json([
                'message' => 'You already have a subscription'
            ], Response::HTTP_BAD_REQUEST);
        }

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        $lineItems = [
            [
                'price' => $plan->stripe_plan_id,
                'quantity' => 1,
            ],
        ];

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'customer_email' => $user->email,
                'line_items' => $lineItems,
                'mode' => 'subscription',
                'success_url' => redirect()->away(env('FRONT_URL') . '/payments/success')->getTargetUrl() . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => redirect()->away(env('FRONT_URL') . '/payments/cancel')->getTargetUrl(),
            ]);

            //create a subscription in our database
            Subscription::create([
                'user_id' => $user->id,
                'session_id' => $session->id,
                'price' => $plan->price,
                'status' => 'unpaid',
                'plan_id' => $plan->id,
            ]);

            //update user plan
            $user->stripe_plan_id = $plan->id;
            $user->save();


            return response()->json([
                'url' => $session->url
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function success(Request $request)
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $sessionId = $request->session_id;

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if (!$session) {
                throw new \Exception('Session not found');
            }

            $user = auth()->user();
            $subscription = Subscription::where('session_id', $sessionId)->where('user_id', $user->id)
                ->latest()->first();

            if (!$subscription) {
                $user->stripe_plan_id = null;
                $user->save();

                throw new \Exception('Subscription not found');
            }

            if ($subscription->status === 'paid' && $subscription->payment) {
                throw new \Exception('Subscription already paid');
            }

            $stripeSubscription = $stripe->subscriptions->retrieve(
                $session->subscription
            );
            //create a payment in our database
            $payment = Payment::create([
                'price' => $session->amount_total / 100,
                'subscription_id' => $subscription->id,
                'st_customer_id' => $session->customer,
                'st_subscription_id' => $session->subscription,
                'st_payment_intent_id' => $session->payment_intent,
                'st_payment_method' => $session->payment_method_types[0],
                'st_payment_status' => $session->payment_status,
                'date' => $session->created,
                'end_at' => $stripeSubscription->current_period_end,
            ]);

            //update subscription status
            $subscription->status = 'paid';
            $subscription->payment_id = $payment->id;
            $subscription->save();

            return response()->json([
                'message' => 'Payment successful',
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function cancel(Request $request)
    {
        return redirect()->away(env('FRONT_URL') . '/payments/cancel');
    }
}
