<div
    style="max-width: 600px; background-color: #ffffff; margin: auto; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);">
    <div
        style="text-align: center; background-color: #0073e6; color: white; padding: 10px; border-radius: 10px 10px 0 0;">
        <h2>Order Confirmation</h2>
    </div>
    <div style="padding: 20px;">
        <p>Hi <strong>{{customer_name}}</strong>,</p>
        <p>Thank you for your order! Here are the details:</p>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: left;">Item</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: left;">Quantity</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: left;">Price</th>
            </tr>
            {{order_items}}
        </table>
        <p><strong>Total:</strong> {{order_total}}</p>
        <p>Order ID: <strong>{{order_id}}</strong></p>
        <p>Expected Delivery: <strong>{{delivery_date}}</strong></p>
        <p><a href="{{order_link}}"
                style="display: inline-block; padding: 10px 20px; background-color: #0073e6; color: white; text-decoration: none; border-radius: 5px;">View
                Order</a></p>
    </div>
    <div style="text-align: center; padding: 10px; font-size: 14px; color: #777;">
        <p>&copy; 2025 Your Company. All rights reserved.</p>
    </div>
</div>