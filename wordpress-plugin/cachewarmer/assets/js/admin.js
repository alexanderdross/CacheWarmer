/**
 * CacheWarmer Admin JavaScript
 */
(function ($) {
    'use strict';

    var CW = window.cachewarmerAdmin || {};

    // ──────────────────────────────────────────────
    // Warm Form
    // ──────────────────────────────────────────────

    $(document).on('submit', '#cw-warm-form', function (e) {
        e.preventDefault();

        var $form    = $(this);
        var $btn     = $form.find('#cw-warm-submit');
        var $spinner = $form.find('#cw-warm-spinner');
        var $msg     = $form.find('#cw-warm-message');
        var url      = $form.find('#cw-sitemap-url').val();
        var targets  = [];

        $form.find('input[name="targets[]"]:checked').each(function () {
            targets.push($(this).val());
        });

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $msg.hide();

        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_start_warm',
                nonce: CW.nonce,
                sitemapUrl: url,
                targets: targets
            },
            success: function (response) {
                if (response.success) {
                    $msg.removeClass('error').addClass('success')
                        .html(CW.i18n.warmingStarted + ' Job ID: <code>' + response.data.jobId + '</code>')
                        .show();
                    $form.find('#cw-sitemap-url').val('');
                    refreshJobsTable();
                } else {
                    $msg.removeClass('success').addClass('error')
                        .text(response.data ? response.data.message : CW.i18n.error)
                        .show();
                }
            },
            error: function () {
                $msg.removeClass('success').addClass('error').text(CW.i18n.error).show();
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // ──────────────────────────────────────────────
    // Jobs Table
    // ──────────────────────────────────────────────

    function refreshJobsTable() {
        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_get_jobs',
                nonce: CW.nonce,
                limit: 20
            },
            success: function (response) {
                if (!response.success || !response.data) return;

                var $tbody = $('#cw-jobs-table tbody');
                $tbody.empty();

                if (response.data.length === 0) {
                    $tbody.append('<tr><td colspan="6">No jobs yet. Start a warming job above.</td></tr>');
                    return;
                }

                $.each(response.data, function (i, job) {
                    var targets = Array.isArray(job.targets) ? job.targets : [];
                    var progress = job.total_urls > 0
                        ? Math.round((job.processed_urls / job.total_urls) * 100)
                        : 0;
                    var host = '';
                    try { host = new URL(job.sitemap_url).hostname; } catch (e) { host = job.sitemap_url; }

                    var tagsHtml = '';
                    $.each(targets, function (j, t) {
                        tagsHtml += '<span class="cachewarmer-tag">' + escHtml(t) + '</span>';
                    });

                    var row = '<tr data-job-id="' + escAttr(job.id) + '">' +
                        '<td><span class="cachewarmer-badge badge-' + escAttr(job.status) + '">' + escHtml(capitalize(job.status)) + '</span></td>' +
                        '<td class="column-sitemap" title="' + escAttr(job.sitemap_url) + '">' + escHtml(host) + '</td>' +
                        '<td><div class="cachewarmer-progress"><div class="cachewarmer-progress-bar" style="width:' + progress + '%"></div>' +
                        '<span class="cachewarmer-progress-text">' + job.processed_urls + '/' + job.total_urls + '</span></div></td>' +
                        '<td>' + tagsHtml + '</td>' +
                        '<td>' + escHtml(job.created_at) + '</td>' +
                        '<td>' +
                        '<button class="button button-small cw-job-details" data-job-id="' + escAttr(job.id) + '">Details</button> ' +
                        '<button class="button button-small button-link-delete cw-job-delete" data-job-id="' + escAttr(job.id) + '">Delete</button>' +
                        '</td></tr>';

                    $tbody.append(row);
                });
            }
        });

        // Also update status cards.
        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: { action: 'cachewarmer_get_status', nonce: CW.nonce },
            success: function (response) {
                if (!response.success) return;
                var d = response.data;
                $('#cw-status-queued').text(d.jobs.queued);
                $('#cw-status-running').text(d.jobs.running);
                $('#cw-status-completed').text(d.jobs.completed);
                $('#cw-status-failed').text(d.jobs.failed);
                $('#cw-status-total').text(d.totalUrlsProcessed);
            }
        });
    }

    // Auto-refresh every 10s if on dashboard.
    if ($('#cw-jobs-table').length) {
        setInterval(refreshJobsTable, 10000);
    }

    // ──────────────────────────────────────────────
    // Job Details
    // ──────────────────────────────────────────────

    $(document).on('click', '.cw-job-details', function () {
        var jobId  = $(this).data('job-id');
        var $modal = $('#cw-job-modal');
        var $body  = $('#cw-job-modal-body');

        $body.html('<div class="spinner is-active" style="float:none;"></div>');
        $modal.show();

        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_get_job',
                nonce: CW.nonce,
                jobId: jobId
            },
            success: function (response) {
                if (!response.success) {
                    $body.html('<p class="error">Job not found.</p>');
                    return;
                }

                var job   = response.data.job;
                var stats = response.data.stats;

                var html = '<dl class="cachewarmer-job-meta">';
                html += '<div><dt>Job ID</dt><dd><code>' + escHtml(job.id) + '</code></dd></div>';
                html += '<div><dt>Status</dt><dd><span class="cachewarmer-badge badge-' + escAttr(job.status) + '">' + escHtml(capitalize(job.status)) + '</span></dd></div>';
                html += '<div><dt>Sitemap</dt><dd>' + escHtml(job.sitemap_url) + '</dd></div>';
                html += '<div><dt>Progress</dt><dd>' + job.processed_urls + ' / ' + job.total_urls + '</dd></div>';
                html += '<div><dt>Started</dt><dd>' + escHtml(job.started_at || '-') + '</dd></div>';
                html += '<div><dt>Completed</dt><dd>' + escHtml(job.completed_at || '-') + '</dd></div>';
                html += '</dl>';

                if (job.error) {
                    html += '<div class="notice notice-error"><p>' + escHtml(job.error) + '</p></div>';
                }

                html += '<h3>Results by Target</h3>';
                html += '<div class="cachewarmer-stats-grid">';

                if (stats && Object.keys(stats).length > 0) {
                    $.each(stats, function (target, counts) {
                        html += '<div class="cachewarmer-stat-card">';
                        html += '<h4>' + escHtml(target) + '</h4>';
                        html += '<div class="stat-row"><span>Success</span><span class="stat-success">' + (counts.success || 0) + '</span></div>';
                        html += '<div class="stat-row"><span>Failed</span><span class="stat-failed">' + (counts.failed || 0) + '</span></div>';
                        html += '<div class="stat-row"><span>Skipped</span><span class="stat-skipped">' + (counts.skipped || 0) + '</span></div>';
                        html += '</div>';
                    });
                } else {
                    html += '<p>No results yet.</p>';
                }

                html += '</div>';
                $body.html(html);
            },
            error: function () {
                $body.html('<p class="error">Failed to load job details.</p>');
            }
        });
    });

    // Close modal.
    $(document).on('click', '.cachewarmer-modal-close', function () {
        $('#cw-job-modal').hide();
    });

    $(document).on('click', '#cw-job-modal', function (e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    // ──────────────────────────────────────────────
    // Delete Job
    // ──────────────────────────────────────────────

    $(document).on('click', '.cw-job-delete', function () {
        if (!confirm(CW.i18n.confirmDelete)) return;

        var jobId = $(this).data('job-id');

        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_delete_job',
                nonce: CW.nonce,
                jobId: jobId
            },
            success: function () {
                $('tr[data-job-id="' + jobId + '"]').fadeOut(300, function () {
                    $(this).remove();
                });
            }
        });
    });

    // ──────────────────────────────────────────────
    // Sitemaps – cron helpers
    // ──────────────────────────────────────────────

    function buildCronExpression(frequency, hour) {
        hour = parseInt(hour, 10) || 0;
        switch (frequency) {
            case 'hourly':
                return '0 * * * *';
            case 'every_6_hours': {
                var h6 = [hour, (hour + 6) % 24, (hour + 12) % 24, (hour + 18) % 24].sort(function (a, b) { return a - b; });
                return '0 ' + h6.join(',') + ' * * *';
            }
            case 'every_12_hours': {
                var h12 = [hour, (hour + 12) % 24].sort(function (a, b) { return a - b; });
                return '0 ' + h12.join(',') + ' * * *';
            }
            case 'daily':
                return '0 ' + hour + ' * * *';
            default:
                return '';
        }
    }

    function formatCronLabel(cron) {
        if (!cron) return '<em>None</em>';
        if (cron === '0 * * * *') return 'Hourly';
        var m = cron.match(/^0 (\S+) \* \* \*$/);
        if (m) {
            var parts = m[1].split(',');
            var pad = function (n) { return ('0' + n).slice(-2); };
            if (parts.length === 1) return 'Daily at ' + pad(parts[0]) + ':00';
            if (parts.length === 2) return 'Every 12h (from ' + pad(Math.min.apply(null, parts)) + ':00)';
            if (parts.length === 4) return 'Every 6h (from ' + pad(Math.min.apply(null, parts)) + ':00)';
        }
        return escHtml(cron);
    }

    // Show/hide time dropdown based on frequency selection.
    $(document).on('change', '#cw-new-sitemap-frequency', function () {
        var freq = $(this).val();
        if (freq !== 'none' && freq !== 'hourly') {
            $('#cw-start-time-wrap').show();
        } else {
            $('#cw-start-time-wrap').hide();
        }
    });

    // ──────────────────────────────────────────────
    // Sitemaps
    // ──────────────────────────────────────────────

    $(document).on('submit', '#cw-add-sitemap-form', function (e) {
        e.preventDefault();

        var $form    = $(this);
        var $spinner = $form.find('#cw-sitemap-spinner');
        var $msg     = $form.find('#cw-sitemap-message');
        var url      = $form.find('#cw-new-sitemap-url').val();
        var freq     = $form.find('#cw-new-sitemap-frequency').val();
        var hour     = $form.find('#cw-new-sitemap-hour').val();
        var cron     = buildCronExpression(freq, hour);

        $spinner.addClass('is-active');
        $msg.hide();

        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_add_sitemap',
                nonce: CW.nonce,
                url: url,
                cronExpression: cron
            },
            success: function (response) {
                if (response.success) {
                    var s = response.data;
                    var cronDisplay = formatCronLabel(s.cron_expression);

                    $('#cw-no-sitemaps-row').remove();

                    var row = '<tr data-sitemap-id="' + escAttr(s.id) + '">' +
                        '<td>' + escHtml(s.domain) + '</td>' +
                        '<td><a href="' + escAttr(s.url) + '" target="_blank" rel="noopener">' + escHtml(s.url) + '</a></td>' +
                        '<td>' + cronDisplay + '</td>' +
                        '<td><em>Never</em></td>' +
                        '<td>' +
                        '<button class="button button-small button-primary cw-warm-sitemap" data-sitemap-id="' + escAttr(s.id) + '">Warm Now</button> ' +
                        '<button class="button button-small button-link-delete cw-delete-sitemap" data-sitemap-id="' + escAttr(s.id) + '">Delete</button>' +
                        '</td></tr>';

                    $('#cw-sitemaps-table tbody').append(row);
                    $form.find('#cw-new-sitemap-url').val('');
                    $form.find('#cw-new-sitemap-frequency').val('none');
                    $form.find('#cw-new-sitemap-hour').val('3');
                    $('#cw-start-time-wrap').hide();

                    $msg.removeClass('error').addClass('success').text('Sitemap registered.').show();
                } else {
                    $msg.removeClass('success').addClass('error')
                        .text(response.data ? response.data.message : CW.i18n.error).show();
                }
            },
            error: function () {
                $msg.removeClass('success').addClass('error').text(CW.i18n.error).show();
            },
            complete: function () {
                $spinner.removeClass('is-active');
            }
        });
    });

    $(document).on('click', '.cw-delete-sitemap', function () {
        if (!confirm(CW.i18n.confirmDelete)) return;

        var sitemapId = $(this).data('sitemap-id');

        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_delete_sitemap',
                nonce: CW.nonce,
                sitemapId: sitemapId
            },
            success: function () {
                $('tr[data-sitemap-id="' + sitemapId + '"]').fadeOut(300, function () {
                    $(this).remove();
                });
            }
        });
    });

    $(document).on('click', '.cw-warm-sitemap', function () {
        var $btn      = $(this);
        var sitemapId = $btn.data('sitemap-id');

        $btn.prop('disabled', true).text('Starting...');

        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_warm_sitemap',
                nonce: CW.nonce,
                sitemapId: sitemapId
            },
            success: function (response) {
                if (response.success) {
                    $btn.text('Started!');
                    setTimeout(function () {
                        $btn.prop('disabled', false).text('Warm Now');
                    }, 2000);
                } else {
                    $btn.prop('disabled', false).text('Warm Now');
                    alert(response.data ? response.data.message : CW.i18n.error);
                }
            },
            error: function () {
                $btn.prop('disabled', false).text('Warm Now');
                alert(CW.i18n.error);
            }
        });
    });

    // ──────────────────────────────────────────────
    // Bulk Import
    // ──────────────────────────────────────────────

    $(document).on('submit', '#cw-bulk-import-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $spinner = $form.find('#cw-bulk-spinner');
        var $msg = $form.find('#cw-bulk-message');
        var urls = $form.find('#cw-bulk-urls').val();

        $spinner.addClass('is-active');
        $msg.hide();

        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_bulk_add_sitemaps',
                nonce: CW.nonce,
                urls: urls
            },
            success: function (response) {
                if (response.success) {
                    var count = response.data.added.length;
                    var errCount = response.data.errors.length;
                    var msg = count + ' sitemap(s) added.';
                    if (errCount > 0) msg += ' ' + errCount + ' invalid URL(s) skipped.';
                    $msg.removeClass('error').addClass('success').text(msg).show();
                    $form.find('#cw-bulk-urls').val('');
                    if (count > 0) location.reload();
                } else {
                    $msg.removeClass('success').addClass('error')
                        .text(response.data ? response.data.message : CW.i18n.error).show();
                }
            },
            error: function () {
                $msg.removeClass('success').addClass('error').text(CW.i18n.error).show();
            },
            complete: function () {
                $spinner.removeClass('is-active');
            }
        });
    });

    // ──────────────────────────────────────────────
    // Auto-Detect Sitemaps
    // ──────────────────────────────────────────────

    $(document).on('click', '#cw-detect-sitemaps', function () {
        var $btn = $(this);
        var $textarea = $('#cw-bulk-urls');

        $btn.prop('disabled', true).text('Detecting...');

        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_detect_sitemaps',
                nonce: CW.nonce
            },
            success: function (response) {
                if (response.success && response.data.sitemaps.length > 0) {
                    var existing = $textarea.val().trim();
                    var newUrls = response.data.sitemaps.join('\n');
                    $textarea.val(existing ? existing + '\n' + newUrls : newUrls);
                    $btn.text('Found ' + response.data.sitemaps.length + '!');
                } else {
                    $btn.text('None found');
                }
            },
            error: function () {
                $btn.text('Error');
            },
            complete: function () {
                setTimeout(function () {
                    $btn.prop('disabled', false).text('Auto-Detect Local Sitemaps');
                }, 2000);
            }
        });
    });

    // ──────────────────────────────────────────────
    // Export Results
    // ──────────────────────────────────────────────

    $(document).on('click', '.cw-export-results', function () {
        var jobId = $(this).data('job-id');
        var format = $(this).data('format') || 'csv';

        $.ajax({
            url: CW.ajaxUrl,
            method: 'POST',
            data: {
                action: 'cachewarmer_export_results',
                nonce: CW.nonce,
                jobId: jobId,
                format: format
            },
            success: function (response) {
                if (!response.success) {
                    alert(response.data ? response.data.message : 'Export failed');
                    return;
                }
                var content = format === 'csv' ? response.data.content : JSON.stringify(response.data.content, null, 2);
                var blob = new Blob([content], { type: format === 'csv' ? 'text/csv' : 'application/json' });
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = response.data.filename;
                link.click();
            }
        });
    });

    // ──────────────────────────────────────────────
    // Utility functions
    // ──────────────────────────────────────────────

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escAttr(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

})(jQuery);
