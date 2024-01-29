<?php
session_start();


// --- CREATE DATABASE CONNECTION --- //
$env = parse_ini_file('.env');
$db_name = $env['DBNAME'];
$db_user = $env['DBUSER'];
$db_password = $env['DBPASS'];
$pdo = new PDO("mysql:host=localhost;port=3306;dbname=$db_name",$db_user,$db_password);



// --- DEFINE VARIABLES FOR NUMBERS --- //
$user = '';
$codeblock = null;
$numbersArr = array();
// $ns_access = '';
$_SESSION['access'] = "";
global $numbersHTML;
$_SESSION['numbersHTML'] = "";


// --- MONITOR IP ACCESS AND USAGE --- //
$USERIPADDRESS = $_SERVER['REMOTE_ADDR'];

if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $USERIPADDRESS = $_SERVER['HTTP_X_FORWARDED_FOR'];
}
error_log("Page accessed by $USERIPADDRESS");



// --- NETSAPIENS AND VIP AUTHORIZATION --- //
$auth = curl_init();

curl_setopt_array($auth, [
    CURLOPT_URL => "https://crexendo-core-031-dfw.cls.iaas.run/ns-api/oauth2/token/?grant_type=password&client_id=archertest&client_secret=90056b1f11f8c87fff30fd1b5acafd04&username=anicholson%40crexendo.com&password=Crexendo2022!",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "",
]);

$authResponse = curl_exec($auth);
$err = curl_error($auth);

$decodeAuth = json_decode($authResponse, true);
// print("response from auth request: " . $authResponse);

if (curl_errno($auth)) {
    error_log("Error on load. \n" . curl_error($auth));
};

curl_close($auth);

$_SESSION['access'] = $decodeAuth['access_token'];
$ns_access = $decodeAuth['access_token'];


// CHECK FOR NSTOKEN IN URL AND PULL USER'S DOMAIN
if (isset($_REQUEST['cookiename'])) {
    $cookies = explode("-", $_REQUEST['cookiename']);
    $domain = $cookies[1];
    $user = $cookies[2];
    

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://portal.crexendovip.com/ns-api/?object=subscriber&action=read",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\n\t\"user\": \"${user}\",\n\t\"domain\": \"${domain}\"\n}",
        CURLOPT_HTTPHEADER => [
          "Authorization: Bearer ${ns_access}",
          "Content-Type: application/json"
        ],
      ]);

    $response = curl_exec($curl);
    // $decodedResponse = json_decode($response, true);

    // echo($response);
    $xml = new SimpleXMLElement($response);

    $_SESSION["domain"] = $xml->subscriber->domain;
    $_SESSION["user"] = $xml->subscriber->user;
    $_SESSION["caller-id-number"] = $xml->subscriber->{'callid_nmbr'};
    $_SESSION["caller-id-name"] = $xml->subscriber->{'callid_name'};
    $_SESSION["login-username"] = $xml->subscriber->{'subscriber_login'};
    // var_dump($_SESSION);


    // error_log($_SESSION);
    // var_dump($_SESSION);

    curl_close($curl);

    loadNumbers($_SESSION['domain']);
    logUser();

} else {
    die("Unauthorized");
}



// --- LOG USER AND DOMAIN ACCESSED --- //
function logUser() {
    $errorlogUser = $_SESSION['user'];
    $errorlogDomain = $_SESSION['domain'];
    $errorlogIP = $_SERVER['REMOTE_ADDR'];
    error_log("ACCESS GRANTED TO $errorlogUser@$errorlogDomain BY IP $errorlogIP");
}


// --- PULL NUMBERS FROM DOMAIN --- //
function loadNumbers($domain) {
    $ns_access = $_SESSION["access"];
    $domain = $_SESSION["domain"];


    $numbersCURL = curl_init();
    curl_setopt_array($numbersCURL, [
        CURLOPT_URL => "https://portal.crexendovip.com/ns-api/?object=phonenumber&action=read",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\n\t\"dialplan\":\"DID Table\",\n\t\"dest_domain\": \"${domain}\"\n}",
        CURLOPT_HTTPHEADER => [
          "Authorization: Bearer ${ns_access}"
        ],
      ]);

    // var_dump($headers);

    $response = curl_exec($numbersCURL);

    // var_dump(json_decode($response, true));
    // $decodeNumbers = json_decode($response, true);

    $numXML = new SimpleXMLElement($response);


    if (curl_errno($numbersCURL)) { 
        var_dump(curl_error($numbersCURL)); 
    }

    for($i=0; $i<$numXML->count(); $i++) {
        $matchrule = strval($numXML->phonenumber[$i]->matchrule);
        $matchrule = str_replace("sip:1", "", $matchrule);
        $matchrule = str_replace("@*", "", $matchrule);

        if (strpos($matchrule, "sip:52") !== false) {
            $matchrule = str_replace("sip:", "", $matchrule);
        }

        $numbersArr[] = $matchrule;
    }



    curl_close($numbersCURL);
    $_SESSION['CIDNumbers'] = $numbersArr;
    checkDB();
};



// --- CHECK DATABASE FOR USER'S AVAILABLE CID NUMBERS --- //
function checkDB() {
    $loggedUser = $_SESSION['user'];
    $loggedDomain = $_SESSION['domain'];
    global $pdo;

    $stmt = $pdo->prepare('SELECT Users.UserID, Users.Name, Users.Extension, Domains.Name FROM Users JOIN Domains ON Users.DomainID=Domains.DomainID WHERE Users.Extension = :userext AND Domains.Name = :userdomain');
    $stmt->execute(array(':userext' => $loggedUser, ':userdomain' => $loggedDomain));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($row != false) {
        // var_dump($row);

        $_SESSION['user_id'] = $row['UserID'];
        // var_dump($_SESSION['user_id']);
        $usernumbers = array();

        $stmt2 = $pdo->prepare('SELECT * FROM Numbers WHERE UserID = :user_id');
        $stmt2->execute(array(':user_id' => $_SESSION['user_id']));

        while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $usernumbers[] = $row2['Number'];
        }

        $_SESSION['UserNumbers'] = $usernumbers;
    } else {
        print('No Data Found');
    }
}



// --- ON SCREEN NUMBER LISTING --- //
function listNumbers($arr) {
    // var_dump($_SESSION['UserNumbers']);

    foreach ($arr as $number) {
        if (in_array($number, $_SESSION['UserNumbers'])) {
            echo ('<li onclick="updateNumber('. '`' . $_SESSION['access'] . '`' . ',' . '`' . $_SESSION['login-username'] . '`' . ', ' . '`' . $_SESSION['caller-id-name'] . '`' . ', ' . '`' . $number . '`' . ')"><a class="dropdown-item" value="'. $number . '">' . $number . '</a></li>');
        }
        // $numbersHTML .= '<li onclick="updateNumber('. '`' . $_SESSION['access'] . '`' . ', ' . '`' . $_SESSION['login-username'] . '`' . ', ' . '`' . $_SESSION['caller-id-name'] . '`' . ', ' . '`' . $number . '`' . ')"><a class="dropdown-item" value="'. $number . '">' . $number . '</a></li>';
    }
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
        <!--  
        THIS CODE WAS WRITTEN BY SOFTWARE DEV AND VOIP ENGINEER ARCHER NICHOLSON FOR USE ON THE CREXENDOVIP WEBPHONE. 
        ALL INFORMATION IS PROPRIETARY AND IS NOT TO BE SHARED WITH ANY THIRD PARTIES. 
        TO REPORT A BUG, PLEASE REACH OUT TO ANICHOLSON@CREXENDO.COM 
        -->

        <div>
            <div class="card text-center" id="CIDUMenuCard">
                <div class="card-body">
                    <h5 class="card-title">Change Caller ID</h5>
                    <div class="card-text">
                        <p>Caller ID Number: </p>
                        <div class="btn-group">
                            <button class="btn dropdown-toggle" style="background-color: rgb(0,118,206); color: rgba(255,255,255,0.87);" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="numberDropdown"><?php echo htmlentities($_SESSION['caller-id-number'])  ?></button>
                            <ul class="dropdown-menu">
                                <?php 
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