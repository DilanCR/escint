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
    <div class="account-section ptb-80">
        <div class="account-area">
            <div class="account-form-area">
                <div class="account-logo">
                    <a class="site-logo site-title" href="{{ setRoute('index') }}">
                        <img src="{{ get_logo($basic_settings) }}" data-white_img="{{ get_logo($basic_settings,'white') }}"
                        data-dark_img="{{ get_logo($basic_settings,'dark') }}" alt="logo">
                    </a>
                </div>
                <h3 class="title">{{ __(@$auth_text->value->language->$lang->login_title) }}</h3>
                <p>{{ __(@$auth_text->value->language->$lang->login_text) }}</p>
                <form action="{{ setRoute('user.login.submit') }}" class="account-form" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12 form-group">
                            @include('admin.components.form.input',[
                                    'name'          => "credentials",
                                    'placeholder'   => __("Username OR Email Address"),
                                    'required'      => true,
                                ]) 
                        </div>
                        <div class="col-lg-12 form-group show_hide_password">
                            <input type="password" class="form-control form--control" name="password" placeholder="{{ __('Enter Password') }}..." required>
                            <span class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></span>
                        </div>
                        <div class="col-lg-12 form-group"> 
                            <div class="forgot-item text-end">
                                <label><a href="{{ setRoute('user.password.forgot') }}" class="gradient-text">{{ __("forgot Password") }}</a></label>
                            </div>
                        </div>
                        <div class="col-lg-12 form-group text-center">
                            <button type="submit" class="btn--base w-100">{{ __("Login Now") }}</button>
                        </div>
                        <div class="col-lg-12 text-center">
                            <div class="account-item">
                                <label>{{ __("Don't Have An Account?") }} <a href="{{ setRoute('user.register') }}">{{ __("Register Now") }}</a></label>
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