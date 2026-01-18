<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_ad_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_ad_batches', 'cancel_requested_at')) {
                $table->timestamp('cancel_requested_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('meta_ad_batches', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancel_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meta_ad_batches', function (Blueprint $table) {
            if (Schema::hasColumn('meta_ad_batches', 'cancel_requested_at')) {
                $table->dropColumn('cancel_requested_at');
            }
            if (Schema::hasColumn('meta_ad_batches', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
};
