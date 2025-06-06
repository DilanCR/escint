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
        Schema::create('escrow_conversation_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("escrow_chat_id"); 
            $table->string("attachment",255)->nullable();
            $table->text("attachment_info",1000)->nullable();
            $table->timestamps();

            $table->foreign("escrow_chat_id")->references("id")->on("escrow_chats")->onDelete("cascade")->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('escrow_conversation_attachments');
    }
};
