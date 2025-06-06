
@extends('user.layouts.master')

@push('css')
    
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Money Exchange")])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="row mt-20 mb-20-none">
        <div class="col-xl-7 col-lg-7 mb-20">
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{ __("Money Exchange") }}</h4>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ setRoute('user.money.exchange.submit') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 form-group text-center">
                                <div class="exchange-area">
                                    <code class="d-block text-center"><span>{{ __("Exchange Rate") }}</span> <span class="exchangeRateShow"></span></code>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                <label>{{ __("Exchange From") }}<span>*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form--control" name="exchange_from_amount" value="{{ old('exchange_from_amount')}}" placeholder="{{ __("enter Amount") }}">
                                    <select class="form--control nice-select exchangeFromCurrency" name="exchange_from_currency">
                                        @foreach ($user_wallets as $item)
                                        <option 
                                        value="{{ $item->currency->code }}"
                                        data-id="{{ $item->currency->id }}"
                                        data-rate="{{ $item->currency->rate }}"
                                        data-code="{{ $item->currency->code }}"
                                        data-type="{{ $item->currency->type }}"
                                        data-symbol="{{ $item->currency->symbol }}"
                                        data-balance="{{ $item->balance }}"
                                        {{ get_default_currency_code() == $item->currency->code ? "selected": "" }}
                                            >{{ $item->currency->code }}</option>
                                        @endforeach 
                                    </select>
                                </div>  
                                <code class="d-block mt-10 text-end fromWalletBalanceShow"></code>
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                <label>{{ __("Exchange To") }}<span>*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form--control" name="exchange_to_amount" placeholder="{{ __("enter Amount") }}" readonly>
                                    <select class="form--control nice-select exchangeToCurrency" name="exchange_to_currency">
                                        @foreach ($user_wallets as $item)
                                        <option 
                                        value="{{ $item->currency->code }}"
                                        data-id="{{ $item->currency->id }}"
                                        data-rate="{{ $item->currency->rate }}"
                                        data-code="{{ $item->currency->code }}"
                                        data-type="{{ $item->currency->type }}"
                                        {{ get_default_currency_code() == $item->currency->code ? "selected": "" }}
                                            >{{ $item->currency->code }}</option>
                                        @endforeach 
                                    </select>
                                </div> 
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                <div class="note-area">
                                    <code class="d-block limit-show"></code>
                                    <code class="d-block fees-show"></code>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12">
                            <button type="submit" class="btn--base w-100">{{ __("Exchange Money") }}</button>
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
                                        <span>{{ __("From Wallet") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-list-right">
                                <span class="text--success fromWallet">--</span>
                            </div>
                        </div>
                        <div class="preview-list-item">
                            <div class="preview-list-left">
                                <div class="preview-list-user-wrapper">
                                    <div class="preview-list-user-icon">
                                        <i class="las la-receipt"></i>
                                    </div>
                                    <div class="preview-list-user-content">
                                        <span>{{ __("To Exchange") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-list-right">
                                <span class="toExchange">--</span>
                            </div>
                        </div>
                        <div class="preview-list-item">
                            <div class="preview-list-left">
                                <div class="preview-list-user-wrapper">
                                    <div class="preview-list-user-icon">
                                        <i class="las la-receipt"></i>
                                    </div>
                                    <div class="preview-list-user-content">
                                        <span>{{ __("Exchange Rate") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-list-right">
                                <span class="rateShow">--</span>
                            </div>
                        </div>
                        <div class="preview-list-item">
                            <div class="preview-list-left">
                                <div class="preview-list-user-wrapper">
                                    <div class="preview-list-user-icon">
                                        <i class="las la-receipt"></i>
                                    </div>
                                    <div class="preview-list-user-content">
                                        <span>{{ __("Total Exchange Amount") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-list-right">
                                <span class="text--danger requestAmount">--</span>
                            </div>
                        </div>
                        <div class="preview-list-item">
                            <div class="preview-list-left">
                                <div class="preview-list-user-wrapper">
                                    <div class="preview-list-user-icon">
                                        <i class="las la-receipt"></i>
                                    </div>
                                    <div class="preview-list-user-content">
                                        <span>{{ __("Converted Amount") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-list-right">
                                <span class="receiveAmount">--</span>
                            </div>
                        </div>
                        <div class="preview-list-item">
                            <div class="preview-list-left">
                                <div class="preview-list-user-wrapper">
                                    <div class="preview-list-user-icon">
                                        <i class="las la-receipt"></i>
                                    </div>
                                    <div class="preview-list-user-content">
                                        <span>{{ __("Total Charge") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-list-right">
                                <span class="fees">--</span>
                            </div>
                        </div>
                        <div class="preview-list-item">
                            <div class="preview-list-left">
                                <div class="preview-list-user-wrapper">
                                    <div class="preview-list-user-icon">
                                        <i class="las la-receipt"></i>
                                    </div>
                                    <div class="preview-list-user-content">
                                        <span>{{ __("Total Payable") }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-list-right">
                                <span class="payInTotal">--</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="dashboard-list-area mt-20">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ __("Money Exchange Log") }}</h4>
            <div class="dashboard-btn-wrapper">
                <div class="dashboard-btn">
                    <a href="{{ setRoute('user.transactions.index','money-exchange') }}" class="btn--base">{{ __("View More") }}</a>
                </div>
            </div>
        </div>
        @include('user.components.wallets.transation-log', compact('transactions'))
    </div>
</div>
@endsection

@push('script')
    <script>
        $(document).ready(function(){
            callFunctions() 
            $('.fromWalletBalanceShow').html("{{ __('Available balance') }}: " + $("select[name=exchange_from_currency] :selected").attr("data-symbol") + $("select[name=exchange_from_currency] :selected").attr("data-balance"));
        })
        $('.exchangeFromCurrency').on('change', function(){
            callFunctions()
            $('.fromWalletBalanceShow').html("{{ __('Available balance') }}: " + $("select[name=exchange_from_currency] :selected").attr("data-symbol") + $("select[name=exchange_from_currency] :selected").attr("data-balance"));
        })
        $('.exchangeToCurrency').on('change', function(){
            callFunctions()
        })
        $('input[name=exchange_from_amount]').keyup(function(){ 
            callFunctions()
        })
        function callFunctions() {
            getExchangeRate(); 
            previewDetails();
            getFees();
            getLimit();
        }

        var fixedCharge     = "{{ $charges->fixed_charge ?? 0 }}";
        var percentCharge   = "{{ $charges->percent_charge ?? 0 }}";
        var minLimit        = "{{ $charges->min_limit ?? 0 }}";
        var maxLimit        = "{{ $charges->max_limit ?? 0 }}";
        
        function acceptVar() {
            var exchangeFromAmount = $("input[name=exchange_from_amount]").val();  
            var exchangeFromRate = $("select[name=exchange_from_currency] :selected").attr("data-rate");  
            var exchangeFromCode = $("select[name=exchange_from_currency] :selected").attr("data-code");  
            var exchangeFromType = $("select[name=exchange_from_currency] :selected").attr("data-type"); 

            var exchangeToRate = $("select[name=exchange_to_currency] :selected").attr("data-rate");  
            var exchangeToCode = $("select[name=exchange_to_currency] :selected").attr("data-code");  
            var exchangeToType = $("select[name=exchange_to_currency] :selected").attr("data-type"); 
            if (exchangeFromType == "CRYPTO") {
                var exchangeFromDigit = 8;
            } else {
                var exchangeFromDigit = 2;
            }
            if (exchangeToType == "CRYPTO") {
                var exchangeToDigit = 8;
            } else {
                var exchangeToDigit = 2;
            }

            return {
                exchangeFromAmount: exchangeFromAmount,
                exchangeFromRate: exchangeFromRate,
                exchangeFromCode: exchangeFromCode,
                exchangeFromDigit: exchangeFromDigit,

                exchangeToRate: exchangeToRate,
                exchangeToCode: exchangeToCode,
                exchangeToDigit: exchangeToDigit,
            };
        }
        //calculate exchange rate
        function getExchangeRate(){
            var exchangeRate = parseFloat(acceptVar().exchangeToRate) / parseFloat(acceptVar().exchangeFromRate); 
            $('.exchangeRateShow').html("1 " + acceptVar().exchangeFromCode +" = " + exchangeRate.toFixed(acceptVar().exchangeToDigit) + " " + acceptVar().exchangeToCode);
            var exchangeToConverMmount = acceptVar().exchangeFromAmount*exchangeRate;
            $("input[name=exchange_to_amount]").val(exchangeToConverMmount.toFixed(acceptVar().exchangeToDigit));
        }
        function getLimit(){
            var exchangeFromCode =  acceptVar().exchangeFromCode; 
            var exchangeRate = (1/parseFloat(acceptVar().exchangeToRate)) * parseFloat(acceptVar().exchangeFromRate);
            var min_limit = minLimit;
            var max_limit = maxLimit;
            
            var min_limit_calc = parseFloat(min_limit*exchangeRate);
            var max_limit_clac = parseFloat(max_limit*exchangeRate);
            $('.limit-show').html("{{ __('limit') }}: " + min_limit_calc.toFixed(acceptVar().exchangeFromDigit) + " " + exchangeFromCode + " - " + max_limit_clac.toFixed(acceptVar().exchangeFromDigit) + " " + exchangeFromCode);

        }
        //calculate fees 
        function feesCalculation(){
            var exchangeFromAmount =  acceptVar().exchangeFromAmount;
            var exchangeFromRate =  acceptVar().exchangeFromRate;
            var exchangeFromCode =  acceptVar().exchangeFromCode; 

            var fixedChargeCalculation = parseFloat(exchangeFromRate)*fixedCharge;
            var percentChargeCalculation = parseFloat(percentCharge/100)*parseFloat(exchangeFromAmount*1);
            var totalCharge = fixedChargeCalculation+percentChargeCalculation;

            return {
                fixed_charge: fixedChargeCalculation,
                percent_charge: percentChargeCalculation,
                total_charge: totalCharge,
            };

        }
        function getFees() {
            var exchangeFromCode =  acceptVar().exchangeFromCode;
            var charges = feesCalculation();
            $('.fees-show').html("{{ __('Charge') }}: " + parseFloat(charges.fixed_charge).toFixed(acceptVar().exchangeFromDigit) + " " + exchangeFromCode +" + " + parseFloat(percentCharge) + "%" + " = "+ parseFloat(charges.total_charge).toFixed(acceptVar().exchangeFromDigit) + " " + exchangeFromCode);
        }
        //preview details
        function previewDetails(){
            var exchangeFromAmount =  acceptVar().exchangeFromAmount;
            var exchangeFromRate =  acceptVar().exchangeFromRate;
            var exchangeFromCode =  acceptVar().exchangeFromCode;

            var exchangeToRate =  acceptVar().exchangeToRate;
            var exchangeToCode =  acceptVar().exchangeToCode;
            //exchange rate 
            var exchangeRate = parseFloat(exchangeToRate) / parseFloat(exchangeFromRate);

            $('.fromWallet').html(exchangeFromCode);
            $('.toExchange').html(exchangeToCode);
            $('.rateShow').html("1 " + exchangeFromCode +" = " + exchangeRate.toFixed(acceptVar().exchangeToDigit) + " " + exchangeToCode)
            $('.requestAmount').html(exchangeFromAmount*1 + " " +exchangeFromCode);
            //converted amount
            var convertedAmount = exchangeFromAmount*exchangeRate;
            $('.receiveAmount').html(parseFloat(convertedAmount).toFixed(acceptVar().exchangeToDigit) + " " +exchangeToCode);
            //show total fees
            var charges = feesCalculation();
            $('.fees').html(charges.total_charge.toFixed(acceptVar().exchangeFromDigit) + " " + exchangeFromCode);
            // Pay In Total
            var pay_in_total = parseFloat(charges.total_charge) + parseFloat(exchangeFromAmount*1);
            $('.payInTotal').text(parseFloat(pay_in_total).toFixed(acceptVar().exchangeFromDigit) + " " + exchangeFromCode);
        }

    </script>
@endpush