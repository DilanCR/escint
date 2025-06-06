<div class="dashboard-list-wrapper">
   @forelse ($transactions as $item)
   <div class="dashboard-list-item-wrapper">
    <div class="dashboard-list-item {{ $item->status == 1 ? "receive": "sent" }} ">
        <div class="dashboard-list-left">
            <div class="dashboard-list-user-wrapper">
                <div class="dashboard-list-user-icon">
                    <i class="las la-arrow-up"></i>
                </div>
                <div class="dashboard-list-user-content">
                    @if ($item->type == payment_gateway_const()::TYPEADDMONEY)
                    <h4 class="title">{{ __("Add Balance via") }} <span class="text--warning">{{ $item->gateway_currency->name }}</span></h4>
                    @elseif ($item->type == payment_gateway_const()::TYPEMONEYOUT)
                        <h4 class="title">{{ __("Money Out via") }} <span class="text--warning">{{ $item->gateway_currency->name }}</span></h4>
                    @elseif ($item->type == payment_gateway_const()::TYPEMONEYEXCHANGE)
                    <h4 class="title">{{ __("Exchange Money") }} <span class="text--warning">{{ $item->sender_currency_code }} To {{ $item->details->charges->exchange_currency }}</span></h4>
                    @elseif ($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE)
                    <h4 class="text--warning">{{ __("Balance Update From Admin (".$item->sender_currency_code.")") }} </h4>
                    @endif
                    <span class="{{ $item->stringStatus->class }}">{{ $item->stringStatus->value }} </span>
                </div>
            </div>
        </div>
        <div class="dashboard-list-right">
        @if ($item->type == payment_gateway_const()::TYPEADDMONEY)
            <h4 class="main-money text--warning">{{ get_amount($item->sender_request_amount,$item->sender_currency_code) }}</h4>
            <h6 class="exchange-money fw-bold">{{ get_amount($item->total_payable,$item->gateway_currency->currency_code) }}</h6>
            @elseif($item->type == payment_gateway_const()::TYPEMONEYOUT)
            <h4 class="main-money text--warning"> {{ get_amount($item->total_payable,$item->gateway_currency->currency_code) }}</h4>
            <h6 class="exchange-money fw-bold">{{ get_amount($item->sender_request_amount,$item->sender_currency_code) }}</h6>
            @elseif($item->type == payment_gateway_const()::TYPEMONEYEXCHANGE)
            <h4 class="main-money text--warning"> {{ get_amount($item->total_payable,$item->details->charges->exchange_currency) }}</h4>
            <h6 class="exchange-money fw-bold">{{ get_amount($item->sender_request_amount,$item->sender_currency_code) }}</h6>   
            @elseif ($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE)
            <h4 class="main-money text--warning">{{ get_amount($item->sender_request_amount,$item->sender_currency_code) }}</h4>
            <h6 class="exchange-money fw-bold">{{ get_amount($item->available_balance,$item->sender_currency_code) }}</h6>
     
        @endif
        </div>
    </div>
    <div class="preview-list-wrapper">
        <div class="preview-list-item">
            <div class="preview-list-left">
                <div class="preview-list-user-wrapper">
                    <div class="preview-list-user-icon">
                        <i class="lab la-tumblr"></i>
                    </div>
                    <div class="preview-list-user-content">
                        <span>{{ __('Transaction ID') }}</span>
                    </div>
                </div>
            </div>
            <div class="preview-list-right">
                <span>{{ $item->trx_id }}</span>
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
                @if ($item->type == payment_gateway_const()::TYPEADDMONEY || $item->type == payment_gateway_const()::TYPEMONEYOUT)
                <span>1 {{ $item->sender_currency_code }} = {{ get_amount($item->exchange_rate,$item->gateway_currency->currency_code,3) }}</span> 
                @elseif ($item->type == payment_gateway_const()::TYPEMONEYEXCHANGE)
                <span>1 {{ $item->sender_currency_code }} = {{ get_amount($item->exchange_rate,$item->details->charges->exchange_currency,3) }}</span> 
                @elseif ($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE)
                <span>1 {{ $item->sender_currency_code }} = 1 {{ $item->sender_currency_code }}</span>

                @endif
                
            </div>
        </div> 
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
                @if ($item->type == payment_gateway_const()::TYPEADDMONEY || $item->type == payment_gateway_const()::TYPEMONEYOUT)
                <span>{{ @get_amount($item->transaction_details->total_charge,$item->gateway_currency->currency_code) }}</span>
                @elseif ($item->type == payment_gateway_const()::TYPEMONEYEXCHANGE)
                <span>{{ get_amount($item->details->charges->total_charge,$item->sender_currency_code) }}</span> 
                @elseif ($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE)
                <span>0 {{ $item->sender_currency_code }}</span>
                @endif
            </div>
        </div> 
        @if ($item->reject_reason != null)
        <div class="preview-list-item">
            <div class="preview-list-left">
                <div class="preview-list-user-wrapper"> 
                    <div class="preview-list-user-icon">
                        <i class="las la-smoking"></i>
                    </div>
                    <div class="preview-list-user-content">
                        <span>{{ __('Reject Reason') }}</span>
                    </div>
                </div>
            </div> 
            <div class="preview-list-right">
                <span>{{ $item->reject_reason }}</span>
            </div>
        </div> 
        @endif
        <div class="preview-list-item">
            <div class="preview-list-left">
                <div class="preview-list-user-wrapper"> 
                    <div class="preview-list-user-icon">
                        <i class="las la-clock"></i>
                    </div>
                    <div class="preview-list-user-content">
                        <span>{{ __('Date') }}</span>
                    </div>
                </div>
            </div> 
            <div class="preview-list-right">
                <span>{{ $item->created_at }}</span>
            </div>
        </div> 
        @if ($item->type == payment_gateway_const()::TYPEADDMONEY)
        @if ($item->gateway_currency->gateway->isTatum($item->gateway_currency->gateway) && $item->status == payment_gateway_const()::STATUSWAITING)
            <div class="preview-list-item d-block">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-times-circle"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{ __("Txn Hash") }}</span>
                        </div>
                    </div>
                    <form action="{{ setRoute('user.add.money.payment.crypto.confirm', $item->trx_id) }}" method="POST">
                        @csrf
                        @php
                            $input_fields = $item->details->payment_info->requirements ?? [];
                        @endphp

                        @foreach ($input_fields as $input)
                            <div class="">
                                <input type="text" class="form-control" name="{{ $input->name }}" placeholder="{{ $input->placeholder ?? "" }}" required>
                            </div>
                        @endforeach

                        <div class="text-end">
                            <button type="submit" class="btn--base my-2">{{ __("Process") }}</button>
                        </div>

                    </form>
                </div>
            </div>
        @endif
    @endif
    </div>
</div>
   @empty
   <div class="alert alert-primary" style="margin-top: 37.5px; text-align:center">{{ __('No data found!') }}</div>
   @endforelse
   {{ get_paginate($transactions) }}
</div>