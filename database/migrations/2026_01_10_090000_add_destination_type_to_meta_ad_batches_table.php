<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('meta_ad_batches', 'destination_type')) {
            Schema::table('meta_ad_batches', function (Blueprint $table) {
                $table->string('destination_type')->nullable()->after('objective');
            });
        }

        $this->backfillDestinationType();
    }

    public function down(): void
    {
        if (Schema::hasColumn('meta_ad_batches', 'destination_type')) {
            Schema::table('meta_ad_batches', function (Blueprint $table) {
                $table->dropColumn('destination_type');
            });
        }
    }

    private function backfillDestinationType(): void
    {
        DB::table('meta_ad_batches')
            ->select('id', 'settings')
            ->whereNull('destination_type')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $settings = $row->settings;
                    if (is_string($settings)) {
                        $decoded = json_decode($settings, true);
                        $settings = is_array($decoded) ? $decoded : [];
                    }

                    if (!is_array($settings)) {
                        continue;
                    }

                    $destinationType = $settings['destination_type'] ?? null;
                    if (!$destinationType) {
                        continue;
                    }

                    DB::table('meta_ad_batches')
                        ->where('id', $row->id)
                        ->update(['destination_type' => $destinationType]);
                }
            });
    }
};
