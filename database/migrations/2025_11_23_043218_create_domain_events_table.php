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
        Schema::create('domain_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');           // 'App\Events\OrderPlaced'
            $table->string('aggregate_type');       // 'Order'
            $table->string('aggregate_id');         // '123'
            $table->json('payload');                // Event data
            $table->json('metadata')->nullable();   // User ID, IP, etc for debugging and audit trail purpose
            $table->timestamp('created_at');
            $table->timestamp('processed_at')->nullable();

            $table->index(['aggregate_type', 'aggregate_id']); // use case is that we want to get all the rows from aggregate_type and aggregate_id to rebuild order state
            $table->index('event_type'); // use case is when we want to do analytics for all the OrderPlaced of the day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_events');
    }
};
