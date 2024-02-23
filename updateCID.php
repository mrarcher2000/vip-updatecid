<?php
ini_set('session.cookie_samesite', 'None');
session_set_cookie_params(['samesite' => 'None']);
session_start();

// $access = $_SESSION["access"];

if (!$access) {
    $referrerURL = $_SERVER['HTTP_REFERER'];
    $splitURL = explode("&", $referrerURL);
    error_log($splitURL[1]);
    $splitURL2 = explode("=", $splitURL[1]);

    error_log($splitURL2[0] . "\n" . $splitURL2[1]);

    if ($splitURL2[0] == "cookie") {
        $access = $splitURL2[1];
    }
};

error_log($access);

if ( isset($access) && isset($_REQUEST['uid']) ) {
    error_log("Starting updateCID request");
    $curl = curl_init();

    $uid = $_REQUEST['uid'];
    $callid_name = $_REQUEST['callid_name'];
    $callid_nmbr = $_REQUEST['callid_nmbr'];
    global $access;

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.crexendovip.com/ns-api/?object=subscriber&action=update",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\n\t\"uid\": \"$uid\",\n\t\"callid_name\": \"$callid_name\",\n\t\"callid_nmbr\": \"$callid_nmbr\"\n}",
        CURLOPT_HTTPHEADER => [
            "Authorization: $access",
            "Content-Type: application/json"
        ],
    ]);


    $response = curl_exec($curl);
    $err = curl_error($curl);

    $responseData = curl_getinfo($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    // curl_close($curl);

    error_log($response);
    error_log("Status: $statusCode");


    if ($err) {
        echo "Error #:" . $err;
        http_response_code($statusCode);
        curl_close($curl);
        exit;
    }


    if ($statusCode == 202) {
        http_response_code($statusCode);
        curl_close($curl);
        exit;
    }
}

?>