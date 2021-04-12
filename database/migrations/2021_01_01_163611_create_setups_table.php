<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setups', function (Blueprint $table) {
            $table->id();
            $table->string('account', 16)->unique();
            $table->string('active_from', 30);
            $table->string('active_to', 30);
            $table->string('company', 55)->nullable();
            $table->string('custodian_email', 55);
            $table->string('address', 55)->nullable();
            $table->string('city', 30)->nullable();
            $table->string('state', 35)->nullable();
            $table->string('zip', 5)->nullable();
            $table->string('email', 55)->nullable();
            $table->string('phone', 16)->nullable();
            $table->string('fax', 16)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('setups');
    }
}
