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
        add_action('save_post_hr_reservation', [$this, 'rs_save_meta_box']);
        add_action('add_meta_boxes', [$this, 'rs_add_meta_boxes']);
        add_action( 'admin_init', [$this, 'rs_register_settings'] );
        add_action( 'admin_menu', [$this, 'rs_add_settings_page'] );
        add_action( 'admin_post_process_reservation', [$this, 'process_reservation'] );
        add_action( 'admin_post_nopriv_process_reservation', [$this, 'process_reservation'] );
        add_action( 'add_meta_boxes', [$this, 'rv_order_meta_box'] );
        add_action( 'restrict_manage_posts',[$this, 'add_custom_meta_filter'] );
        add_action( 'pre_get_posts', [$this, 'filter_posts_by_custom_meta'] );
        add_action("admin_enqueue_scripts", [$this, "admin_assets"]);
        add_action('save_post_rv_order', [$this, 'save_rv_order_status']);
        add_action('manage_rv_order_posts_custom_column', [$this, 'rv_order_custom_column'], 10, 2);
        add_filter('manage_rv_order_posts_columns', [$this, 'rv_order_columns']);

    }

    function admin_assets() {
        wp_enqueue_style("yhs-admin-style", YHS_RV_URL . '/assets/css/admin.css');     
    }
    function rv_order_columns($columns) {

        unset($columns['date']);
        $columns['rv_order_status'] = __('Status', 'textdomain');
        $columns['rv_order_payment'] = __('Payment', 'textdomain');
        $columns['date'] = __('Date', 'textdomain');
        return $columns;
    }

    function rv_order_custom_column($column, $post_id) {
        if ($column == 'rv_order_status') {
            $status = get_post_status($post_id);
            echo ucfirst(str_replace('rv-', '', $status));
        }
        if ($column == 'rv_order_payment') {
            $payment_method = get_post_meta($post_id, 'payment_method', true);
            echo ucfirst($payment_method);
        }
    }

    function save_rv_order_status($post_id) {
        // Prevent infinite loop
        remove_action('save_post_rv_order', [$this, 'save_rv_order_status']);
    
        // Security Check
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['rv_order_status']) || !current_user_can('edit_post', $post_id)) {
            return;
        }
    
        // Sanitize and update the post status
        $status = sanitize_text_field($_POST['rv_order_status']);
        wp_update_post([
            'ID'          => $post_id,
            'post_status' => $status,
        ]);
    
        // Re-add action after update
        add_action('save_post_rv_order', [$this, 'save_rv_order_status']);
    }

    // Get meta values for the select filter
    function filter_posts_by_custom_meta( $query ) {
        global $pagenow;
        if ( is_admin() && $pagenow == 'edit.php' && isset( $_GET['rv_payment_method'] ) && !empty( $_GET['rv_payment_method'] ) ) {
            $query->query_vars['meta_query'] = array(
                array(
                    'key'   => 'payment_method',
                    'value' => sanitize_text_field( $_GET['rv_payment_method'] ),
                    'compare' => '=',
                )
            );
        }
    }
    

    function add_custom_meta_filter( $post_type ) {
        if ( 'rv_order' !== $post_type ) {
            return;
        }
    
        $meta_key = 'payment_method'; // Replace with your meta key
        $values = get_meta_values( $meta_key, $post_type );
    
        ?>
        <select name="rv_payment_method">
            <option value=""><?php _e( 'Filter by Payment Method', 'textdomain' ); ?></option>
            <?php foreach ( $values as $value ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset($_GET['payment_method']) ? $_GET['payment_method'] : '', $value ); ?>>
                    <?php echo ucfirst(esc_html( $value )); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    // Save meta box data
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
                'supports'    => array('title', 'editor', 'thumbnail','page-attributes'),
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

        register_post_status('rv-pending', array(
            'label'                     => _x('Pending', 'rv_order'),
            'public'                    => true,
            'show_in_admin_status_list'  => true,
            'show_in_admin_all_list'     => true,
            'label_count'                => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>')
        ));

        register_post_status('rv-processing', array(
            'label'                     => _x('Processing', 'rv_order'),
            'public'                    => true,
            'show_in_admin_status_list'  => true,
            'show_in_admin_all_list'     => true,
            'label_count'                => _n_noop('Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>')
        ));
    
        register_post_status('rv-completed', array(
            'label'                     => _x('Completed', 'rv_order'),
            'public'                    => true,
            'show_in_admin_status_list'  => true,
            'show_in_admin_all_list'     => true,
            'label_count'                => _n_noop('Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>')
        ));



    }
    // Add Settings Page
    public function rs_add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=hr_reservation',
            __( 'Reservation Settings', 'textdomain' ),
            __( 'Settings', 'textdomain' ),
            'manage_options',
            'rs_paypal_settings',
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
                    settings_fields( 'rs_paypal_settings_group' );
                    do_settings_sections( 'rs_paypal_settings' );
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function rs_register_settings() {
        add_option('rs_paypal_client_id', '');
        add_option('rs_paypal_secret', '');
        add_option('rs_paypal_email', '');
        add_option('paypal_environment', 'https://www.sandbox.paypal.com/cgi-bin/webscr'); 
        register_setting('rs_paypal_settings_group', 'rs_paypal_client_id');
        register_setting('rs_paypal_settings_group', 'rs_paypal_secret');
        register_setting('rs_paypal_settings_group', 'rs_paypal_email');
        register_setting('rs_paypal_settings_group', 'paypal_environment');
    
        add_settings_section(
            'rs_paypal_section',
            'PayPal Settings',
            [$this,'rs_paypal_section_callback'],
            'rs_paypal_settings'
        );

        add_settings_field(
            'paypal_environment',
            'PayPal Environment',
            [$this, 'paypal_environment_field_callback'],
            'rs_paypal_settings',
            'rs_paypal_section'
        );
        add_settings_field(
            'rs_paypal_email',
            'PayPal Business Email',
            [$this, 'rs_paypal_business_email_callback'],
            'rs_paypal_settings',
            'rs_paypal_section'
        );
    
    
        add_settings_field(
            'rs_paypal_client_id',
            'PayPal Client ID',
            [$this, 'rs_paypal_client_id_callback'],
            'rs_paypal_settings',
            'rs_paypal_section'
        );
    
        add_settings_field(
            'rs_paypal_secret',
            'PayPal Secret Key',
            [$this,'rs_paypal_secret_callback'],
            'rs_paypal_settings',
            'rs_paypal_section'
        );


    }

    function paypal_environment_field_callback() {
        $environment = get_option( 'paypal_environment', 'https://www.sandbox.paypal.com/cgi-bin/webscr' );
        ?>
        <input type="radio" name="paypal_environment" value="https://www.sandbox.paypal.com/cgi-bin/webscr" <?php checked( $environment, 'https://www.sandbox.paypal.com/cgi-bin/webscr' ); ?>> Sandbox<br>
        <input type="radio" name="paypal_environment" value="https://www.paypal.com/cgi-bin/webscr" <?php checked( $environment, 'https://www.paypal.com/cgi-bin/webscr' ); ?>> Live<br>
        <?php
    }

    
    function rs_paypal_section_callback() {
        echo '<p>Enter your PayPal API credentials below.</p>';
    }
    
    function rs_paypal_client_id_callback() {
        $client_id = get_option('rs_paypal_client_id');
        echo '<input type="text" name="rs_paypal_client_id" value="' . esc_attr($client_id) . '" class="regular-text">';
    }
    function rs_paypal_business_email_callback() {
        $rs_paypal_email = get_option('rs_paypal_email');
        echo '<input type="text" name="rs_paypal_email" value="' . esc_attr($rs_paypal_email) . '" class="regular-text">';
    }
    
    function rs_paypal_secret_callback() {
        $secret = get_option('rs_paypal_secret');
        echo '<input type="password" name="rs_paypal_secret" value="' . esc_attr($secret) . '" class="regular-text">';
    }

    public function process_reservation() {
        if ( !isset( $_POST['reservation_nonce_field'] ) || !wp_verify_nonce( $_POST['reservation_nonce_field'], 'reservation_nonce' ) ) {
            $_SESSION['rv_error'] = 'Invalid request';
            wp_redirect( wp_get_referer() );
            exit;
        }
        $_SESSION['rv_form'] = $_POST;

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
        $total_price = intval( $_POST['rv-total-price'] ?? 0 );
        $payment_option = sanitize_text_field( $_POST['payment-option'] ?? '' );
        $rv_items = sanitize_text_field( $_POST['rv-items'] ?? '' );

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
                'rv_items'  => $rv_items,
            ],
        ] );


        if('paypal' == $payment_option){
                        
            $paypal_url = get_option('paypal_environment'); // Change to live PayPal URL;
            $business_email = get_option('rs_paypal_email'); 
            $return_url = home_url('/bird-arrangements-thankyou/'); 
            $cancel_url = home_url('/bird-arrangements/'); 
            $notify_url = home_url('/paypal-ipn'); 
            
            // PayPal Form Data 
            $paypal_args = array(
                'cmd'           => '_xclick',
                'business'      =>  $business_email,
                'item_name'     => 'Reservation Order',
                'amount'        => number_format((float)$total_price, 2, '.', ''),
                'currency_code' => 'EUR',
                'return'        => $return_url,
                'cancel_return' => $cancel_url,
                'notify_url'    => $notify_url,
				'custom'        => $order_id,
     
            );
        
            // Generate PayPal redirect URL
            $query_string = http_build_query($paypal_args);
            $paypal_redirect_url = $paypal_url . '?' . $query_string;      
            unset($_SESSION['rv_form']);
            wp_redirect($paypal_redirect_url);
            exit;

        }else if('invoice' == $payment_option){
            rs_send_invoice($order_id, [
                'first-name' => $first_name,
                'last-name' => $last_name,
                'email' => $email,
                'street' => $street,
                'postal-code' => $postal_code,
                'city' => $city,
                'country' => $country,
                'rv_items'  => $rv_items,
                'total_price'     => $total_price,
            ]);
            unset($_SESSION['rv_form']);
            update_rv_order_status($order_id, 'rv-pending');
            wp_redirect(home_url('/bird-arrangements-thankyou/'));
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
        $payment_method = get_post_meta( $post->ID, 'payment_method', true );
        $status = get_post_status($post->ID);
        ?>

        <div class="rv_order_details_wrapper"> 
                <p><strong>Order ID:</strong> <?php echo esc_html( $post->ID ); ?></p>
                <p><strong>Date:</strong> <?php echo esc_html( get_the_date( 'Y-m-d H:i:s', $post->ID ) ); ?></p>          

                <label for="rv_order_status"><?php _e('Order Status:', 'textdomain'); ?></label>
                <select name="rv_order_status" id="rv_order_status">
                    <option value="rv-pending" <?php selected($status, 'rv-pending'); ?>>Pending</option>
                    <option value="rv-processing" <?php selected($status, 'rv-processing'); ?>>Processing</option>
                    <option value="rv-completed" <?php selected($status, 'rv-completed'); ?>>Completed</option>
                </select>
                <p><strong>Payment Method:</strong> <?php echo ucfirst( esc_html( $payment_method )); ?></p>

            <div class="rv_order_details">
            <div class="rv_order_details_left">

                <p><strong>Items:</strong></p>            
                <table class="rv-order-details-table">
                    <?php

                    $rv_items = get_post_meta( $post->ID, 'rv_items', true );          
                    if(!is_array($rv_items)){
                        $rv_items = json_decode($rv_items);
                    }   
                    $total_amount = 0;    
                    if ( !empty( $rv_items ) && is_array($rv_items) ) {
                        echo '<tr><th>Items</th><th>Price (€)</th><th>Quantity</th></tr>';                    
                        foreach ( $rv_items as $key => $item ) {
                            $subtotal = $item->price;
							$subtotal = remove_currency_symbol($subtotal);
                            $total_amount += $subtotal;
                            echo '<tr>';
                            echo '<td>' . esc_html($item->items) . '</td>';
                            echo '<td>' . esc_html($item->price) . '</td>';
                            echo '<td>' . esc_html($item->quantity) . '</td>';
                            echo '</tr>';                     
                        }                
                        echo '<tr>';
                        echo '<td><strong>Total</strong></td>';
                        echo '<td colspan="2"><strong>' .  esc_html(number_format($total_amount, 2, ',', '.')) . ' €</strong></td>';
                        echo '</tr>';
                    } else {
                        echo '<li>No items found.</li>';
                    }
                    ?>
                </table>
            </div>
            <div class="rv_order_details_right">
            <p><strong>Customer Details:</strong></p>     
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
                </table>
            </div>
            </div>
        </div>
        <?php
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