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
                                <span class="text--warning">{{ get_amount($escrowData->escrow->escrow_total_charge, $escrowData->escrow->escrow_currency) }}</span>
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
                                <span>{{ get_amount($escrowData->escrow->seller_amount, $escrowData->escrow->escrow_currency) }}</span>
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
                                <span>{{ $escrowData->escrow->payment_method }}</span>
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
                                <span>1 {{ $escrowData->escrow->escrow_currency }} = {{ get_amount($escrowData->escrow->gateway_exchange_rate,$escrowData->escrow->gateway_currency) }}</span>
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
                                <span class="text--info last">{{ get_amount($escrowData->escrow->buyer_amount,$escrowData->escrow->gateway_currency)}}</span>
                            </div>
                        </div> 
                    </div>
                </div>
            </div> 
            <form action="{{ route('user.my-escrow.razor.callback') }}" method="GET">
                <script
                    src="https://checkout.razorpay.com/v1/checkout.js"
                    data-key="{{ $data['public_key'] }}"
                    data-amount="{{ intval($escrow_data->escrow->buyer_amount) }}"
                    data-currency="INR"
                    data-name="Escrow Create"
                    data-description="Escrow Create"
                    data-image="https://your-awesome-site.com/logo.png"
                    data-prefill.name="{{ auth()->user()->username }}"
                    data-prefill.email="{{ auth()->user()->email }}"
                    data-theme.color="#F37254"
                ></script>
                <input type="hidden" value="{{ $orderId }}" name="razorpay_order_id">
                <input type="hidden" value="{{ $escrow_data->trx }}" name="trx">
                <input type="hidden" value="INR" name="razorpay_currency">
                <input type="hidden" value="{{ intval($escrow_data->escrow->buyer_amount) }}" name="razorpay_amount">
                <input type="hidden" value="Escrow Create" name="razorpay_merchant_name">
                <input type="hidden" value="Payment for Order ID: {{ $orderId }}" name="razorpay_description">
                <input type="hidden" value="{{ env('APP_URL') }}/payment/failure" name="razorpay_cancel_url">
                <button type="submit" class="btn--base mt-20 w-100">{{ __("Pay Now") }}</button>
            </form> 
        </div> 
    </div> 
</div>
@endsection 