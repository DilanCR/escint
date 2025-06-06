<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('escrows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('escrow_category_id');
            $table->unsignedBigInteger('payment_gateway_currency_id')->nullable();
            $table->string('escrow_id')->comment('Escrow ID');
            $table->integer('payment_type')->comment('"1" => "Wallet", "2" => "gateway", "3" => "did not paid"');
            $table->string('role');
            $table->string('who_will_pay');
            $table->integer('buyer_or_seller_id');
            $table->decimal('amount', 28, 8);
            $table->string('escrow_currency');
            $table->string('title');
            $table->text('remark')->nullable();
            $table->text('file')->nullable();
            $table->tinyInteger('status')->comment(' "1" => "Ongoing", "2" => "Payment Pending", "3" => "Approvel Pending", "4" => "Released", "5" => "Active Dispute", "6" => "Disputed", "7" => "Canceled", "8" => "Refunded" ');
            $table->text('details')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            
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
        Schema::dropIfExists('escrows');
    }
};
