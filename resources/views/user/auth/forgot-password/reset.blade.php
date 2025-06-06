@extends('layouts.master')
@section('content')
    <div class="account-section ptb-80">
        <div class="account-area">
            <div class="account-form-area">
                <div class="account-logo">
                    <a class="site-logo site-title" href="{{ setRoute('index') }}">
                        <img src="{{ get_logo($basic_settings) }}" data-white_img="{{ get_logo($basic_settings,'white') }}"
                        data-dark_img="{{ get_logo($basic_settings,'dark') }}" alt="logo">
                    </a>
                </div>
                <h3 class="title">{{ __("Password Reset") }}</h3>
                <p>{{ __("Reset your password") }}</p>
                <form action="{{ setRoute('user.password.reset',$token) }}" class="account-form" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12 form-group show_hide_password">
                            <input type="password" class="form-control form--control" name="password" placeholder="{{ __("Enter New Password") }}..." required>
                            <span class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></span>
                        </div>
                        <div class="col-lg-12 form-group show_hide_password">
                            <input type="password" class="form-control form--control" name="password_confirmation" placeholder="{{ __("Enter Confirm Password") }}..." required>
                            <span class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></span>
                        </div>
                        <div class="col-lg-12 form-group">
                            <div class="forgot-item">
                                <label><a href="{{ setRoute('user.login') }}" class="text--base">{{ __("Login") }}</a></label>
                            </div>
                        </div>
                        <div class="col-lg-12 form-group text-center">
                            <button type="submit" class="btn--base w-100">{{ __("Reset") }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection