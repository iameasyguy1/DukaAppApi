<?php

 function get_number_type($msisdn){
    $safaricom = "/^(?:254|\+254|0)?((?:(?:7(?:(?:[01249][0-9])|(?:5[789])|(?:6[89])))|(?:1(?:[1][0-5])))[0-9]{6})$/";
    $airtel = "/^(?:254|\+254|0)?((?:(?:7(?:(?:3[0-9])|(?:5[0-6])|(8[5-9])))|(?:1(?:[0][0-2])))[0-9]{6})$/";
    $orange= "/^(?:254|\+254|0)?(77[0-6][0-9]{6})$/";
    $equitel= "/^(?:254|\+254|0)?(76[34][0-9]{6})$/";
    if (preg_match($orange, $msisdn)){
        return true;
    }elseif(preg_match($safaricom, $msisdn)){
        return true;
    }elseif(preg_match($airtel, $msisdn)){
        return true;
    }elseif(preg_match($equitel, $msisdn)){
        return true;
    }else{
        return false;
    }

}


if (!function_exists('get_paysoko_token')){
    function get_paysoko_token(){
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

        if ($response->getBody()){
            $resp_contents= json_decode($response->getBody());

            return $resp_contents->Token;
        }
    }
}

if (!function_exists('register_vendor_to_paysoko')){
    function register_vendor_to_paysoko($first_name,$last_name,$phone,$email,$business_name,$site_url){

        $url = env('PAYSOKO_URL')."create-or-update-vendor";
        $data = [
            "first_name" => $first_name,
            "last_name" => $last_name,
            "country_code" => "254",
            "phone" => $phone,
            "email" => $email,
            "defaultCountry" => "ke",
            "business_name" => $business_name,
            "site_url" => $site_url,
            "type" => "Standard",
            "note" => "dukapp api",
            "Status" => "Moderation",
            "microservice" => "dukaapp"
        ];


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization:'.'Bearer '.get_paysoko_token(),
                'Content-Type:application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }
}

function sms_notify($mobile, $message)
{
    $response = 'error';
    $api_key = env('CG_API_KEY');
    $profile_code= env('CG_PROFILE_CODE');
    $k =gen_ref();
    $receiver ="254". substr($mobile, -9);
    $curl = curl_init();
    curl_setopt_array(
        $curl, array(

            CURLOPT_URL => 'https://sms.crossgatesolutions.com:18095/v1/bulksms/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'api-key:'.$api_key,
                'Content-Type: application/json'

            ),
            CURLOPT_POSTFIELDS =>json_encode(array(
                "profile_code" => $profile_code,
                "messages" => array(
                    array(
                        "mobile_number" => $receiver,
                        "message" => $message,
                        "message_type" => "promotional",
                        "message_ref" => $k
                    )
                ),
                "dlr_callback_url" => 'http://example.com'
            ))
        )

    );

    $response = curl_exec($curl);
    Log::info("cg >>".$response);
    $err = curl_error($curl);
    Log::error("cg >>".$err);
    curl_close($curl);
    if (!$err) {
        $response = 'success';
    } else {
        $response = $err;
    }
    return $response;

}
function sms_notify1($receiver, $message)
{

    $response = 'error';

    $api_key = env('TENA_API_KEY');
    $partnerID = env('TENA_PROFILE_ID');
    $shortcode = env('TENA_SHORTCODE');
    $curl = curl_init();
    $curl_post_data = array(
        "apikey" =>$api_key,
        "partnerID" => $partnerID,
        "mobile"=>$receiver,
        "message"=>$message,
        "shortcode" => $shortcode,
        "pass_type" => 'plain'
    );
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://sms.tenasms.com/api/services/sendsms/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>json_encode($curl_post_data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    Log::info("Tena ".$response);
    $err = curl_error($curl);
    Log::error("Tena >>".$err);
    curl_close($curl);
    if (!$err) {
        $response = 'success';
    } else {
        $response = 'error';
    }
    return $response;
}

function gen_ref() {
    $n =10;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0; $i < $n; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

function send_sms_notification($receiver, $message){
    $opt=sms_notify($receiver, $message);
    if ($opt!='success'){
        sms_notify1($receiver, $message);
    }
}

//seller balance
if (!function_exists('seller_balance')){
    function seller_balance($email){
        $url = env('PAYSOKO_URL')."get-wallet-balance";
        $data = [

            "email" => $email,
            "currency_code" => 'KES',
            "microservice" => "dukaapp"

        ];
        $curl = curl_init();
//
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization:'.'Bearer '.get_paysoko_token(),
                'Content-Type:application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        if (json_decode($response)->Code=="200"){
            return json_decode($response)->balance ;
        }else{
            return 'N/A';
        }
    }
}
//seller withdrawal
if (!function_exists('seller_withdraw_request')){
    function seller_withdraw_request($amount,$email,$phone){
        $url = env('PAYSOKO_URL')."request-withdrawal";
        $data = [
            "amount" =>$amount,
            "email" => $email,
            "currency_code" => 'KES',
            "phone" => "254". substr($phone, -9),
            "callback_url" =>url('api/withdrawal'),
            "platform"=>"ecommerce",
            "withdrawal_medium"=>1,
            "withdrawal_medium_destination"=>"254". substr($phone, -9),
            "microservice" =>"dukaapp"

        ];
        $curl = curl_init();
//
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization:'.'Bearer '.get_paysoko_token(),
                'Content-Type:application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
