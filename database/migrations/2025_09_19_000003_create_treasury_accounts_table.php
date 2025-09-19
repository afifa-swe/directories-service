<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('treasury_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('account');
            $table->string('mfo');
            $table->string('name');
            $table->string('department');
            $table->string('currency');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('treasury_accounts');
    }
};
