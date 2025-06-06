@extends('user.layouts.master') 
@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Add Money")])
@endsection

@section('content') 
    <div class="body-wrapper">
        <div class="row mt-20 mb-20-none">
            <div class="col-xl-7 col-lg-7 mb-20">
                <div class="custom-card mt-10">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ __("Add Money") }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="card-form" action="{{ setRoute("user.add.money.submit") }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 form-group text-center">
                                    <div class="exchange-area">
                                        <code class="d-block text-center"><span>{{ __("Exchange Rate") }}</span> <span class="rate-show">--</span></code>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Payment Gateway") }}<span>*</span></label>
                                    <select class="form--control nice-select gateway-select" name="gateway_currency">
                                        @foreach ($payment_gateways_currencies ?? [] as $item)
                                        <option
                                            value="{{ $item->alias  }}"
                                            data-currency="{{ $item->currency_code }}"
                                            data-min_amount="{{ $item->min_limit }}"
                                            data-max_amount="{{ $item->max_limit }}"
                                            data-percent_charge="{{ $item->percent_charge }}"
                                            data-fixed_charge="{{ $item->fixed_charge }}"
                                            data-type="{{ @$item->currency->type }}"
                                            data-rate="{{ $item->rate }}"
                                            >
                                            {{ $item->name }}
                                            @if ($item->gateway->type == payment_gateway_const()::MANUAL)
                                                {{ "(Manual)" }}
                                            @endif
                                        </option>
                                    @endforeach
                                    </select>
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Amount") }}<span>*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form--control" placeholder="{{ __('enter Amount') }}..." required name="amount" value="{{ old("amount") }}"> 
                                        <select class="form--control nice-select" name="sender_currency">
                                            @foreach ($sender_currency ?? [] as $item)
                                                <option 
                                                value="{{ $item->code}}"
                                                data-id="{{ $item->id }}"
                                                data-rate="{{ $item->rate }}"
                                                data-symbol="{{ $item->symbol }}"
                                                data-type="{{ $item->type }}"
                                                {{ old('sender_currency') == $item->code ? "selected": "" }} {{ get_default_currency_code() == $item->code ? "selected": "" }}
                                                >{{ $item->code}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <code class="d-block mt-10 text-end balance-show"></code>
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    <div class="note-area">
                                        <code class="d-block limit-show">--</code>
                                        <code class="d-block fees-show">--</code>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12">
                                <button type="submit" class="btn--base w-100">{{ __("Add Money") }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-5 col-lg-5 mb-20">
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
                                    <span class="text--success request-amount">--</span>
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
                                    <span class="text--info conversion-amount">--</span>
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
                                    <span class="text--warning fees">--</span>
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
                                    <span class="text--info last pay-in-total">--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-list-area mt-20">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __("Add Money Log") }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <a href="{{ setRoute('user.transactions.index','add-money') }}" class="btn--base">{{ __("View More") }}</a>
                    </div>
                </div>
            </div>
           @include('user.components.wallets.transation-log', compact('transactions'))
        </div>
    </div>
@endsection 
@push('script')
<script> 

   $('select[name=gateway_currency]').on('change',function(){
       getExchangeRate($(this));
       getLimit();
       getFees();
       getPreview();
   });
   $(document).ready(function(){
       getExchangeRate();
       getLimit();
       getFees();
       senderBalance(); 
   });
   $('select[name=sender_currency]').on('change',function(){   
       getExchangeRate();
       getLimit();
       getFees();
       getPreview();
       senderBalance();
   });
   $("input[name=amount]").keyup(function(){
        getFees();
        getPreview();
   });
   // get sender wallet balance by currency 
   function senderBalance(){
        var senderCurrencyId = $("select[name=sender_currency] :selected").attr("data-id"); 
        $.ajax({
        type:'get',
            url:"{{ route('user.add.money.available.balance.byCurrency') }}",
            data:{id:senderCurrencyId},
            success:function(data){
                $('.balance-show').html("{{ __('Available balance') }}: " + $("select[name=sender_currency] :selected").attr("data-symbol") + parseFloat(data).toFixed(2));
                // alert(data);
            }
        }); 
   }

   // calculate exchange rate 
   function getExchangeRate(event) {
       var element = event;
       var gatewayCode = acceptVar().gatewayCode;
       var gatewayRate = acceptVar().gatewayRate;
       var gatewayMinAmount = acceptVar().gatewayMinAmount;
       var gatewayMaxAmount = acceptVar().gatewayMaxAmount; 
       var senderRate = acceptVar().senderRate; 

       var exchangeRate = (1/senderRate)*gatewayRate; 

       $('.rate-show').html("1 " + acceptVar().senderCurrencyCode + " = " + parseFloat(exchangeRate).toFixed(acceptVar().gatewayCurrencyDigit) + " " + gatewayCode);
   }
   //minimum and maxmimum money limite
   function getLimit() {
       var gateway_currency_code = acceptVar().gatewayCode;
       var gateway_currency_rate = acceptVar().gatewayRate;
       var senderRate = acceptVar().senderRate; 
       var min_limit = acceptVar().gatewayMinAmount;
       var max_limit =acceptVar().gatewayMaxAmount;
       
       var exchangeRate = (1/senderRate)*gateway_currency_rate;

       if($.isNumeric(min_limit) || $.isNumeric(max_limit)) {
           var min_limit_calc = parseFloat(min_limit/exchangeRate).toFixed(acceptVar().senderCurrencyDigit); 
           var max_limit_clac = parseFloat(max_limit/exchangeRate).toFixed(acceptVar().senderCurrencyDigit);
           $('.limit-show').html("{{ __('limit') }}: " + min_limit_calc + " " + acceptVar().senderCurrencyCode + " - " + max_limit_clac + " " + acceptVar().senderCurrencyCode);
           return {
               minLimit:min_limit_calc,
               maxLimit:max_limit_clac,
           };
       }else {
           $('.limit-show').html("--");
           return {
               minLimit:0,
               maxLimit:0,
           };
       }
   }

   // get variables 
   function acceptVar() {
       var senderCurrencyCode = $("select[name=sender_currency] :selected").val();
       var senderRate = $("select[name=sender_currency] :selected").attr("data-rate"); 
       var senderCurrencyType = $("select[name=sender_currency] :selected").attr("data-type"); 

       var selectedVal = $("select[name=gateway_currency] :selected");
       var gatewayCode = $("select[name=gateway_currency] :selected").attr("data-currency");
       var gatewayRate = $("select[name=gateway_currency] :selected").attr("data-rate");
       var gatewayMinAmount = $("select[name=gateway_currency] :selected").attr("data-min_amount");
       var gatewayMaxAmount = $("select[name=gateway_currency] :selected").attr("data-max_amount");
       var gatewayFixedCharge = $("select[name=gateway_currency] :selected").attr("data-fixed_charge");
       var gatewayPercentCharge = $("select[name=gateway_currency] :selected").attr("data-percent_charge");
       var gatewayCurrencyType = $("select[name=gateway_currency] :selected").attr("data-type"); 

       if (senderCurrencyType == "CRYPTO") {
        var senderCurrencyDigit = 8;
       } else {
        var senderCurrencyDigit = 3;
       }
       if (gatewayCurrencyType !=null && gatewayCurrencyType == "CRYPTO") {
        var gatewayCurrencyDigit = 8;
       } else {
        var gatewayCurrencyDigit = 3;
       }

       return {
            gatewayCode:gatewayCode,
            gatewayRate:gatewayRate,
            gatewayMinAmount:gatewayMinAmount,
            gatewayMaxAmount:gatewayMaxAmount,
            gatewayFixedCharge:gatewayFixedCharge,
            gatewayPercentCharge:gatewayPercentCharge,
            gatewayCurrencyDigit:gatewayCurrencyDigit,
            selectedVal:selectedVal,

            senderCurrencyCode:senderCurrencyCode,
            senderRate:senderRate,
            senderCurrencyDigit:senderCurrencyDigit,

       };
   }

   function feesCalculation() {
       var gateway_currency_code = acceptVar().gatewayCode;
       var gateway_currency_rate = acceptVar().gatewayRate;
       var sender_amount = $("input[name=amount]").val();
       var senderRate = acceptVar().senderRate; 

       sender_amount == "" ? (sender_amount = 0) : (sender_amount = sender_amount);

       var exchangeRate = (1/senderRate)*gateway_currency_rate; 

       var fixed_charge = acceptVar().gatewayFixedCharge;
       var percent_charge = acceptVar().gatewayPercentCharge;
       if ($.isNumeric(percent_charge) && $.isNumeric(fixed_charge) && $.isNumeric(sender_amount)) {
           // Process Calculation
           var fixed_charge_calc = parseFloat(fixed_charge);
           var percent_charge_calc = (parseFloat(sender_amount) * parseFloat(exchangeRate)) * (parseFloat(percent_charge) / 100);
           var total_charge = parseFloat(fixed_charge_calc) + parseFloat(percent_charge_calc);
           total_charge = parseFloat(total_charge).toFixed(acceptVar().gatewayCurrencyDigit);
        //    console.log(total_charge);
           // return total_charge;
           return {
               total: total_charge,
               fixed: fixed_charge_calc,
               percent: percent_charge,
           };
       } else {
           // return "--";
           return false;
       }
   }

   function getFees() {
       var gateway_currency_code = acceptVar().gatewayCode;
       var percent = acceptVar().gatewayPercentCharge;
       var charges = feesCalculation();
       if (charges == false) {
           return false;
       }
       $(".fees-show").html("{{ __('Charge') }}: " + parseFloat(charges.fixed).toFixed(2) + " " + gateway_currency_code + " + " + parseFloat(charges.percent).toFixed(2) + "%");
   }
   function getPreview() {
           var senderAmount = $("input[name=amount]").val();
           var gateway_currency_code = acceptVar().gatewayCode;
           var gateway_currency_rate = acceptVar().gatewayRate;
           
            var gatewayRate = acceptVar().gatewayRate;
           var senderRate = acceptVar().senderRate; 
           var exchangeRate = (1/senderRate)*gatewayRate; 
           // var receiver_currency = acceptVar().rCurrency;
           senderAmount == "" ? senderAmount = 0 : senderAmount = senderAmount;

           // Sending Amount
           $('.request-amount').text(parseFloat(senderAmount).toFixed(acceptVar().senderCurrencyDigit) + " " + acceptVar().senderCurrencyCode);
            // conversion-amount
             var totalPay = parseFloat(senderAmount) * parseFloat(exchangeRate)
            $('.conversion-amount').text(parseFloat(totalPay).toFixed(acceptVar().gatewayCurrencyDigit) + " " + gateway_currency_code);
           // Fees
           var charges = feesCalculation();
           // console.log(total_charge + "--");
           $('.fees').text(charges.total + " " + gateway_currency_code);

           // Pay In Total
           var totalPay = parseFloat(senderAmount) * parseFloat(exchangeRate);
            var pay_in_total = parseFloat(charges.total) + parseFloat(totalPay);
           $('.pay-in-total').text(parseFloat(pay_in_total).toFixed(acceptVar().gatewayCurrencyDigit) + " " + gateway_currency_code);

       }


</script>
@endpush