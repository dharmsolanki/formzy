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
        // Labels the customer saw when answering, stored as { key: label }
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->json('field_labels')->nullable()->after('data');
        });

        // Removed fields are archived, not deleted
        Schema::table('form_fields', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('order');
        });

        // Changes whenever the fields of a form change, so stale open forms can be detected
        Schema::table('forms', function (Blueprint $table) {
            $table->unsignedInteger('fields_version')->default(1)->after('success_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('fields_version');
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn('field_labels');
        });
    }
};