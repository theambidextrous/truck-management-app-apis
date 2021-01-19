<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('fname', 30);
            $table->string('lname', 30);
            $table->string('address', 55);
            $table->string('city', 30);
            $table->string('state', 35);
            $table->string('zip', 5);
            $table->string('email', 55);
            $table->string('phone', 16);
            $table->string('license', 30);
            $table->string('experience', 2);
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
        Schema::dropIfExists('drivers');
    }
}
