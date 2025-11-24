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
        Schema::create('inventory', function (Blueprint $table) {
            $table->string('product_id')->primary();
            $table->string('name');
            $table->integer('stock');
            $table->timestamps();
        });
        // For this design, we assume that we are using a single warehouse, so we dont support mutiple warehouses with multiple locations
        // if we want to support multiple warehouses, or multiple locations, then will do pivot table
        // something like product, warehouse [many to many with inventory table at the middle]
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
