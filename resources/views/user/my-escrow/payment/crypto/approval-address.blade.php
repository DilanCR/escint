@extends('user.layouts.master')

@push('css')

@endpush

@section('content')

<div class="body-wrapper">
    <div class="row mb-30-none">
        <div class="col-xl-6 col-lg-6 mb-20">
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{ __($page_title) }}</h4>
                </div>
                <div class="card-body">
                    @if ($escrowData->status == escrow_const()::PAYMENT_WATTING)
                        <form class="row g-4 submit-form" method="POST" action="{{ setRoute('user.escrow-action.payment.crypto.confirm',$escrowData->escrow_id) }}">
                            @csrf
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" value="{{ $escrowData->details->payment_info->receiver_address ?? "" }}" class="form-control form--control ref-input copiable" readonly>
                                    <div class="input-group-append" style="cursor: pointer">
                                        <span class="input-group-text copytext copy-button">
                                            <i class="la la-copy"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mx-auto mt-4 text-center">
                                <img class="mx-auto" src="{{ $escrowData->details->payment_info->receiver_qr_image ?? "" }}" alt="Qr Code">
                            </div>

                            {{-- Print Dynamic Input Filed if Have START --}}
                            @foreach ($escrowData->details->payment_info->requirements ?? [] as $input)
                                <div class="form-group col-12">
                                    <label for="">{{ $input->label }} </label>
                                    <input type="text" name="{{ $input->name }}" placeholder="{{ $input->placeholder ?? "" }}" class="form-control" @if ($input->required)
                                        @required(true)
                                    @endif>
                                </div>
                            @endforeach
                            {{-- Print Dynamic Input Filed if Have END --}}

                            <div class="col-12 mt-5">
                                <button type="submit" class="btn--base w-100 text-center">{{ __("Proceed") }}</button>
                            </div>
                        </form>
                    @else
                        <div class="payment-received-alert">
                            <div class="text-center text--success">
                                {{ __("Payment Received Successfully!") }}
                            </div>

                            <div class="txn-hash text-center mt-2 text--info">
                                <strong>{{ __("Txn Hash:") }} </strong>
                                <span>{{ $escrowData->details->payment_info->txn_hash ?? "" }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-6 mb-20">
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{__("Add Money Preview")}}</h4>
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
                                <span class="text--warning">{{ get_amount($escrowData->escrowDetails->fee,$escrowData->escrow_currency) }}</span>
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
                                <span>{{ @$escrowData->paymentGatewayCurrency->name }}</span>
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
                                <span>1 {{ $escrowData->escrow_currency }} = {{ get_amount($escrowData->escrowDetails->gateway_exchange_rate,$escrowData->paymentGatewayCurrency->currency_code) }}</span>
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
                                <span class="text--info last">{{ get_amount($escrowData->escrowDetails->buyer_pay,$escrowData->paymentGatewayCurrency->currency_code)}}</span>
                            </div>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
    <script>
        $(".copy-button").click(function(){
            var value = $('.copiable').val()
            navigator.clipboard.writeText(value);
            throwMessage('success',['Text successfully copied.']);
        });
    </script>
@endpush
