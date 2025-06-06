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
        Schema::table('setup_pages', function (Blueprint $table) {
            $table->string('type',50)->default('setup-page')->after('id');
            $table->longText('details')->nullable()->after('url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('setup_pages', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('details');
        });
    }
};
