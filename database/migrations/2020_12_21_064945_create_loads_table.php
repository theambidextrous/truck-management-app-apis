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
            $table->string('booking_date', 255);
            $table->string('number', 55)->unique();
            $table->string('origin', 55);
            $table->string('destination', 55);
            $table->string('milage', 5)->default(0.0);
            $table->string('rate', 10);
            $table->string('weight', 10);
            $table->string('truck', 30);
            $table->string('driver', 30);
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
        Schema::dropIfExists('loads');
    }
}
