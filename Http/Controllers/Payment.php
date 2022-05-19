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


    public function test(Document $invoice){
        dd($invoice);
    }   


    public function complete(Invoice $invoice, Request $request)
    {

        // dd($invoice);

        $setting = $this->setting;

        $paypal_log = new Logger('Aamarpay');
        $paypal_log->pushHandler(new StreamHandler(storage_path('logs/aamarpay.log')), Logger::INFO);

    
        if (!$invoice) {
            return;
        }

        $inviId = $request->opt_a;
        $invoData = DB::table('documents')->where('id', $inviId)->first();
          
        $d = Invoice::find(1);
        dd($d);
        
         dd($invoice->id);
        
        
        $invoice->company_id = $invoData->company_id;
        $invoice->amount= $invoData->amount;
        $invoice->currency_code= $invoData->currency_code;
        // $invoice->id= $invoData->id;
        $invoice->category_id= $invoData->category_id;
        $invoice->contact_id= $invoData->contact_id;
        $invoice->type= $invoData->type;
        $invoice->document_number= $invoData->document_number;

        $invoice->order_number= $invoData->order_number;
        $invoice->status= $invoData->status;
        $invoice->issued_at= $invoData->issued_at;
        $invoice->due_at= $invoData->due_at;
        $invoice->currency_rate= $invoData->currency_rate;
        $invoice->contact_name= $invoData->contact_name;
        $invoice->contact_email= $invoData->contact_email;
        $invoice->contact_tax_number= $invoData->contact_tax_number;
        $invoice->contact_phone= $invoData->contact_phone;
        $invoice->contact_address= $invoData->contact_address;
        $invoice->contact_city= $invoData->contact_city;
        $invoice->contact_zip_code= $invoData->contact_zip_code;
        $invoice->contact_state= $invoData->contact_state;
        $invoice->contact_country= $invoData->contact_country;
        $invoice->notes= $invoData->notes;
        $invoice->footer= $invoData->footer;
        $invoice->parent_id= $invoData->parent_id;
        $invoice->created_from= $invoData->created_from;
        $invoice->created_by= $invoData->created_by;
        $invoice->created_at= $invoData->created_at;
        $invoice->updated_at= $invoData->updated_at;
        $invoice->deleted_at= $invoData->deleted_at;



        

        $request['amount'] = $request->other_currency;
        $request['currency'] = $request->currency_merchant;
        $request['company_id'] = $invoData->company_id;

        

        //  dd($request->all());


        switch ($request['status_code']) {
            case '2':
                event(new PaymentReceived($invoice, $request->merge(['type' => 'income', 'name' => 'aamarpay', '_token' => 'Qx97EsiyYNjAALD3jiseLBE0zG1vFN8zPGTp6LfM'])));
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
    }
}
