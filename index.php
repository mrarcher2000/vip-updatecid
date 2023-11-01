<?php
session_start();

$env = parse_ini_file('.env');


$user = '';
$codeblock = null;
$numbersArr = array();
$ns_access = '';
global $numbersHTML;
$_SESSION['numbersHTML'] = "";
$_SESSION['access'] = $env['ACCESS'];


// CHECK FOR nsToken IN URL AND PULL USER'S DOMAIN
if (isset($_REQUEST['cookiename'])) {
    $cookies = explode("-", $_REQUEST['cookiename']);
    $domain = $cookies[1];
    $user = $cookies[2];
    $ns_access = $_SESSION['access'];
    
    // $ns_access = $_REQUEST['nsToken'];
    // $_SESSION["access"] = $_REQUEST['nsToken'];

    $curl = curl_init();
    $headers = array(
        "Authorization: Bearer ${ns_access}",
        "Accept: application/json"
    );


    curl_setopt($curl, CURLOPT_URL, "https://crexendo-core-031-mci.crexendo.ucaas.run/ns-api/v2/domains/$domain/users/$user");
    error_log("Sending request to https://crexendo-core-031-mci.crexendo.ucaas.run/ns-api/v2/domains/$domain/users/$user");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    $decodedResponse = json_decode($response, true);

    // error_log($decodedResponse);
    // $codeblock = $decodedResponse;
    $_SESSION["domain"] = $decodedResponse['domain'];
    $_SESSION["user"] = $decodedResponse["user"];
    $_SESSION["caller-id-number"] = $decodedResponse["caller-id-number"];
    $_SESSION["caller-id-name"] = $decodedResponse["caller-id-name"];
    $_SESSION["login-username"] = $decodedResponse["login-username"];


    // error_log($_SESSION);
    // var_dump($_SESSION);

    curl_close($curl);

    loadNumbers($decodedResponse['domain']);

} else {
    die("Unauthorized");
}


function loadNumbers($domain) {
    $ns_access = $_SESSION["access"];
    $domain = $_SESSION["domain"];


    $numbersCURL = curl_init();
    $headers = array(
        "Authorization: Bearer ${ns_access}",
        "Accept: application/json"
    );

    curl_setopt($numbersCURL, CURLOPT_URL, "https://crexendo-core-031-mci.crexendo.ucaas.run/ns-api/v2/domains/".$domain."/phonenumbers");
    curl_setopt($numbersCURL, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($numbersCURL, CURLOPT_RETURNTRANSFER, true);

    // var_dump($headers);

    $response = curl_exec($numbersCURL);

    // var_dump(json_decode($response, true));
    $decodeNumbers = json_decode($response, true);

    if (curl_errno($numbersCURL)) { 
        var_dump(curl_error($numbersCURL)); 
    }


    foreach($decodeNumbers as $number) {
        $numbersArr[] = substr($number["phonenumber"], 1);
        // var_dump($numbersArr);
    }

    curl_close($numbersCURL);
    // listNumbers($numbersArr);
    $_SESSION['CIDNumbers'] = $numbersArr;
};

// TODO: refactor? still not listing any numbers at this time and not sure how to fix. var_dump is pulling numbers correctly but numbersHTML does not show in dropdown
function listNumbers($arr) {
    // var_dump($arr);
    // var_dump($numbersHTML);



    foreach ($arr as $number) {
        // $numbersHTML .= '<li onclick="updateNumber('. '`' . $_SESSION['access'] . '`' . ', ' . '`' . $_SESSION['login-username'] . '`' . ', ' . '`' . $_SESSION['caller-id-name'] . '`' . ', ' . '`' . $number . '`' . ')"><a class="dropdown-item" value="'. $number . '">' . $number . '</a></li>';
        echo ('<li onclick="updateNumber('. '`' . $_SESSION['access'] . '`' . ',' . '`' . $_SESSION['login-username'] . '`' . ', ' . '`' . $_SESSION['caller-id-name'] . '`' . ', ' . '`' . $number . '`' . ')"><a class="dropdown-item" value="'. $number . '">' . $number . '</a></li>');
    }
    // var_dump($numbersHTML);

}


?>

<html>

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="CrexendoVIP Webphone Iframe" />
        <meta name="author" content="Archer Nicholson CrexendoVIP Engineer" />
        <title>Caller ID Update</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    </head>

    <body>

        <div>
            <div class="card text-center" id="CIDUMenuCard">
                <div class="card-body">
                    <h5 class="card-title">Change Caller ID</h5>
                    <div class="card-text">
                        <p>Caller ID Number: </p>
                        <div class="btn-group">
                            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="numberDropdown"><?php echo htmlentities($_SESSION['caller-id-number'])  ?></button>
                            <ul class="dropdown-menu">
                                <?php 

                                // TODO:: FIGURE OUT A WAY TO LIST ALL NUMBERS AQUI
                                    listNumbers($_SESSION['CIDNumbers']);
                                ?>
                            </ul>
                        </div>
                        <div>
                            <p id="message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </body>

 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

    <script src="./updateCID.js"></script>
</html>