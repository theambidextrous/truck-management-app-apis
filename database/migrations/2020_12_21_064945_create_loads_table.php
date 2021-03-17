<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loads', function (Blueprint $table) {
            $table->id();
            $table->string('dispatcher', 5);
            /** pickup */
            $table->string('date', 15);
            $table->string('bol', 55)->unique();
            $table->string('company', 55);
            $table->string('contact', 55)->nullable();
            $table->string('street', 255);
            $table->string('city', 55);
            $table->string('state', 3);
            $table->string('zip', 6);
            $table->string('broker', 55);
            /** delivery */
            $table->string('d_date', 55);
            $table->string('pol', 55)->unique();
            $table->string('d_company', 55);
            $table->string('d_contact', 55)->nullable();
            $table->string('d_street', 255);
            $table->string('d_city', 55);
            $table->string('d_state', 3);
            $table->string('d_zip', 6);
            $table->text('delivery_docs')->nullable();
            /** universal */
            $table->string('truck', 30);
            $table->string('trailer', 55);
            $table->string('miles', 10);
            $table->string('weight', 10);
            $table->string('rate', 10);
            $table->string('driver_a', 10);
            $table->string('driver_b', 10)->nullable();
            $table->boolean('is_delivered')->default(false);
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
        Schema::dropIfExists('loads');
    }
}
