/**
 * CacheWarmer License Manager – Admin JS
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Lizenz-Key Copy Button
        $(document).on('click', '.cwlm-copy-key', function () {
            var key = $(this).data('key');
            navigator.clipboard.writeText(key).then(function () {
                var btn = $(this);
                var originalText = btn.text();
                btn.text('Kopiert!');
                setTimeout(function () {
                    btn.text(originalText);
                }, 2000);
            }.bind(this));
        });

        // Bulk-Actions
        $('#cwlm-select-all').on('change', function () {
            $('input.cwlm-select-item').prop('checked', $(this).prop('checked'));
        });

        // Confirm-Dialoge für destruktive Aktionen
        $(document).on('click', '.cwlm-confirm-action', function (e) {
            var message = $(this).data('confirm') || 'Sind Sie sicher?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-submit Filter (nur sichtbare Selects, keine hidden Inputs)
        $('.cwlm-filter-bar select').on('change', function () {
            $(this).closest('form').submit();
        });
    });
})(jQuery);
