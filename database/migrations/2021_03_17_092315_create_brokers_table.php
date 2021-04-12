<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrokersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brokers', function (Blueprint $table) {
            $table->id();
            $table->string('account', 16)->default(1);
            $table->string('name',55);
            $table->string('email', 30)->nullable();
            $table->string('phone', 15)->nullable();
            $table->timestamps();
        });
        $path = storage_path('app/brokers.txt');
        $data = file($path, FILE_IGNORE_NEW_LINES);
        $final = [];
        foreach( $data as $brk ){
            $entry = [ 'name' => $brk ];
            array_push($final, $entry);
        }
        DB::table('brokers')->insert($final);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brokers');
    }
}
