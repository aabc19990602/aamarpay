<?php

namespace Modules\Aamarpay\Http\Controllers;

use App\Abstracts\Http\PaymentController;
use App\Events\Document\PaymentReceived;
use App\Http\Requests\Portal\InvoicePayment as PaymentRequest;
use App\Models\Document\Document;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Payment extends PaymentController
{
    public $alias = 'aamarpay';

    public $type = 'redirect';

    public function show(Document $invoice, PaymentRequest $request)
    {

        
        $setting = $this->setting;
        
        $this->setContactFirstLastName($invoice);

        $setting['action'] = ($setting['mode'] == 'live') ? 'https://secure.aamarpay.com/request.php' : 'https://sandbox.aamarpay.com/request.php';

        $invoice_url = $this->getInvoiceUrl($invoice);


        $url = $setting['action']; // live url https://secure.aamarpay.com/request.php

        $fields = array(
            'store_id' => $setting['storeId'], 
             'amount' => $invoice->amount, 
            'payment_type' => 'VISA', 
            'currency' => $invoice->currency_code,  //currenct will be USD/BDT
            'tran_id' => rand(11111111111,99999999999), 
            'cus_name' => $invoice->contact_name,  
            'cus_email' => $invoice->contact_email,
            'cus_add1' => $invoice->customer_address, 
            'cus_add2' => $invoice->customer_address, 
            'cus_city' => $invoice->contact_city,  
            'cus_state' => $invoice->contact_state,  
            'cus_postcode' => $invoice->contact_zip_code, 
            'cus_country' => $invoice->contact_country,  
            'cus_phone' => $invoice->contact_phone, 
            'cus_fax' => 'Not¬Applicable',  //fax
            'desc' => 'payment description', 
            'success_url' => route('portal.aamarpay.invoices.complete', $invoice->id), //your success route
            'fail_url' => route('portal.aamarpay.invoices.complete', $invoice->id), //your fail route
            'cancel_url' => 'http://localhost/foldername/cancel.php', //your cancel url
            'opt_a' => $invoice->id,  //optional paramter
            'signature_key' => $setting['signatureKey']); 

            $fields_string = http_build_query($fields);

     
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_URL, $url);  
  
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $url = str_replace('"', '', stripslashes(curl_exec($ch)));	
        curl_close($ch); 

        // $url = $url_forward;
         $html = view('aamarpay::show', compact('setting', 'invoice', 'invoice_url', 'url'))->render();

        // end api call

    
        return response()->json([
            'code' => $setting['code'],
            'name' => $setting['name'],
            'description' => trans('aamarpay::general.description'),
            'redirect' => false,
            'html' => $html,
        ]);
    }



    public function test(){
        dd("d sa mkdnsak dnskaj ndkj");
    }


    public function return(Document $invoice, Request $request)
    {
        $success = true;

        switch ($request['payment_status']) {
            case 'Completed':
                $message = trans('messages.success.added', ['type' => trans_choice('general.payments', 1)]);
                break;
            case 'Canceled_Reversal':
            case 'Denied':
            case 'Expired':
            case 'Failed':
            case 'Pending':
            case 'Processed':
            case 'Refunded':
            case 'Reversed':
            case 'Voided':
                $message = trans('messages.error.added', ['type' => trans_choice('general.payments', 1)]);
                $success = false;
                break;
        }

        if ($success) {
            flash($message)->success();
        } else {
            flash($message)->warning();
        }

        $invoice_url = $this->getInvoiceUrl($invoice);

        return redirect($invoice_url);
    }

    public function complete(Document $invoice, Request $request)
    {
        $setting = $this->setting;

        $paypal_log = new Logger('Aamarpay');

        $paypal_log->pushHandler(new StreamHandler(storage_path('logs/aamarpay.log')), Logger::INFO);

        

        if (!$invoice) {
            return;
        }

        dd($invoice->ampunt);


        $url = ($setting['mode'] == 'live') ? 'https://ipnpb.paypal.com/cgi-bin/webscr' : 'https://www.sandbox.paypal.com/cgi-bin/webscr';



        $client = new Client(['verify' => false]);

        $paypal_request['cmd'] = '_notify-validate';

        foreach ($request->toArray() as $key => $value) {
            $paypal_request[$key] = urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
        }

        $response = $client->post($url, $paypal_request);

        if ($response->getStatusCode() != 200) {
            $paypal_log->info('PAYPAL_STANDARD :: CURL failed ', $response->getBody()->getContents());
        } else {
            $response = $response->getBody()->getContents();
        }

        if ($setting['debug']) {
            $paypal_log->info('PAYPAL_STANDARD :: IPN REQUEST: ', $request->toArray());
        }

        if ((strcmp($response, 'VERIFIED') != 0 || strcmp($response, 'UNVERIFIED') != 0)) {
            $paypal_log->info('PAYPAL_STANDARD :: VERIFIED != 0 || UNVERIFIED != 0 ' . $request->toArray());

            return;
        }

        switch ($request['payment_status']) {
            case 'Completed':
                $receiver_match = (strtolower($request['receiver_email']) == strtolower($setting['email']));

                $total_paid_match = ((double) $request['mc_gross'] == $invoice->amount);

                if ($receiver_match && $total_paid_match) {
                    event(new PaymentReceived($invoice, $request->merge(['type' => 'income'])));
                }

                if (!$receiver_match) {
                    $paypal_log->info('PAYPAL_STANDARD :: RECEIVER EMAIL MISMATCH! ' . strtolower($request['receiver_email']));
                }

                if (!$total_paid_match) {
                    $paypal_log->info('PAYPAL_STANDARD :: TOTAL PAID MISMATCH! ' . $request['mc_gross']);
                }
                break;
            case 'Canceled_Reversal':
            case 'Denied':
            case 'Expired':
            case 'Failed':
            case 'Pending':
            case 'Processed':
            case 'Refunded':
            case 'Reversed':
            case 'Voided':
                $paypal_log->info('PAYPAL_STANDARD :: NOT COMPLETED ' . $request->toArray());
                break;
        }
    }
}
