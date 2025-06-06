<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\PaymentGatewayConst;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('Money in/deposit/add, withdrawal');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('user_wallet_id')->nullable();
            $table->unsignedBigInteger('payment_gateway_currency_id')->nullable();
            $table->string('trx_id')->comment('Transaction ID');
            $table->decimal('sender_request_amount', 28, 8)->nullable();
            $table->string('sender_currency_code');
            $table->decimal('total_payable', 28, 8)->nullable();
            $table->decimal('available_balance', 28, 8);
            $table->decimal('exchange_rate', 28, 8);
            $table->string('charge_status', 20)->nullable()->comment('Charge added == +, minus == -');
            $table->string('remark')->nullable();
            $table->text('details')->nullable();
            $table->text('reject_reason')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->enum("attribute",[
                PaymentGatewayConst::SEND,
                PaymentGatewayConst::RECEIVED,
                PaymentGatewayConst::PENDING,
                PaymentGatewayConst::REJECTED,
            ]);
            $table->timestamps();

            $table->foreign("user_wallet_id")->references("id")->on("user_wallets")->onDelete("cascade")->onUpdate("cascade");
            $table->foreign("payment_gateway_currency_id")->references("id")->on("payment_gateway_currencies")->onDelete("cascade")->onUpdate("cascade");
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade")->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
