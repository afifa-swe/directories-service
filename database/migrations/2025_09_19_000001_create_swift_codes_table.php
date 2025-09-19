<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('swift_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('swift_code');
            $table->string('bank_name');
            $table->string('country');
            $table->string('city');
            $table->string('address');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('swift_codes');
    }
};
