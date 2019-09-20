<?php

$scriptPath = dirname(__FILE__);
$path = realpath($scriptPath . '/./');
$filepath = explode("wp-content",$path);
define('WP_USE_THEMES', false);
require(''.$filepath[0].'/wp-blog-header.php');
require(''.$filepath[0].'/wp-load.php');

parse_str($_SERVER['QUERY_STRING'], $output);
$data = $output['data'];
$code = $output['code'];
$data = json_decode($data, TRUE);

function addBankRefToOrder($order_id, $bank_ref) {
    global $wpdb;
    $bank_ref_label = "_order_bank_ref";
    $bank_ref_value = $bank_ref;
   
    $wpdb->query( $wpdb->prepare( 
        "
            INSERT INTO $wpdb->postmeta
            ( post_id, meta_key, meta_value )
            VALUES ( %d, %s, %s )
        ", 
        $order_id, 
        $bank_ref_label, 
        $bank_ref_value 
    ) );
}

if ($code == 'PENDING') {

    $order_id = $data['reference_id'];
    $invoice_number = $data['bill_code'];
    $bill_date = $data['bill_date'];
    $bill_amount = $data['amount'];
    $currency = $data['currency'];
    $bill_description = $data['description'];
    $payment_url = $data['payment_url'];
    $biller_code_url = $data['biller_codes'];    
    $agencies = $data['app_or_agency_payment_methods'];
    $web_payments = $data['web_payment_methods'];
    $web_payment_arr = array();
    $agencies_arr = array();

    foreach( $web_payments as $web_payment) {
        $web_payment_arr[] = $web_payment['logo'];
    }
    $web_payment_arr = serialize($web_payment_arr);

    foreach( $agencies as $agency) {
        $agencies_arr[] = $agency['logo'] .'+'. $agency['biller_code'];
    }
    $agencies_arr = serialize($agencies_arr);

    global $wpdb;
    
    $mypaylater = $wpdb->get_row("SELECT * FROM wp_wc_paylater where order_id= $order_id");
    if(is_null($mypaylater)) {
        $wpdb->query("INSERT INTO wp_wc_paylater ( order_id, invoice_number, bill_date, bill_amount, currency, bill_description, payment_url, web_payment_methods, app_or_agency_payment_methods )
        VALUES ( $order_id, '$invoice_number', '$bill_date', $bill_amount, '$currency', '$bill_description','$payment_url', '$web_payment_arr','$agencies_arr' )");
    }

    $returnURL = site_url().'/my-account/paylater/?order_id='.$order_id;

    wp_redirect( $returnURL );
    exit;
}
