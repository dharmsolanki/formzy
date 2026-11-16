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
        Schema::table('form_fields', function (Blueprint $table) {
            // Value already filled in for the customer (checkbox: options separated by commas)
            $table->text('default_value')->nullable()->after('options');
            $table->string('placeholder', 100)->nullable()->after('default_value');
            $table->string('help_text', 200)->nullable()->after('placeholder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn(['default_value', 'placeholder', 'help_text']);
        });
    }
};