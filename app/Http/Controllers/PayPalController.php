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
use PayPal\Api\PaymentExecution;

class PayPalController extends Controller
{
    protected $apiContext;

    public function __construct()
    {
    $this->apiContext = new ApiContext(
    new OAuthTokenCredential(
    config('paypal')['sandbox']['client_id'],
    config('paypal')['sandbox']['client_secret']
    )
    );

    $this->apiContext->setConfig([
    'mode' => config('sandbox'),
    ]);
    }

    public function createPayment(Request $request)
    {
    $payer = new Payer();
    $payer->setPaymentMethod('paypal');

    $amount = new Amount();
    $amount->setTotal($request->input('amount'));
    $amount->setCurrency('USD');

    $transaction = new Transaction();
    $transaction->setAmount($amount);

    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl(route('paypal.execute'))
    ->setCancelUrl(route('paypal.cancel'));

    $payment = new Payment();
    $payment->setIntent('sale')
    ->setPayer($payer)
    ->setTransactions([$transaction])
    ->setRedirectUrls($redirectUrls);

    try {
    $payment->create($this->apiContext);

        $approvalLink = $payment->getApprovalLink();

        // Redirect the user to PayPal for payment approval
        return redirect($approvalLink);
    } catch (\Exception $e) {
    return $e->getMessage();
    }
    }

    public function executePayment(Request $request)
    {
        Log::info($request->all());
        // Retrieve payment ID from the request (assuming it's passed as a query parameter)
        $paymentId = 'PAYID-MYERQRY1RC15742CN538734V';

        $payerId = $request->query('PayerID');
        dd($payerId);

        // Execute the payment
        $payment = Payment::get($paymentId, $this->apiContext);
        $execution = new PaymentExecution();
        $execution->setPayerId($request->query('PayerID')); // Payer ID received from the approval redirect
        $payment->execute($execution, $this->apiContext);

        $transactions = $payment->getTransactions();
        $transactionId = $transactions[0]->getId();

        // Redirect the user to a success page
        return redirect()->route('payment.success', ['transaction_id' => $transactionId]);
    }
    public function cancelPayment(){
        return "success";
    }
}
