
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razorpay Payment</title>
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
                            <h4 class="title">{{ __("Summery") }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="preview-list-wrapper">
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-receipt"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Entered Amount") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="text--success">{{ number_format(@$oldData->amount->requested_amount,2 )}} {{ @$oldData->amount->sender_currency }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-battery-half"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Exchange Rate") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="text--danger">{{ __("1") }} {{ @$oldData->amount->sender_currency }} =  {{ number_format(@$oldData->amount->exchange_rate,2 )}} {{ @$oldData->amount->gateway_cur_code }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-money-check-alt"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Conversion Amount") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="conversion">{{ number_format(@$oldData->amount->requested_amount*$oldData->amount->exchange_rate,2 )}} {{ @$oldData->amount->gateway_cur_code }}</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-battery-half"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __("Total Fees & Charges") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="text--warning">{{ number_format(@$oldData->amount->gateway_total_charge,2 )}} {{ @$oldData->amount->gateway_cur_code }}</span>
                                    </div>
                                </div> 
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-money-check-alt"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span class="last">{{ __("Total Payable") }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="text--info last pay-in-total">{{ number_format(@$oldData->amount->total_payable_amount,2 )}} {{ @$oldData->amount->gateway_cur_code }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> 
                    <form action="{{ route('api.v1.add-money.razor.callback') }}" method="GET"> 
                        <script
                            src="https://checkout.razorpay.com/v1/checkout.js"
                            data-key="{{ $oldData->response->public_key }}"
                            data-amount="{{ intval($oldData->amount->total_payable_amount) }}"
                            data-currency="INR"
                            data-name="Add Money"
                            data-description="Add Money"
                            data-image="https://your-awesome-site.com/logo.png"
                            data-prefill.name="ssss"
                            data-prefill.email="ssss"
                            data-theme.color="#F37254"
                        ></script>
                        <input type="hidden" value="{{ $oldData->response->order_id }}" name="razorpay_order_id">
                        <input type="hidden" value="INR" name="razorpay_currency">
                        <input type="hidden" value="{{ intval($oldData->amount->total_payable_amount) }}" name="razorpay_amount">
                        <input type="hidden" value="Add Money" name="razorpay_merchant_name">
                        <input type="hidden" value="Payment for Order ID: {{ $oldData->response->order_id }}" name="razorpay_description">
                        <input type="hidden" value="/payment/failure" name="razorpay_cancel_url">
                        <button type="submit" class="btn--base mt-20 w-100">{{ __("Pay Now") }}</button>
                    </form> 
                </div>  
            </div> 
        </div>
    </div>

</body>
</html>


