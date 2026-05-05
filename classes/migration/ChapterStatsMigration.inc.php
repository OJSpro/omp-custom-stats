<?php

/**
 * @file plugins/generic/customStats/classes/migration/ChapterStatsMigration.inc.php
 *
 * Custom Statistics Plugin - Chapter Statistics Migration
 *
 * Adds chapter_id and chapter_views_offset columns to support chapter-level statistics.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChapterStatsMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('custom_stats_offsets')) {
            Schema::table('custom_stats_offsets', function (Blueprint $table) {
                // Add chapter_id column (nullable for submission-level stats)
                if (!Schema::hasColumn('custom_stats_offsets', 'chapter_id')) {
                    $table->bigInteger('chapter_id')->nullable()->after('submission_id');
                    $table->foreign('chapter_id')->references('chapter_id')->on('submission_chapters')->onDelete('cascade');
                    $table->index(['chapter_id'], 'custom_stats_offsets_chapter_id');
                }
                
                // Add chapter_views_offset column
                if (!Schema::hasColumn('custom_stats_offsets', 'chapter_views_offset')) {
                    $table->integer('chapter_views_offset')->default(0)->after('file_downloads_offset');
                }
            });
            
            // Drop old unique constraint and create new one including chapter_id
            Schema::table('custom_stats_offsets', function (Blueprint $table) {
                $table->dropUnique('custom_stats_offsets_submission_context');
                $table->unique(['submission_id', 'context_id', 'chapter_id'], 'custom_stats_offsets_unique');
            });
        }
    }

    /**
     * Reverse the migration.
     */
    public function down()
    {
        if (Schema::hasTable('custom_stats_offsets')) {
            Schema::table('custom_stats_offsets', function (Blueprint $table) {
                $table->dropUnique('custom_stats_offsets_unique');
                $table->dropForeign(['chapter_id']);
                $table->dropColumn(['chapter_id', 'chapter_views_offset']);
                $table->unique(['submission_id', 'context_id'], 'custom_stats_offsets_submission_context');
            });
        }
    }
}
