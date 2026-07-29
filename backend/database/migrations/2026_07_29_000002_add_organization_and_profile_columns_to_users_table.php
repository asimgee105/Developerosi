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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('active_organization_id')->nullable()->after('remember_token');
            $table->string('avatar_url')->nullable()->after('active_organization_id');

            $table->foreign('active_organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_organization_id']);
            $table->dropColumn(['active_organization_id', 'avatar_url']);
        });
    }
};
