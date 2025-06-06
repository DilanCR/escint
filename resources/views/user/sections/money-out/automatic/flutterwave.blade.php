@extends('user.layouts.master')

@push('css')

@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("withdraw")])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="row mt-20 mb-20-none">
        <div class="col-lg-6 mb-20"> 
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{__(@$page_title)}}</h4>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ setRoute("user.money.out.confirm.automatic") }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="gateway_name" value="{{ strtolower($gateway->name) }}">
                        <div class="row">
                            <div class="col-lg-12 form-group">
                                <label for="bank_name">{{ __("Select Bank") }} <span>*</span></label>
                                <select name="bank_name" class="form--control select2-basic" required data-placeholder="{{ __("Select Bank") }}" >
                                      <option disabled selected value="">{{ __("Select Bank") }}</option>
                                    @foreach ($allBanks ??[] as $bank)
                                        <option value="{{ $bank['code'] }}">{{ $bank['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-12 form-group">
                                <label for="account_number">{{ __("Account Number") }} <span>*</span></label>
                                <input type="text" class="form--control check_bank number-input" id="account_number"  name="account_number" value="{{ old('account_number') }}" placeholder="{{ __("Account Number") }}">
                                <label class="exist text-start"></label>
                            </div>
                            <div class="col-xl-12 col-lg-12">
                                <button type="submit" class="btn--base w-100 btn-loading withdraw " > {{ __("Confirm") }}

                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area"> 
                        <h5 class="title">{{__("Withdraw Money Information!")}}</h5>
                    </div>
                    <div class="dash-payment-body">
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
                                    <span class="request-amount">{{ number_format(@$moneyOutData->amount,2 )}} {{ get_default_currency_code() }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-exchange-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Exchange Rate") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="request-amount">{{ __("1") }} {{ get_default_currency_code() }} =  {{ number_format(@$moneyOutData->gateway_rate,2 )}} {{ @$moneyOutData->gateway_currency }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="lab la-get-pocket"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Conversion Amount") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="conversion">{{ number_format(@$moneyOutData->conversion_amount,2 )}} {{ @$moneyOutData->gateway_currency }}</span>
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
                                    <span class="fees">{{ number_format(@$moneyOutData->gateway_charge,2 )}} {{ @$moneyOutData->gateway_currency }}</span>
                                </div>
                            </div>

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span class="">{{ __("Will Get") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--success ">{{ number_format(@$moneyOutData->will_get,2 )}} {{ @$moneyOutData->gateway_currency }}</span>
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
                                    <span class="text--warning last">{{ number_format(@$moneyOutData->payable,2 )}} {{ get_default_currency_code() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection 
