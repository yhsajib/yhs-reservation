<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$tables = get_rev_product('tables');
$tickets = get_rev_product('tickets');
$paypal = get_option('rs_enable_paypal');
$invoice = get_option('rs_enable_invoice');
$rvtemp = get_session_tempdata($_SESSION['rv_form']);
?>
<section class="exclusive-seating-section">
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
                            <select name="ticket-quantity_<?php echo $ticket['id']; ?>" class="ticket-quantity"
                                data-price="<?php echo get_rv_price($ticket['id']); ?>"
                                data-label="<?php echo $ticket['title']; ?>">
                                <?php for($i=0; $i<=6; $i++) : ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($_SESSION['rv_form']['ticket-quantity_'. $ticket['id']]) && $_SESSION['rv_form']['ticket-quantity_'. $ticket['id']] == $i ? 'selected' : ''); ?>><?php echo $i; ?></option>
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
                            <select name="table-quantity_<?php echo $table['id']; ?>" class="table-quantity"
                                data-price="<?php echo get_rv_price($table['id']); ?>"
                                data-label="<?php echo $table['title']; ?>">
                                <?php for($i=0; $i<=2; $i++) : ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($_SESSION['rv_form']['table-quantity_'. $table['id']]) && $_SESSION['rv_form']['table-quantity_'. $table['id']] == $i ? 'selected' : ''); ?> ><?php echo $i; ?></option>
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
                <div>
                    <p class="pb-1"><strong>We are pleased to inform you that by purchasing a ticket or a table, you are supporting the Elton John AIDS Foundation.</strong></p>
                   <p class="pb-1"> <strong>Please note:</strong> A maximum of 6 tickets and/or 2
                    tables can be purchased per booking. For individual requests,
                    please send us an email at:
                    maikel@hummingbirdfashionaward.com. Kindly note that
                    availability is limited.
                    </p>
                </div>
                <small class="yhs-rv-notice error"> <?php 
            if (!empty($_SESSION['rv_error'])) { 
                echo esc_html($_SESSION['rv_error']);   
                unset($_SESSION['rv_error']);
            } ?> </small>
            </div>
        </div>
        <div class="steps step-two">
            <!-- Left side -->
            <!-- Personal Information for Booking: -->
            <div class="billing">
                <h2>Personal Information for Booking:</h2>
                <div class="billing-item">
                    <label for="first-name">
                        <input type="text" required name="first-name" id="first-name" value="<?php echo esc_attr($rvtemp['first-name']); ?>" />
                        <span>* First Name</span>
                    </label>
                </div>
                <div class="billing-item">
                    <label for="last-name">
                        <input type="text" required name="last-name" id="last-name" value=" <?php echo esc_attr($rvtemp['last-name']); ?>" />
                        <span>* Last Name</span>
                    </label>
                </div>
                <div class="billing-item">
                    <label for="email">
                        <input type="email" required name="email" id="email" value="<?php echo esc_attr($rvtemp['email']); ?>" />
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
                        <input type="text" required name="street" id="street" value=" <?php echo esc_attr($rvtemp['street']); ?>" />
                        <span>* Street</span>
                    </label>
                </div>
                <div class="billing-item">
                    <label for="postal-code">
                        <input type="text" required name="postal-code" id="postal-code" value="<?php echo esc_attr($rvtemp['postal-code']); ?>"  />
                        <span>* Postal Code</span>
                    </label>
                </div>
                <div class="billing-item">
                    <label for="city">
                        <input type="text" required name="city" id="city"  value="<?php echo esc_attr($rvtemp['city']); ?>"/>
                        <span>* City</span>
                    </label>
                </div>
                <!-- Country -->
                <div class="quantity select-section country-selection">
                    <div class="select">
                    <select id="country" name="country" required class="form-control">
                        <option value="">-- Select Country --</option>
                        <?php
                        $countries = [
                            "Afghanistan", "Åland Islands", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", 
                            "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", 
                            "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", 
                            "Botswana", "Brazil", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", 
                            "Cape Verde", "Central African Republic", "Chad", "Chile", "China", "Colombia", "Comoros", "Congo", "Costa Rica", 
                            "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "Ecuador", 
                            "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Fiji", "Finland", "France", "Gabon", 
                            "Gambia", "Georgia", "Germany", "Ghana", "Greece", "Greenland", "Grenada", "Guatemala", "Guinea", "Haiti", "Honduras", 
                            "Hungary", "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", 
                            "Kazakhstan", "Kenya", "Kuwait", "Latvia", "Lebanon", "Lithuania", "Luxembourg", "Malaysia", "Malta", "Mexico", "Mongolia", 
                            "Morocco", "Nepal", "Netherlands", "New Zealand", "Nigeria", "Norway", "Pakistan", "Peru", "Philippines", "Poland", 
                            "Portugal", "Qatar", "Romania", "Russia", "Saudi Arabia", "Singapore", "South Africa", "South Korea", "Spain", "Sweden", 
                            "Switzerland", "Thailand", "Turkey", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "Vietnam", "Zimbabwe"
                        ];
                        
                        foreach ($countries as $country) {
                            $selected = ($rvtemp['country'] == $country) ? "selected" : "";
                            echo "<option value='" . htmlspecialchars($country) . "' $selected>" . htmlspecialchars($country) . "</option>";
                        }
                        ?>
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
            <p>Total <strong id="total-price"></strong></p>
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
                <input type="hidden" name="rv-items" id="rv-items" value="0" />
                <input type="hidden" name="ticket-total-quantity" id="ticket_total_quantity" value="0" />
                <input type="hidden" name="table-total-quantity" id="table_total_quantity" value="0" />
                <input type="hidden" name="rv-total-price" id="rv-total-price" value="0" />
				
				<div class="terms-condition">
					 <label for="term-policy">
                        <input type="checkbox" name="term-policy" id="term-policy" value="1" />
                        <p>
                        <span>I agree to the <strong><a href="https://hummingbirdfashionaward.com/terms-and-conditions" target="_blank">Terms</a></strong> and <strong><a href="https://hummingbirdfashionaward.com/privacy-policy" target="_blank">Privacy Policy</a></strong></span>
                        </p>
                    </label>
				</div>
                <button type="submit" class="submit-button common-button" id="complete-reservation">
                    <span>Complete Reservation</span>
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/xmark.png" alt="arrow-right" />
                </button>
            </div>
        </div>
    </form>
</section>