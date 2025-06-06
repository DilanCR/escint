@extends('admin.layouts.master')

@push('css')
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __('Add Money Details'),
    ])
@endsection

@section('content')

<div class="custom-card">
    <div class="card-header">
        <h6 class="title">{{ __("Escrow Details") }}</h6>
    </div>
    <div class="card-body">
        <form class="card-form">
            <div class="row align-items-center mb-10-none">
                <div class="col-xl-4 col-lg-4 form-group">
                    <ul class="user-profile-list-two">
                        <li class="one">{{ __("Date")}}: <span>{{ @$escrows->created_at->format('d-m-y h:i:s A') }}</span></li>
                        <li class="two">{{ __("Escrow ID")}}: <span>{{ @$escrows->escrow_id }}</span></li> 
                        <li class="three">{{ __("Created By")}}: <span>{{ @$escrows->user->username }}</span></li> 
                        <li class="three">{{ __("category")}}: <span>{{ @$escrows->escrowCategory->name }}</span></li> 
                        <li class="four">{{ __("Total Price")}}: <span>{{ @get_amount($escrows->amount,$escrows->escrow_currency) }}</span></li> 
                        <li class="five">{{ __("Charge Payer")}}: <span>{{ @$escrows->string_who_will_pay->value }}</span></li> 
                    </ul>
                </div>

                <div class="col-xl-4 col-lg-4 form-group">
                    <div class="user-profile-thumb">
                        <img src="" alt="payment">
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 form-group">
                    <ul class="user-profile-list two"> 
                        <li class="one">{{ __("Fees & Charges")}}: <span>{{ get_amount($escrows->escrowDetails->fee,$escrows->escrow_currency) }}</span></li> 
                        <li class="two">{{ __("Seller Amount")}}: <span>{{ get_amount($escrows->escrowDetails->seller_get,$escrows->escrow_currency) }}</span></li> 
                        @if ($escrows->payment_type == escrow_const()::GATEWAY)
                        <li class="three">{{ __("Pay with")}}: <span>{{ $escrows->paymentGatewayCurrency->name }}</span></li> 
                        <li class="three">{{ __("Exchange Rate")}}: <span>{{ "1 ".$escrows->escrow_currency." = ".get_amount($escrows->escrowDetails->gateway_exchange_rate,$escrows->paymentGatewayCurrency->currency_code) }}</span></li> 
                        <li class="four">{{ __("Buyer Paid")}}: <span>{{ get_amount($escrows->escrowDetails->buyer_pay,$escrows->paymentGatewayCurrency->currency_code) }}</span></li> 
                        @endif
                        @if ($escrows->payment_type == escrow_const()::MY_WALLET)
                        <li class="three">{{ __("Pay with")}}: <span>{{ "Wallet" }}</span></li> 
                        <li class="three">{{ __("Exchange Rate")}}: <span>{{ "1 ".$escrows->escrow_currency." = 1 ".$escrows->escrow_currency }}</span></li> 
                        <li class="four">{{ __("Buyer Paid")}}: <span>{{ get_amount($escrows->escrowDetails->buyer_pay,$escrows->escrow_currency) }}</span></li>
                        @endif
                       
                        <li class="five">{{ __("Status")}}:  <span class="{{ @$escrows->stringStatus->class }}">{{ @$escrows->stringStatus->value }}</span></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div> 
<div class="custom-card mt-15">
    <div class="card-header">
        <h6 class="title">{{ __("Information of Logs")}}</h6>
    </div>
    <div class="card-body">
        <h5><strong>{{ __("Title")}}:</strong> {{ $escrows->title }}</h5>
        @foreach ($escrows->file ?? [] as $key => $item)
        <p><strong>{{ __("Attachment")}}</strong>: 
            <span class="text--danger text-right">
                <a href="{{ files_asset_path('escrow-temp-file') . "/" . $item->attachment }}" target="_blanck">
                    {{ Str::words(json_decode($item->attachment_info)->original_base_name ?? "", 5, '...' . json_decode($item->attachment_info)->extension ?? "" ) }}
                </a>
            </span>
        </p>
        @endforeach
        <p><strong>{{ __("Details")}}:</strong> {{ $escrows->remark }}</p>
    </div>
    @if ($escrows->payment_gateway_currency_id !== null && $escrows->paymentGatewayCurrency->gateway->type == App\Constants\PaymentGatewayConst::MANUAL) 
    <div class="card-body">
        <ul class="product-sales-info">

            @foreach (json_decode($escrows->details) ?? [] as $item)
            <li>
                @if (@$item->type == "file")
                    @php
                        $file_link = get_file_link("kyc-files",$item->value);
                    @endphp
                    <span class="kyc-title">{{ $item->label }}:</span>
                    @if (its_image($item->value))
                        <div class="kyc-image ">
                            <img class="img-fluid" width="200px" src="{{ $file_link }}" alt="{{ $item->label }}">
                        </div>
                    @else
                        <span class="text--danger">
                            @php
                                $file_info = get_file_basename_ext_from_link($file_link);
                            @endphp
                            <a href="{{ setRoute('file.download',["kyc-files",$item->value]) }}" >
                                {{ Str::substr($file_info->base_name ?? "", 0 , 20 ) ."..." . $file_info->extension ?? "" }}
                            </a>
                        </span>
                    @endif
                @else
                    <span class="kyc-title">{{ @$item->label }}:</span>
                    <span>{{ @$item->value }}</span>
                @endif
            </li>
        @endforeach
        </ul>
    </div>
    @endif
</div> 
@if(@$escrows->status == 2)
<div class="product-sales-btn">
    <button type="button" class="btn btn--base approvedBtn">{{ __("Approve")}}</button>
    <button type="button" class="btn btn--danger rejectBtn" >{{ __("Reject")}}</button>
</div>
@endif
@if(@$escrows->status == 2)
<div class="modal fade" id="approvedModal" tabindex="-1" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header p-3" id="approvedModalLabel">
                <h5 class="modal-title">{{ __("Approved Confirmation")}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="modal-form" action="{{ setRoute('admin.escrow.manual.payment.approved') }}" method="POST"> 
                    @csrf
                    @method("PUT")
                    <div class="row mb-10-none">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <input type="hidden" name="id" value={{ @$escrows->id }}>
                           <p>{{ __("Are you sure to approved this request?")}}</p>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--danger" data-bs-dismiss="modal">{{ __("Cancel")}}</button>
                <button type="submit" class="btn btn--base btn-loading">{{ __("Approved")}}</button>
            </div>
        </form>
        </div>
    </div>
</div>
<div class="modal fade" id="rejectModal" tabindex="-1" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header p-3" id="rejectModalLabel">
                <h5 class="modal-title">{{ __("Rejection Confirmation")}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="modal-form" action="{{ setRoute('admin.escrow.manual.payment.rejected') }}" method="POST">
                    @csrf
                    @method("PUT")
                    <div class="row mb-10-none">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <input type="hidden" name="id" value={{ @$escrows->id }}>
                            @include('admin.components.form.textarea',[
                                'label'         => 'Explain Rejection Reason*',
                                'name'          => 'reject_reason',
                                'value'         => old('reject_reason')
                            ])
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--danger" data-bs-dismiss="modal">{{ __("Cancel")}}</button>
                <button type="submit" class="btn btn--base">{{ __("Confirm")}}</button>
            </div>
        </form>
        </div>
    </div>
</div>
@endif

@endsection
@push('script')
<script>
    $(document).ready(function(){
        @if($errors->any())
        var modal = $('#rejectModal');
        modal.modal('show');
        @endif
    });
</script>
<script>
     (function ($) {
        "use strict";
        $('.approvedBtn').on('click', function () {
            var modal = $('#approvedModal');
            modal.modal('show');
        });
        $('.rejectBtn').on('click', function () {
            var modal = $('#rejectModal');
            modal.modal('show');
        });
    })(jQuery);

</script>
@endpush


