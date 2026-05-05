{**
 * Custom Statistics Tab Template
 * Displays in the publication workflow
 *}
<tab id="customStats" label="{translate key="plugins.generic.customStats.tab.label"}">
    <div class="pkp_form_section">
        <p class="description">{translate key="plugins.generic.customStats.helpText"}</p>

        <form id="customStatsForm" data-submission-id="{$submissionId|escape}" data-save-url="{$saveUrl|escape}">

            {* Current OMP Statistics *}
            <div class="section">
                <h3>{translate key="plugins.generic.customStats.currentOmpStats"}</h3>
                <table class="pkp_table">
                    <tr>
                        <td><strong>{translate key="plugins.generic.customStats.abstractViews"}:</strong></td>
                        <td>{$ompAbstractViews|escape}</td>
                    </tr>
                    <tr>
                        <td><strong>{translate key="plugins.generic.customStats.fileDownloads"}:</strong></td>
                        <td>{$ompFileDownloads|escape}</td>
                    </tr>
                </table>
            </div>

            {* Custom Offsets Input *}
            <div class="section" style="margin-top: 20px;">
                <h3>{translate key="plugins.generic.customStats.customOffsets"}</h3>

                <div class="pkp_form_field">
                    <label for="abstractViewsOffset">
                        {translate key="plugins.generic.customStats.abstractViewsOffset"}
                    </label>
                    <input type="number" id="abstractViewsOffset" name="abstractViewsOffset"
                        value="{$abstractViewsOffset|escape}" min="0" data-omp-value="{$ompAbstractViews|escape}"
                        data-validation-numeric="{translate key="plugins.generic.customStats.validation.numeric"}"
                        data-validation-positive="{translate key="plugins.generic.customStats.validation.positive"}"
                        class="pkp_form_input" />
                </div>

                <div class="pkp_form_field">
                    <label for="fileDownloadsOffset">
                        {translate key="plugins.generic.customStats.fileDownloads"}
                    </label>
                    <input type="number" id="fileDownloadsOffset" name="fileDownloadsOffset"
                        value="{$fileDownloadsOffset|escape}" min="0" data-omp-value="{$ompFileDownloads|escape}"
                        class="pkp_form_input" />
                </div>
            </div>

            {* Combined Totals Display *}
            <div class="section"
                style="margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-radius: 4px;">
                <h3>{translate key="plugins.generic.customStats.combinedTotal"}</h3>
                <table class="pkp_table">
                    <tr>
                        <td><strong>{translate key="plugins.generic.customStats.abstractViews"}:</strong></td>
                        <td><span id="combinedAbstractViews" style="font-weight: bold; color: #1e6292;">0</span></td>
                    </tr>
                    <tr>
                        <td><strong>{translate key="plugins.generic.customStats.fileDownloads"}:</strong></td>
                        <td><span id="combinedFileDownloads" style="font-weight: bold; color: #1e6292;">0</span></td>
                    </tr>
                </table>
            </div>

            {* Chapter Statistics Section *}
            {if $chapters && count($chapters) > 0}
                <div class="section" style="margin-top: 30px;">
                    <h3>Chapter Statistics Offsets</h3>
                    <p><em>Enter legacy view statistics for each chapter.</em></p>

                    {foreach from=$chapters item=chapter}
                        <div class="pkp_form_field"
                            style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 4px;">
                            <h4 style="margin-top: 0;">{$chapter.title|escape}</h4>
                            <div style="display: flex; gap: 20px;">
                                <div style="flex: 1;">
                                    <div style="font-size: 0.9em; color: #666;">OMP Views: <strong>{$chapter.ompViews}</strong>
                                    </div>
                                    <label for="chapterOffset_{$chapter.id}">Legacy Views Offset</label>
                                    <input type="number" id="chapterOffset_{$chapter.id}" name="chapterOffsets[{$chapter.id}]"
                                        value="{$chapter.viewsOffset}" min="0" class="pkp_form_input chapter-offset-input"
                                        data-chapter-id="{$chapter.id}" data-omp-views="{$chapter.ompViews}" />
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.9em; color: #666;">Combined Total:</div>
                                    <div style="font-size: 1.5em; font-weight: bold; color: #1e6292;"
                                        id="chapterCombined_{$chapter.id}">
                                        {$chapter.ompViews + $chapter.viewsOffset}
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            {/if}

            {* Save Button *}
            <div style="margin-top: 20px;">
                <button type="button" id="saveCustomStats" class="pkp_button">
                    {translate key="plugins.generic.customStats.save"}
                </button>
            </div>

            {* Message Display *}
            <div id="customStatsMessage" style="display: none; margin-top: 15px; padding: 10px; border-radius: 4px;">
            </div>
        </form>
    </div>

    {* Load JavaScript *}
    <script src="{$pluginJsUrl|escape}"></script>

    <style>
        #customStatsMessage.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        #customStatsMessage.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .pkp_form_field {
            margin-bottom: 15px;
        }

        .pkp_form_field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .pkp_form_input {
            width: 100%;
            max-width: 300px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .pkp_table {
            width: 100%;
            max-width: 500px;
        }

        .pkp_table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .section {
            margin-bottom: 20px;
        }
    </style>
</tab>