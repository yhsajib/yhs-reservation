<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly

class YhsAdmin {
    private static ?YhsAdmin $instance = null;   

    // Private constructor to enforce singleton
    public function __construct() {
        add_action('init', [$this, 'rs_register_post_types']);
        add_action('save_post', [$this, 'rs_save_meta_box']);
        add_action('add_meta_boxes', [$this, 'rs_add_meta_boxes']);
        add_action( 'admin_init', [$this, 'rs_register_settings'] );
        add_action( 'admin_menu', [$this, 'rs_add_settings_page'] );
        add_action( 'admin_post_process_reservation', [$this, 'process_reservation'] );
        add_action( 'admin_post_nopriv_process_reservation', [$this, 'process_reservation'] );
        add_action( 'add_meta_boxes', [$this, 'rv_order_meta_box'] );
        add_action( "wp_ajax_process_paypal_payment", [$this, "process_paypal_payment"] );
        add_action( "wp_ajax_nopriv_process_paypal_payment", [$this, "process_paypal_payment"] );

    }
    public  function rs_save_meta_box($post_id) {
        if (!isset($_POST['rs_meta_box_nonce']) || !wp_verify_nonce($_POST['rs_meta_box_nonce'], 'rs_save_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['reservation_price'])) {
            update_post_meta($post_id, '_reservation_price', sanitize_text_field($_POST['reservation_price']));
        }
    }
    public function rs_add_meta_boxes() {
        add_meta_box(
            'reservation_details',
            __('Reservation Details', 'textdomain'),
            [$this, 'rs_render_meta_box'],
            'hr_reservation',
            'normal',
            'high'
        );
    }
    public  function rs_render_meta_box($post) {
        $price = get_post_meta($post->ID, '_reservation_price', true);
        wp_nonce_field('rs_save_meta_box', 'rs_meta_box_nonce');
        ?>
        <p>
            <label for="reservation_price">Price:</label>
            <input type="number" id="reservation_price" name="reservation_price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0">
        </p>
        <?php
    }

    public function rs_register_post_types() {
    
        // Reservation Post Type
        register_post_type('hr_reservation',
            array(
                'labels'      => array(
                    'name'          => __('Reservations', 'textdomain'),
                    'singular_name' => __('Reservation', 'textdomain'),
                ),
                'public'      => true,
                'has_archive' => true,
                'supports'    => array('title', 'editor', 'thumbnail'),
                'menu_icon'  => 'dashicons-tickets-alt',
            )
        );
    
        // Order Post Type (Child of Reservation)
        register_post_type('rv_order',
            array(
                'labels'      => array(
                    'name'          => __('Orders', 'textdomain'),
                    'singular_name' => __('Order', 'textdomain'),
                ),
                'public'      => false,
                'show_ui'     => true,
                'supports'    => array('title'),
                'menu_icon'  => 'dashicons-cart',
            )
        );
    
        // Reservation Type Taxonomy
        $labels = array(
            'name'              => _x('Types', 'taxonomy general name'),
            'singular_name'     => _x('Type', 'taxonomy singular name'),
            'search_items'      => __('Search Types'),
            'all_items'         => __('All Types'),
            'parent_item'       => __('Parent Type'),
            'parent_item_colon' => __('Parent Type:'),
            'edit_item'         => __('Edit Type'),
            'update_item'       => __('Update Type'),
            'add_new_item'      => __('Add New Type'),
            'new_item_name'     => __('New Type Name'),
            'menu_name'         => __('Types'),
        );
    
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'reservation-type'),
        );
    
        register_taxonomy('reservation_type', array('hr_reservation'), $args);
    }
    // Add Settings Page
    public function rs_add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=hr_reservation',
            __( 'Reservation Settings', 'textdomain' ),
            __( 'Settings', 'textdomain' ),
            'manage_options',
            'rs_settings',
            [$this, 'rs_render_settings_page']
        );
    }

    // Render Settings Page
    public function rs_render_settings_page() {
        ?>
    <div class="wrap">
        <h1><?php echo __( 'Reservation Settings', 'textdomain' ); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'rs_settings_group' );
                do_settings_sections( 'rs_settings' );
                submit_button();
            ?>
        </form>
    </div>
        <?php
    }

    public function rs_register_settings() {
        register_setting( 'rs_settings_group', 'rs_enable_paypal' );
        register_setting( 'rs_settings_group', 'rs_enable_invoice' );
        add_settings_section( 'rs_payment_section', __( 'Payment Methods', 'textdomain' ), null, 'rs_settings' );
        add_settings_field( 'rs_enable_paypal', __( 'Enable PayPal', 'textdomain' ), [$this,'rs_enable_paypal_callback'], 'rs_settings', 'rs_payment_section' );
        add_settings_field( 'rs_enable_invoice', __( 'Enable Invoice', 'textdomain' ), [$this, 'rs_enable_invoice_callback'], 'rs_settings', 'rs_payment_section' );
    }

    
    function rs_enable_paypal_callback() {
        $option = get_option('rs_enable_paypal');
        echo '<input type="checkbox" name="rs_enable_paypal" value="1" ' . checked(1, $option, false) . ' />';
    }

    function rs_enable_invoice_callback() {
        $option = get_option('rs_enable_invoice');
        echo '<input type="checkbox" name="rs_enable_invoice" value="1" ' . checked(1, $option, false) . ' />';
    }

    public function process_reservation() {
        if ( !isset( $_POST['reservation_nonce_field'] ) || !wp_verify_nonce( $_POST['reservation_nonce_field'], 'reservation_nonce' ) ) {
            $_SESSION['rv_error'] = 'Invalid request';
            wp_redirect( wp_get_referer() );
            exit;
        }

        // Get and sanitize form data
        $first_name = sanitize_text_field( $_POST['first-name'] ?? '' );
        $last_name = sanitize_text_field( $_POST['last-name'] ?? '' );
        $email = sanitize_email( $_POST['email'] ?? '' );
        $street = sanitize_text_field( $_POST['street'] ?? '' );
        $postal_code = sanitize_text_field( $_POST['postal-code'] ?? '' );
        $city = sanitize_text_field( $_POST['city'] ?? '' );
        $country = sanitize_text_field( $_POST['country'] ?? '' );
        $ticket_quantity = intval( $_POST['ticket-total-quantity'] ?? 0 );
        $table_quantity = intval( $_POST['table-total-quantity'] ?? 0 );
        $rv_total_price = intval( $_POST['rv-total-price'] ?? 0 );
        $payment_option = sanitize_text_field( $_POST['payment-option'] ?? '' );

        // Validate required fields
        $required_fields = [
            'First Name'     => $first_name,
            'Last Name'      => $last_name,
            'Email'          => $email,
            'Street'         => $street,
            'Postal Code'    => $postal_code,
            'Country'        => $country,
            'City'           => $city,
            'Payment Option' => $payment_option,
        ];
        // Validate ticket and table quantity
        if ( $ticket_quantity < 1 && $table_quantity < 1 ) {
            $_SESSION['rv_error'] = 'At least one ticket or table must be selected.';
            wp_redirect( wp_get_referer() );
            exit;
        }

        foreach ( $required_fields as $field_name => $field_value ) {
            if ( empty( $field_value ) ) {
                $_SESSION['rv_error'] = "$field_name is required.";
                wp_redirect( wp_get_referer() );
                exit;
            }
        }

        // Validate email
        if ( !is_email( $email ) ) {
            $_SESSION['rv_error'] = 'Invalid email address';
            wp_redirect( wp_get_referer() );
            exit;
        }

        // Calculate total price
        $total_price = $rv_total_price;
        if('paypal' == $payment_option){

        }else if('invoice' == $payment_option){

        }

        // Create a new rv_order post
        $order_id = wp_insert_post( [
            'post_type'   => 'rv_order',
            'post_status' => 'publish',
            'post_title'  => 'Order for ' . $first_name . ' ' . $last_name,
            'meta_input'  => [
                'customer_name'   => $first_name . ' ' . $last_name,
                'customer_email'  => $email,
                'billing_address' => "$street, $postal_code, $city , $country",
                'ticket_quantity' => $ticket_quantity,
                'table_quantity'  => $table_quantity,
                'total_price'     => $total_price,
                'payment_method'  => $payment_option,
            ],
        ] );

        if ( $order_id ) {
            wp_redirect( home_url( '/thank-you' ) ); // Redirect to the thank-you page
            exit;
        } else {
            $_SESSION['rv_error'] = 'Error creating order';
            wp_redirect( wp_get_referer() );
            exit;
        }
    }

    // Add meta box to display order details
    public function rv_order_meta_box() {
        add_meta_box(
            'rv_order_details',
            'Reservation Order Details',
            [$this, 'rv_order_meta_box_callback'],
            'rv_order',
            'normal',
            'high'
        );
    }
    // Callback function to display meta box content
    public function rv_order_meta_box_callback( $post ) {
        // Retrieve order meta values
        $customer_name = get_post_meta( $post->ID, 'customer_name', true );
        $customer_email = get_post_meta( $post->ID, 'customer_email', true );
        $billing_address = get_post_meta( $post->ID, 'billing_address', true );
        $ticket_quantity = get_post_meta( $post->ID, 'ticket_quantity', true );
        $table_quantity = get_post_meta( $post->ID, 'table_quantity', true );
        $total_price = get_post_meta( $post->ID, 'total_price', true );
        $payment_method = get_post_meta( $post->ID, 'payment_method', true );

        // Display the meta values in a structured format
        ?>
    <style>
        .rv-order-details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .rv-order-details-table th, .rv-order-details-table td {
            border: 1px solid #ddd;
            padding: 10px;
        }
        .rv-order-details-table th {
            background: #f7f7f7;
            text-align: left;
        }
    </style>

    <table class="rv-order-details-table">
        <tr>
            <th>Customer Name</th>
            <td><?php echo esc_html( $customer_name ); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo esc_html( $customer_email ); ?></td>
        </tr>
        <tr>
            <th>Billing Address</th>
            <td><?php echo esc_html( $billing_address ); ?></td>
        </tr>
        <tr>
            <th>Ticket Quantity</th>
            <td><?php echo esc_html( $ticket_quantity ); ?></td>
        </tr>

        <tr>
            <th>Table Quantity</th>
            <td><?php echo esc_html( $table_quantity ); ?></td>
        </tr>
        <tr>
            <th>Total Price</th>
            <td><strong><?php echo esc_html( $total_price ); ?></strong></td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td><?php echo esc_html( $payment_method ); ?></td>
        </tr>
    </table>
        <?php
}

    /* PayPal Integration */
    public function process_paypal_payment() {
        if ( !isset( $_POST['items'] ) || !isset( $_POST['total_price'] ) ) {
            wp_send_json_error( ['message' => 'Invalid request'] );
            wp_die();
        }

        $items = json_decode( stripslashes( $_POST['items'] ), true );
        $total_price = $_POST['total_price'];

        // PayPal configuration
        $paypal_url = "https://www.paypal.com/cgi-bin/webscr"; // Live PayPal
        $business_email = "your-paypal-email@example.com"; // Change this to your PayPal email

        $return_url = home_url( "/thank-you" );
        $cancel_url = home_url( "/checkout" );

        // Generate PayPal form data
        $query_data = [
            "cmd"           => "_xclick",
            "business"      => $business_email,
            "item_name"     => "Reservation Payment",
            "amount"        => $total_price,
            "currency_code" => "USD",
            "return"        => $return_url,
            "cancel_return" => $cancel_url,
            "notify_url"    => home_url( "/wp-json/paypal-webhook" ), // Optional IPN
        ];

        // Create redirect URL
        $redirect_url = $paypal_url . "?" . http_build_query( $query_data );

        // Return PayPal redirect URL
        wp_send_json_success( ["redirect_url" => $redirect_url] );
        wp_die();
    }

    public static function getInstance(): YhsAdmin {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}

// Initialize the class
YhsAdmin::getInstance();
