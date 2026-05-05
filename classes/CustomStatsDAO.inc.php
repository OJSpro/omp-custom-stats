<?php

/**
 * @file plugins/generic/customStats/classes/CustomStatsDAO.inc.php
 *
 * Custom Statistics Plugin - Data Access Object
 *
 * Manages custom statistics offsets in the database.
 */

use Illuminate\Support\Facades\DB;

class CustomStatsDAO
{
    /**
     * Get custom statistics offsets for a submission
     *
     * @param int $submissionId
     * @param int $contextId
     * @return array|null Array with 'abstract_views_offset' and 'file_downloads_offset', or null if not found
     */
    public function getOffsets(int $submissionId, int $contextId): ?array
    {
        $result = DB::table('custom_stats_offsets')
            ->where('submission_id', '=', $submissionId)
            ->where('context_id', '=', $contextId)
            ->first();

        if ($result) {
            return [
                'abstract_views_offset' => (int) $result->abstract_views_offset,
                'file_downloads_offset' => (int) $result->file_downloads_offset,
                'date_created' => $result->date_created,
                'date_modified' => $result->date_modified,
            ];
        }

        return null;
    }

    /**
     * Save or update custom statistics offsets for a submission
     *
     * @param int $submissionId
     * @param int $contextId
     * @param int $abstractViewsOffset
     * @param int $fileDownloadsOffset
     * @return bool Success status
     */
    public function saveOffsets(
        int $submissionId,
        int $contextId,
        int $abstractViewsOffset,
        int $fileDownloadsOffset
    ): bool {
        $now = date('Y-m-d H:i:s');
        
        $existing = $this->getOffsets($submissionId, $contextId);
        
        if ($existing) {
            // Update existing record
            return DB::table('custom_stats_offsets')
                ->where('submission_id', '=', $submissionId)
                ->where('context_id', '=', $contextId)
                ->update([
                    'abstract_views_offset' => $abstractViewsOffset,
                    'file_downloads_offset' => $fileDownloadsOffset,
                    'date_modified' => $now,
                ]) !== false;
        } else {
            // Insert new record
            return DB::table('custom_stats_offsets')
                ->insert([
                    'submission_id' => $submissionId,
                    'context_id' => $contextId,
                    'abstract_views_offset' => $abstractViewsOffset,
                    'file_downloads_offset' => $fileDownloadsOffset,
                    'date_created' => $now,
                    'date_modified' => null,
                ]);
        }
    }

    /**
     * Delete custom statistics offsets for a submission
     *
     * @param int $submissionId
     * @param int $contextId
     * @return bool Success status
     */
    public function deleteOffsets(int $submissionId, int $contextId): bool
    {
        return DB::table('custom_stats_offsets')
            ->where('submission_id', '=', $submissionId)
            ->where('context_id', '=', $contextId)
            ->delete() !== false;
    }

    /**
     * Get all submissions with custom offsets for a context
     *
     * @param int $contextId
     * @return array
     */
    public function getAllOffsetsForContext(int $contextId): array
    {
        $results = DB::table('custom_stats_offsets')
            ->where('context_id', '=', $contextId)
            ->get();

        $offsets = [];
        foreach ($results as $result) {
            $offsets[$result->submission_id] = [
                'abstract_views_offset' => (int) $result->abstract_views_offset,
                'file_downloads_offset' => (int) $result->file_downloads_offset,
            ];
        }

        return $offsets;
    }
    
    /**
     * Get custom statistics offsets for a chapter
     * @param int $chapterId
     * @param int $submissionId
     * @param int $contextId
     * @return array
     */
    public function getChapterOffsets($chapterId, $submissionId, $contextId)
    {
        $result = DB::table('custom_stats_offsets')
            ->where('chapter_id', '=', $chapterId)
            ->where('submission_id', '=', $submissionId)
            ->where('context_id', '=', $contextId)
            ->whereNotNull('chapter_id')
            ->first();
        
        if ($result) {
            return [
                'chapter_views_offset' => (int) $result->chapter_views_offset,
            ];
        }
        
        return [
            'chapter_views_offset' => 0,
        ];
    }
    
    /**
     * Save custom statistics offsets for a chapter
     * @param int $chapterId
     * @param int $submissionId
     * @param int $contextId
     * @param int $chapterViewsOffset
     * @return bool
     */
    public function saveChapterOffsets($chapterId, $submissionId, $contextId, $chapterViewsOffset)
    {
        $now = date('Y-m-d H:i:s');
        
        // Check if record exists
        $exists = DB::table('custom_stats_offsets')
            ->where('chapter_id', '=', $chapterId)
            ->where('submission_id', '=', $submissionId)
            ->where('context_id', '=', $contextId)
            ->exists();
        
        if ($exists) {
            // Update existing record
            return DB::table('custom_stats_offsets')
                ->where('chapter_id', '=', $chapterId)
                ->where('submission_id', '=', $submissionId)
                ->where('context_id', '=', $contextId)
                ->update([
                    'chapter_views_offset' => $chapterViewsOffset,
                    'date_modified' => $now,
                ]) !== false;
        } else {
            // Insert new record
            return DB::table('custom_stats_offsets')
                ->insert([
                    'chapter_id' => $chapterId,
                    'submission_id' => $submissionId,
                    'context_id' => $contextId,
                    'abstract_views_offset' => 0,
                    'file_downloads_offset' => 0,
                    'chapter_views_offset' => $chapterViewsOffset,
                    'date_created' => $now,
                    'date_modified' => null,
                ]);
        }
    }
}
