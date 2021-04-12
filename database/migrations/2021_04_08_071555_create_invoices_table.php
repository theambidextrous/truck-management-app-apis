<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('account', 16);
            $table->string('address', 55);
            $table->string('invoice_amount', 10);
            $table->string('paid_amount', 10)->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->string('next_auto_charge', 30);
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
        Schema::dropIfExists('invoices');
    }
}
