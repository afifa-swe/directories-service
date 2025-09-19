<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('budget_holders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tin');
            $table->string('name');
            $table->string('region');
            $table->string('district');
            $table->string('address');
            $table->string('phone');
            $table->string('responsible');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('budget_holders');
    }
};
