<?php

namespace App\Traits\EscrowPaymentGateway;

use Exception;
use App\Models\User;
use App\Models\Escrow;
use Illuminate\Support\Str;
use App\Models\EscrowDetails;
use App\Models\TemporaryData;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Models\Admin\CryptoAsset;
use App\Constants\EscrowConstants;
use App\Models\Admin\BasicSettings;
use Illuminate\Support\Facades\DB; 
use App\Constants\NotificationConst;
use App\Http\Helpers\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response; 
use App\Constants\PaymentGatewayConst; 
use App\Notifications\Escrow\EscrowRequest;
use Illuminate\Http\Client\RequestException; 
use App\Http\Helpers\Api\Helpers as ApiResponse; 
use App\Http\Helpers\Response as HelpersResponse; 
use App\Models\Admin\PaymentGateway as PaymentGatewayModel;
use App\Events\User\NotificationEvent as UserNotificationEvent;

trait Tatum {
 

    private $tatum_gateway_credentials;
    private $request_credentials;
    private $tatum_api_base_url = "https://api-eu1.tatum.io/";
    private $tatum_api_v3       = "v3";

    public function tatumInit($escrow_data = null) {  
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        // Need to show request currency wallet information
        $currency = $escrow_data->gateway_currency; 
        $gateway = PaymentGatewayModel::find($currency->payment_gateway_id);
         
        $crypto_asset = $gateway->cryptoAssets->where('coin', $currency->currency_code)->first();
        $crypto_active_wallet = collect($crypto_asset->credentials->credentials ?? [])->where('status', true)->first();
        if(!$crypto_asset || !$crypto_active_wallet) throw new Exception("Gateway is not available right now! Please contact with system administration");

        
        try{
            if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
                $escrow_id = $this->createTatumEscrowUpdate($escrow_data, $crypto_active_wallet);
                return redirect()->route('user.escrow-action.payment.crypto.address',  $escrow_id);
            }else {
                $escrow_id = $this->createTatumEscrow($escrow_data, $crypto_active_wallet); 
                return redirect()->route('user.my-escrow.payment.crypto.address', $escrow_id);
            } 
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        } 
        throw new Exception("No Action Executed!");
    }

    public function tatumInitApi($escrow_data = null) { 
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        // Need to show request currency wallet information
        $currency = $escrow_data->gateway_currency; 
        $gateway = PaymentGatewayModel::find($currency->payment_gateway_id);

        $crypto_asset = $gateway->cryptoAssets->where('coin', $currency->currency_code)->first();
        $crypto_active_wallet = collect($crypto_asset->credentials->credentials ?? [])->where('status', true)->first();
        if(!$crypto_asset || !$crypto_active_wallet) throw new Exception("Gateway is not available right now! Please contact with system administration");
 
            try{
                if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
                    $escrow_id = $this->createTatumEscrowUpdatePaymentApprovalapi($escrow_data, $crypto_active_wallet); 
                    $submit_url = route('api.v1.api-escrow-action.payment.crypto.confirm',$escrow_id); 
                }else {
                    $escrow_id = $this->createTatumEscrow($escrow_data, $crypto_active_wallet); 
                    $submit_url = route('api.v1.my-escrow.payment.crypto.confirm',$escrow_id); 
                } 

                $data = [
                    'redirect_url'      => false,
                    'redirect_links'    => [],
                    'type'              => PaymentGatewayConst::CRYPTO_NATIVE, 
                    'address_info'      => [
                        'coin'          => $crypto_asset->coin,
                        'address'       => $crypto_active_wallet->address,
                        'input_fields'  => $this->tatumUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY),
                        'submit_url'    => $submit_url,
                        'method'        => "post",
                    ],
                ];

                $payment_informations = [
                    'trx'                   => $escrow_data->trx,
                    'gateway_currency_name' => $escrow_data->gateway_currency->name,
                    'request_amount'        => get_amount($escrow_data->escrow->amount, $escrow_data->escrow->escrow_currency),
                    'exchange_rate'         => "1".' '.$escrow_data->escrow->escrow_currency.' = '. get_amount($escrow_data->escrow->gateway_exchange_rate, $escrow_data->escrow->gateway_currency),
                    'total_charge'          => get_amount($escrow_data->escrow->escrow_total_charge, $escrow_data->escrow->escrow_currency),
                    'charge_payer'          => $escrow_data->escrow->charge_payer,
                    'seller_get'            => get_amount($escrow_data->escrow->seller_amount, $escrow_data->escrow->escrow_currency),
                    'payable_amount'        => get_amount($escrow_data->escrow->buyer_amount, $escrow_data->escrow->gateway_currency),
               ];
               $data =[
                    'gategay_type'          => $escrow_data->gateway_currency->gateway->type,
                    'gateway_currency_name' => $escrow_data->gateway_currency->name,
                    'alias'                 => $escrow_data->gateway_currency->alias,
                    'identify'              => $escrow_data->gateway_currency->gateway->name,
                    'payment_informations'  => $payment_informations,
                    'action_type'           => $data['type']  ?? false,
                    'address_info'          => $data['address_info'] ?? [],
                    'url'                   => false,
                    'method'                => "get",
               ];
               $message = ['success'=>['Escrow Payment Gateway Captured Successful']];
               return ApiResponse::success($message, $data);      
                 
            }catch(Exception $e) {
                throw new Exception($e->getMessage());
            }  

        throw new Exception("No Action Executed!");
    }


    public function createTatumEscrow($escrow_data, $crypto_active_wallet) {
        $identifier = $escrow_data->trx ?? session()->get('identifier');
        $tempData   = TemporaryData::where("identifier",$identifier)->first();
      
        $escrowData = $tempData->data->escrow; 
        $qr_image = 'https://qrcode.tec-it.com/API/QRCode?data='.$crypto_active_wallet->address;

        DB::beginTransaction();
        try{ 
           $escrowCreate = Escrow::create([
                'user_id'                     => $escrowData->user_id,
                'escrow_category_id'          => $escrowData->escrow_category_id,
                'payment_gateway_currency_id' => $escrowData->payment_gateway_currency_id ?? null,
                'escrow_id'                   => 'EC'.getTrxNum(),
                'payment_type'                => EscrowConstants::DID_NOT_PAID,
                'role'                        => $escrowData->role,
                'who_will_pay'                => $escrowData->charge_payer,
                'buyer_or_seller_id'          => $escrowData->buyer_or_seller_id,
                'amount'                      => $escrowData->amount,
                'escrow_currency'             => $escrowData->escrow_currency,
                'title'                       => $escrowData->title,
                'remark'                      => $escrowData->remarks,
                'file'                        => json_decode($tempData->data->attachment),
                'status'                      => EscrowConstants::PAYMENT_WATTING,
                'details'                       => [
                    'payment_info'    => [
                        'payment_type'      => PaymentGatewayConst::CRYPTO, 
                        'currency'          => $tempData->data->gateway_currency->currency_code,
                        'receiver_address'  => $crypto_active_wallet->address,
                        'receiver_qr_image' => $qr_image,
                        'requirements'      => $this->tatumUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY), 
                    ], 
                ],
                'created_at'                  => now(),
            ]);
            EscrowDetails::create([
                'escrow_id'             => $escrowCreate->id ?? 0,
                'fee'                   => $escrowData->escrow_total_charge,
                'seller_get'            => $escrowData->seller_amount,
                'buyer_pay'             => $escrowData->buyer_amount,
                'gateway_exchange_rate' => $escrowData->gateway_exchange_rate,
                'created_at'            => now(),
            ]);
            DB::commit();
            //send user notification
            $byerOrSeller = User::findOrFail($escrowData->buyer_or_seller_id);
            $byerOrSeller->notify(new EscrowRequest($byerOrSeller,$escrowCreate));
            $notification_content = [
                'title'   => "Escrow Request",
                'message' => "A user created an escrow with you",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::ESCROW_CREATE,
                'user_id' => $escrowData->buyer_or_seller_id,
                'message' => $notification_content,
            ]);
            TemporaryData::where("identifier", $tempData->identifier)->delete();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        } 
        return $escrowCreate->escrow_id;
    }
    public function createTatumEscrowUpdate($escrow_data, $crypto_active_wallet) {   
        $qr_image = 'https://qrcode.tec-it.com/API/QRCode?data='.$crypto_active_wallet->address;
        $escrowData = Escrow::where('escrow_id',$escrow_data->trx)->first();
        $escrowDetails = $escrowData->escrowDetails;
        $escrowData->payment_gateway_currency_id = $escrow_data->gateway_currency->id;
        $escrowData->status = EscrowConstants::PAYMENT_WATTING; 
        $escrowData->details = [
            'payment_info'    => [
                'payment_type'      => PaymentGatewayConst::CRYPTO, 
                'receiver_address'  => $crypto_active_wallet->address,
                'receiver_qr_image' => $qr_image,
                'requirements'      => $this->tatumUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY), 
            ], 
        ];
        $escrowDetails->buyer_pay = $escrow_data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $escrow_data->escrow->eschangeRate;

        DB::beginTransaction();
        try{
            $escrowData->save();
            $escrowDetails->save();
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        } 
        return $escrowData->escrow_id;
    }
    public function createTatumEscrowUpdatePaymentApprovalapi($escrow_data, $crypto_active_wallet) {   
        // dd($escrow_data);
        $qr_image = 'https://qrcode.tec-it.com/API/QRCode?data='.$crypto_active_wallet->address;
        $escrowData = Escrow::where('id',$escrow_data->escrow->escrow_id)->first();
        $escrowDetails = $escrowData->escrowDetails;
        $escrowData->payment_gateway_currency_id = $escrow_data->gateway_currency->id;
        $escrowData->status = EscrowConstants::PAYMENT_WATTING; 
        $escrowData->details = [
            'payment_info'    => [
                'payment_type'      => PaymentGatewayConst::CRYPTO, 
                'receiver_address'  => $crypto_active_wallet->address,
                'receiver_qr_image' => $qr_image,
                'requirements'      => $this->tatumUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY), 
            ], 
        ];
        $escrowDetails->buyer_pay = $escrow_data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $escrow_data->escrow->gateway_exchange_rate;

        DB::beginTransaction();
        try{
            $escrowData->save();
            $escrowDetails->save();
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        } 
        return $escrowData->escrow_id;
    }

    public function tatumUserTransactionRequirements($trx_type = null) {
        $requirements = [
            PaymentGatewayConst::TYPEADDMONEY => [
                [
                    'type'          => 'text',
                    'label'         =>  "Txn Hash",
                    'placeholder'   => "Enter Txn Hash",
                    'name'          => "txn_hash",
                    'required'      => true,
                    'validation'    => [
                        'min'           => "0",
                        'max'           => "250",
                        'required'      => true,
                    ]
                ]
            ],
        ];

        if($trx_type) {
            if(!array_key_exists($trx_type, $requirements)) throw new Exception("User Transaction Requirements Not Found!");
            return $requirements[$trx_type];
        }

        return $requirements;
    }

    public function getTatumCredentials($output)
    {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");

        $testnet_key_sample = ['test','testnet','demo','sandbox', 'tatum test','tatum testnet','test key', 'sandbox key'];
        $mainnet_key_sample = ['main','mainnet','live','production','tatum main','tatum mainnet','main key','production key'];

        $mainnet_key = PaymentGateway::getValueFromGatewayCredentials($gateway,$mainnet_key_sample);
        $testnet_key = PaymentGateway::getValueFromGatewayCredentials($gateway,$testnet_key_sample);

        $mode = $gateway->env;

        $gateway_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => PaymentGatewayConst::ENV_SANDBOX,
            PaymentGatewayConst::ENV_PRODUCTION => PaymentGatewayConst::ENV_PRODUCTION,
        ];

        if(array_key_exists($mode,$gateway_register_mode)) {
            $mode = $gateway_register_mode[$mode];
        }else {
            $mode = PaymentGatewayConst::ENV_SANDBOX;
        }

        $credentials = (object) [
            'testnet'           => $testnet_key,
            'mainnet'           => $mainnet_key,
            'mode'              => $mode
        ];

        $this->tatum_gateway_credentials = $credentials;

        return $credentials;
    }

    public function getTatumRequestCredentials($output = null)
    {
        $credentials = $this->tatum_gateway_credentials;
        if(!$output) $output = $this->output;

        $request_credentials = [];
        if($output['gateway']->env == PaymentGatewayConst::ENV_PRODUCTION) {
            $request_credentials['token']   = $credentials->mainnet;
        }else {
            $request_credentials['token']   = $credentials->testnet;
        }

        $this->request_credentials = (object) $request_credentials;
        return (object) $request_credentials;
    }

    public function isTatum($gateway)
    {
        $search_keyword = ['tatum','tatum gateway','gateway tatum','crypto tatum','tatum crypto','tatum blockchain','blockchain tatum'];
        $gateway_name = $gateway->name;

        $search_text = Str::lower($gateway_name);
        $search_text = preg_replace("/[^A-Za-z0-9]/","",$search_text);
        foreach($search_keyword as $keyword) {
            $keyword = Str::lower($keyword);
            $keyword = preg_replace("/[^A-Za-z0-9]/","",$keyword);
            if($keyword == $search_text) {
                return true;
                break;
            }
        }
        return false;
    }

    public function setTatumCredentials($gateway) {
        $tatum_credentials = $this->getTatumCredentials(['gateway' => $gateway]);
        $request_credentials = $this->getTatumRequestCredentials(['gateway' => $gateway]);

        return [
            'tatum_credentials'     => $tatum_credentials,
            'request_credentials'   => $request_credentials,
        ];
    }

    public function getTatumAssets($gateway)
    {
        $credentials = $this->setTatumCredentials($gateway);

        $result['wallets'] = $this->getTatumWallets($gateway, $credentials['request_credentials']);

        return $gateway->cryptoAssets->where('type', PaymentGatewayConst::ASSET_TYPE_WALLET);
    }

    // Get wallet address with balance and mnemonic
    public function getTatumWallets($gateway, $request_credentials)
    {
        $crypto_assets          = $gateway->cryptoAssets->pluck("coin")->toArray();
        $gateway_supported_coins  = $gateway->supported_currencies;
        $new_coins = array_diff($gateway_supported_coins, $crypto_assets);

        $generate_wallets = [];

        foreach($new_coins as $coin) {
            if($this->tatumRegisteredChains($coin)['status'] != true) continue;

            try{
                $blockchain_wallet = $this->generateWalletFromTatum($coin);
                $crypto_asset = $this->insertRecordTatum($blockchain_wallet, $gateway);
            }catch(Exception $e) {
                throw new Exception($e->getMessage());
            }

            $generate_wallets[$coin] = $blockchain_wallet;
        }

        return $generate_wallets;

    }

    /**
     * Insert/Store wallet information to local database
     * @param array $blockchain_wallet_info
     * @param \App\Models\Admin\PaymentGateway $gateway
     */
    public function insertRecordTatum($blockchain_wallet_info, $gateway)
    {
        $credentials            = $blockchain_wallet_info['credentials'];
        $coin_info              = $blockchain_wallet_info['coin_info'];
        $credentials['id']      = uniqid() . time();
        $credentials['balance'] = [
            'balance'       => 0,
        ];
        $credentials['status']  = true; // project active address

        // Insert record to database
        DB::beginTransaction();
        try{
            $inserted_id = DB::table('crypto_assets')->insertGetId([
                'payment_gateway_id'   => $gateway->id,
                'type'                  => PaymentGatewayConst::ASSET_TYPE_WALLET,
                'chain'                 => $coin_info['chain'],
                'coin'                  => $coin_info['coin'],
                'credentials'           => json_encode([]),
                'created_at'            => now()
            ]);

            $crypto_asset = CryptoAsset::find($inserted_id);

            // add subscription on address
            $subscription = $this->tatumSubscriptionForAccountTransaction($crypto_asset,$credentials['address']);
            $credentials['subscribe_id']    = $subscription->id;

            $crypto_asset->update([
                'credentials'       => [
                    'credentials'   => [$credentials]
                ]
            ]);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }

        return $crypto_asset;
    }

    /**
     * Transfer Specific Coin Method for Collecting Credentials Using Tatum API Endpoints
     * @param string $coin
     * @return array|boolean $credentials
     */
    public function generateWalletFromTatum($coin)
    {
        $register_coin = $this->tatumRegisteredChains($coin);

        switch($coin) {
            case "ETH":
                $credentials = $this->generateEthereumWallet($register_coin);
                break;
            case "BTC":
                $credentials = $this->generateBitcoinWallet($register_coin);
                break;
            case "SOL":
                $credentials = $this->generateSolanaWallet($register_coin);
                break;
            default:
                throw new Exception("Coin [$coin] functions not declared");
        }

        return [
            'credentials'   => $credentials,
            'coin_info'     => $register_coin,
        ];
    }

    /**
     * Generate Solana Wallet Information Using Tatum (mnemonic, xpub, private key, address)
     * @param array $coin_info
     * @return array $credentials
     */
    public function generateSolanaWallet($coin_info)
    {
        // request for generate wallet
        $wallet_api_endpoint = $this->getTatumGenerateWalletEndpoint($coin_info);
        $response = Http::withHeaders([
            'x-api-key' => $this->request_credentials->token,
        ])->get($wallet_api_endpoint)->throw(function(Response $response, RequestException $exception) {
            throw new Exception($exception->getMessage());
        })->object();

        $credentials['mnemonic']        = $response->mnemonic;
        $credentials['address']         = $response->address;
        $credentials['private_key']     = $response->privateKey;

        return $credentials;
    }

    /**
     * Generate Ethereum Wallet Information Using Tatum (mnemonic, xpub, private key, address)
     * @param array $coin_info
     * @return array $credentials
     */
    public function generateEthereumWallet($coin_info)
    {
        $headers['x-api-key'] = $this->request_credentials->token;

        if($this->getTatumCoinTestnet($coin_info)) {
            $headers['x-testnet-type']  = $this->getTatumCoinTestnet($coin_info);
        }

        // request for generate wallet
        $wallet_api_endpoint = $this->getTatumGenerateWalletEndpoint($coin_info);
        $response = Http::withHeaders($headers)
                        ->get($wallet_api_endpoint)
                        ->throw(function(Response $response, RequestException $exception) {
                            throw new Exception($exception->getMessage());
                        })->object();

        $credentials['mnemonic']    = $response->mnemonic;
        $credentials['xpub']        = $response->xpub;

        // request for generate private key
        $private_key_api_endpoint = $this->getTatumGeneratePrivateKeyEndpoint($coin_info);
        $index = 0;
        $private_key_response = Http::withHeaders([
            'Content-Type'      => 'application/json',
            'x-api-key'         => $this->request_credentials->token,
        ])->post($private_key_api_endpoint,[
            'index'     => $index,
            'mnemonic'  => $credentials['mnemonic'],
        ])->throw(function(Response $response, RequestException $exception) {
            throw new Exception($exception->getMessage());
        })->object();

        $credentials['private_key'] = $private_key_response->key;

        // request for public address using extended public key (xpub)
        $public_address_api_endpoint = $this->getTatumGeneratePublicAddressEndpoint($coin_info, $credentials['xpub'], $index);
        $public_address_response = Http::withHeaders([
            'x-api-key' => $this->request_credentials->token,
        ])->get($public_address_api_endpoint)->throw(function(Response $response, RequestException $exception) {
            throw new Exception($exception->getMessage());
        })->object();

        $credentials['address']     = $public_address_response->address;

        return $credentials;
    }

    /**
     * Generate Bitcoin Wallet Information Using Tatum (mnemonic, xpub, private key, address)
     * @param array $coin_info
     * @return array $credentials
     */
    public function generateBitcoinWallet($coin_info): array
    {
        // request for generate wallet
        $wallet_api_endpoint = $this->getTatumGenerateWalletEndpoint($coin_info);
        $response = Http::withHeaders([
            'x-api-key' => $this->request_credentials->token,
        ])->get($wallet_api_endpoint)->throw(function(Response $response, RequestException $exception) {
            throw new Exception($exception->getMessage());
        })->object();

        $credentials['mnemonic']    = $response->mnemonic;
        $credentials['xpub']        = $response->xpub;

        // request for generate private key
        $private_key_api_endpoint = $this->getTatumGeneratePrivateKeyEndpoint($coin_info);
        $index = 0;
        $private_key_response = Http::withHeaders([
            'Content-Type'      => 'application/json',
            'x-api-key'         => $this->request_credentials->token,
        ])->post($private_key_api_endpoint,[
            'index'     => $index,
            'mnemonic'  => $credentials['mnemonic'],
        ])->throw(function(Response $response, RequestException $exception) {
            throw new Exception($exception->getMessage());
        })->object();

        $credentials['private_key'] = $private_key_response->key;

        // request for public address using extended public key (xpub)
        $public_address_api_endpoint = $this->getTatumGeneratePublicAddressEndpoint($coin_info, $credentials['xpub'], $index);
        $public_address_response = Http::withHeaders([
            'x-api-key' => $this->request_credentials->token,
        ])->get($public_address_api_endpoint)->throw(function(Response $response, RequestException $exception) {
            throw new Exception($exception->getMessage());
        })->object();

        $credentials['address']     = $public_address_response->address;

        return $credentials;
    }

    /**
     * Make Tatum Endpoint For Generate Blockchain Address From Extended Public Key (xpub)
     * @param array $coin_info
     * @return string
     */
    public function getTatumGeneratePublicAddressEndpoint($coin_info, $xpub = null, $index = 0): string
    {
        if(isset($coin_info['generate_account_address_from_xpub']) && isset($coin_info['generate_account_address_from_xpub']) != "") {
            $public_address_api_endpoint = $coin_info['generate_account_address_from_xpub'];
        }else {
            $public_address_api_endpoint = $this->tatumRegisteredEndpoints('generate_account_address_from_xpub');
        }

        $chain = $coin_info['chain'];
        $public_address_api_endpoint = str_replace('{chain}',$chain, $public_address_api_endpoint);
        $public_address_api_endpoint = $this->tatum_api_base_url . $this->tatum_api_v3 . "/" . $public_address_api_endpoint;

        if($xpub) {
            $public_address_api_endpoint = str_replace('{xpub}',$xpub, $public_address_api_endpoint);
        }

        $public_address_api_endpoint = str_replace('{index}',$index, $public_address_api_endpoint);

        return $public_address_api_endpoint;
    }

    /**
     * Make Tatum Endpoint For Generate Wallet Information in Blockchain From Registered Coin Information
     * @param array $coin_info
     * @return string
     */
    public function getTatumGenerateWalletEndpoint($coin_info): string
    {
        if(isset($coin_info['generate_wallet']) && isset($coin_info['generate_wallet']) != "") {
            $wallet_api_endpoint = $coin_info['generate_wallet'];
        }else {
            $wallet_api_endpoint = $this->tatumRegisteredEndpoints('generate_wallet');
        }

        $chain = $coin_info['chain'];
        $wallet_api_endpoint = str_replace('{chain}',$chain, $wallet_api_endpoint);
        $wallet_api_endpoint = $this->tatum_api_base_url . $this->tatum_api_v3 . "/" . $wallet_api_endpoint;

        return $wallet_api_endpoint;
    }

    /**
     * Make Tatum Endpoint For Generate Private Key Using Wallet Mnemonic in Blockchain
     * @param array $coin_info
     * @return string
     */
    public function getTatumGeneratePrivateKeyEndpoint($coin_info): string
    {
        if(isset($coin_info['generate_private_key']) && isset($coin_info['generate_private_key']) != "") {
            $private_key_api_endpoint = $coin_info['generate_private_key'];
        }else {
            $private_key_api_endpoint = $this->tatumRegisteredEndpoints('generate_private_key');
        }

        $chain = $coin_info['chain'];
        $private_key_api_endpoint = str_replace('{chain}',$chain, $private_key_api_endpoint);
        $private_key_api_endpoint = $this->tatum_api_base_url . $this->tatum_api_v3 . "/" . $private_key_api_endpoint;

        return $private_key_api_endpoint;
    }

    /**
     * Register Tatum Endpoints for Further Action
     * @param string $endpoint_key
     * @return string|array $generated_endpoints
     */
    public function tatumRegisteredEndpoints($endpoint_key = null)
    {
        $generated_endpoints = [
            'generate_wallet'                           => '{chain}/wallet',
            'generate_account_address_from_xpub'        => '{chain}/address/{xpub}/{index}',
            'generate_private_key'                      => '{chain}/wallet/priv',
            'account_balance'                           => '{chain}/account/balance/{address}',
            'address_transaction_subscription'          => 'subscription',
            'address_transaction_subscription_cancel'   => 'subscription/{id}',
        ];

        if($endpoint_key) {
            if(!array_key_exists($endpoint_key, $generated_endpoints)) throw new Exception("Provided endpoint key [$endpoint_key] not registered");
            return $generated_endpoints[$endpoint_key];
        }

        return $generated_endpoints;
    }

    /**
     * Check testnet is available or not. If it's available and gateway env is sandbox return testnet chain otherwise always return false
     * @param array $coin_info
     * @return string|boolean
     */
    public function getTatumCoinTestnet($coin_info)
    {
        if($coin_info['testnet'] == false) return false;

        if($this->tatum_gateway_credentials->mode == PaymentGatewayConst::ENV_SANDBOX) {
            return $coin_info['testnet'];
        }

        return false;
    }

    /**
     * Registered available chain in this project
     * @param string $coin
     * @return array $register_coins
     */
    public function tatumRegisteredChains($coin = null)
    {
        $register_coins = [
            'ETH'       => [
                'chain'             => 'ethereum',
                'testnet'           => 'ethereum-sepolia',
                'coin'              => 'ETH',
                'status'            => true,
            ],
            'BTC'       => [
                'chain'             => 'bitcoin',
                'testnet'           => false,
                'coin'              => "BTC",
                'status'            => true,
                'account_balance'   => "{chain}/address/balance/{address}",
            ],
            'USDT'      => [
                'chain'     => 'tron',
                'testnet'   => false,
                'coin'      => 'USDT',
                'status'    => false,
            ],
            'SOL'       => [
                'chain'     => 'solana',
                'testnet'   => false,
                'coin'      => 'SOL',
                'status'    => true,
            ]
        ];

        if($coin) {
            if(!array_key_exists($coin, $register_coins)) throw new Exception("Provided coin [$coin] not registered in tatum");
            return $register_coins[$coin];
        }

        return $register_coins;
    }

    /**
     * Get Active Chain (Where Status Is true)
     * @param string $coin
     * @return array $register_coins
     */
    public function tatumActiveChains() {
        $registered_chains = $this->tatumRegisteredChains();

        $active_chains = array_filter($registered_chains, function($item) {
            if($item['status'] == true) return $item;
        });

        return $active_chains;
    }

    /**
     * Get Tatum Wallet Balance Using Specific Address
     * @param \App\Models\Admin\CryptoAsset $crypto_asset
     * @param array $wallet_info
     * @return \Exception|object
     */
    public function getTatumAddressBalance($crypto_asset, $wallet_credentials)
    {

        $coin_info = $this->tatumRegisteredChains($crypto_asset->coin);
        $this->setTatumCredentials($crypto_asset->gateway);

        // GET Wallet Balance Using Address
        $wallet_balance_endpoint = $this->getTatumWalletBalanceEndpoint($coin_info, $wallet_credentials['address']);

        $headers['x-api-key'] = $this->request_credentials->token;
        if($this->getTatumCoinTestnet($coin_info)) {
            $headers['x-testnet-type']  = $this->getTatumCoinTestnet($coin_info);
        }

        $response = Http::withHeaders($headers)
                        ->get($wallet_balance_endpoint)
                        ->throw(function(Response $response, RequestException $exception) {
                            throw new Exception($exception->getMessage());
                        })->object();

        return $response;
    }

    public function getTatumWalletBalanceEndpoint($coin_info, $address): string
    {

        if(isset($coin_info['account_balance']) && isset($coin_info['account_balance']) != "") {
            $wallet_balance_endpoint = $coin_info['account_balance'];
        }else {
            $wallet_balance_endpoint = $this->tatumRegisteredEndpoints('account_balance');
        }

        $chain = $coin_info['chain'];
        $wallet_balance_endpoint = str_replace('{chain}',$chain, $wallet_balance_endpoint);
        $wallet_balance_endpoint = str_replace('{address}',$address, $wallet_balance_endpoint);
        $wallet_balance_endpoint = $this->tatum_api_base_url . $this->tatum_api_v3 . "/" . $wallet_balance_endpoint;

        return $wallet_balance_endpoint;
    }

    public function tatumSubscriptionForAccountTransaction($crypto_asset, $address) {
        $gateway = $crypto_asset->gateway;
        $this->setTatumCredentials($gateway);

        $coin_info = $this->tatumRegisteredChains($crypto_asset->coin);

        $testnet = $this->getTatumCoinTestnet($coin_info);

        $endpoint = $this->tatumRegisteredEndpoints('address_transaction_subscription');
        $endpoint = $this->tatum_api_base_url . $this->tatum_api_v3 . "/" . $endpoint;

        $query_param = [];
        if($testnet) {
            $query_param['testnetType'] = $testnet;
            $endpoint = $endpoint . "?" . http_build_query($query_param);
        }

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'x-api-key'     => $this->request_credentials->token,
        ])->post($endpoint, [
            'type' => 'ADDRESS_TRANSACTION',
            'attr'  => [
                'address'   => $address,
                'chain'     => $coin_info['coin'],
                'url'       => route('user.add.money.payment.callback',['gateway' => $gateway->alias,'token' => PaymentGatewayConst::CALLBACK_HANDLE_INTERNAL])
            ]
        ])->throw(function(Response $response, RequestException $exception) {
            throw new Exception($exception->getMessage());
        })->object();

        return $response;
    }

    public function tatumCallbackResponse(array $response_data, $gateway) { 
        // store crypto transaction record in local database
        $record['transaction_type'] = ucwords($response_data['type']);

        if($response_data['type'] == 'native') {
            $record['sender_address']     = $response_data['counterAddress'];
            $record['receiver_address']   = $response_data['address'];
            $record['status']             = PaymentGatewayConst::NOT_USED;
        }else if($response_data['type'] == 'fee') {
            $record['sender_address']     = $response_data['address'];
            $record['receiver_address']   = "";
            $record['status']             = PaymentGatewayConst::USED;
        }

        $record['txn_hash']             = $response_data['txId'];
        $record['asset']                = $response_data['asset'];
        $record['chain']                = $response_data['chain'];
        $record['block_number']         = $response_data['blockNumber'];
        $record['callback_response']    = json_encode($response_data);
        $record['amount']               = $response_data['amount'];

        if($this->senderAddressIsTatumRegistered($gateway, $record['asset'], $record['sender_address'])) {
            $record['status']             = PaymentGatewayConst::SENT;
        }

        DB::beginTransaction();
        try{

            DB::table("crypto_transactions")->insert($record);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            logger("Tatum Gateway Transaction Failed! ",[
                'response_data'     => $response_data,
                'exception'         => $e,
                'gateway'           => $gateway,
            ]);
            return HelpersResponse::error(['Failed to complete task!'],[],500);
        }
        HelpersResponse::success(['SUCCESS'],[],200);
    }

    public function senderAddressIsTatumRegistered($gateway, $coin, $address) {
        $crypto_assets = $gateway->cryptoAssets->where('coin', $coin)->first();
        if(!$crypto_assets) return false;

        $credentials = collect($crypto_assets->credentials->credentials ?? [])->pluck("address")->toArray();
        if(in_array($address, $credentials)) return true;

        return false;
    }

    public function tatumSubscriptionCancelForAccountTransaction($subscription_id, $gateway)
    {
        if($subscription_id) {
            $this->setTatumCredentials($gateway);

            $endpoint = $this->tatumRegisteredEndpoints('address_transaction_subscription_cancel');
            $endpoint = str_replace('{id}',$subscription_id, $endpoint);
            $endpoint = $this->tatum_api_base_url . $this->tatum_api_v3 . "/" . $endpoint;

            $response = Http::withHeaders([
                'x-api-key'     => $this->request_credentials->token,
            ])->delete($endpoint)->throw(function(Response $response, RequestException $exception) {
                throw new Exception($exception->getMessage());
            })->object();

            return true;
        }

        return false;
    }

}
