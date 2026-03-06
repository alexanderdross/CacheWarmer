/**
 * SearchForge License Manager – Admin JS
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Lizenz-Key Copy Button
        $(document).on('click', '.sflm-copy-key', function () {
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
        $('#sflm-select-all').on('change', function () {
            $('input.sflm-select-item').prop('checked', $(this).prop('checked'));
        });

        // Confirm-Dialoge für destruktive Aktionen
        $(document).on('click', '.sflm-confirm-action', function (e) {
            var message = $(this).data('confirm') || 'Sind Sie sicher?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-submit Filter (nur sichtbare Selects, keine hidden Inputs)
        $('.sflm-filter-bar select').on('change', function () {
            $(this).closest('form').submit();
        });

        // Focus management: move focus to first input when toggle forms open
        $('#sflm-new-license-btn').on('click', function () {
            var form = $('#sflm-new-license-form');
            form.slideToggle(200, function () {
                if (form.is(':visible')) {
                    form.find('input:visible, select:visible, textarea:visible').first().trigger('focus');
                }
            });
        });

        $('#sflm-new-product-btn').on('click', function () {
            var form = $('#sflm-new-product-form');
            form.slideToggle(200, function () {
                if (form.is(':visible')) {
                    form.find('input:visible, select:visible, textarea:visible').first().trigger('focus');
                }
            });
        });
    });
})(jQuery);
