    <div class="sidebar">
        <div class="sidebar-inner">
            <div class="sidebar-menu-area">
                <div class="sidebar-logo">
                    <a class="site-logo site-title" href="{{ setRoute('index') }}">
                        <img src="{{ get_logo($basic_settings) }}" data-white_img="{{ get_logo($basic_settings,'white') }}"
                        data-dark_img="{{ get_logo($basic_settings,'dark') }}" alt="logo">
                    </a>
                    <button class="sidebar-menu-bar">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                </div>
                <div class="sidebar-menu-wrapper">
                    <ul class="sidebar-menu">
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.dashboard') }}">
                                <i class="menu-icon las la-palette"></i>
                                <span class="menu-title">{{ __("Dashboard") }}</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.my-escrow.index') }}">
                                <i class="menu-icon las la-handshake"></i>
                                <span class="menu-title">{{ __("My Escrow") }}</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.transactions.index') }}">
                                <i class="menu-icon las la-wallet"></i>
                                <span class="menu-title">{{ __("Transactions") }}</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.add.money.index') }}">
                                <i class="menu-icon las la-sign"></i>
                                <span class="menu-title">{{ __("Add Money") }}</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.money.out.index') }}">
                                <i class="menu-icon las la-swatchbook"></i>
                                <span class="menu-title">{{ __("Money Out") }}</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.money.exchange.index') }}">
                                <i class="menu-icon las la-exchange-alt"></i>
                                <span class="menu-title">{{ __("Money Exchange") }}</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.security.google.2fa') }}">
                                <i class="menu-icon las la-user-lock"></i>
                                <span class="menu-title">{{ __("2FA Security") }}</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.authorize.kyc') }}">
                                <i class="las la-user-shield menu-icon"></i>
                                <span class="menu-title">{{ __("KYC Verification") }}</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="javascript:void(0)" class="logout-btn">
                                <i class="menu-icon las la-sign-out-alt"></i>
                                <span class="menu-title">{{ __("Logout") }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="sidebar-doc-box bg-overlay-base bg_img" data-background="{{ asset('public/frontend') }}/images/element/side-bg.webp">
                <div class="sidebar-doc-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="sidebar-doc-content">
                    <h4 class="title">{{ __("Help Center") }}</h4>
                    <p>{{ __("How can we help you") }}?</p>
                    <div class="sidebar-doc-btn">
                        <a href="{{ setRoute('user.support.ticket.index') }}" class="btn--base w-100">{{ __("Get Support") }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('script')
    <script>
        $(".logout-btn").click(function(){
            var actionRoute =  "{{ setRoute('user.logout') }}";
            var target      = 1;
            var message     = `{{ __("Are you sure to logout?") }}`;
            var logout     = `{{ __("Logout") }}`;

            openAlertModal(actionRoute,target,message,logout,"POST");
        });
    </script>
@endpush