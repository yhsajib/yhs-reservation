<?php

$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = [];
foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) === 2) {
        $myPost[$keyval[0]] = urldecode($keyval[1]);
    }
}


$req = 'cmd=_notify-validate';
foreach ($myPost as $key => $value) {
    $value = urlencode($value);
    $req .= "&$key=$value";
}


//$paypal_url = "https://ipnpb.paypal.com/cgi-bin/webscr"; // Live
$paypal_url = "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr"; // Sandbox

$ch = curl_init($paypal_url);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);
$res = curl_exec($ch);
curl_close($ch);

// STEP 4: Check response
if (strcmp($res, "VERIFIED") == 0) {

    // OPTIONAL: Load WordPress (if you want to access WP functions)
    require_once('../../../wp-load.php');

    // STEP 5: Handle IPN logic
    $payment_status = $_POST['payment_status'];
    $payer_email = $_POST['payer_email'];
    $txn_id = $_POST['txn_id'];
    $amount = $_POST['mc_gross'];
    $item_name = $_POST['item_name'];
    $custom = $_POST['custom']; // Optional custom data passed from PayPal

    // Log to a file
    file_put_contents(__DIR__ . '/ipn-log.txt', "VERIFIED\n" . print_r($_POST, true), FILE_APPEND);

    // Example: Create custom order post in WordPress
    if ($payment_status === "Completed") {
            // Redirect User to PayPal
         update_rv_order_status($txn_id, 'rv-completed');
    }else{
        // Handle other payment statuses (e.g., Pending, Failed)
        update_rv_order_status($txn_id, 'rv-pending');
    }

} else {
    // Invalid IPN
    file_put_contents(__DIR__ . '/ipn-log.txt', "INVALID\n" . print_r($_POST, true), FILE_APPEND);
}
