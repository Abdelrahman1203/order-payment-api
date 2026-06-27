<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
            $table->string('payment_method');
            $table->decimal('amount', 10, 2);
            $table->string('gateway_reference')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
