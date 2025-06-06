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
        Schema::create('escrow_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('escrow_id'); 
            $table->decimal('fee', 28, 8);
            $table->decimal('seller_get', 28, 8);
            $table->decimal('buyer_pay', 28, 8);
            $table->decimal('gateway_exchange_rate', 28, 8);
            $table->timestamps();

            $table->foreign("escrow_id")->references("id")->on("escrows")->onDelete("cascade")->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('escrow_details');
    }
};
