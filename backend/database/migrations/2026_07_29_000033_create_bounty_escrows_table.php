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
        Schema::create('bounty_escrows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('issue_id'); // maps to tasks table (the sprint ticket)
            $table->string('smart_contract_address');
            $table->string('client_wallet');
            $table->string('dev_wallet');
            $table->decimal('amount_usdc', 15, 2);
            $table->string('status')->default('Locked'); // Locked, Disputed, Released
            $table->string('oracle_tx_hash')->nullable();
            $table->timestamps();

            $table->foreign('issue_id')
                ->references('id')
                ->on('tasks')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bounty_escrows');
    }
};
