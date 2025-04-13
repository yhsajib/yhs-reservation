<div
    style="max-width: 600px; background-color: #ffffff; margin: auto; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);">
    <div
        style="text-align: center; background-color: #0073e6; color: white; padding: 10px; border-radius: 10px 10px 0 0;">
        <h2>Hummingbird Reservation Confirmation #<?php echo $order_id; ?></h2>
    </div>
    <div style="padding: 20px;">
        <p>Hi <strong><?php echo $data['first-name'] . ' ' . $data['last-name']; ?></strong>,</p>
        <p>Thank you for your binding reservation! Here are the details:</p>
        <table style="width: 100%; border-collapse: collapse;text-align: left;">
            <?php
                    $total_amount = 0;  
                    $items = $data['rv_items'];

                    if (is_string($items)) {
                      
                        $items = html_entity_decode($items);
                        $items = stripslashes($items); 

                        error_log('Cleaned JSON string: ' . $items);

                        $items = json_decode($items);
                        if (is_string($items)) {                      
                            $items = json_decode($items);
                        }
                    }

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('JSON decode error: ' . json_last_error_msg());
                    }

                    if (!empty($items) && is_array($items)) {
                        echo '<tr><th>Items</th><th>Price (€)</th><th>Quantity</th></tr>';
                        $total_amount = 0;

                        foreach ($items as $item) {                       
                            $subtotal = remove_currency_symbol($item->price);
                            $total_amount += $subtotal;
                            echo '<tr>';
                            echo '<td>' . esc_html($item->items) . '</td>';
                            echo '<td>' . esc_html($item->price) . '</td>';
                            echo '<td>' . esc_html($item->quantity) . '</td>';
                            echo '</tr>';
                        }

                        echo '<tr>';
                        echo '<td><strong>Total</strong></td>';
                        echo '<td colspan="2"><strong>' . esc_html(number_format($total_amount, 2, ',', '.')) . ' €</strong></td>';
                        echo '</tr>';
                    } else {
                        echo '<li>No items found.</li>';
                    }
            ?>
        </table>
    </div>
    <div style="text-align: center; padding: 10px; font-size: 14px; color: #777;">
        <p>&copy; 2025 Hummingbirdfashionaward. All rights reserved.</p>
    </div>
</div>