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
    <form action="{{ setRoute('user.my-escrow.confirm') }}" class="preview-form" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" value="{{ $identifier }}" name="identifier">
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
                                    <span>{{ $oldData->title }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-user-tag"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("My Role") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text-capitalize role">{{ $oldData->role }}</span>
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
                                    <span>{{ $oldData->product_type }}</span>
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
                                    <span class="text--success">{{ $oldData->amount }} {{ $oldData->escrow_currency }}</span>
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
                                        @if ($oldData->charge_payer == "half")
                                            {{ "50%-50%" }}
                                        @else 
                                            {{ $oldData->charge_payer }}
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
                                    <span class="text--warning">{{ get_amount($oldData->escrow_total_charge,$oldData->escrow_currency) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-wallet"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span class="sellerGet">{{ __('Seller Will Get') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ get_amount($oldData->seller_amount,$oldData->escrow_currency) }}</span>
                                </div>
                            </div>
                            @if ($oldData->role == "buyer")
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
                                    <span>{{ $oldData->payment_method }}</span>
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
                                    <span>1 {{ $oldData->escrow_currency }} = {{ get_amount($oldData->gateway_exchange_rate,$oldData->gateway_currency) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span class="last buyerPay">{{ __('Buyer Will Pay') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--info last">{{ get_amount($oldData->buyer_amount,$oldData->gateway_currency)}}</span>
                                </div>
                            </div> 
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn--base mt-20 w-100">{{ $oldData->role == "buyer" ?  __("Confirm & Pay"):  __("Confirm & Send") }}</button>
    </form>
</div>
@endsection
@push('script')
    <script>
        $(document).ready(function(){
            var role = $('.role').text();
            if (role == "seller") {
                $('.sellerGet').text({{ __('I Will Get') }});
            } 
            if (role == "buyer") {
                $('.buyerPay').text({{ __('You Will Pay') }});
            } 
        });
    </script>
@endpush