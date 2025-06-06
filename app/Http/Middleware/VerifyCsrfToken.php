<?php

namespace App\Http\Middleware;

use App\Constants\PaymentGatewayConst;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'user/username/check',
        'user/check/email',

        '/add-money/sslcommerz/success',
        '/add-money/sslcommerz/cancel',
        '/add-money/sslcommerz/fail',
        '/add-money/sslcommerz/ipn',
        
        '/api/v1/add-money/sslcommerz/success',
        '/api/v1/add-money/sslcommerz/cancel',
        '/api/v1/add-money/sslcommerz/fail',
        '/api/v1/add-money/sslcommerz/ipn',
        
        '/my-escrow/sslcommerz/success',
        '/my-escrow/sslcommerz/cancel',
        '/my-escrow/sslcommerz/fail',
        '/my-escrow/sslcommerz/ipn',

        '/api-my-escrow/sslcommerz/cancel',
        '/api-my-escrow/sslcommerz/success',
        '/api-my-escrow/sslcommerz/fail',
        '/api-my-escrow/sslcommerz/ipn',

        '/escrow-action/sslcommerz/success',
        '/escrow-action/sslcommerz/cancel',
        '/escrow-action/sslcommerz/fail',
        '/escrow-action/sslcommerz/ipn',

        '/api-escrow-action/sslcommerz/success',
        '/api-escrow-action/sslcommerz/cancel',
        '/api-escrow-action/sslcommerz/fail',
        '/api-escrow-action/sslcommerz/ipn',

        'escrow/conversation/file-upload',

        'user/payment-escrow/'.PaymentGatewayConst::RAZORPAY,
        'user/payment-escrow-approval/'.PaymentGatewayConst::RAZORPAY,

        'user/add-money/success/response/global/'.PaymentGatewayConst::RAZORPAY,
        'user/add-money/success/response/'.PaymentGatewayConst::RAZORPAY,
        'user/add-money/cancel/response/'.PaymentGatewayConst::RAZORPAY,
    ];
}
