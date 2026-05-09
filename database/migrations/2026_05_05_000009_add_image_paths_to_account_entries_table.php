<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_entries', function (Blueprint $table) {
            $table->json('image_paths')->nullable()->after('image_path');
        });

        DB::table('account_entries')
            ->whereNotNull('image_path')
            ->orderBy('id')
            ->get(['id', 'image_path'])
            ->each(function ($entry): void {
                DB::table('account_entries')
                    ->where('id', $entry->id)
                    ->update([
                        'image_paths' => json_encode([$entry->image_path], JSON_UNESCAPED_UNICODE),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('account_entries', function (Blueprint $table) {
            $table->dropColumn('image_paths');
        });
    }
};