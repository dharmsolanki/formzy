<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Stable key and system flag for every form field
        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('key', 40)->nullable()->after('form_id');
            $table->boolean('is_system')->default(false)->after('key');
        });

        // 2. Give existing fields a key (f + id), so no row is left empty
        foreach (DB::table('form_fields')->select('id')->get() as $row) {
            DB::table('form_fields')->where('id', $row->id)->update(['key' => 'f' . $row->id]);
        }

        // 3. A key must be unique inside one form
        Schema::table('form_fields', function (Blueprint $table) {
            $table->unique(['form_id', 'key']);
        });

        // 4. Customer contact columns on submissions (searchable, indexed)
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('customer_email')->nullable()->after('data');
            $table->string('customer_mobile', 10)->nullable()->after('customer_email');

            $table->index('customer_email');
            $table->index('customer_mobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropIndex(['customer_email']);
            $table->dropIndex(['customer_mobile']);
            $table->dropColumn(['customer_email', 'customer_mobile']);
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropUnique(['form_id', 'key']);
            $table->dropColumn(['key', 'is_system']);
        });
    }
};