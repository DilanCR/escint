<?php
namespace App\Constants;

class EscrowConstants 
{
    const ONGOING          = 1;
    const PAYMENT_PENDING  = 2;
    const APPROVAL_PENDING = 3;
    const RELEASED         = 4;
    const ACTIVE_DISPUTE   = 5;
    const DISPUTED         = 6;
    const CANCELED         = 7;
    const REFUNDED         = 8;
    const PAYMENT_WATTING  = 9;

    const SELLER_TYPE = "seller";
    const BUYER_TYPE  = "buyer";

    const ME     = "me";
    const SELLER = "seller";
    const BUYER  = "buyer";
    const HALF   = "half";

    const MY_WALLET    = 1;
    const GATEWAY      = 2;
    const DID_NOT_PAID = 3;
}
