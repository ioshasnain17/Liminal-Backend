<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe;
use Charge;
use Illuminate\Support\Carbon;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\Transaction;

use PayPal\Api\RedirectUrls;
class SubscriptionController extends Controller
{
    protected $apiContext;

    public function __construct()
    {
        // Set up PayPal API context
        $paypalConfig = config('paypal');
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                $paypalConfig['sandbox']['client_id'],
                $paypalConfig['sandbox']['client_secret']
            )
        );
        $this->apiContext->setConfig($paypalConfig['settings']);
    }


    public function paySubscription(Request $request)
    {
        $stripe = new \Stripe\StripeClient('sk_test_51KGwr3EhNBd9PttKGUAeAWJFipO7TVXG6RkEJOgAoXogyvN422hTsRcagaSDSsbZcm93OkfrHD72HqteMEgy8nzU00frSoaq9H');
        $date = Carbon::now();
        $end_data = $date->addDays(30);
        $user = Auth::user();
        try {
            if($request->plan_id==1){
                Subscription::create
                ([
                    'user_id' =>Auth::id(),
                    'plan_id' => 1,
                    'payment_method' => 'free',
                    'payment_status' => 1,
                    'start_date' => \Illuminate\Support\Carbon::now(),
                    'end_date' => $end_data,
                ]);

            }
            else {
//             $token =$stripe->tokens->create([
//              'card' => [
//                'number' => '4242424242424242',
//                'exp_month' => '5',
//                'exp_year' => '2024',
//                'cvc' => '314',
//              ],
//            ]);
                if ($request->payment_method == "stripe") {
                    $token = $stripe->customers->createSource($user->stripe_customer_id, ['source' => 'tok_visa']);

                    $charge = $stripe->charges->create([
                        'amount' => $request->amount * 100, // amount in cents
                        'currency' => 'usd',
                        'description' => 'Payment for subscription',
                        'source' => $token['id'],
                        'customer' => $user->stripe_customer_id, // Customer ID from your database
                    ]);
                }
                if ($request->payment_method == 'paypal') {
                    $charge = Self::processPayment($request);
                    dd($charge);
                }

                if ($charge->status == 'succeeded') {


                    Subscription::updateOrcreate
                    (['user_id' => Auth::id()], [
                        'user_id' => Auth::id(),
                        'plan_id' => $request->plan_id,
                        'payment_method' => $request->payment_method,
                        'payment_status' => 1,
                        'start_date' => Carbon::now(),
                        'end_date' => $end_data,
                    ]);
                    $user->update(['subscribed' => true]);
                    return response()->json(['success' => true, 'message' => 'Payment successful.']);

                } else {
                    return response()->json(['success' => false, 'message' => 'Payment Unsuccessful.']);
                }
            }
            // Process further as needed
        } catch (Stripe\Exception\CardException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    public function processPayment($request)
    {
        // Set up the payment amount
        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($request->amount); // Assuming the amount is passed in the request

        // Set up the payer
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        // Set up the transaction
        $transaction = new Transaction();
        $transaction->setAmount($amount);
        // Set up the redirect URLs
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl('http://127.0.0.1:8000/api/login-user')
            ->setCancelUrl('http://127.0.0.1:8000/api/login-user');

        // Create the payment
        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions([$transaction]);

        // Execute payment and get the result
        try {
            $payment->create($this->apiContext);
            return response()->json(['paymentId' => $payment->getId()]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
