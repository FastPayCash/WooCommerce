<?php
/*
  Plugin Name: Fastpay V2(Hosted)-WooCommerce WP V5.x.x
  Plugin URI: https://secure.fast-pay.cash/docs/
  Description: This plugin allows you to accept payments on your WooCommerce store from customers using Fastpay.
  Version: 2.1.0
  Author: Leton MIah
  Author Email: leton.miah@fast-pay.cash
  Copyright: © 2018-2020 Fastpay.
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if (!defined('ABSPATH')) exit; // Exit if accessed directly
add_action('plugins_loaded', 'woocommerce_fastpay_init', 0);
add_action('plugins_loaded', array(Create_fastpay_ipn_page_url::get_instance(), 'setup')); // IPN page setup

function woocommerce_fastpay_init()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_fastpay extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'fastpay';
            $this->medthod_title = 'FastPay';
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title            = $this->settings['title'];
            $this->description      = $this->settings['description'];
            $this->store_id      = $this->settings['store_id'];
            $this->store_id      = $this->settings['store_id'];
            $this->store_password   = $this->settings['store_password'];
            $this->testmode           = $this->get_option('testmode');
            $this->testurl            =  "https://dev.fast-pay.cash/";
            $this->liveurl          =  "https://secure.fast-pay.cash/";
            $this->redirect_page_id = $this->settings['redirect_page_id'];

            $this->msg['message'] = "";
            $this->msg['class'] = "";

          
			add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_Fastpay_response'));
        
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }
			add_action('woocommerce_receipt_fastpay', array($this, 'receipt_page'));
        }
        function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enabled', 'NGC'),
                    'type' => 'checkbox',
                    'label' => __('Enable FastPay Payment Module.', 'NGC'),
                    'default' => 'no'
                ),
                'testmode' => array(
                    'title'       => __('Testmode', 'woocommerce'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable Testmode', 'woocommerce'),
                    'default'     => 'no',
                    'description' => __('Use Sandbox (testmode) API for development purposes. Don\'t forget to uncheck before going live.'),
                ),
                'title' => array(
                    'title' => __('Title to show', 'NGC'),
                    'type' => 'text',
                    'description' => __('This will be shown as the payment method name on the checkout page.', 'NGC'),
                    'default' => __('FastPay', 'NGC')
                ),
                'description' => array(
                    'title' => __('Description to show', 'NGC'),
                    'type' => 'textarea',
                    'description' => __( 'This will be shown as the payment method description on the checkout page.', 'NGC'),
                    'default' => __('Pay securely by Mobile Wallet through FastPay.', 'NCG')
                ),
                'store_id' => array(
                    'title' => __('Store ID', 'NCG'),
                    'type' => 'text',
                    'description' => __( 'API store id <span style="color: red;">(NOT the merchant panel id)</span>. You should obtain this informaton from FastPay.')
                ),
                'store_password' => array(
                    'title' => __('Store Password', 'NGC'),
                    'type' => 'text',
                    'description' => __( 'API store password <span style="color: red;">(NOT the merchant panel password)</span>. You should obtain this informaton from FastPay.')
                ),
                'redirect_page_id' => array(
                    'title' => __('Select Success Page'),
                    'type' => 'select',
                    'options' => $this->get_pages( 'Select Success Page'),
                    'description' => "User will be redirected here after a successful payment."
                ),
                'fail_page_id' => array(
                    'title' => __('Fail / Cancel Page'),
                    'type' => 'select',
                    'options' => $this->get_pages( 'Select Fail / Cancel Page'),
                    'description' => "User will be redirected here if transaction fails or get canceled."
                ),
                'ipnurl' => array(
                    'title' => __('IPN (Instant Payment Notification) URL', 'NCG'),
                    'type' => 'text',
                    'default' => __(get_site_url(null, null, null) . '/index.php?fastpayipn', 'NGC'),
                    'description' => __('Copy this URL and set as "IPN URL" in your Merchant Panel.')
                ),
            );
        }

        public function admin_options()
        {
            echo '<h3>' . __('FastPay Payment Gateway', 'fastpay') . '</h3>';
            echo '<p>' . __('Configure parameters to start accepting payments.') . '</p>';
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            echo '</table>';
        }

        function plugins_url($path = '', $plugin = '')
        {
            $path = wp_normalize_path($path);
            $plugin = wp_normalize_path($plugin);
            $mu_plugin_dir = wp_normalize_path(WPMU_PLUGIN_DIR);

            if (!empty($plugin) && 0 === strpos($plugin, $mu_plugin_dir)) {
                    $url = WPMU_PLUGIN_URL;
                } else {
                    $url = WP_PLUGIN_URL;
                }

            $url = set_url_scheme($url);

            if (!empty($plugin) && is_string($plugin)) {
                $folder = dirname(plugin_basename($plugin));
                if ('.' != $folder){
                    $url .= '/' . ltrim($folder, '/');
                }
            }

            if ($path && is_string($path))
            {
                $url .= '/' . ltrim($path, '/');
            }

            /**
             * Filters the URL to the plugins directory.
             *
             * @since 2.8.0
             *
             * @param string $url    The complete URL to the plugins directory including scheme and path.
             * @param string $path   Path relative to the URL to the plugins directory. Blank string
             *                       if no path is specified.
             * @param string $plugin The plugin file path to be relative to. Blank string if no plugin
             *                       is specified.
             */
            return apply_filters('plugins_url', $url, $path, $plugin);
        }

        /**
         *  There are no payment fields for fastpay, but we want to show the description if set.
         **/
        function payment_fields()
        {
            if ($this->description) echo wpautop(wptexturize($this->description));
        }

        /**
         * Receipt Page
         **/
        function receipt_page($order)
        {
            echo '<p>' . __('Thank you for your order, please click the button below to pay with FastPay.', 'fastpay') . '</p>';
            echo $this->generate_fastpay_form($order);
        }

        /**
         * Generate fastpay button link
         **/
        public function generate_fastpay_form($order_id)
        {
            global $woocommerce;
            // global $product;
            $order = new WC_Order($order_id);
            $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
            $fail_url = ($this->fail_page_id == "" || $this->fail_page_id == 0) ? get_site_url() . "/" : get_permalink($this->fail_page_id);
            $redirect_url = add_query_arg('wc-api', get_class($this), $redirect_url);
            $fail_url = add_query_arg('wc-api', get_class($this), $fail_url);
            $declineURL = $order->get_cancel_order_url();

            
            $post_data = array(
                'merchant_mobile_no' => $this->store_id,
                'store_password'  => $this->store_password,
                'bill_amount'  => $order->order_total,
                'order_id'       => $order_id,
                'success_url'   => $redirect_url,
                'fail_url'      => $fail_url,
                'cancel_url'    => $declineURL,
            );




            if ($this->testmode == 'yes') {
                $liveurl = $this->testurl."merchant/generate-payment-token";
            } else {
                $liveurl = $this->liveurl."merchant/generate-payment-token";
            }

            # REQUEST SEND TO FASTPAY
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $liveurl);
            curl_setopt($handle, CURLOPT_TIMEOUT, 10);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($handle, CURLOPT_POST, 1);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

            $content = curl_exec($handle);
            $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            if ($code == 200 && !(curl_errno($handle))) {
                curl_close($handle);
                $fastpayResponse = $content;
                # PARSE THE JSON RESPONSE
                $responseDecode = json_decode($fastpayResponse, true);
                

                if ($responseDecode['code'] != '200') {
                    echo "FAILED TO CONNECT WITH FastPay API";
                    echo "<br/>Failed Reason: ";
		    print_r($responseDecode['messages']);
                    exit;
                }
				
            } else {
                curl_close($handle);
                echo "FAILED TO CONNECT WITH FASTPAY API";
                exit;
            }
			
            if ($this->testmode == 'yes') {
               
				$redirect_url = $this->testurl."merchant/payment?token=".$responseDecode['token'];
				
            } else {
                
				$redirect_url = $this->liveurl."merchant/payment?token=".$responseDecode['token'];
            }
			
            return '<form action="'.$redirect_url.'" method="post" id="fastpay_payment_form">
                <input type="submit" class="button-alt" id="submit_fastpay_payment_form" value="' . __('Pay via Fastpay', 'fastpay') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart', 'fastpay') . '</a>
                <script type="text/javascript">
                    jQuery(function(){
                        jQuery("body").block({
                            message: "' . __('Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', 'fastpay') . '",
                            overlayCSS: {
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
                        jQuery("#submit_fastpay_payment_form").click();
                    });
                </script>
            </form>';
        }
        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);
            return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url(true));
        }

        /**
         * Check for valid fastpay server callback
         **/
        function check_fastpay_response()
        {
            global $woocommerce;
            $order_id = $_REQUEST['order_id'];
            $order = wc_get_order($order_id);
            $fail_url = ($this->fail_page_id == "" || $this->fail_page_id == 0) ? get_site_url() . "/" : get_permalink($this->fail_page_id);
            $fail_url = add_query_arg('wc-api', get_class($this), $fail_url);
			
            if (isset($order_id)) {
                $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
                $fail_url = ($this->fail_page_id == "" || $this->fail_page_id == 0) ? get_site_url() . "/" : get_permalink($this->fail_page_id);
                $this->msg['class'] = 'error';
                $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
				$post_data = array();
				$post_data['merchant_mobile_no'] = $this->store_id;
				$post_data['store_password']= $this->store_password;
				$post_data['order_id']= $order_id;
                                
                if ('yes' == $this->testmode) {
                   $requested_url = "https://dev.fast-pay.cash/merchant/payment/validation";
                } else {
                    $requested_url = " https://secure.fast-pay.cash/merchant/payment/validation";
                }

				$handle = curl_init();
				curl_setopt($handle, CURLOPT_URL, $requested_url );
				curl_setopt($handle, CURLOPT_TIMEOUT, 10);
				curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($handle, CURLOPT_POST, 1 );
				curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

				$result = curl_exec($handle);
                
				$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);




                if ($code == 200 && !(curl_errno($handle))) {
                    # TO CONVERT AS ARRAY
                    # $result = json_decode($result, true);
                    # $status = $result['status'];  

                    # TO CONVERT AS OBJECT
                    $result = json_decode($result);
					
					# TRANSACTION INFO
					$messages = $result->messages;
					$code = $result->code; #if $code is not 200 then something is wrong with your request.
					$data = $result->data;

            
                    # TRANSACTION INFO
                    $status = $data->status;
                    $tran_date = $data->received_at;
                    $tran_id = $data->transaction_id;
                    $order_id = $data->order_id;
                    $amount = $data->bill_amount;
                    
                    # ISSUER INFO
                    $card_no = $data->customer_account_no;
               


                    $message = '';
                    $message .= 'Payment Status = ' . $status . "\n";
                    $message .= 'Fastpay txnid = ' . $tran_id . "\n";
                    $message .= 'Your Oder id = ' . $order_id . "\n";
                    $message .= 'Payment Date = ' . $tran_date . "\n";
                    $message .= 'Customer Account No = ' . $card_no . "\n";

                    
                
                }

                if ($order_id !='') {
                    try {
                        $order = wc_get_order($order_id);
                        $amount = $amount;
                       
                        if ($status == "Success") {

                            $this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                            $this->msg['class'] = 'success';
                            if ($order->get_status() == 'pending') // If IPN Enable. Then oreder status will be updated by IPN page.So no need to update again.
                                {
                                    $order->update_status('Processing');
                                    $order->payment_complete();
                                }
                                
                            $order->add_order_note($message);
                            $order->add_order_note($this->msg['message']);
                            $woocommerce->cart->empty_cart();
                            $return_url = $order->get_checkout_order_received_url();
                            $redirect_url  = str_replace('http:', 'http:', $return_url);
                        } else {
                            $this->msg['class'] = 'error';
                            $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                            //Here you need to put in the routines for a failed
                            //transaction such as sending an email to customer
                            //setting database status etc etc
                        }

                        //removed for WooCOmmerce 2.0
                        //add_action('the_content', array(&$this, 'showMessage'));
                    } catch (Exception $e) {
                        // $errorOccurred = true;
                        $msg = "Error";
                    }
                }

                wp_redirect($redirect_url);
            }
        }

        function showMessage($content)
        {
            return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
        }

        // get all pages
        function get_pages($title = false, $indent = true)
        {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title) $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
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
    function woocommerce_add_fastpay_gateway($methods)
    {
        $methods[] = 'WC_fastpay';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_fastpay_gateway');

    function plugin_page_settings_link($links)
    {
        $links[] = '<a href="' .
        admin_url( 'admin.php?page=wc-settings&tab=checkout&section=fastpay') .
        '">' . __('Settings') . '</a>';
        return $links;
    }
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'plugin_page_settings_link');

    /**
     *  Add Custom Icon 
     */

    function custom_gateway_icon($icon, $id)
    {
        if ($id === 'fastpay') {
            return '<img src="' . plugins_url( 'images/fastpay.png', __FILE__) . '" > ';
        } else {
            return $icon;
        }
    }
    add_filter('woocommerce_gateway_icon', 'custom_gateway_icon', 10, 2);
}

class Create_fastpay_ipn_page_url
{

    protected static $instance = NULL;

    public function __construct()
    { }

    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    public function setup()
    {
        add_action('init', array($this, 'rewrite_rules'));
        add_filter('query_vars', array($this, 'query_vars'), 10, 1);
        add_action('parse_request', array($this, 'parse_request'), 10, 1);

        register_activation_hook(__FILE__, array($this, 'flush_rules'));
    }

    public function rewrite_rules()
    {
        add_rewrite_rule('fastpayipn/?$', 'index.php?fastpayipn', 'top');
    }

    public function flush_rules()
    {
        $this->rewrite_rules();
        flush_rewrite_rules();
    }

    public function query_vars($vars)
    {
        $vars[] = 'fastpayipn';
        return $vars;
    }

    public function parse_request($wp)
    {
        if (array_key_exists('fastpayipn', $wp->query_vars)) {
            include plugin_dir_path(__FILE__) . 'fastpay_ipn.php';
            exit();
        }
    }
}
