@extends('user.layouts.master')

@push('css')
<style>
    .razorpay-payment-button{
        display: none;
    }
  </style>
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Razorpay Payment")])
@endsection

@section('content')
<div class="body-wrapper">
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
                                <span class="text--success">{{ number_format(@$output['amount']->requested_amount,2 )}} {{ @$output['amount']->sender_currency }}</span>
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
                                <span class="text--danger">{{ __("1") }} {{ @$output['amount']->sender_currency }} =  {{ number_format(@$output['amount']->exchange_rate,2 )}} {{ @$output['amount']->gateway_cur_code }}</span>
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
                                <span class="conversion">{{ number_format(@$output['amount']->requested_amount*$output['amount']->exchange_rate,2 )}} {{ @$output['amount']->gateway_cur_code }}</span>
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
                                <span class="text--warning">{{ number_format(@$output['amount']->gateway_total_charge,2 )}} {{ @$output['amount']->gateway_cur_code }}</span>
                            </div>
                        </div> 
                        <div class="preview-list-item">
                            <div class="preview-list-left">
                                <div class="preview-list-user-wrapper">
                                    <div class="preview-list-user-icon">
                                        <i class="las la-money-check-alt"></i>
                                    </div>
                                    <div class="preview-list-user-content">
                                        <span class="last">{{ __("Total Payable Amount") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-list-right">
                                <span class="text--info last pay-in-total">{{ number_format(@$output['amount']->total_payable_amount,2 )}} {{ @$output['amount']->gateway_cur_code }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
            <form action="{{ route('user.add.money.razor.callback') }}" method="GET">
                <script
                    src="https://checkout.razorpay.com/v1/checkout.js"
                    data-key="{{ $data['public_key'] }}"
                    data-amount="{{ intval($output['amount']->total_payable_amount) }}"
                    data-currency="INR"
                    data-name="Add Money"
                    data-description="Add Money"
                    data-image="https://your-awesome-site.com/logo.png"
                    data-prefill.name="{{ auth()->user()->username }}"
                    data-prefill.email="{{ auth()->user()->email }}"
                    data-theme.color="#F37254"
                ></script>
                <input type="hidden" value="{{ $orderId }}" name="razorpay_order_id">
                <input type="hidden" value="INR" name="razorpay_currency">
                <input type="hidden" value="{{ intval($output['amount']->total_payable_amount) }}" name="razorpay_amount">
                <input type="hidden" value="Add Money" name="razorpay_merchant_name">
                <input type="hidden" value="Payment for Order ID: {{ $orderId }}" name="razorpay_description">
                <input type="hidden" value="{{ env('APP_URL') }}/payment/failure" name="razorpay_cancel_url">
                <button type="submit" class="btn--base mt-20 w-100">{{ __("Pay Now") }}</button>
            </form> 
        </div>  
    </div> 
</div>
@endsection

@push('script')

@endpush
