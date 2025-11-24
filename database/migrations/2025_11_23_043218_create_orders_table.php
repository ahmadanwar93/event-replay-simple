<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('product_id'); // we are not creating product table, hence string is fine
            $table->integer('quantity'); // auto increment use case for integer column type is on primary key
            // quantity is the amount of item physical wise for the order
            $table->decimal('total', 10, 2); // total is total price
            $table->string('status')->default('pending'); // will do the check on the application layer, NOT on db layer
            // the values that we will saved are pending, shipped and cancelled
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
