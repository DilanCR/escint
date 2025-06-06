@extends('layouts.master')
@php
    $lang = selectedLang();
    $auth_slug = Illuminate\Support\Str::slug(App\Constants\SiteSectionConst::AUTH_SECTION);
    $auth_text = App\Models\Admin\SiteSections::getData( $auth_slug)->first();
@endphp
@section('content')
   <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Account
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <div class="account-section">
        <div class="account-area">
            <div class="account-form-area">
                <div class="account-logo">
                    <a class="site-logo site-title" href="{{ setRoute('index') }}">
                        <img src="{{ get_logo($basic_settings) }}" data-white_img="{{ get_logo($basic_settings,'white') }}"
                        data-dark_img="{{ get_logo($basic_settings,'dark') }}" alt="logo">
                    </a>
                </div>
                <h3 class="title">{{ __(@$auth_text->value->language->$lang->register_title) }}</h3>
                <p>{{ __(@$auth_text->value->language->$lang->register_text) }}</p>
                <form action="{{ setRoute('user.register.submit') }}" class="account-form" method="POST">
                    @csrf
                    <div class="row">
                        
                        <div class="col-xl-12 col-lg-12 form-group custom-toggle">
                            @include('admin.components.form.switcher',[
                                'name'          => 'type',
                                'value'         => old('type','buyer'),
                                'options'       => ['Buyer' => 'buyer','Seller' => 'seller'],
                            ])
                        </div>
                        <div class="col-lg-6 col-md-12 form-group">
                            @include('admin.components.form.input',[
                                'name'          => "firstname",
                                'placeholder'   => __("First Name"),
                                'value'         => old("firstname"),
                            ])
                        </div>
                        <div class="col-lg-6 col-md-12 form-group">
                            @include('admin.components.form.input',[
                                'name'          => "lastname",
                                'placeholder'   => __("Last Name"),
                                'value'         => old("lastname"),
                            ])
                        </div>
                        <div class="col-lg-12 form-group">
                            @include('admin.components.form.input',[
                                    'type'          => "email",
                                    'name'          => "email",
                                    'placeholder'   => __("Email"),
                                    'value'         => old("email"),
                                ])
                        </div>
                        <div class="col-lg-12 form-group show_hide_password">
                            <input type="password" class="form--control" name="password" placeholder="{{ __('Password') }}" required>
                            <a href="javascript:void(0)" class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></a> 
                        </div>
                        <div class="col-lg-12 form-group">
                            <div class="custom-check-group">
                                <input type="checkbox" id="level-1" name="agree">
                                <label for="level-1">{{ __('I have agreed with') }} <a href="{{ route('page.view','terms-and-conditions') }}" target="_blanck">{{ __('Terms Of Use') }}</a> & <a href="{{ route('page.view','privacy-policy') }}" target="_blanck">{{ __('Privacy Policy') }}</a></label>
                            </div> 
                        </div>
                        <div class="col-lg-12 form-group text-center">
                            <button type="submit" class="btn--base w-100">{{ __("Register Now") }}</button>
                        </div>
                        <div class="col-lg-12 text-center">
                            <div class="account-item">
                                <label>{{ __("Already Have An Account?") }} <a href="{{ setRoute('user.login') }}">{{ __("Login Now") }}</a></label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Account
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@endsection 