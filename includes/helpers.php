<?php
// Sanitization
function rs_sanitize_reservation_data( $data ) {
    return [

        'ticket-quantity' => absint( $data['ticket-quantity'] ),
        'table-category'  => sanitize_text_field( $data['table-category'] ),
        'table-quantity'  => absint( $data['table-quantity'] ),
        'first-name'      => sanitize_text_field( $data['first-name'] ),
        'last-name'       => sanitize_text_field( $data['last-name'] ),
        'email'           => sanitize_email( $data['email'] ),
        'street'          => sanitize_text_field( $data['street'] ),
        'postal-code'     => sanitize_text_field( $data['postal-code'] ),
        'city'            => sanitize_text_field( $data['city'] ),
        'payment-option'  => sanitize_text_field( $data['payment-option'] ),
    ];
}

// PayPal Processing
function rs_process_paypal( $order_id, $data ) {
    // Implement PayPal API integration
    return "https://www.paypal.com/checkoutnow?token=..."; // Return PayPal approval URL
}

// Invoice Email
function rs_send_invoice( $order_id, $data ) {
    $to = $data['email'];
    $subject = 'Invoice for Order #' . $order_id;
    $message = rs_generate_invoice_email( $order_id, $data );
	$headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail( $to, $subject, $message,$headers );
}

function rs_generate_invoice_email( $order_id, $data ) {
    ob_start();
    include YHS_RV_PATH . 'templates/email-invoice.php';
    return ob_get_clean();
}

function get_rev_product( $type = 'tables' ) {
    $args = [
        'post_type'      => 'hr_reservation',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'tax_query'      => [
            [
                'taxonomy' => 'reservation_type',
                'field'    => 'slug',
                'terms'    => $type,
            ],
        ],
    ];

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        $rooms = [];
        foreach ( $query->posts as $post_id ) {
            $rooms[] = [
                'id'      => $post_id->ID,
                'title'   => get_the_title( $post_id ),
                'content' => get_post_field( 'post_content', $post_id ),
            ];
        }
        return $rooms;
        wp_reset_postdata();
    } else {
        return false;
    }

}

function get_rv_price( $id ) {
    return get_post_meta( $id, '_reservation_price', true );
}

/**
 * Helper function to get all unique meta values for a specific meta key.
 */
function get_meta_values( $meta_key, $post_type ) {
    global $wpdb;
    $query = $wpdb->prepare( "
        SELECT DISTINCT meta_value FROM $wpdb->postmeta
        WHERE meta_key = %s
        AND meta_value != ''
        ORDER BY meta_value ASC
    ", $meta_key );
    return $wpdb->get_col( $query );
}

function update_rv_order_status( $order_id, $new_status ) {
    if ( in_array( $new_status, ['rv-pending', 'rv-processing', 'rv-completed'] ) ) {
        wp_update_post( [ 'ID' => $order_id, 'post_status' => $new_status ] );
    }
}

function get_session_tempdata( $session_form_data ) {

    $first_name = isset( $session_form_data['first-name'] ) ? $session_form_data['first-name'] : '';
    $last_name = isset( $session_form_data['last-name'] ) ? $session_form_data['last-name'] : '';
    $email = isset( $session_form_data['email'] ) ? $session_form_data['email'] : '';
    $street = isset( $session_form_data['street'] ) ? $session_form_data['street'] : '';
    $postal_code = isset( $session_form_data['postal-code'] ) ? $session_form_data['postal-code'] : '';
    $city = isset( $session_form_data['city'] ) ? $session_form_data['city'] : '';
    $ticket_quantity = isset( $session_form_data['ticket-quantity'] ) ? $session_form_data['ticket-quantity'] : '';
    $table_category = isset( $session_form_data['table-category'] ) ? $session_form_data['table-category'] : '';
    $table_quantity = isset( $session_form_data['table-quantity'] ) ? $session_form_data['table-quantity'] : '';
    $payment_option = isset( $session_form_data['payment-option'] ) ? $session_form_data['payment-option'] : '';
    $country = isset( $session_form_data['country'] ) ? $session_form_data['country'] : '';

    return [
        'first-name'     => $first_name,
        'last-name'      => $last_name,
        'email'          => $email,
        'street'         => $street,
        'postal-code'    => $postal_code,
        'city'           => $city,
        'country'        => $country,
        'table-category' => $table_category,
        'table-quantity' => $table_quantity,
        'payment-option' => $payment_option,
    ];
}

function remove_currency_symbol($amount) {
    // Remove non-numeric characters except ',' and '.'
    $amount = preg_replace('/[^0-9,\.]/', '', $amount);

    // Convert European format (8.000,00) to standard format (8000.00)
    $amount = str_replace('.', '', $amount); // Remove thousand separator
    $amount = str_replace(',', '.', $amount); // Replace comma with dot for decimal

    return (float) $amount; // Convert to float
}