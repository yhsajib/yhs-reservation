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

class Yhs_Reservation {
    private static ?Yhs_Reservation $instance = null;

    public function __construct() {

        add_action( "plugin_loaded", [ $this, "init" ] );

    }
    public function init() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/helpers.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';
        add_action( 'wp_enqueue_scripts', [$this, 'rs_enqueue_scripts'] );
        add_shortcode( 'rs_checkout', [$this, 'rs_load_checkout_template'] );
        add_shortcode( 'rs_thank_you', [$this, 'rs_load_thank_you_template'] );
        add_action( 'init', [$this, 'start_session'] );

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
