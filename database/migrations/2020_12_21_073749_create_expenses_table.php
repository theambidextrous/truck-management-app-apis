<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('type', 55);
            $table->string('truck', 55);
            $table->string('amount', 55);
            $table->string('description', 55);
            $table->string('startdate', 55)->nullable();
            $table->string('enddate', 55)->nullable();
            $table->string('frequency', 55)->nullable();
            $table->string('limit', 55)->nullable();
            $table->string('city', 55)->nullable();
            $table->string('state', 55)->nullable();
            $table->string('misc_amount', 55)->nullable();
            $table->boolean('is_active', 55)->default(true);
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
        Schema::dropIfExists('expenses');
    }
}
