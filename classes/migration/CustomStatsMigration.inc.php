<?php

/**
 * @file plugins/generic/customStats/classes/migration/CustomStatsMigration.inc.php
 *
 * Custom Statistics Plugin - Database Migration
 *
 * Creates the custom_stats_offsets table for storing legacy statistics offsets.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomStatsMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('custom_stats_offsets')) {
            Schema::create('custom_stats_offsets', function (Blueprint $table) {
                $table->comment('Custom statistics offsets for migrating legacy usage data.');
                $table->bigIncrements('custom_stats_offset_id');
                
                $table->bigInteger('submission_id');
                $table->foreign('submission_id')->references('submission_id')->on('submissions')->onDelete('cascade');
                $table->index(['submission_id'], 'custom_stats_offsets_submission_id');
                
                $table->bigInteger('context_id');
                $table->foreign('context_id')->references('press_id')->on('presses')->onDelete('cascade');
                $table->index(['context_id'], 'custom_stats_offsets_context_id');
                
                $table->integer('abstract_views_offset')->default(0);
                $table->integer('file_downloads_offset')->default(0);
                
                $table->datetime('date_created');
                $table->datetime('date_modified')->nullable();
                
                $table->unique(['submission_id', 'context_id'], 'custom_stats_offsets_submission_context');
            });
        }
    }

    /**
     * Reverse the migration.
     */
    public function down()
    {
        Schema::dropIfExists('custom_stats_offsets');
    }
}
