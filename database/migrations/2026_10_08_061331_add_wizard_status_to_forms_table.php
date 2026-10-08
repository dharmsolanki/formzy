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
        Schema::table('forms', function (Blueprint $table) {
            $table->unsignedTinyInteger('wizard_step')->default(1)->after('is_active');
            $table->boolean('is_complete')->default(false)->after('wizard_step');
            $table->text('success_message')->nullable()->after('is_complete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['wizard_step', 'is_complete', 'success_message']);
        });
    }
};