<?php

/**
 * @file plugins/generic/customStats/classes/CustomStatsHelper.inc.php
 *
 * Custom Statistics Plugin - Helper Class
 *
 * Provides utility methods for applying custom offsets to statistics.
 */

class CustomStatsHelper
{
    /**
     * Apply custom offsets to statistics array
     *
     * @param array $stats Original statistics array with keys like 'abstract', 'pdf', 'html', 'other'
     * @param int $submissionId
     * @param int $contextId
     * @return array Modified statistics with offsets applied
     */
    public static function applyOffsets(array $stats, int $submissionId, int $contextId): array
    {
        $customStatsDao = new \CustomStatsDAO();
        $offsets = $customStatsDao->getOffsets($submissionId, $contextId);
        
        if (!$offsets) {
            return $stats;
        }
        
        // Apply abstract views offset
        if (isset($stats['abstract']) && $offsets['abstract_views_offset'] > 0) {
            $stats['abstract'] += $offsets['abstract_views_offset'];
        }
        
        // Apply file downloads offset proportionally across file types
        if ($offsets['file_downloads_offset'] > 0) {
            $totalDownloads = ($stats['pdf'] ?? 0) + ($stats['html'] ?? 0) + ($stats['other'] ?? 0);
            
            if ($totalDownloads > 0) {
                // Distribute offset proportionally
                $pdfRatio = ($stats['pdf'] ?? 0) / $totalDownloads;
                $htmlRatio = ($stats['html'] ?? 0) / $totalDownloads;
                $otherRatio = ($stats['other'] ?? 0) / $totalDownloads;
                
                if (isset($stats['pdf'])) {
                    $stats['pdf'] += (int) ($offsets['file_downloads_offset'] * $pdfRatio);
                }
                if (isset($stats['html'])) {
                    $stats['html'] += (int) ($offsets['file_downloads_offset'] * $htmlRatio);
                }
                if (isset($stats['other'])) {
                    $stats['other'] += (int) ($offsets['file_downloads_offset'] * $otherRatio);
                }
            } else {
                // No existing downloads, add all to PDF
                if (isset($stats['pdf'])) {
                    $stats['pdf'] += $offsets['file_downloads_offset'];
                } else {
                    $stats['pdf'] = $offsets['file_downloads_offset'];
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get total file downloads including offset
     *
     * @param array $stats Statistics array
     * @return int Total downloads
     */
    public static function getTotalDownloads(array $stats): int
    {
        return ($stats['pdf'] ?? 0) + ($stats['html'] ?? 0) + ($stats['other'] ?? 0);
    }
}
