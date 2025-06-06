<?php
namespace App\Constants;
use App\Models\UserWallet;
use Illuminate\Support\Str;

class PaymentGatewayConst {
    
    const APP       = "APP";
    const TYPEADDMONEY      = "ADD-MONEY";
    const TYPEMONEYOUT      = "MONEY-OUT";
    const TYPEMONEYEXCHANGE = "MONEY-EXCHANGE";
    const TYPEADDSUBTRACTBALANCE = "ADD-SUBTRACT-BALANCE";
    const AUTOMATIC = "AUTOMATIC";
    const MANUAL    = "MANUAL";
    const ADDMONEY  = "Add Money";
    const MONEYOUT  = "Money Out";
    const ACTIVE    =  true;

    const PAYPAL        = 'paypal';
    const STRIPE        = 'stripe';
    const MANUA_GATEWAY = 'manual';
    const FLUTTER_WAVE  = 'flutterwave';
    const RAZORPAY      = 'razorpay';
    const SSLCOMMERZ    = 'sslcommerz';
    const QRPAY         = 'qrpay';
    const PAGADITO      = 'pagadito';
    const TATUM         = 'tatum';
    const COINGATE      = 'coingate';
    const COIN_GATE     = 'coingate';
    const PERFECT_MONEY = 'perfect-money';
    const PAYSTACK      = "paystack";

    const SEND = "SEND";
    const RECEIVED = "RECEIVED";
    const PENDING = "PENDING";
    const REJECTED = "REJECTED";
    const CREATED = "CREATED";
    const SUCCESS = "SUCCESS";
    const EXPIRED = "EXPIRED";

    const NOT_USED  = "NOT-USED";
    const USED      = "USED";
    const SENT      = "SENT";

    const FIAT                      = "FIAT";
    const CRYPTO                    = "CRYPTO";
    const CRYPTO_NATIVE             = "CRYPTO_NATIVE";

    const ASSET_TYPE_WALLET         = "WALLET";
    const CALLBACK_HANDLE_INTERNAL  = "CALLBACK_HANDLE_INTERNAL";

    const STATUSSUCCESS  = 1;
    const STATUSPENDING  = 2;
    const STATUSHOLD     = 3;
    const STATUSREJECTED = 4;
    const STATUSWAITING     = 5;
    
    const ENV_SANDBOX    = "SANDBOX";
    const ENV_PRODUCTION = "PRODUCTION";

    const REDIRECT_USING_HTML_FORM = "REDIRECT_USING_HTML_FORM";

    const PROJECT_CURRENCY_SINGLE   = "PROJECT_CURRENCY_SINGLE";
    const PROJECT_CURRENCY_MULTIPLE = "PROJECT_CURRENCY_MULTIPLE";

    public static function add_money_slug() {
        return Str::slug(self::ADDMONEY);
    }


    public static function money_out_slug() {
        return Str::slug(self::MONEYOUT);
    }
    public static function register($alias = null) {
        $gateway_alias  = [
            self::PAYPAL        => "paypalInit",
            self::STRIPE        => "stripeInit",
            self::MANUA_GATEWAY => "manualInit",
            self::FLUTTER_WAVE  => 'flutterwaveInit',
            self::RAZORPAY      => 'razorInit',
            self::SSLCOMMERZ    => 'sslcommerzInit',
            self::QRPAY => "qrpayInit",
            self::PAGADITO => 'pagaditoInit',
            self::TATUM         => 'tatumInit',
            self::COIN_GATE => 'coinGateInit',
            self::PERFECT_MONEY => 'perfectMoneyInit',
            self::PAYSTACK      => 'paystackInit'
        ];

        if($alias == null) {
            return $gateway_alias;
        }

        if(array_key_exists($alias,$gateway_alias)) {
            return $gateway_alias[$alias];
        }
        return "init";
    }
    public static function apiAuthenticateGuard() {
        return [
            'api'   => 'web',
        ];
    }
    public static function registerWallet() {
        return [
            'web'       => UserWallet::class,
            'api'       => UserWallet::class,
        ];
    }
    public static function registerRedirection() {
        return [
            'web'       => [
                'return_url'    => 'user.add.money.perfect-money.payment.success',
                'cancel_url'    => 'user.add.money.payment.cancel',
                'callback_url'  => 'user.add.money.perfect-money.payment.callback',
                'redirect_form' => 'user.add.money.payment.redirect.form',
                'btn_pay'       => 'user.add.money.payment.btn.pay',
            ],
            'api'       => [
                'return_url'    => 'api.user.add.money.payment.success',
                'cancel_url'    => 'api.user.add.money.payment.cancel',
                'callback_url'  => 'user.add.money.payment.callback',
                'redirect_form' => 'user.add.money.payment.redirect.form',
                'btn_pay'       => 'api.v1.add-money.payment.btn.pay',
            ],
        ];
    }
    public static function registerGatewayRecognization() {
        return [ 
            'isPaypal'          => self::PAYPAL,
            'isCoinGate'        => self::COIN_GATE,
            'isQrpay'           => self::QRPAY,
            'isTatum'           => self::TATUM,
            'isStripe'          => self::STRIPE, 
            'isSslCommerz'      => self::SSLCOMMERZ,
            'isRazorpay'        => self::RAZORPAY,
            'isPerfectMoney'    => self::PERFECT_MONEY,
            'isPayStack'        => self::PAYSTACK,
        ];
    }
}
