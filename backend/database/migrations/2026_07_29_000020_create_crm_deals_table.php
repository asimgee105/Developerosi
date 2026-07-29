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
        Schema::create('crm_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('contact_id');
            $table->string('title');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('stage')->default('lead'); // lead, contacted, qualified, proposal, won, lost
            $table->integer('probability_percentage')->default(10);
            $table->date('expected_close_date')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table->foreign('contact_id')
                ->references('id')
                ->on('crm_contacts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_deals');
    }
};
