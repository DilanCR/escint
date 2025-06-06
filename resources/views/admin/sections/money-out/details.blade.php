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
        'active' => __('Money Out Details'),
    ])
@endsection
@section('content')
<div class="custom-card">
    <div class="card-header">
        <h6 class="title">{{ __("Money Out Details") }}</h6>
    </div>
    <div class="card-body">
        <form class="card-form">
            <div class="row align-items-center mb-10-none">
                <div class="col-xl-4 col-lg-4 form-group">
                    <ul class="user-profile-list-two">
                        <li class="one">{{ __("Date")}}: <span>{{ @$data->created_at->format('d-m-y h:i:s A') }}</span></li>
                        <li class="two">{{ __("TRX ID")}}: <span>{{ @$data->trx_id }}</span></li>
                        <li class="three">{{ __("Full Name")}}: <span>
                            @if($data->user_id != null)
                                    <a href="{{ setRoute('admin.users.details',$data->user->username) }}">{{ $data->user->fullname }} ({{ __("USER") }})</a>  
                            @endif
                            </span></li>
                        <li class="four">{{ __("Method")}}: <span>{{ @$data->gateway_currency->name }}</span></li>
                        <li class="five">{{ __("Amount")}}: <span>{{ get_amount(@$data->sender_request_amount,$data->sender_currency_code) }}</span></li>
                    </ul>
                </div>

                <div class="col-xl-4 col-lg-4 form-group">
                    <div class="user-profile-thumb">
                        <img src="{{ get_gateway_image($data->gateway_currency->payment_gateway_id) }}" alt="payment">
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 form-group">
                    <ul class="user-profile-list two">
                        <li class="one">{{ __("Fees & Charge")}}: <span>{{ number_format(@$data->transaction_details->total_charge,2) }} {{ @$data->gateway_currency->currency_code }}</span></li>
                        <li class="two">{{ __("Conversion Amount")}}: <span>{{ number_format(@$data->sender_request_amount*$data->exchange_rate,2) }} {{ @$data->gateway_currency->currency_code }}</span></li>
                        <li class="three">{{ __("Rate")}}: <span>1 {{ $data->sender_currency_code }} = {{ number_format(@$data->exchange_rate,2) }} {{ @$data->gateway_currency->currency_code }}</span></li>
                        <li class="four">{{ __("Will Get")}}: <span>{{ get_amount(@$data->total_payable,$data->gateway_currency->currency_code) }}</span></li>
                        <li class="four">{{ __("Total Payable")}}: <span>{{ get_amount(@$data->sender_request_amount,$data->sender_currency_code) }}</span></li>
                        <li class="five">{{ __("Status")}}:  <span class="{{ @$data->stringStatus->class }}">{{ @$data->stringStatus->value }}</span></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

@if ($data->gateway_currency->gateway->type == App\Constants\PaymentGatewayConst::MANUAL) 
<div class="custom-card mt-15">
    <div class="card-header">
        <h6 class="title">{{ __("Information of Logs")}}</h6>
    </div>
    <div class="card-body">
        <ul class="product-sales-info">

            @foreach ($data->details ?? [] as $item)
            <li>
                @if ($item->type == "file")
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
                    <span class="kyc-title">{{ $item->label }}:</span>
                    <span>{{ $item->value }}</span>
                @endif
            </li>
        @endforeach
        </ul>
    </div>
</div> 
@endif 
@if(@$data->status == 2)
<div class="product-sales-btn">
    <button type="button" class="btn btn--base approvedBtn">{{ __("Approve")}}</button>
    <button type="button" class="btn btn--danger rejectBtn" >{{ __("Reject")}}</button>
</div>
@endif
@if(@$data->status == 2)
<div class="modal fade" id="approvedModal" tabindex="-1" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header p-3" id="approvedModalLabel">
                <h5 class="modal-title">{{ __("Approved Confirmation")}} ( <span class="fw-bold text-danger">{{ get_amount(@$data->total_payable,$data->gateway_currency->currency_code) }}</span> )</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="modal-form" action="{{ setRoute('admin.money.out.approved') }}" method="POST">

                    @csrf
                    @method("PUT")
                    <div class="row mb-10-none">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <input type="hidden" name="id" value={{ @$data->id }}>
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
                <h5 class="modal-title">{{ __("Rejection Confirmation")}} ( <span class="fw-bold text-danger">{{ get_amount(@$data->sender_request_amount,$data->sender_currency_code) }}</span> )</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="modal-form" action="{{ setRoute('admin.money.out.rejected') }}" method="POST">
                    @csrf
                    @method("PUT")
                    <div class="row mb-10-none">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <input type="hidden" name="id" value={{ @$data->id }}>
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
