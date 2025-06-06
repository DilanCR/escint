@extends('user.layouts.master')  
@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Create New Escrow")])
@endsection
@php
    $user_type = auth()->user()->type;
@endphp
@section('content')
<div class="body-wrapper">
    <div class="row mt-20 mb-20-none">
        <div class="col-xl-12 col-lg-12 mb-20">
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{ __("Create New Escrow") }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ setRoute('user.my-escrow.submit')}}" class="card-form escrow-form" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-xl-6 col-lg-6 form-group">
                                <label>{{ __("Title") }}<span>*</span></label>
                                <input type="text" name="title" class="form--control" value="{{ $user_pass_data['title'] ?? old('title') }}" placeholder="{{ __("enter Title") }}..." required>
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group transaction-type">
                                <label>{{ __("category") }}<span>*</span></label>
                                <select class="form--control trx-type-select nice-select" name="escrow_category" required> 
                                    @foreach ($escrowCategories as $item)
                                    <option value="{{ $item->id }}" {{ old('escrow_category') == $item->id ? "selected": "" }}>{{ $item->name }}</option>
                                    @endforeach 
                                </select>
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                <label>{{ __("My Role") }}<span>*</span></label>
                                <select class="form--control nice-select role" name="role" required>
                                    <option value="buyer" {{ ($user_pass_data['role'] ?? $user_type) == "buyer" ? "selected": "" }}>{{ __("Buyer") }}</option>
                                    <option value="seller" {{ ($user_pass_data['role'] ?? $user_type) == "seller" ? "selected": "" }}>{{ __("Seller") }}</option>
                                </select>
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                <label>{{ __("who Will Pay The Fees") }}<span>*</span></label>
                                <div class="who_will_pay_select">

                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                <label ><span class="buyer_seller_show" style="color:#c4c6c7 "></span><span>*</span></label>
                                <input type="text" name="buyer_seller_identify" class="form--control buyer_seller_identify" value="{{ old('buyer_seller_identify') }}" placeholder="{{ __('Enter Username or Email') }}..." required>
                                <div class="userCheckMessage text-end"></div>
                            </div>
                            <div class="col-xl-6 col-lg-6 form-group">
                                <label>{{ __("Amount") }}<span>*</span></label>
                                <div class="input-group">
                                    <input type="text" name="amount" id="amount" class="form--control" value="{{ $user_pass_data['amount'] ?? old('amount') }}" placeholder="{{ __('enter Amount') }}..." required>
                                    <select class="form--control nice-select" name="escrow_currency" required>
                                        @foreach ($currencies as $item)
                                        <option 
                                        value="{{ $item->code}}"
                                        data-id="{{ $item->id }}"
                                        data-symbol="{{ $item->symbol }}"
                                        data-type="{{ $item->type }}"
                                        {{ ($user_pass_data['escrow_currency'] ?? old('escrow_currency')) == $item->code ? "selected": "" }} {{ get_default_currency_code() == $item->code ? "selected": "" }}
                                        >{{ $item->code}}</option>
                                        @endforeach 
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-12 form-group">
                                <label>{{ __("Remarks") }} <span class="text--base">({{ __("Optional") }})</span></label>
                                <textarea class="form--control" name="remarks" placeholder="{{ __("Write Here") }}…">{{ old('remarks') }}</textarea>
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                <label>{{ __("Attachments") }} <span class="text--base">(Optional) </span> (Supported files jpg, jpeg, png, pdf, zip)</label>
                                <div class="">
                                    <input type="file" class="" name="file[]" id="fileUpload" data-height="130" accept=".jpg,.jpeg,.png,.pdf,.zip" data-max_size="20" data-file_limit="15">
                                </div>
                            </div> 
                            <div class="col-xl-12 col-lg-12 form-group paymentMethodSelectForBuyer">
                                <label>{{ __("Pay with") }}<span>*</span></label>
                                <select class="form--control payment_gateway" name="payment_gateway">
                                    <option value="myWallet" class="my_wallet_balance">My Wallet : 0.000 USD</option>
                                    @foreach ($payment_gateways_currencies as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name}}
                                        @if ($item->gateway->type == payment_gateway_const()::MANUAL)
                                            {{ "(Manual)" }}
                                        @endif
                                    </option>
                                    @endforeach 
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12">
                            <button type="submit" class="btn--base mt-10 w-100">{{ __("Submit Escrow") }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 
@endsection
@push('script')
    <script>
        $(document).ready(function(){
            getRole();
            setOptionsForWhoWillPay();
            userWalletByCurrency();
            paymentMethodAvailable();
        });
        $('.role').on('change',function(){
            getRole();
            setOptionsForWhoWillPay();
            paymentMethodAvailable();
        });
        $('select[name=escrow_currency]').on('change',function(){
            userWalletByCurrency();
        });
        $('.buyer_seller_identify').on('focusout',function(){
            var userCheck = $(".buyer_seller_identify").val(); 
            // alert(userCheck)
            $.ajax({
            type:'get',
                url:"{{ route('user.my-escrow.userCheck') }}",
                data:{userCheck:userCheck},
                success:function(data){ 
                    if (data == true) {
                        $('.userCheckMessage').html('<span style="color: rgb(5 161 5)">Valid</span>');
                    }else{
                        $('.userCheckMessage').html('<span style="color: rgb(185 7 7)">Invalid</span>');
                    }
                }
            }); 
        });
        function getRole() {
            var role = $("select[name=role] :selected").val();
            if (role == "seller") { 
                $('.buyer_seller_show').text("{{ __('Buyer Username/Email') }}"); 
            }else{
                $('.buyer_seller_show').text("{{ __('Seller Username/Email') }}"); 
            }
        }
        function setOptionsForWhoWillPay() {
            var role = $("select[name=role] :selected").val();
            if (role == "seller") {    
                $('.who_will_pay_select').empty().append(
                '<select class="form--control who_will_pay_options nice-select" name="who_will_pay_options" required>'+
                    `<option value="me">{{ __("Me") }}</option>`+
                    `<option value="buyer">{{ __("Buyer") }}</option>`+
                    `<option value="half">50% - 50%</option>`+
                '</select>'
                );
                
            }else{  
                $('.who_will_pay_select').empty().append(
                '<select class="form--control who_will_pay_options nice-select" name="who_will_pay_options" required>'+
                    `<option value="me">{{ __("Me") }}</option>`+
                    `<option value="seller">{{ __("Seller") }}</option>`+
                    `<option value="half">50% - 50%</option>`+
                '</select>'
                );
            }
            $('.who_will_pay_options').niceSelect('destroy');
            $('.who_will_pay_options').niceSelect();
        }
        // get user wallet balance by currency 
        function userWalletByCurrency(){
                var currencyId = $("select[name=escrow_currency] :selected").attr("data-id"); 
                var currencyCode = $("select[name=escrow_currency] :selected").val(); 
                $.ajax({
                type:'get',
                    url:"{{ route('user.my-escrow.available.balance.byCurrency') }}",
                    data:{id:currencyId},
                    success:function(data){
                        $('.my_wallet_balance').html("My Wallet : "+ data +" "+ currencyCode)
                        $('.payment_gateway').niceSelect('destroy'); //destroy the plugin 
                        $('.payment_gateway').niceSelect(); //apply again
                    }
                });  
        }
        function paymentMethodAvailable(){
            var role = $("select[name=role] :selected").val();
            if (role == "seller") { 
                $('.paymentMethodSelectForBuyer').css("display", "none"); 
            }else{
                $('.paymentMethodSelectForBuyer').css("display", "block"); 
            }
        }
        const numberField = document.getElementById('amount'); 
        numberField.addEventListener('input', function (e) {
            const input = e.target.value;
            // Use a regular expression to match only numeric characters
            const numericInput = input.replace(/[^0-9]/g, '');
            // Set the input field value to the numeric input
            e.target.value = numericInput;
        });
    </script>
@endpush