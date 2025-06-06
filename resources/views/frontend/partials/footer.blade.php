@php 
    $pages = App\Models\Admin\SetupPage::where(['type' => 'useful-links', 'status' => true])->get();
@endphp
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Footer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<footer class="footer-section section--bg">
    <div class="circle-blur"></div>
    <div class="container">
        <div class="footer-wrapper">
            <div class="footer-logo">
                <a class="site-logo" href="{{ setRoute('index') }}"><img src="{{ get_logo($basic_settings) }}" alt="logo"></a>
            </div>
            <ul class="footer-list">
                <li>
                    <a href="{{ setRoute('faq') }}" target="_blanck">{{ __('FAQ') }}</a>
                </li>
                @foreach ($pages as $item)
                <li>
                    <a href="{{ route('page.view',$item->slug) }}" target="_blanck">{{ __($item->title) }}</a>
                </li>
                @endforeach
            </ul>
            <div class="copyright-area">
                <p>{{ __('Made by') }} <span class="gradient-text">{{ $basic_settings->site_name }}</span></p>
            </div>
        </div>
    </div>
</footer>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Footer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start cookie
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<div class="cookie-main-wrapper">
    <div class="cookie-content">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M21.598 11.064a1.006 1.006 0 0 0-.854-.172A2.938 2.938 0 0 1 20 11c-1.654 0-3-1.346-3.003-2.937c.005-.034.016-.136.017-.17a.998.998 0 0 0-1.254-1.006A2.963 2.963 0 0 1 15 7c-1.654 0-3-1.346-3-3c0-.217.031-.444.099-.716a1 1 0 0 0-1.067-1.236A9.956 9.956 0 0 0 2 12c0 5.514 4.486 10 10 10s10-4.486 10-10c0-.049-.003-.097-.007-.16a1.004 1.004 0 0 0-.395-.776zM12 20c-4.411 0-8-3.589-8-8a7.962 7.962 0 0 1 6.006-7.75A5.006 5.006 0 0 0 15 9l.101-.001a5.007 5.007 0 0 0 4.837 4C19.444 16.941 16.073 20 12 20z"/><circle cx="12.5" cy="11.5" r="1.5"/><circle cx="8.5" cy="8.5" r="1.5"/><circle cx="7.5" cy="12.5" r="1.5"/><circle cx="15.5" cy="15.5" r="1.5"/><circle cx="10.5" cy="16.5" r="1.5"/></svg>
        <p class="text-white">{{ __(strip_tags(@$cookie->value->desc)) }} <a href="{{ url('/').'/'.@$cookie->value->link }}">{{ __("privacy Policy") }}</a></p>
    </div>
    <div class="cookie-btn-area">
        <button class="cookie-btn">{{__("Allow")}}</button>
        <button class="cookie-btn-cross">{{__("Decline")}}</button>
    </div>
</div>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End cookie
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->