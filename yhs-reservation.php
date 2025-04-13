<?php
/*
Plugin Name: Reservation System
Plugin URI:
Description: A custom reservation system for tickets and tables
Version: 1.0
Author: YHS
Author URI:
License: GPL2
*/

// Security check to prevent direct access
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

define('YHS_RV_URL', plugin_dir_url( __FILE__ ));
define('YHS_RV_PATH', plugin_dir_path( __FILE__ ));
class Yhs_Reservation {
    private static ?Yhs_Reservation $instance = null;

    public function __construct() {
        add_action( "plugin_loaded", [ $this, "init" ] );
        add_action("init", function () {
            add_rewrite_rule('^paypal-ipn/?$', 'index.php?rs_ipn_listener=1', 'top');            
        });
        add_filter('query_vars', function($vars) {
            $vars[] = 'rs_ipn_listener';
            return $vars;
        });
        add_action('template_redirect', [$this, 'rs_handle_ipn_listener']);
    }
    public function init() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/helpers.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';     
        require_once plugin_dir_path( __FILE__ ) . 'includes/import-export.php';     
        add_action( 'wp_enqueue_scripts', [$this, 'rs_enqueue_scripts'] );
        add_shortcode( 'rs_checkout', [$this, 'rs_load_checkout_template'] );
        add_shortcode( 'rs_thank_you', [$this, 'rs_load_thank_you_template'] );
        add_action( 'init', [$this, 'start_session'] );

    }

    function rs_handle_ipn_listener() {
        if (get_query_var('rs_ipn_listener') == 1) {
    
            // STEP 1: Read the IPN message from PayPal and log it
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
    
            $paypal_url = "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr"; // Sandbox
            //$paypal_url = "https://ipnpb.paypal.com/cgi-bin/webscr"; // Live
    
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
    
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                file_put_contents(__DIR__ . '/ipn-curl-error.txt', $error, FILE_APPEND);
            }
    
            curl_close($ch);
    
            if (strcmp($res, "VERIFIED") == 0) {
                // Verified IPN
                $payment_status = $_POST['payment_status'];
                $payer_email = $_POST['payer_email'];
                $order_id = $_POST['custom'];
                $amount = $_POST['mc_gross'];
                $item_name = $_POST['item_name'];
    
               // file_put_contents(__DIR__ . '/ipn-log.txt', "VERIFIED\n" . print_r($_POST, true), FILE_APPEND);
    
                if ($payment_status === "Completed") {
                    update_rv_order_status($order_id, 'rv-completed');
                } else {
                    update_rv_order_status($order_id, 'rv-pending');
                }
            } else {
                // Invalid IPN
                //file_put_contents(__DIR__ . '/ipn-log.txt', "INVALID\n" . print_r($_POST, true), FILE_APPEND);
            }
    
            // Stop WP from rendering a page
            status_header(200);
            exit;
        }
    }

    public function start_session() {
        if ( !session_id() ) {
            session_start();
        }
    }

    public function rs_load_checkout_template() {
        $template = plugin_dir_path( __FILE__ ) . 'templates/checkout.php';
        if ( file_exists( $template ) ) {
            ob_start();
            include $template;
            return ob_get_clean();
        }
        return '<p>Checkout page not found.</p>';
    }

    public function rs_load_thank_you_template() {
        $template = plugin_dir_path( __FILE__ ) . 'templates/thank-you.php';
        if ( file_exists( $template ) ) {
            ob_start();
            include $template;
            return ob_get_clean();
        }
        return '<p>Thank you page not found.</p>';
    }

    public function rs_enqueue_scripts() {
        wp_enqueue_style( 'rs-style', plugins_url( 'assets/css/style.css', __FILE__ ) );
        wp_enqueue_script( 'rs-script', plugins_url( 'assets/js/scripts.js', __FILE__ ), [ 'jquery' ], '1.0', true );

        wp_localize_script( 'rs-script', 'rs_obj', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'rs_ajax_nonce' ),
        ] );
    }

    public static function getInstance(): Yhs_Reservation {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

Yhs_Reservation::getInstance();
