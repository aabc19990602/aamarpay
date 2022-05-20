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
use App\Utilities\Date;
use App\Models\Document\Document as Invoice;

use DB;

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

        $url = $setting['action'];
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
            'fail_url' => route('portal.aamarpay.invoices.return', $invoice->id), //your fail route
            'cancel_url' => route('portal.aamarpay.invoices.return', $invoice->id), //your cancel url
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





    public function return(Document $invoice, Request $request)
    {
        $success = true;

        switch ($request['status_code']) {
            case '2':
                $message = trans('messages.success.added', ['type' => trans_choice('general.payments', 1)]);
                break;
            default:
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




    public function complete(Invoice $invoice, Request $request)
    {

        $setting = $this->setting;

        $paypal_log = new Logger('Aamarpay');
        $paypal_log->pushHandler(new StreamHandler(storage_path('logs/aamarpay.log')), Logger::INFO);

    
        if (!$invoice) {
            return;
        }

        $request['amount'] = $request->other_currency;
        $request['currency'] = $request->currency_merchant;
        $request['company_id'] = $invoice->company_id;



        switch ($request['status_code']) {
            case '2':
                event(new PaymentReceived($invoice, $request->merge(['type' => 'income', 'name' => 'aamarpay', '_token' => 'Qx97EsiyYNjAALD3jiseLBE0zG1vFN8zPGTp6LfM'])));
                $message = trans('messages.success.added', ['type' => trans_choice('general.payments', 1)]);
                flash($message)->success();
                break;
            case '1':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
            case '10':
            case '11':
            case '12':
            case '13':
                $paypal_log->info('PAYPAL_STANDARD :: NOT COMPLETED ' . $request->toArray());
                break;
        }

        $invoice_url = $this->getInvoiceUrl($invoice);

        return redirect($invoice_url);
    }
}
