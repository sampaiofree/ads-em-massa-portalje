<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('meta_ad_batches', 'error_message')) {
            Schema::table('meta_ad_batches', function (Blueprint $table) {
                $table->text('error_message')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('meta_ad_batches', 'error_message')) {
            Schema::table('meta_ad_batches', function (Blueprint $table) {
                $table->dropColumn('error_message');
            });
        }
    }
};
