/**
 * Custom Statistics Form JavaScript
 * Handles AJAX form submission and real-time calculations
 */
(function ($) {
    $(document).ready(function () {
        var $form = $('#customStatsForm');
        if (!$form.length) return;

        var $abstractInput = $('#abstractViewsOffset');
        var $downloadsInput = $('#fileDownloadsOffset');
        var $saveBtn = $('#saveCustomStats');
        var $messageDiv = $('#customStatsMessage');

        // Calculate and display combined totals in real-time
        function updateCombinedTotals() {
            var abstractOffset = parseInt($abstractInput.val()) || 0;
            var downloadsOffset = parseInt($downloadsInput.val()) || 0;

            var ompAbstract = parseInt($abstractInput.data('omp-value')) || 0;
            var ompDownloads = parseInt($downloadsInput.data('omp-value')) || 0;

            var combinedAbstract = ompAbstract + abstractOffset;
            var combinedDownloads = ompDownloads + downloadsOffset;

            $('#combinedAbstractViews').text(combinedAbstract.toLocaleString());
            $('#combinedFileDownloads').text(combinedDownloads.toLocaleString());
        }

        // Update chapter combined totals in real-time
        function updateChapterCombinedTotals() {
            $('.chapter-offset-input').each(function () {
                var $input = $(this);
                var chapterId = $input.data('chapter-id');
                var ompViews = parseInt($input.data('omp-views')) || 0;
                var offset = parseInt($input.val()) || 0;
                var combined = ompViews + offset;

                $('#chapterCombined_' + chapterId).text(combined.toLocaleString());
            });
        }

        // Update totals when inputs change
        $abstractInput.on('input', updateCombinedTotals);
        $downloadsInput.on('input', updateCombinedTotals);
        $('.chapter-offset-input').on('input', updateChapterCombinedTotals);

        // Validate inputs
        function validateInputs() {
            var abstractVal = $abstractInput.val();
            var downloadsVal = $downloadsInput.val();

            // Check if numeric
            if (abstractVal && isNaN(abstractVal)) {
                showMessage('error', $('#abstractViewsOffset').data('validation-numeric'));
                return false;
            }
            if (downloadsVal && isNaN(downloadsVal)) {
                showMessage('error', $('#fileDownloadsOffset').data('validation-numeric'));
                return false;
            }

            // Check if positive
            var abstractNum = parseInt(abstractVal) || 0;
            var downloadsNum = parseInt(downloadsVal) || 0;

            if (abstractNum < 0 || downloadsNum < 0) {
                showMessage('error', $('#abstractViewsOffset').data('validation-positive'));
                return false;
            }

            return true;
        }

        // Show message
        function showMessage(type, message) {
            $messageDiv.removeClass('success error').addClass(type).text(message).show();
            setTimeout(function () {
                $messageDiv.fadeOut();
            }, 5000);
        }

        // Handle form submission
        $saveBtn.on('click', function (e) {
            e.preventDefault();

            if (!validateInputs()) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).addClass('is_loading');
            $messageDiv.hide();

            // Collect chapter offsets
            var chapterOffsets = {};
            $('.chapter-offset-input').each(function () {
                var $input = $(this);
                var chapterId = $input.data('chapter-id');
                var offset = parseInt($input.val()) || 0;
                chapterOffsets[chapterId] = offset;
            });

            $.ajax({
                url: $form.data('save-url'),
                type: 'POST',
                data: {
                    submissionId: $form.data('submission-id'),
                    abstractViewsOffset: parseInt($abstractInput.val()) || 0,
                    fileDownloadsOffset: parseInt($downloadsInput.val()) || 0,
                    chapterOffsets: chapterOffsets
                },
                dataType: 'json',
                success: function (response) {
                    $btn.prop('disabled', false).removeClass('is_loading');

                    if (response.status === true) {
                        showMessage('success', response.content.message || 'Saved successfully');
                        updateCombinedTotals();
                        updateChapterCombinedTotals();
                    } else {
                        showMessage('error', response.content || 'Error saving offsets');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).removeClass('is_loading');
                    showMessage('error', 'Server error. Please try again.');
                }
            });
        });

        // Initialize combined totals on page load
        updateCombinedTotals();
        updateChapterCombinedTotals();
    });
})(jQuery);
