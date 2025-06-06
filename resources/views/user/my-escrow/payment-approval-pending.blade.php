@extends('user.layouts.master') 
@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Escrow Details")])
@endsection
@push('css')
    <style>
        .text-capitalize{
            text-transform: capitalize;
        }
    </style>
@endpush
@section('content')
<div class="body-wrapper">
    <form action="{{ setRoute('user.escrow-action.paymentApprovalSubmit',$escrow->id) }}" class="preview-form" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="escrowAmount" value="{{ $escrow->amount }}">
        <input type="hidden" name="escrowCurrencyRate" value="{{ $escrow->escrowCurrency->rate }}">
        <input type="hidden" name="escrowCurrencyCode" value="{{ $escrow->escrow_currency }}">
        <input type="hidden" name="escrowRole" value="{{ $escrow->role }}">
        <input type="hidden" name="escrowChargePayer" value="{{ $escrow->who_will_pay }}">
        <input type="hidden" name="escrowTotalCharge" value="{{ $escrow->escrowDetails->fee }}">
        <div class="row mt-20 mb-20-none">
            <div class="col-xl-6 col-lg-6 mb-20">
                <div class="custom-card mt-10">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ __("Escrow Details") }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-heading"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Title") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ substr($escrow->title,0,50)."..." }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-user-tag"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Role") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text-capitalize role">{{ $escrow->role == "seller" ? __("Buyer") : __("Seller") }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-box"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Product Type") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ $escrow->escrowCategory->name }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-dollar-sign"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Total Price") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--success">{{ get_amount($escrow->amount,$escrow->escrow_currency) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-tags"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Charge Payer") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text-capitalize">
                                        @if ($escrow->role == "seller" && $escrow->string_who_will_pay->value == "Me")
                                            {{ "Seller" }}
                                        @elseif ($escrow->role == "seller" && $escrow->string_who_will_pay->value == "Buyer")
                                        {{ "Me" }}
                                        @else
                                        {{ $escrow->string_who_will_pay->value }}
                                        @endif 
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 mb-20">
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
                                    <span class="pay_with_gateway">--</span>
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
                                    <span class="eschangeRateShow">--</span>
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
                                    <span class="text--info last totalPayable">--</span>
                                </div>
                            </div> 
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12 col-lg-12 form-group paymentMethodSelectForBuyer">
                <label>{{ __("Pay with") }}<span>*</span></label>
                <select class="form--control payment_gateway nice-select" name="payment_gateway">
                    <option 
                    value="myWallet"
                    data-code="{{ $escrow->escrow_currency }}"
                    data-rate="{{ $escrow->escrowCurrency->rate }}"
                    data-name="My Wallet"
                     class="my_wallet_balance">{{ __("My Wallet") }} {{ get_amount($user_wallet->balance,$escrow->escrow_currency) }}</option>
                    @foreach ($payment_gateways_currencies as $item)
                    <option 
                    value="{{ $item->id }}"
                    data-code="{{ $item->currency_code }}"
                    data-rate="{{ $item->rate }}"
                    data-name="{{ $item->name }}"
                    >
                    {{ $item->name}}
                    @if ($item->gateway->type == payment_gateway_const()::MANUAL)
                        {{ "(Manual)" }}
                    @endif
                </option>
                    @endforeach 
                </select>
            </div>
        </div>
        <button type="submit" class="btn--base mt-20 w-100">{{ __("Confirm & Pay") }}</button>
    </form>
</div>
@endsection
@push('script')
    <script>
        $(document).ready(function(){
            approvalPendingDetails()
            
        });
        $('select[name=payment_gateway]').on('change',function(){  
            approvalPendingDetails()
        }); 
        function approvalPendingDetails() {
            var payment_gateway_value = $("select[name=payment_gateway] :selected").val(); 
            var payment_gateway_name = $("select[name=payment_gateway] :selected").attr("data-name"); 
            var payment_gateway_rate = $("select[name=payment_gateway] :selected").attr("data-rate"); 
            var payment_gateway_currency_code = $("select[name=payment_gateway] :selected").attr("data-code"); 

            var escrow_amount = $("input[name=escrowAmount]").val();
            var escrow_currency_rate = $("input[name=escrowCurrencyRate]").val();
            var escrow_currency_code = $("input[name=escrowCurrencyCode]").val();

            var escrow_role = $("input[name=escrowRole]").val();
            var escrow_charge_payer = $("input[name=escrowChargePayer]").val();
            var escrow_total_charge = $("input[name=escrowTotalCharge]").val();

            //calculate payable amount 
            var exchange_rate = (1/escrow_currency_rate)*payment_gateway_rate;
            //buyer amount calculate 
            var buyerAmount = 0;
            if (escrow_role == "seller" && escrow_charge_payer == "me") {
                var buyerAmount = escrow_amount*exchange_rate; 
            }else if(escrow_role == "seller" && escrow_charge_payer == "buyer"){
                var escrow_total_amount = parseFloat(escrow_amount) + parseFloat(escrow_total_charge);
                var buyerAmount = escrow_total_amount*exchange_rate;
            }else if(escrow_role == "seller" && escrow_charge_payer == "half"){
                var escrow_total_amount = parseFloat(escrow_amount) + parseFloat(escrow_total_charge/2);
                var buyerAmount = escrow_total_amount*exchange_rate;
            } 
            //approval pending details preview 
            $('.pay_with_gateway').text(payment_gateway_name);
            $('.eschangeRateShow').text('1 ' + escrow_currency_code + ' = '+ exchange_rate.toFixed(2) + ' ' + payment_gateway_currency_code);
            $('.totalPayable').text(buyerAmount.toFixed(2) + " " + payment_gateway_currency_code);

        }
    </script>
@endpush