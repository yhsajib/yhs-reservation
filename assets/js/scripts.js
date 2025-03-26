jQuery(document).ready(function ($) {
    function getSelectedItems() {
        let items = [];

        // Get selected tables
        $(".table-quantity").each(function () {
            let quantity = parseInt($(this).val()) || 0;
            if (quantity > 0) {
                let label = $(this).data("label");
                let price = $(this).data("price");
                items.push({ type: "table", label, quantity, price });
            }
        });

        // Get selected tickets
        $(".ticket-quantity").each(function () {
            let quantity = parseInt($(this).val()) || 0;
            if (quantity > 0) {
                let label = $(this).data("label");
                let price = $(this).data("price");
                items.push({ type: "ticket", label, quantity, price });
            }
        });

        return items;
    }

/*     $("#complete-reservation").on("click", function (e) {

        e.preventDefault();
        let selectedPayment = $('input[name="payment-option"]:checked').val();

        if (!selectedPayment) {
            alert("Please select a payment method.");
            return;
        }

        let items = getSelectedItems();
        let totalPrice = $("#total-price").text();

        if (selectedPayment === "paypal") {
            $.ajax({
                url: rs_obj.ajax_url,
                type: "POST",
                data: {
                    action: "process_paypal_payment",
                    items: JSON.stringify(items),
                    total_price: totalPrice,
                },
                success: function (response) {
                    if (response.success && response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        alert("Error processing PayPal payment.");
                    }
                },
            });
        }else{

        }
    }); */
    function updateTotalPrice() {
        console.log("updateTotalPrice() function called");

        let tableTotal = 0, ticketTotal = 0;
        let tableCount = 0, ticketCount = 0;
        let orderSummary = [];
        let errorMessage = "";

        // Function to calculate total and count
        function calculateTotal(selector, defaultLabel) {
            let total = 0, count = 0;
            $(selector).each(function () {
                let price = $(this).data("price") || 0;
                let quantity = parseInt($(this).val()) || 0;
                let label = $(this).data("label") || defaultLabel;

                if (quantity > 0) {
                    total += price * quantity;
                    count += quantity;
                    orderSummary.push(`${quantity} x ${label}`);
                }
            });
            return { total, count };
        }

        // Calculate tables and tickets
        let tableData = calculateTotal(".table-quantity", "Table");
        let ticketData = calculateTotal(".ticket-quantity", "Ticket");

        tableTotal = tableData.total;
        tableCount = tableData.count;
        ticketTotal = ticketData.total;
        ticketCount = ticketData.count;

        $("#ticket_total_quantity").val(ticketCount);
        $("#table_total_quantity").val(tableCount);
        // Validation checks
        if (tableCount > 2) {
            errorMessage = "You can only select up to 2 tables.";
            $(".table-quantity").val(0);
        } else if (ticketCount > 6) {
            errorMessage = "You can only select up to 6 tickets.";
            $(".ticket-quantity").val(0);
        } else if (tableCount < 2 || ticketCount < 6) {
            errorMessage = "";
        }

        // Update UI
        $(".yhs-rv-notice").text(errorMessage);
        let grandTotal = tableTotal + ticketTotal;

        if (errorMessage) {
            $("#table-total-price, #ticket-total-price, #total-price, #order-label").text(0);
            return;
        }

        $("#order-label").text(orderSummary.join(", "));
        $("#total-price").text(grandTotal);
        $("#rv-total-price").val(grandTotal);
    }

    // Event listener for quantity changes
    $(document).on("change", ".table-quantity, .ticket-quantity", updateTotalPrice);
});
