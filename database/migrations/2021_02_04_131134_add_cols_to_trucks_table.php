<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsToTrucksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->string('insurance_expires', 12)->nullable();
            $table->string('inspection_expires', 12)->nullable();
            $table->string('registration_expires', 12)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trucks', function (Blueprint $table) {
            //
        });
    }
}
