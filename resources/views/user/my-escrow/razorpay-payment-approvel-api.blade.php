

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Design</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            width: 300px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease-in-out;
        }

        .card:hover {
            transform: scale(1.05);
        }

        .card img {
            width: 100%;
            height: auto;
        }

        .card-content {
            padding: 16px;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .card-description {
            font-size: 1rem;
            color: #666;
        }

        .card-footer {
            padding: 12px;
            background-color: #f0f0f0;
            text-align: center;
        }
        .btn--base {
            display: inline-block;
            padding: 10px 20px; 
            font-size: 1rem;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out;
            width: 100%; /* Set the width to 100% */
        }

        .btn--base:hover {
            background-color: #4CAF50; /* Change to your desired hover color */
            color: #fff; /* Change to your desired text color on hover */
        }
        .razorpay-payment-button{
            display: none;
        }
        .custom-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 300px;
            margin-top: 10px;
        }

        .dashboard-header-wrapper {
            background-color: #4CAF50;
            color: #fff;
            padding: 10px;
            text-align: center;
        }

        .title {
            margin: 0;
            font-size: 1.2rem;
        }

        .card-body {
            padding: 16px;
        }

        .preview-list-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }

        .preview-list-right {
            text-align: right;
        }

        .text--success {
            color: #4CAF50;
        }

        .text--danger {
            color: #FF5733;
        }

        .conversion {
            color: #333;
        }

        .text--warning {
            color: #FFC300;
        }

        .text--info {
            color: #3498DB;
        }

        .last {
            font-weight: bold;
        }

        .pay-in-total {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

    <div class="card">
        <div class="body-wrapper">
            @php
                $oldData = $temData->data;
            @endphp
            <div class="row mt-20 mb-20-none">
                <div class="col-xl-8 col-lg-8 mx-auto mb-20"> 
                    <div class="custom-card mt-10">
                        <div class="dashboard-header-wrapper">
                            <h4 class="title">{{ __("Payment Details") }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="preview-list-wrapper">
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-battery-half"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Fees & Charge") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="text--warning">{{ get_amount($temData->data->escrow->escrow_total_charge, $temData->data->escrow->escrow_currency) }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-wallet"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span class="sellerGet">{{ __("Seller Will Get") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span>{{ get_amount($temData->data->escrow->seller_amount, $temData->data->escrow->escrow_currency) }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="lab la-get-pocket"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Pay With") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span>{{ $temData->data->gateway_currency->name }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="lab la-get-pocket"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Exchange Rate") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span>1 {{ $temData->data->escrow->escrow_currency }} = {{ get_amount($temData->data->escrow->gateway_exchange_rate,$temData->data->escrow->gateway_currency) }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-money-check-alt"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span class="last buyerPay">{{ __("Buyer Will Pay") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="text--info last">{{ get_amount($temData->data->escrow->buyer_amount,$temData->data->escrow->gateway_currency)}}</span>
                                    </div>
                                </div> 
                            </div>
                        </div>
                    </div> 
                    <form action="{{ route('api.v1.api-escrow-action.payment.approval.razorCallback') }}" method="GET">
                        <script
                            src="https://checkout.razorpay.com/v1/checkout.js"
                            data-key="{{ $request_data['public_key'] }}"
                            data-amount="{{ intval($temData->data->escrow->buyer_amount) }}"
                            data-currency="INR"
                            data-name="Escrow Payment Approval"
                            data-description="Escrow Payment Approval"
                            data-image="https://your-awesome-site.com/logo.png"
                            data-prefill.name=""
                            data-prefill.email=""
                            data-theme.color="#F37254"
                        ></script>
                        <input type="hidden" value="{{ $request_data['order_id'] }}" name="razorpay_order_id">
                        <input type="hidden" value="{{ $request_data['trx'] }}" name="trx">
                        <input type="hidden" value="INR" name="razorpay_currency">
                        <input type="hidden" value="{{ intval($temData->data->escrow->buyer_amount) }}" name="razorpay_amount">
                        <input type="hidden" value="Escrow Create" name="razorpay_merchant_name">
                        <input type="hidden" value="Payment for Order ID: {{ $request_data['order_id'] }}" name="razorpay_description">
                        <input type="hidden" value="{{ env('APP_URL') }}/payment/failure" name="razorpay_cancel_url">
                        <button type="submit" class="btn--base mt-20 w-100">{{ __("Pay Now") }}</button>
                    </form> 
                </div>  
            </div> 
        </div>
    </div>

</body>
</html> 