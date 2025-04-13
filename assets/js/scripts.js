jQuery(document).ready(function ($) {
    function getSelectedItems() {
        let allitems = [];

        // Get selected tables
        $(".table-quantity").each(function () {
            let quantity = parseInt($(this).val()) || 0;
            if (quantity > 0) {
                let items = $(this).data("label");
                let price = parseFloat($(this).data("price")) || 0;
                let totalPrice = price * quantity;
                allitems.push({ type: "table", items, quantity, price: formatPriceEUR(totalPrice) });
            }
        });

        // Get selected tickets
        $(".ticket-quantity").each(function () {
            let quantity = parseInt($(this).val()) || 0;
            if (quantity > 0) {
                let items = $(this).data("label");
                let price = parseFloat($(this).data("price")) || 0;
                let totalPrice = price * quantity;
                allitems.push({ type: "ticket", items, quantity, price: formatPriceEUR(totalPrice) });
            }
        });

        return allitems;
    }

    function updateTotalPrice() {
		 $("#rv-items").val(JSON.stringify(getSelectedItems()));
        let tableTotal = 0, ticketTotal = 0;
        let tableCount = 0, ticketCount = 0;
        let orderSummary = [];
        let errorMessage = "";

        function calculateTotal(selector, defaultLabel) {
            let total = 0, count = 0;
            let categorySummary = [];
            $(selector).each(function () {
                let price = parseFloat($(this).data("price")) || 0;
                let quantity = parseInt($(this).val()) || 0;
                let label = $(this).data("label") || defaultLabel;

                // Extract first part of label if it contains "–"
                label = label.split("–")[0].trim();

                if (quantity > 0) {
                    let itemTotal = price * quantity;
                    total += itemTotal;
                    count += quantity;
                    categorySummary.push(`${quantity} x ${label} `);
                }
            });
            return { total, count, summary: categorySummary };
        }

        // Calculate totals
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
            errorMessage = "The maximum number of tables per booking has been exceeded. Please adjust your quantity to the maximum limit of 2 tables.";
            $(".table-quantity").val("0").trigger("change"); // Reset only tables
            tableTotal = 0;
            tableCount = 0;
            tableData.summary = []; // Remove table labels
        }

        if (ticketCount > 6) {
            errorMessage = "The maximum number of tickets per booking has been exceeded. Please adjust your quantity to the maximum limit of 6 tickets.";
            $(".ticket-quantity").val("0").trigger("change"); // Reset only tickets
            ticketTotal = 0;
            ticketCount = 0;
            ticketData.summary = []; // Remove ticket labels
        }

        // Update order label (removes reset category labels)
        orderSummary = [...tableData.summary, ...ticketData.summary];

        // Update total price correctly
        let grandTotal = tableTotal + ticketTotal;

        $("#order-label").text(orderSummary.length ? orderSummary.join(", ") : ""); // Remove label if empty
        $("#total-price").text(formatPriceEUR(grandTotal));
        $("#rv-total-price").val(grandTotal);

        // Show validation error message
        $(".yhs-rv-notice").text(errorMessage);
    }

    // Event listener for quantity changes
    $(document).on("change", ".table-quantity, .ticket-quantity", updateTotalPrice);
	
   $('#complete-reservation').click(function (e) {   
    
    if(getSelectedItems().length === 0) {
        e.preventDefault(); // Prevent form submission
        errorMessage = "Please select at least one table or ticket.";
        $(".yhs-rv-notice").text(errorMessage);
        return; // Stop execution if no items are selected
    }
    var form = $(this).closest('form')[0]; // Get the form element

    if (!form.checkValidity()) {
        form.reportValidity(); // Show validation messages for required fields
        return; // Stop execution if required fields are missing
    }

    var selectedPayment = $('input[name="payment-option"]:checked').val();
    var termPolicy = $('input[name="term-policy"]:checked').val();
    
    if (!selectedPayment || !termPolicy) {
        e.preventDefault(); // Prevent form submission
    
        let errorMessage = "";
    
        if (!selectedPayment) {
            errorMessage = "Please select a payment option.";
        } else if (!termPolicy) {
            errorMessage = "Please check Terms and Privacy Policy.";
        }
    
        $(".yhs-rv-notice").text(errorMessage);
    } else {
        form.submit(); // Submit the form if all validations pass
    }
    
});


});

function formatPriceEUR(amount) {
    return new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + " €";
}
