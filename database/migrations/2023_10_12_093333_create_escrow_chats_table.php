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
        Schema::create('escrow_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("escrow_id");
            $table->unsignedBigInteger("sender");
            $table->string("sender_type");
            $table->unsignedBigInteger("receiver")->nullable();
            $table->string("receiver_type")->nullable();
            $table->text("message")->nullable();
            $table->boolean("seen")->default(false);
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
        Schema::dropIfExists('escrow_chats');
    }
};
