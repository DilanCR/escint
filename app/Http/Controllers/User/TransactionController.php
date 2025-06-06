<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Constants\PaymentGatewayConst;

class TransactionController extends Controller
{
    public function slugValue($slug) {
        $values =  [
            'add-money'             => PaymentGatewayConst::TYPEADDMONEY,
            'money-out'             => PaymentGatewayConst::TYPEMONEYOUT,
            'money-exchange'        => PaymentGatewayConst::TYPEMONEYEXCHANGE,
        ];

        if(!array_key_exists($slug,$values)) return abort(404);
        return $values[$slug];
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($slug = null)
    {
        if($slug != null){
            $transactions = Transaction::where(['user_id' => auth()->user()->id, 'type' => $this->slugValue($slug)])->orderByDesc("id")->paginate(12);
            $page_title = ucwords(remove_speacial_char($slug," ")) . " Log";
        }else {
            $transactions = Transaction::where('user_id', auth()->user()->id)->orderByDesc("id")->paginate(12);
            $page_title = __("Transaction Log");
        }
        return view('user.sections.transactions.index', compact('transactions','page_title'));
    }

    
}
