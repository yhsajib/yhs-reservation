<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$tables = get_rev_product('tables');
$tickets = get_rev_product('tickets');
$paypal = get_option('rs_enable_paypal');
 $invoice = get_option('rs_enable_invoice');
?>
<section class="exclusive-seating-section">
    <div class="yhs-rv-notice error">
        <p>
            <?php 
            if (!empty($_SESSION['rv_error'])) { 
                echo esc_html($_SESSION['rv_error']);   
                unset($_SESSION['rv_error']);
            }
        ?> </p>
    </div>


    <form action="<?php echo admin_url('admin-post.php'); ?>" method="POST" class="exclusive-seating-form">
    <input type="hidden" name="action" value="process_reservation">
    <?php wp_nonce_field('reservation_nonce', 'reservation_nonce_field'); ?>
        <div class="step-one">
            <div class="steps">
                <!-- Left side -->
                <!-- Ticket category -->
                <div class="category">
                    <h2>Ticket Category:</h2>
                    <?php  
                                if($tickets) {
                                    foreach($tickets as $ticket) {
                            ?>
                    <div class="category-item">
                        <div class="select">
                            <select name="ticket-quantity" class="ticket-quantity"
                                data-price="<?php echo get_rv_price($ticket['id']); ?>"
                                data-label="<?php echo $ticket['title']; ?>">
                                <?php for($i=0; $i<=6; $i++) : ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <p>
                            <strong><?php echo $ticket['title']; ?></strong>
                            <span><?php echo $ticket['content']; ?></span>
                        </p>
                    </div>

                    <?php 
                                    }
                                }
                            ?>
                </div>
                <!-- Right side -->
                <!-- Table category -->
                <div class="category">
                    <h2>Table Category:</h2>
                    <?php 
                                    if($tables){
                                        foreach($tables as $table){                                        
                                ?>
                    <div class="category-item">
                        <div class="select">
                            <select name="table-quantity" class="table-quantity"
                                data-price="<?php echo get_rv_price($table['id']); ?>"
                                data-label="<?php echo $table['title']; ?>">
                                <?php for($i=0; $i<=2; $i++) : ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <p>
                            <strong><?php echo $table['title']; ?></strong>
                            <span><?php echo $table['content']; ?></span>
                        </p>
                    </div>
                    <?php 
                                    }
                                }
                                ?>
                </div>
            </div>
            <div class="warning-message">
                <p>
                    <strong>Please note:</strong> A maximum of 6 tickets and/or 2
                    tables can be purchased per booking. For individual requests,
                    please send us an email at:
                    maikel@hummingbirdfashionaward.com. Kindly note that
                    availability is limited.
                </p>
                <small>The maximum number of tickets/tables per booking has been
                    exceeded. Please adjust your quantity to the maximum limit of
                    6 tickets and/or 2 tables.</small>
            </div>
        </div>
        <div class="steps step-two">
            <!-- Left side -->
            <!-- Personal Information for Booking: -->
            <div class="billing">
                <h2>Personal Information for Booking:</h2>
                <div class="billing-item">
                    <label for="first-name">
                        <input type="text" name="first-name" id="first-name" />
                        <span>* First Name</span>
                    </label>
                </div>
                <div class="billing-item">
                    <label for="last-name">
                        <input type="text" name="last-name" id="last-name" />
                        <span>* Last Name</span>
                    </label>
                </div>
                <div class="billing-item">
                    <label for="email">
                        <input type="email" name="email" id="email" />
                        <span>* Email</span>
                    </label>
                </div>
            </div>

            <!-- Right side -->
            <!-- Billing Address: -->
            <div class="billing">
                <h2>Billing Address:</h2>
                <div class="billing-item">
                    <label for="street">
                        <input type="text" name="street" id="street" />
                        <span>* Street</span>
                    </label>
                </div>
                <div class="billing-item">
                    <label for="postal-code">
                        <input type="text" name="postal-code" id="postal-code" />
                        <span>* Postal Code</span>
                    </label>
                </div>
                <div class="billing-item">
                    <label for="city">
                        <input type="text" name="city" id="city" />
                        <span>* City</span>
                    </label>
                </div>
                <!-- Country -->
                <div class="quantity select-section country-selection">
                    <div class="select">
                        <select name="country" id="country">
                            <option value="1">-- Select Country --</option>
                            <option value="United States of America">
                                United States of America
                            </option>
                            <option value="United Kingdom">United Kingdom</option>
                            <option value="Germany">Germany</option>
                            <option value="France">France</option>
                            <option value="Spain">Spain</option>
                            <option value="Italy">Italy</option>
                            <option value="Netherlands">Netherlands</option>
                        </select>
                    </div>
                    <span>* Country</span>
                </div>
            </div>
        </div>

        <!-- Payment  -->
        <div class="total-amount">
            <p>
                Total Order
                <span id="order-label"></span>
            </p>
            <p>Total: <strong id="total-price"></strong><span id="currency-symbol">€</span></p>
        </div>
        <div class="payment-container">
            <div class="payment">
                <h2>Payment options choose PayPal or Invoice:</h2>
                <div class="payment-item">
                    <label for="paypal">
                        <input type="radio" name="payment-option" id="paypal" value="paypal" />
                        <p>
                            <strong>PayPal</strong>
                            <span>(Checkout via Paypal)</span>
                        </p>
                    </label>
                </div>
                <div class="payment-item">
                    <label for="invoice">
                        <input type="radio" name="payment-option" id="invoice" value="invoice" />
                        <p>
                            <strong>Invoice</strong>
                            <span>(invoice sent via E-mail)</span>
                        </p>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="payment">
                <input type="hidden" name="ticket-total-quantity" id="ticket_total_quantity" value="0" />
                <input type="hidden" name="table-total-quantity" id="table_total_quantity" value="0" />
                <input type="hidden" name="rv-total-price" id="rv-total-price" value="0" />
                <button type="submit" class="submit-button common-button" id="complete-reservation">
                    <span>Complete Reservation</span>
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/xmark.png" alt="arrow-right" />
                </button>
            </div>
        </div>
    </form>
</section>