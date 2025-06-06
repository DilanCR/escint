@extends('user.layouts.master')

@push('css')

@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Manual Payment")])
@endsection

@section('content') 
<div class="body-wrapper">
    <div class="row mt-20 mb-20-none">
        <div class="col-xl-7 col-lg-7 mb-20">
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{__(@$page_title)}}</h4>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ setRoute("user.add.money.manual.payment.confirmed") }}" method="POST" enctype="multipart/form-data">
                        <h5 class="title">
                            @php
                                echo @$gateway->desc;
                            @endphp
                        </h5>
                        @csrf
                        <div class="row">
                            @foreach ($gateway->input_fields as $item)
                                @if ($item->type == "select")
                                    <div class="col-lg-12 form-group">
                                        <label for="{{ $item->name }}">{{ $item->label }}
                                            @if($item->required == true)
                                            <span class="text-white">*</span>
                                            @else
                                            <span class="">( {{ __("Optional") }} )</span>
                                            @endif
                                        </label>
                                        <select name="{{ $item->name }}" id="{{ $item->name }}" class="form--control nice-select">
                                            <option selected disabled>Choose One</option>
                                            @foreach ($item->validation->options as $innerItem)
                                                <option value="{{ $innerItem }}">{{ $innerItem }}</option>
                                            @endforeach
                                        </select> 
                                    </div>
                                @elseif ($item->type == "file")
                                    <div class="col-lg-12 form-group">
                                        <label for="{{ $item->name }}">{{ $item->label }}
                                            @if($item->required == true)
                                            <span class="text-white">*</span>
                                            @else
                                            <span class="">( {{ __("Optional") }} )</span>
                                            @endif
                                        </label>
                                        <input type="{{ $item->type }}" class="form--control" name="{{ $item->name }}" value="{{ old($item->name) }}">
                                    </div>
                                @elseif ($item->type == "text")
                                    <div class="col-lg-12 form-group">
                                        <label for="{{ $item->name }}">{{ $item->label }}
                                            @if($item->required == true)
                                            <span class="text-white">*</span>
                                            @else
                                            <span class="">( {{ __("Optional") }} )</span>
                                            @endif
                                        </label>
                                        <input type="{{ $item->type }}" class="form--control" placeholder="{{ ucwords(str_replace('_',' ', $item->name)) }}" name="{{ $item->name }}" value="{{ old($item->name) }}">
                                    </div>
                                @elseif ($item->type == "textarea")
                                    <div class="col-lg-12 form-group">
                                        @include('admin.components.form.textarea',[
                                            'label'     => $item->label,
                                            'name'      => $item->name,
                                            'value'     => old($item->name),
                                        ])
                                    </div>
                                @endif
                            @endforeach
                            <div class="col-xl-12 col-lg-12">
                                <button type="submit" class="btn--base w-100 btn-loading"> {{ __("Confirm Payment") }}</button>
                            </div>
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
                                <span class="text--success">{{ number_format(@$hasData->data->amount->requested_amount,$digitShow )}} {{ @$hasData->data->amount->sender_currency }}</span>
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
                                <span class="text--danger">{{ __("1") }} {{ @$hasData->data->amount->sender_currency }} =  {{ number_format(@$hasData->data->amount->exchange_rate,$digitShow )}} {{ @$hasData->data->amount->gateway_cur_code }}</span>
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
                                <span class="conversion">{{ number_format(@$hasData->data->amount->requested_amount*$hasData->data->amount->exchange_rate,$digitShow )}} {{ @$hasData->data->amount->gateway_cur_code }}</span>
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
                                <span class="text--warning">{{ number_format(@$hasData->data->amount->gateway_total_charge,$digitShow )}} {{ @$hasData->data->amount->gateway_cur_code }}</span>
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
                                <span class="text--info last pay-in-total">{{ number_format(@$hasData->data->amount->total_payable_amount,$digitShow )}} {{ @$hasData->data->amount->gateway_cur_code }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</div>
@endsection 
