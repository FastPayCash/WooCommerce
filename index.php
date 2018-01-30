<?php
/*
	Plugin Name: FastPay Woo Commerce Payment Gateway
	Plugin URI: http://fast-pay.cash/
	Description: FastPay Woo commerce Secured Wallet System
	Version: 1.0.0
	Author: JM Redwan
	Author URI: http://www.jmredwan.com
    Copyright: © 20015-2016 fastpay.
    License: GNU General Public License v3.0
    License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action('plugins_loaded', 'woocommerce_fastpay_init', 0);
function woocommerce_fastpay_init(){
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_fastpay extends WC_Payment_Gateway{
    public function __construct(){
      $this -> id = 'fastpay';
      $this -> medthod_title = 'fastpay';
      $this -> has_fields = false;

      $this -> init_form_fields();
      $this -> init_settings();

      $this -> title            = $this -> settings['title'];
            $this -> description      = $this -> settings['description'];
            $this -> merchant_id      = $this -> settings['merchant_id'];
	$this -> store_password   = $this -> settings['store_password'];
           $this->testmode              = $this->get_option( 'testmode' );
		   
            $this->testurl           = 'https://dev.fast-pay.cash/merchant/generate-payment-token';
            $this -> liveurl  = 'https://secure.fast-pay.cash/merchant/generate-payment-token';
			
$this -> liveProcessurl  = 'https://secure.fast-pay.cash/merchant/payment';
$this -> testProcessurl  = 'https://dev.fast-pay.cash/merchant/payment';

$this -> liveValidationURL  = 'https://secure.fast-pay.cash/merchant/payment/validation';
$this -> testValidationURL  = 'https://dev.fast-pay.cash/merchant/payment/validation';

      $this -> redirect_page_id = $this -> settings['redirect_page_id'];

      $this -> msg['message'] = "";
      $this -> msg['class'] = "";

      //add_action('init', array(&$this, 'check_fastpay_response'));
            //update for woocommerce >2.0
           // add_action( 'woocommerce_api_wc_fastpay', array( $this, 'check_fastpay_response' ) );
            add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_fastpay_response' ) );
            //add_action('valid-fastpay-request', array($this, 'successful_request'));
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
            add_action('woocommerce_receipt_fastpay', array($this, 'receipt_page'));
           // add_action('woocommerce_thankyou_fastpay',array($this, 'thankyou_page'));
   }
    function init_form_fields(){

       $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'jmredwan'),
                    'type' => 'checkbox',
                    'label' => __('Enable fastpay Payment Module.', 'jmredwan'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:', 'jmredwan'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'jmredwan'),
                    'default' => __('fastpay', 'jmredwan')),
                'description' => array(
                    'title' => __('Description:', 'jmredwan'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'jmredwan'),
                    'default' => __('Pay securely by fastpay Secure Wallet System.', 'jmredwan')),
                'merchant_id' => array(
                    'title' => __('Merchant Mobile No', 'jmredwan'),
                    'type' => 'text',
                    'description' => __('ACCESS CREDENTIALS')),
			
			 'store_password' => array(
                    'title' => __('Store Password', 'jmredwan'),
                    'type' => 'text',
                    'description' => __('ACCESS CREDENTIALS!It is required at payment validation.Note: No need to change the store password')),

            'testmode' => array(
                            'title'       => __( 'fastpay sandbox', 'woocommerce' ),
                            'type'        => 'checkbox',
                            'label'       => __( 'Enable fastpay sandbox', 'woocommerce' ),
                            'default'     => 'no',
                            'description' => __( 'fastpay sandbox can be used to test payments.' ),
                    ),
					
					
					
                  'fail_page_id' => array(
                    'title' => __('Return Page Fail'),
                    'type' => 'select',
                    'options' => $this -> get_pages('Select Page'),
                    'description' => "URL of Fail page"
                ),
                'redirect_page_id' => array(
                    'title' => __('Return Page'),
                    'type' => 'select',
                    'options' => $this -> get_pages('Select Page'),
                    'description' => "URL of success page"
                )
                );
    }

       public function admin_options(){
        echo '<h3>'.__('fastpay Payment Gateway', 'fastpay').'</h3>';
        echo '<p>'.__('fastpay is most popular Wallet System in Kurdistan').'</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';

    }

    /**
     *  There are no payment fields for fastpay, but we want to show the description if set.
     **/
    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }
    /**
     * Receipt Page
     **/
    function receipt_page($order){
        echo '<p>'.__('Thank you for your order, please click the button below to pay with fastpay.', 'fastpay').'</p>';
        echo $this -> generate_fastpay_form($order);
    }
    /**
     * Generate fastpay button link
     **/
    public function generate_fastpay_form($order_id){
 global $woocommerce;
       $order = new WC_Order($order_id);
            $order_id = $order_id.'_'.date("ymds");
            $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
            $fail_url = ($this -> fail_page_id=="" || $this -> fail_page_id==0)?get_site_url() . "/":get_permalink($this -> fail_page_id);
           $redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
            $fail_url = add_query_arg( 'wc-api', get_class( $this ), $fail_url );
            $declineURL = $order->get_cancel_order_url();
$amount = $order -> order_total;
$from = get_option('woocommerce_currency');

if($from=='IQD'){
$totalBillingAmount = round($amount, 2);
}
else {
$to = 'IQD';
$url  = "https://finance.google.com/finance/converter?a=$amount&from=$from&to=$to";
echo 
    $data = file_get_contents($url);
    preg_match("/<span class=bld>(.*)<\/span>/",$data, $converted);
    $converted = preg_replace("/[^0-9.]/", "", $converted[1]);
$totalBillingAmount = round($converted, 2);
}
            $fastpay_param = array(
                'merchant_mobile_no'      => $this -> merchant_id,
                'bill_amount'           => $totalBillingAmount,
                'order_id'         => $order_id,
                'success_url' => $redirect_url,
                'fail_url' => $fail_url,
                'cancel_url' => $declineURL
                );

 if($this->testmode == 'yes'){
                    $liveurl = $this->testurl;
                    $processURL = $this -> testProcessurl;
            }else{
                    $liveurl = $this->liveurl;
$processURL = $this -> liveProcessurl;
            }


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $liveurl,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $fastpay_param,
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
 // echo "cURL Error #:" . $err;
} else {
  //echo $response;
$tokens = json_decode($response,true);
}

$fastpay_args = array(
                'token' => $tokens['token']
                );

        $fastpay_args_array = array();
        foreach($fastpay_args as $key => $value){
          $fastpay_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }
	   
		
        return '<form action="'.$processURL.'" method="post" id="fastpay_payment_form">
            ' . implode('', $fastpay_args_array) . '
            <input type="submit" class="button-alt" id="submit_fastpay_payment_form" value="'.__('Pay via fastpay', 'fastpay').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'fastpay').'</a>
            <script type="text/javascript">
jQuery(function(){
jQuery("body").block(
        {
            message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', 'fastpay').'",
                overlayCSS:
        {
            background: "#fff",
                opacity: 0.6
    },
    css: {
        padding:        20,
            textAlign:      "center",
            color:          "#555",
            border:         "3px solid #aaa",
            backgroundColor:"#fff",
            cursor:         "wait",
            lineHeight:"32px"
    }
    });
    jQuery("#submit_fastpay_payment_form").click();});</script>
            </form>';


    }
    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id){
        global $woocommerce;
    	$order = new WC_Order( $order_id );
        return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url( true ));
    }

    /**
     * Check for valid fastpay server callback
     **/
    function check_fastpay_response(){
        global $woocommerce;
            $info = explode("_", $_REQUEST['order_id']);
            $order_id=$info[0];
           $order = wc_get_order($info[0] );
           $fail_url = ($this -> fail_page_id=="" || $this -> fail_page_id==0)?get_site_url() . "/":get_permalink($this -> fail_page_id);
           $fail_url = add_query_arg( 'wc-api', get_class( $this ), $fail_url );

            if($this->testmode == 'yes'){
                    $ValidationURL = $this -> testValidationURL;
            }else{
                    $ValidationURL = $this -> liveValidationURL;
            }



            if( isset($_REQUEST['order_id'])){
				
                $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
                $fail_url = ($this -> fail_page_id=="" || $this -> fail_page_id==0)?get_site_url() . "/":get_permalink($this -> fail_page_id);
                $order_id = $info[0];
                $this -> msg['class'] = 'error';
                $this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
				
				

$ValidationData = array(
                'merchant_mobile_no'      => $this -> merchant_id,
                'store_password'           => trim($this->store_password),
                'order_id'         => $_REQUEST['order_id']
                );
         
                $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $ValidationURL,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $ValidationData,
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
 // echo "cURL Error #:" . $err;
} else {
$resultsReturn = json_decode($response,true);
}
$status = $resultsReturn['data']['status'];
$code = $resultsReturn['code'];


$Respond = $_REQUEST;

if(array_key_exists('ipn', $Respond)){
return json_encode($Respond);
exit;
}

if($code == 200 && empty($err))
{	

	# TRANSACTION INFO
	$status = $resultsReturn['data']['status'];	
	$tran_date = $resultsReturn['data']['received_at'];
	$bank_tran_id = $resultsReturn['data']['transaction_id'];
	$card_no = $resultsReturn['data']['customer_account_no'];
	$message = '';
	
				
					$message .= 'Payment Status = ' . $status . "\n";
				    
					$message .= 'FastPay txnid = ' . $bank_tran_id . "\n";
					
					$message .= 'Payment Date = ' . $tran_date . "\n";  
				   
					$message .= 'Wallet Number = ' .$card_no . "\n"; 

                    if($status=='Success')
                    {
                        $pay_status = 'success';
                    }
                    elseif($status=='Failed' || $status=='Cancelled'){
                         $pay_status = 'failed';
                     }
                    else
                    {
                         $pay_status = 'failed';
                    }
                }
          //echo  $order_id;exit;
                if($order_id != ''){
                    try{
                        $order = wc_get_order($info[0] );
                        $merchant_id = $_REQUEST['[tran_id'];
                        $amount = $_REQUEST['amount'];
                       
                        $transauthorised = false;
                        //echo $pay_status;exit;
                                if($pay_status=="success"){
                                    //echo 'hi';exit;
                                    $transauthorised = true;
                                    $this -> msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                                    $this -> msg['class'] = 'success';
                                    $order -> update_status('Processing');
                                        $order -> payment_complete();
                                        $order -> add_order_note($message);
                                        $order -> add_order_note($this->msg['message']);
                                        $woocommerce -> cart -> empty_cart();
                                     $return_url = $order->get_checkout_order_received_url();
                                    $redirect_url  = str_replace( 'http:', 'http:', $return_url );
                                   
                                }
								else if($pay_status=="failed"){
                                    $order -> update_status('failed');
                                    $order -> add_order_note($message);
                                    $this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been Failed.";
                                    $this -> msg['class'] = 'Failed';
                                   //$woocommerce -> cart -> empty_cart();
                                   //wc_add_notice( __( 'Unfortunately your card was declined and the order could not be processed. Please try again with a different card or payment method.', 'Error' ) );
                                   wc_add_notice( __( 'Unfortunately your card was declined and the order could not be processed. Please try again with a different card or payment method.', 'woocommerce' ), 'error' );
                                   $redirect_url  = $order->get_cancel_order_url();
                                    
                                   $redirect_url  = $fail_url;
                                }
                                else{
                                    $this -> msg['class'] = 'error';
                                    $this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                                    //Here you need to put in the routines for a failed
                                    //transaction such as sending an email to customer
                                    //setting database status etc etc
                                }
                           
                            //removed for WooCOmmerce 2.0
                            //add_action('the_content', array(&$this, 'showMessage'));
                        }catch(Exception $e){
                            // $errorOccurred = true;
                            $msg = "Error";
                        }

                }
               
                wp_redirect( $redirect_url );
            }

    }

    function showMessage($content){
            return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
        }
     // get all pages
    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }
}
   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_fastpay_gateway($methods) {
        $methods[] = 'WC_fastpay';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_fastpay_gateway' );
}
