<?php

namespace App\Http\Controllers\Api;

use App\Actions\Payment\Tenant\PaymentGatewayIpn;
use App\Enums\PaymentRouteEnum;
use App\Http\Controllers\Controller;
use App\Mail\BasicMail;
use App\Mail\BasicNotify;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Page;
use App\Models\ProductOrder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MpesaController extends Controller
{

    public static function get_paysoko_token(){
        $url=env('PAYSOKO_URL').'create-token';
        $email =env('PAYSOKO_EMAIL');
        $password=env('PAYSOKO_PASSWORD');
        $client = new \GuzzleHttp\Client(['headers' => ['Content-Type' => 'application/json']]);
        $data = [
            "email"     => $email,
            "password"   => $password
        ];
        $response = $client->post($url, [
            'form_params' => $data
        ]);
//         return $response->getBody();
        if ($response->getBody()){
            $resp_contents= json_decode($response->getBody());

            return $resp_contents->Token;
        }
    }



    public static function remote_get($endpoint, $credentials = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic " . $credentials));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        return curl_exec($curl);
    }

    public static function remote_post($endpoint, $data = array())
    {

        $curl = curl_init();
        $data_string = json_encode($data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                "Content-Type:application/json",
                'Authorization:'.'Bearer '.get_paysoko_token(),
            )
        );

        return curl_exec($curl);
    }
    public static function send_stk($phone,$amount,$merchant_email,$description= null,$callback = null){
        $phone = (substr($phone, 0, 1) == "+") ? str_replace("+", "", $phone) : $phone;
        $phone = (substr($phone, 0, 1) == "0") ? preg_replace("/^0/", "254", $phone) : $phone;
        $phone = (substr($phone, 0, 1) == "7") ? "254{$phone}" : $phone;
        $endpoint = env('PAYSOKO_URL')."mpesa-init-stk-email-based";
        $curl_post_data = array(
            "phone_number"=>$phone,
            "amount" => $amount,
            "description"=>$description,
            "merchant_email" =>$merchant_email,
            "merchant_account_number"=>"paysoko shop",
            "callback_url" =>url('api/reconcile'),
            "microservice" =>"dukaapp"
        );
        $response = self::remote_post($endpoint, $curl_post_data);

        $result = json_decode($response, true);

        return is_null($callback)
            ? $result
            : \call_user_func_array($callback, array($result));

    }

    public static function get_network($msisdn)
    {
        $safaricom = "/^(?:254|\+254|0)?((?:(?:7(?:(?:[01249][0-9])|(?:5[789])|(?:6[89])))|(?:1(?:[1][0-5])))[0-9]{6})$/";

        if (preg_match($safaricom, $msisdn)) {
            return true;

        } else {
            return false;
        }

    }

    public static function get_status($MerchantRequestID,$callback = null){
        $endpoint = env('PAYSOKO_URL')."get-safaricom-feedback";
        $curl_post_data = array(
            "merchant_request_id"=>$MerchantRequestID,

        );
        $response = self::remote_post($endpoint, $curl_post_data);

        $result = json_decode($response, true);

        return is_null($callback)
            ? $result
            : \call_user_func_array($callback, array($result));
    }

    public function MpesaStatus($key1){

        $response = new StreamedResponse(function() use ($key1) {
            // Get the $MerchantRequestID from session to check status
            $MerchantRequestID =$key1;
            $mpesa_payment = self::get_status($MerchantRequestID);
            Log::info($mpesa_payment);
            if(!is_null($mpesa_payment)){
                $mpesa_payment_status = $mpesa_payment['status'];

                if(!is_null($mpesa_payment_status)){

                    if($mpesa_payment_status == 200){

                        $ResultCode = json_decode($mpesa_payment['mpesa_record'][0])->ResultCode;
                        echo 'data: ' .$ResultCode. "\n\n";


                    }else{
                        $msg = 'END-OF-STREAM';
                        echo 'data: ' .$msg. "\n\n";
                    }
                }elseif ($mpesa_payment_status == 1){
                    $msg = 'END-OF-STREAM';
                    echo 'data: ' .$msg. "\n\n";
                }


            }else{
                $data = NULL;
                echo 'data: ' .$data. "\n\n";

            }

            // echo 'data: Received At ' . date("Y/m/d h:i:sa") . "\n\n";

            flush();
            sleep(1);
        });

        $response->headers->set("Content-Type", 'text/event-stream');
        $response->headers->set("Cache-Control", 'no-cache');

        return $response;
    }
    public function PayWithMpesa(Request $request){
        $rules =[
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mpesa_no' => 'required|string|max:255',
            'instruction' => 'sometimes|string',
            'quantity' => 'required|integer|min:1',
            'shipping_address' => 'sometimes|string',
            'page_id' => 'required|exists:pages,id',
        ];

        //run validation
        // Run the validation on the request data
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); // 422 is the HTTP status code for unprocessable entity
        }

        if (!self::get_network($request->mpesa_no)) {
            return response()->json([
                'success' => false,
                'errors' => 'Invalid Mpesa Number'
            ], 422); // 422 is the HTTP status code for unprocessable entity
        }



        $page = Page::find($request->page_id);
        $qty = (int) $request->quantity;
        $salePrice = (float) $page->sale_price;
        $total = $qty * $salePrice;
        $owner_email=$page->user->email;
        $phone = "254". substr($request->mpesa_no, -9);
//        Log::debug($request->all());
        $mpesa = self::send_stk($phone,$total,$owner_email,"Order ".$owner_email);
        Log::warning($mpesa);

        try{
            if($mpesa['Code']){
                if($mpesa['Code']=="200"){
                    $order = Order::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'phone' => $request->mpesa_no,
                        'notes' => $request->instruction,
                        'mpesa_no' => $request->mpesa_no,
                        'quantity' => $request->quantity,
                        'total' => $total,
                        'shipping_address' => $request->shipping_address,
                        'order_status' => 'pending',
                        'payment_status' => 'unpaid',
                        'tranx_ref' => $mpesa['MerchantRequestID'],
                        'page_id' => $request->page_id,
                    ]);
                    $mpesa['order']=$order->id;
                    return response()->json($mpesa);
                }
            }else{
                return response()->json([
                    'success' => false,
                    'data'=>$mpesa,
                    'errors' => 'An error occurred'
                ], 500);
            }
        }catch(\Exception $e){
            return response()->json([
                'code' => 500,
                'success' => false,
                'message'=>$mpesa['message'],
                'errors' => $e->getMessage()
            ], 500);
        }




    }

    public function MpesaSuccess(Request $request,$order_id){


        $order = Order::find($order_id);
        if($order){
            $order->update([
                'payment_status' => 'paid'
            ]);

        try{
            $owner=$order->page->user;
            $sub = __('New Order notification from DukaApp.com');
            $shop_owner_message="A new order has been placed on DukaApp.com. Order ID #".$order_id."\nTotal Amount:Kes ".$order->total."\nLogin to dispatch the order";
            $super_admin_message ="A new order has been placed on DukaApp.com. Order ID #".$order_id."\nTotal Amount:Kes ".$order->total."\nAsk ".$owner->phone." to Login and dispatch the order";;
            send_sms_notification($owner->phone,$shop_owner_message);
            send_sms_notification(env('ADMIN_NUMBER'),$super_admin_message);
            \Mail::to([$owner->email])->send(new BasicNotify($shop_owner_message,$sub));
            \Mail::to(['mosesk@paysokosystems.com','collinss@paysokosystems.com','Josepho@paysokosystems.com','Petem@paysokosystems.com'])->send(new BasicNotify($super_admin_message,$sub));
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
            return response()->json([
                'code' => 200,
                'success' => true,
                'message'=>$order,

            ], 200);
        }


        return response()->json([
            'code' => 404,
            'success' => false,
            'message'=>'Order not found!',

        ], 404);
    }
}
