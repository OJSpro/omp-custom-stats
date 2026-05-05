<?php

/**
 * @file plugins/generic/customStats/classes/CustomStatsHandler.inc.php
 *
 * Custom Statistics Plugin - Page Handler
 *
 * Handles AJAX requests for saving and retrieving custom statistics offsets.
 */

use APP\core\Application;
use APP\core\Request;
use APP\handler\Handler;
use PKP\core\JSONMessage;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;

class CustomStatsHandler extends Handler
{
    /** @var CustomStatsPlugin */
    public $plugin;

    /**
     * Set the plugin
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Get custom statistics offsets for a submission
     */
    public function getOffsets($args, $request)
    {
        $submissionId = (int) $request->getUserVar('submissionId');
        $context = $request->getContext();
        
        if (!$submissionId || !$context) {
            return new JSONMessage(false, __('plugins.generic.customStats.saveError'));
        }

        // Check user has appropriate role
        $user = $request->getUser();
        if (!$this->_hasEditPermission($user, $context->getId())) {
            return new JSONMessage(false, 'Unauthorized');
        }

        $customStatsDao = new \CustomStatsDAO();
        $offsets = $customStatsDao->getOffsets($submissionId, $context->getId());

        if (!$offsets) {
            $offsets = [
                'abstract_views_offset' => 0,
                'file_downloads_offset' => 0,
            ];
        }

        return new JSONMessage(true, $offsets);
    }

    /**
     * Save custom statistics offsets for a submission
     */
    public function saveOffsets($args, $request)
    {
        $submissionId = (int) $request->getUserVar('submissionId');
        $abstractViewsOffset = (int) $request->getUserVar('abstractViewsOffset');
        $fileDownloadsOffset = (int) $request->getUserVar('fileDownloadsOffset');
        
        $context = $request->getContext();
        
        if (!$submissionId || !$context) {
            return new JSONMessage(false, __('plugins.generic.customStats.saveError'));
        }

        // Validate inputs
        if ($abstractViewsOffset < 0 || $fileDownloadsOffset < 0) {
            return new JSONMessage(false, __('plugins.generic.customStats.validation.positive'));
        }

        // Check user has appropriate role
        $user = $request->getUser();
        if (!$this->_hasEditPermission($user, $context->getId())) {
            return new JSONMessage(false, 'Unauthorized');
        }

        $customStatsDao = new \CustomStatsDAO();
        $success = $customStatsDao->saveOffsets(
            $submissionId,
            $context->getId(),
            $abstractViewsOffset,
            $fileDownloadsOffset
        );

        if ($success) {
            return new JSONMessage(true, [
                'message' => __('plugins.generic.customStats.saveSuccess'),
                'offsets' => [
                    'abstract_views_offset' => $abstractViewsOffset,
                    'file_downloads_offset' => $fileDownloadsOffset,
                ]
            ]);
        } else {
            return new JSONMessage(false, __('plugins.generic.customStats.saveError'));
        }
    }

    /**
     * Check if user has permission to edit custom statistics
     */
    private function _hasEditPermission($user, $contextId)
    {
        if (!$user) {
            return false;
        }

        $userRoles = $user->getRoles($contextId);
        $allowedRoles = [
            Role::ROLE_ID_MANAGER,
            Role::ROLE_ID_SITE_ADMIN,
            Role::ROLE_ID_SUB_EDITOR
        ];

        foreach ($userRoles as $role) {
            $roleId = is_object($role) ? $role->getRoleId() : $role;
            if (in_array($roleId, $allowedRoles)) {
                return true;
            }
        }

        return false;
    }
}
