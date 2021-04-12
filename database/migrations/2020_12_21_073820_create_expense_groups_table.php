<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateExpenseGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expense_groups', function (Blueprint $table) {
            $table->id();
            $table->string('account', 16)->default(1);
            $table->string('name', 55);
            $table->string('description')->nullable();
            $table->boolean('editable')->default(true);
            $table->timestamps();
        });

        $path = storage_path('app/expgrp.txt');
        $data = file($path, FILE_IGNORE_NEW_LINES);
        $final = [];
        foreach( $data as $grp ){
            $exp = explode("~~", $grp);
            $entry = [ 
                'id' => $exp[0],
                'name' => $exp[1],
                'description' => $exp[2],
                'editable' => $exp[3],
            ];
            array_push($final, $entry);
        }
        DB::table('expense_groups')->insert($final);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expense_groups');
    }
}
