<?php

/**
 * @file plugins/generic/customStats/CustomStatsPlugin.php
 *
 * Custom Statistics Plugin
 *
 * Allows editors to add custom offset values to usage statistics for publications,
 * enabling migration of legacy statistics from previous systems.
 */

namespace APP\plugins\generic\customStats;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\security\Role;

class CustomStatsPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && $this->getEnabled($mainContextId)) {
            // Register AJAX handler
            Hook::add('LoadHandler', [$this, 'handleAjaxRequest']);
            
            // Add custom statistics tab to publication workflow
            Hook::add('Template::Workflow::Publication', [$this, 'addCustomStatsTab']);

            // Hook into template display to modify statistics shown to users
            Hook::add('TemplateManager::display', [$this, 'modifyTemplateStatistics']);
            
            // Hook into API responses for statistics
            Hook::add('API::stats::publications::params', [$this, 'modifyApiStatistics']);
        }

        return $success;
    }
    
    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        if ($request->getUserVar('verb') === 'upgradeChapterStats') {
            // Run the chapter statistics migration
            try {
                $this->import('classes.migration.ChapterStatsMigration');
                $migration = new \ChapterStatsMigration();
                $migration->up();
                
                // Enable chapter stats in plugin settings
                $this->updateSetting($request->getContext()->getId(), 'chapterStatsEnabled', true);
                
                return new \PKP\core\JSONMessage(true, __('plugins.generic.customStats.upgrade.success'));
            } catch (\Exception $e) {
                return new \PKP\core\JSONMessage(false, $e->getMessage());
            }
        }
        
        if ($request->getUserVar('verb') === 'toggleChapterStats') {
            $contextId = $request->getContext()->getId();
            $currentState = $this->getSetting($contextId, 'chapterStatsEnabled');
            $newState = !$currentState;
            
            $this->updateSetting($contextId, 'chapterStatsEnabled', $newState);
            
            $message = $newState 
                ? __('plugins.generic.customStats.chapterStats.enabled')
                : __('plugins.generic.customStats.chapterStats.disabled');
            
            return new \PKP\core\JSONMessage(true, $message);
        }
        
        return parent::manage($args, $request);
    }

    /**
     * Handle AJAX requests for saving custom statistics
     */
    public function handleAjaxRequest($hookName, $params)
    {
        $page = $params[0];
        $op = $params[1];
        
        if ($page === 'customStats' && $op === 'save') {
            // Handle the save request directly
            header('Content-Type: application/json');
            
            $request = Application::get()->getRequest();
            $context = $request->getContext();
            $user = $request->getUser();
            
            if (!$context || !$user) {
                echo json_encode(['status' => false, 'content' => 'Unauthorized']);
                exit;
            }
            
            // Check permissions
            $userRoles = $user->getRoles($context->getId());
            $allowedRoles = [
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_SUB_EDITOR
            ];
            
            $hasPermission = false;
            foreach ($userRoles as $role) {
                $roleId = is_object($role) ? $role->getRoleId() : $role;
                if (in_array($roleId, $allowedRoles)) {
                    $hasPermission = true;
                    break;
                }
            }
            
            if (!$hasPermission) {
                echo json_encode(['status' => false, 'content' => 'Unauthorized']);
                exit;
            }
            
            // Get parameters
            $submissionId = (int) $request->getUserVar('submissionId');
            $abstractViewsOffset = (int) $request->getUserVar('abstractViewsOffset');
            $fileDownloadsOffset = (int) $request->getUserVar('fileDownloadsOffset');
            
            if (!$submissionId) {
                echo json_encode(['status' => false, 'content' => __('plugins.generic.customStats.saveError')]);
                exit;
            }
            
            // Validate inputs
            if ($abstractViewsOffset < 0 || $fileDownloadsOffset < 0) {
                echo json_encode(['status' => false, 'content' => __('plugins.generic.customStats.validation.positive')]);
                exit;
            }
            
            // Save to database
            $this->import('classes.CustomStatsDAO');
            $customStatsDao = new \CustomStatsDAO();
            $success = $customStatsDao->saveOffsets(
                $submissionId,
                $context->getId(),
                $abstractViewsOffset,
                $fileDownloadsOffset
            );
            
            // Save chapter offsets if provided
            $chapterOffsets = $request->getUserVar('chapterOffsets');
            if ($chapterOffsets && is_array($chapterOffsets)) {
                foreach ($chapterOffsets as $chapterId => $offset) {
                    $customStatsDao->saveChapterOffsets(
                        (int) $chapterId,
                        $submissionId,
                        $context->getId(),
                        (int) $offset
                    );
                }
            }
            
            if ($success) {
                echo json_encode([
                    'status' => true,
                    'content' => [
                        'message' => __('plugins.generic.customStats.saveSuccess'),
                        'offsets' => [
                            'abstract_views_offset' => $abstractViewsOffset,
                            'file_downloads_offset' => $fileDownloadsOffset,
                        ]
                    ]
                ]);
            } else {
                echo json_encode(['status' => false, 'content' => __('plugins.generic.customStats.saveError')]);
            }
            exit;
        }
        
        return false;
    }

    /**
     * Add custom statistics tab to the publication workflow
     */
    public function addCustomStatsTab($hookName, $params)
    {
        $smarty = $params[1];
        $output = &$params[2];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $user = $request->getUser();
        $submission = $smarty->getTemplateVars('submission');

        if (!$submission || !$user || !$context) {
            return false;
        }

        // Check if user has appropriate role
        $userRoles = $user->getRoles($context->getId());
        $allowedRoles = [
            Role::ROLE_ID_MANAGER,
            Role::ROLE_ID_SITE_ADMIN,
            Role::ROLE_ID_SUB_EDITOR
        ];

        $hasPermission = false;
        foreach ($userRoles as $role) {
            $roleId = is_object($role) ? $role->getRoleId() : $role;
            if (in_array($roleId, $allowedRoles)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            return false;
        }

        // Get current offsets
        $this->import('classes.CustomStatsDAO');
        $customStatsDao = new \CustomStatsDAO();
        $offsets = $customStatsDao->getOffsets($submission->getId(), $context->getId());
        
        if (!$offsets) {
            $offsets = [
                'abstract_views_offset' => 0,
                'file_downloads_offset' => 0,
            ];
        }

        // Get current OMP statistics directly from database
        $ompAbstractViews = 0;
        $ompFileDownloads = 0;
        
        try {
            // Get abstract views
            $abstractResult = \Illuminate\Support\Facades\DB::table('metrics_submission')
                ->where('submission_id', '=', $submission->getId())
                ->where('context_id', '=', $context->getId())
                ->where('assoc_type', '=', \PKP\core\PKPApplication::ASSOC_TYPE_SUBMISSION)
                ->sum('metric');
            $ompAbstractViews = (int) $abstractResult;
            
            // Get file downloads (all file types)
            $downloadsResult = \Illuminate\Support\Facades\DB::table('metrics_submission')
                ->where('submission_id', '=', $submission->getId())
                ->where('context_id', '=', $context->getId())
                ->where('assoc_type', '=', \PKP\core\PKPApplication::ASSOC_TYPE_SUBMISSION_FILE)
                ->sum('metric');
            $ompFileDownloads = (int) $downloadsResult;
        } catch (\Exception $e) {
            // If stats retrieval fails, just use 0
            $ompAbstractViews = 0;
            $ompFileDownloads = 0;
        }

        // Prepare template variables
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'submissionId' => $submission->getId(),
            'abstractViewsOffset' => $offsets['abstract_views_offset'],
            'fileDownloadsOffset' => $offsets['file_downloads_offset'],
            'ompAbstractViews' => $ompAbstractViews,
            'ompFileDownloads' => $ompFileDownloads,
            'saveUrl' => $request->getDispatcher()->url(
                $request,
                \PKP\core\PKPApplication::ROUTE_PAGE,
                null,
                'customStats',
                'save'
            ),
            'pluginJsUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/customStatsForm.js',
        ]);
        
        // Get publication from submission (more reliable than template variable)
        $publication = $submission->getCurrentPublication();
        if (!$publication) {
            // No publication available, skip chapter stats
            $output .= $templateMgr->fetch($this->getTemplateResource('customStatsTab.tpl'));
            return false;
        }
        
        // Get all chapters for this publication
        $chapterDao = \PKP\db\DAORegistry::getDAO('ChapterDAO');
        $chapters = $chapterDao->getByPublicationId($publication->getId());
        
        // Check if chapter stats are enabled
        $chapterStatsEnabled = $this->getSetting($context->getId(), 'chapterStatsEnabled');
        
        // Get chapter offsets and OMP stats
        $this->import('classes.CustomStatsDAO');
        $customStatsDao = new \CustomStatsDAO();
        
        $chaptersData = [];
        
        // Only fetch chapter data if feature is enabled
        if ($chapterStatsEnabled) {
        
        // Wrap in try-catch in case migration hasn't run yet
        try {
            while ($chapter = $chapters->next()) {
                $chapterId = $chapter->getId();
                
                // Get chapter offsets
                $chapterOffsets = $customStatsDao->getChapterOffsets($chapterId, $submission->getId(), $context->getId());
                
                // Get OMP chapter views
                $ompChapterViews = (int) \Illuminate\Support\Facades\DB::table('metrics_submission')
                    ->where('chapter_id', $chapterId)
                    ->where('assoc_type', 532) // ASSOC_TYPE_CHAPTER
                    ->sum('metric');
                
                $chaptersData[] = [
                    'id' => $chapterId,
                    'title' => $chapter->getLocalizedTitle(),
                    'ompViews' => $ompChapterViews,
                    'viewsOffset' => $chapterOffsets['chapter_views_offset'],
                ];
            }
        } catch (\Exception $e) {
            // Migration not run yet - chapters will not be shown
            $chaptersData = [];
        }
        } // End chapterStatsEnabled check
        
        $templateMgr->assign('chapters', $chaptersData);

        // Add the tab
        $output .= $templateMgr->fetch($this->getTemplateResource('customStatsTab.tpl'));

        return false;
    }

    /**
     * Modify template statistics before display
     * This intercepts templates to add custom offsets to displayed statistics
     */
    public function modifyTemplateStatistics($hookName, $params)
    {
        $templateMgr = $params[0];
        $template = $params[1];
        
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        
        if (!$context) {
            return false;
        }
        
        // Get the submission from template variables
        $submission = $templateMgr->getTemplateVars('monograph');
        if (!$submission) {
            $submission = $templateMgr->getTemplateVars('publication');
        }
        if (!$submission) {
            $submission = $templateMgr->getTemplateVars('submission');
        }
        
        if (!$submission) {
            return false;
        }
        
        $submissionId = is_object($submission) ? $submission->getId() : (int) $submission;
        
        // Get custom offsets
        $this->import('classes.CustomStatsDAO');
        $customStatsDao = new \CustomStatsDAO();
        $offsets = $customStatsDao->getOffsets($submissionId, $context->getId());
        
        if (!$offsets) {
            return false;
        }
        
        // Get existing statistics from template if available
        $publicationStats = $templateMgr->getTemplateVars('publicationStats');
        
        if ($publicationStats && is_array($publicationStats)) {
            // Modify the statistics array
            $this->import('classes.CustomStatsHelper');
            $modifiedStats = \CustomStatsHelper::applyOffsets(
                $publicationStats,
                $submissionId,
                $context->getId()
            );
            $templateMgr->assign('publicationStats', $modifiedStats);
        }
        
        // Also assign offsets as separate variables for themes to use
        $templateMgr->assign([
            'customStatsAbstractOffset' => $offsets['abstract_views_offset'],
            'customStatsDownloadsOffset' => $offsets['file_downloads_offset'],
        ]);
        
        // Modify the theme's template variables (submissionViews and submissionDownloads)
        $currentViews = (int) $templateMgr->getTemplateVars('submissionViews');
        $currentDownloads = (int) $templateMgr->getTemplateVars('submissionDownloads');
        
        if ($currentViews > 0 || $currentDownloads > 0) {
            $combinedViews = $currentViews + (int) $offsets['abstract_views_offset'];
            $combinedDownloads = $currentDownloads + (int) $offsets['file_downloads_offset'];
            
            $templateMgr->assign('submissionViews', $combinedViews);
            $templateMgr->assign('submissionDownloads', $combinedDownloads);
        }
        
        // Handle chapter views if on a chapter page
        $chapter = $templateMgr->getTemplateVars('chapter');
        if ($chapter) {
            $chapterId = is_object($chapter) ? $chapter->getId() : (int) $chapter;
            
            // Get chapter offsets
            $chapterOffsets = $customStatsDao->getChapterOffsets($chapterId, $submissionId, $context->getId());
            
            if ($chapterOffsets && $chapterOffsets['chapter_views_offset'] > 0) {
                $currentChapterViews = (int) $templateMgr->getTemplateVars('chapterViews');
                $combinedChapterViews = $currentChapterViews + (int) $chapterOffsets['chapter_views_offset'];
                $templateMgr->assign('chapterViews', $combinedChapterViews);
            }
        }
        
        return false;
    }
    
    /**
     * Modify API statistics responses
     * This adds custom offsets to statistics returned via API
     */
    public function modifyApiStatistics($hookName, $params)
    {
        // This would modify API responses if needed
        // For now, we'll focus on template-based display
        return false;
    }

    /**
     * Get the installation migration
     */
    public function getInstallMigration()
    {
        $this->import('classes.migration.CustomStatsMigration');
        return new \CustomStatsMigration();
    }
    
    /**
     * Get additional migrations (for chapter stats)
     */
    public function getAdditionalMigrations()
    {
        $this->import('classes.migration.ChapterStatsMigration');
        return [new \ChapterStatsMigration()];
    }
    
    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs)
    {
        $actions = parent::getActions($request, $actionArgs);
        
        if (!$this->getEnabled()) {
            return $actions;
        }
        
        $contextId = $request->getContext()->getId();
        $router = $request->getRouter();
        
        // Check if chapter stats columns exist
        $columnsExist = true;
        try {
            \Illuminate\Support\Facades\DB::table('custom_stats_offsets')
                ->whereNotNull('chapter_id')
                ->limit(1)
                ->get();
        } catch (\Exception $e) {
            // Column doesn't exist, needs initial setup
            $columnsExist = false;
        }
        
        if (!$columnsExist) {
            // Show "Enable Chapter Statistics" button for first-time setup
            $linkAction = new \PKP\linkAction\LinkAction(
                'upgradeChapterStats',
                new \PKP\linkAction\request\AjaxModal(
                    $router->url($request, null, null, 'manage', null, [
                        'verb' => 'upgradeChapterStats',
                        'plugin' => $this->getName(),
                        'category' => 'generic'
                    ]),
                    __('plugins.generic.customStats.upgrade.title')
                ),
                __('plugins.generic.customStats.upgrade.button'),
                null
            );
            
            array_unshift($actions, $linkAction);
        } else {
            // Show toggle button (enable/disable)
            $isEnabled = $this->getSetting($contextId, 'chapterStatsEnabled');
            
            $linkAction = new \PKP\linkAction\LinkAction(
                'toggleChapterStats',
                new \PKP\linkAction\request\RemoteActionConfirmationModal(
                    $request->getSession(),
                    $isEnabled 
                        ? __('plugins.generic.customStats.chapterStats.confirmDisable')
                        : __('plugins.generic.customStats.chapterStats.confirmEnable'),
                    null,
                    $router->url($request, null, null, 'manage', null, [
                        'verb' => 'toggleChapterStats',
                        'plugin' => $this->getName(),
                        'category' => 'generic'
                    ])
                ),
                $isEnabled 
                    ? __('plugins.generic.customStats.chapterStats.disableButton')
                    : __('plugins.generic.customStats.chapterStats.enableButton'),
                null
            );
            
            array_unshift($actions, $linkAction);
        }
        
        return $actions;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.customStats.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.customStats.description');
    }
}
