<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalController extends Controller
{
    private $apiContext;

    public function __construct()
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                env('PAYPAL_CLIENT_ID'),
                env('PAYPAL_SECRET')
            )
        );
        $this->apiContext->setConfig(config('paypal.settings'));
    }

    public function createPayment(Request $request)
    {
        $amount = new Amount();
        $amount->setTotal($request->input('amount'));
        $amount->setCurrency('USD');

        $transaction = new Transaction();
        $transaction->setAmount($amount);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(url('/paypal/execute'))
            ->setCancelUrl(url('/'));

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer(new Payer())
            ->setTransactions([$transaction])
            ->setRedirectUrls($redirectUrls);

        try {
            $payment->create($this->apiContext);
            return response()->json(['approval_url' => $payment->getApprovalLink()]);
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }

    public function executePayment(Request $request)
    {
        $paymentId = $request->input('paymentId');
        $payerId = $request->input('PayerID');

        if (!$paymentId || !$payerId) {
            return response()->json(['error' => 'Payment ID or Payer ID missing'], 400);
        }

        $payment = Payment::get($paymentId, $this->apiContext);

        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            $result = $payment->execute($execution, $this->apiContext);
            return response()->json($result);
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }
}