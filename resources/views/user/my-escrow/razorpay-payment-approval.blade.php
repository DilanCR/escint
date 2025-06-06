@extends('user.layouts.master') 
@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Escrow Manual Payment")])
@endsection
@push('css')
    <style>
        .text-capitalize{
            text-transform: capitalize;
        }
        .razorpay-payment-button{
            display: none;
        }
    </style>
@endpush
 @php 
     $escrow = App\Models\Escrow::find($escrow_data->escrow->escrow_id);
     $escrowData = $escrow_data;
 @endphp
@section('content')
<div class="body-wrapper">
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
                                <span class="text--warning">{{ get_amount($escrow->escrowDetails->fee,$escrow->escrow_currency) }}</span>
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
                                <span>{{ $escrowData->gateway_currency->name }}</span>
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
                                <span>1 {{ $escrow->escrow_currency }} = {{ get_amount($escrowData->escrow->eschangeRate,$escrowData->gateway_currency->currency_code) }}</span>
                            </div>
                        </div>
                        <div class="preview-list-item">
                            <div class="preview-list-left">
                                <div class="preview-list-user-wrapper">
                                    <div class="preview-list-user-icon">
                                        <i class="las la-money-check-alt"></i>
                                    </div>
                                    <div class="preview-list-user-content">
                                        <span class="last buyerPay">{{ __("You Will Pay") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-list-right">
                                <span class="text--info last">{{ get_amount($escrowData->escrow->buyer_amount,$escrowData->gateway_currency->currency_code)}}</span>
                            </div>
                        </div> 
                    </div>
                </div>
            </div> 
            <form action="{{ route('user.escrow-action.razor.callback') }}" method="GET">
                <script
                    src="https://checkout.razorpay.com/v1/checkout.js"
                    data-key="{{ $data['public_key'] }}"
                    data-amount="{{ intval($escrow_data->escrow->buyer_amount) }}"
                    data-currency="INR"
                    data-name="Payment Approvel"
                    data-description="Payment Approvel"
                    data-image="https://your-awesome-site.com/logo.png"
                    data-prefill.name="{{ auth()->user()->username }}"
                    data-prefill.email="{{ auth()->user()->email }}"
                    data-theme.color="#F37254"
                ></script>
                <input type="hidden" value="{{ $orderId }}" name="razorpay_order_id">
                <input type="hidden" value="{{ $escrow->id }}" name="trx">
                <input type="hidden" value="INR" name="razorpay_currency">
                <input type="hidden" value="{{ intval($escrow_data->escrow->buyer_amount) }}" name="razorpay_amount">
                <input type="hidden" value="Payment Approvel" name="razorpay_merchant_name">
                <input type="hidden" value="Payment for Order ID: {{ $orderId }}" name="razorpay_description">
                <input type="hidden" value="{{ env('APP_URL') }}/payment/failure" name="razorpay_cancel_url">
                <button type="submit" class="btn--base mt-20 w-100">{{ __("Pay Now") }}</button>
            </form> 
        </div> 
    </div> 
</div>
@endsection
@push('script')
    <script>
        $(document).ready(function(){
            var role = $('.role').text();
            if (role == "seller") {
                $('.sellerGet').text('I Will Get');
            } 
            if (role == "buyer") {
                $('.buyerPay').text('You Will Pay');
            } 
        });
    </script>
@endpush