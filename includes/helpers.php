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
    wp_mail( $to, $subject, $message );
}

function rs_generate_invoice_email( $order_id, $data ) {
    ob_start();
    include plugin_dir_path( __FILE__ ) . 'templates/email-invoice.php';
    return ob_get_clean();
}

function get_rev_product( $type = 'tables' ) {
    $args = [
        'post_type'      => 'hr_reservation',
        'posts_per_page' => -1,
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
