<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	if(get_option('woocommerce_fastpay_settings')!='') {
		$data=get_option('woocommerce_fastpay_settings');
		if ($data['store_id'] != '' || $data['store_password'] != '') {
			$store_id = $data['store_id'];
			$store_passwd = $data['store_password'];
		} else {
			die("Invalid or Empty Information ");
		}

		if($data[ 'testmode'] == 'yes') {
			$requested_url = "https://dev.fast-pay.cash/merchant/payment/validation";
		} else {
			$requested_url = "https://secure.fast-pay.cash/merchant/payment/validation";
		}
	} else {
		die("FASTPAY payment gateway is not enabled!");
	}
	
		$post_data = array();
		$post_data['merchant_mobile_no']=$store_id;
		$post_data['store_password']= $store_passwd;
		$post_data['order_id']=$_POST['order_id'];

		$handle = curl_init();
		curl_setopt($handle, CURLOPT_URL, $requested_url );
		curl_setopt($handle, CURLOPT_TIMEOUT, 10);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($handle, CURLOPT_POST, 1 );
		curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

		$result = curl_exec($handle);

		$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		if($code == 200 && !( curl_errno($handle)))
		{

			# TO CONVERT AS ARRAY
			# $result = json_decode($result, true);

			# TO CONVERT AS OBJECT
			$result = json_decode($result);

			# TRANSACTION INFO
			$messages = $result->messages;
			$code = $result->code; #if $code is not 200 then something is wrong with your request.
				$data = $result->data;
				
				
				if($order->get_total()== trim($data['bill_amount']))
						{ 
							if($data['status']=='Success') 
							{ 
								if($order->get_status() == 'pending')
								{
									if($_POST['customer_account_no'] != "")
									{							        
										$order->update_status('Processing');
										$order->payment_complete();
										$result_msg =  "Hash validation success.";
									}
									
								}	
								else
								{
									$result_msg=  "Order already in processing Status";
								}
							}
							else
							{
								 $result_msg=  "Your Validation id could not be Verified";
							}
						}

					echo $result_msg;
			

		} else {

			echo "Failed to connect with FastPay";
		}
   


	
?>
