<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaPaymentController extends Controller
{

    public function generateAccessToken()
    {
        $consumer_key = "nV3odxpO3RDiUa7PR469UPtp6AHXRpVNjpNPqnGCfhPAdyat";
        $consumer_secret = "Pn4qzsFPuKAO0tdTlOji6J6xzAzAg2JGGA6lvWVHDg5cbk5GjmiaJAdNbor5HOb3";

        $credentials = base64_encode($consumer_key . ":" . $consumer_secret);
        $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic " . $credentials));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        $access_token = json_decode($curl_response);
        return $access_token->access_token;
    }

    public function STKPush()
    {
        $BusinessShortCode = 174379;
        $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
        $timestamp = Carbon::now()->format('YmdHis');
        $password = base64_encode($BusinessShortCode . $passkey . $timestamp);
        $Amount = 1;
        $PartyA = 254719196591;
        $PartyB = 174379;

        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);

        // Retrieve the access token
        $access_token = $this->generateAccessToken();

        // Set headers with the access token
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ));

        $curl_post_data = array(
            'BusinessShortCode' => $BusinessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $Amount,
            'PartyA' => $PartyA,
            'PartyB' => $PartyB,
            'PhoneNumber' => $PartyA,
            'CallBackURL' => 'https://5732-41-89-227-171.ngrok-free.app/api/callback',
            'AccountReference' => 'Dekut Cafeteria',
            'TransactionDesc' => 'Cafeteria Payment'
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        // Decode and return the response to inspect it
        $response_data = json_decode($curl_response, true);

        return response()->json($response_data);
    }

    public function mpesaValidation(Request $request)
    {
        $result_code = "0";
        $result_description = "Accepted validation request.";
        return $this->createValidationResponse($result_code, $result_description);
    }

    public function mpesaConfirmation(Request $request)
    {
        $rawContent = $request->getContent();
        Log::info('Raw Callback Data:', [$rawContent]);
    
        // Check if the content is empty
        if (empty($rawContent)) {
            Log::error('Received an empty payload');
            return response()->json(["C2BPaymentConfirmationResult" => "Failed"], 400);
        }
    
        // Decode JSON content
        $content = json_decode($rawContent);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode JSON', ['error' => json_last_error_msg()]);
            return response()->json(["C2BPaymentConfirmationResult" => "Failed"], 400);
        }
    
        if (isset($content->Body->stkCallback)) {
            $stkCallback = $content->Body->stkCallback;
            $callbackMetadata = $stkCallback->CallbackMetadata->Item ?? [];
    
            $mpesa = new MpesaTransaction();
            $mpesa->TransactionType = 'Pay Bill';
            $mpesa->TransID = collect($callbackMetadata)->firstWhere('Name', 'MpesaReceiptNumber')->Value ?? null;
            $mpesa->TransTime = collect($callbackMetadata)->firstWhere('Name', 'TransactionDate')->Value ?? null;
            $mpesa->TransAmount = collect($callbackMetadata)->firstWhere('Name', 'Amount')->Value ?? null;
            $mpesa->BusinessShortCode = $stkCallback->MerchantRequestID ?? null;
            $mpesa->MSISDN = collect($callbackMetadata)->firstWhere('Name', 'PhoneNumber')->Value ?? null;
            $mpesa->FirstName = 'Eliud Njuguna';
    
            // Validate essential fields
            if (!$mpesa->TransID || !$mpesa->TransAmount) {
                Log::error('Missing essential transaction details');
                return response()->json(["C2BPaymentConfirmationResult" => "Failed"], 400);
            }
    
            // Save transaction data
            $mpesa->save();
            Log::info('Saved M-Pesa Transaction:', $mpesa->toArray());
    
            return response()->json(["C2BPaymentConfirmationResult" => "Success"]);
        } else {
            Log::error('Unexpected M-Pesa Callback Structure', (array)$content);
            return response()->json(["C2BPaymentConfirmationResult" => "Failed"], 400);
        }
    }
    
    
    
    

    public function mpesaRegisterUrls()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization: Bearer ' . $this->generateAccessToken()));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array(
            'ShortCode' => "600141",
            'ResponseType' => 'Completed',
            'ConfirmationURL' => "https://www.blog.hlab.tech/api/v1/hlab/transaction/confirmation",
            'ValidationURL' => "https://www.blog.hlab.tech/api/v1/hlab/validation"
        )));
        $curl_response = curl_exec($curl);
        echo $curl_response;
    }
}
