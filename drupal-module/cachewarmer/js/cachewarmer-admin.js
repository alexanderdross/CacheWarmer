(function ($, Drupal, drupalSettings) {
  'use strict';

  var urls = drupalSettings.cachewarmer ? drupalSettings.cachewarmer.ajaxUrls : {};

  function escHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
  }

  // --- Dashboard ---

  // Start Warm
  $(document).on('click', '#cachewarmer-start-warm', function () {
    var btn = $(this);
    var sitemapUrl = $('#cachewarmer-sitemap-url').val().trim();
    if (!sitemapUrl) {
      $('#cachewarmer-warm-status').text(Drupal.t('Please enter a sitemap URL.'));
      return;
    }

    var targets = [];
    $('input[name="targets[]"]:checked').each(function () {
      targets.push($(this).val());
    });

    if (targets.length === 0) {
      $('#cachewarmer-warm-status').text(Drupal.t('Please select at least one target.'));
      return;
    }

    btn.prop('disabled', true);
    $('#cachewarmer-warm-status').text(Drupal.t('Starting...'));

    $.ajax({
      url: urls.startWarm,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ sitemap_url: sitemapUrl, targets: targets }),
      success: function (response) {
        if (response.success) {
          $('#cachewarmer-warm-status').text(Drupal.t('Job created successfully!'));
          $('#cachewarmer-sitemap-url').val('');
          refreshJobsTable();
          refreshStatus();
        } else {
          $('#cachewarmer-warm-status').text(response.error || Drupal.t('Failed to start warming.'));
        }
      },
      error: function () {
        $('#cachewarmer-warm-status').text(Drupal.t('Failed to start warming.'));
      },
      complete: function () {
        btn.prop('disabled', false);
      }
    });
  });

  // Refresh jobs table
  function refreshJobsTable() {
    if (!urls.getJobs) return;

    $.getJSON(urls.getJobs, function (response) {
      if (!response.success) return;

      var tbody = $('#cachewarmer-jobs-tbody');
      tbody.empty();

      $.each(response.data, function (i, job) {
        var pct = job.total_urls > 0 ? Math.round((job.processed_urls / job.total_urls) * 100) : 0;
        var targetTags = '';
        if (job.targets && Array.isArray(job.targets)) {
          $.each(job.targets, function (j, t) {
            targetTags += '<span class="cachewarmer-tag">' + escHtml(t) + '</span>';
          });
        }

        var row = '<tr data-job-id="' + escHtml(job.id) + '">' +
          '<td><span class="cachewarmer-badge cachewarmer-badge--' + escHtml(job.status) + '">' + escHtml(job.status) + '</span></td>' +
          '<td>' + escHtml(job.sitemap_url) + '</td>' +
          '<td><div class="cachewarmer-progress"><div class="cachewarmer-progress__bar" style="width:' + pct + '%"></div><span class="cachewarmer-progress__text">' + job.processed_urls + '/' + job.total_urls + '</span></div></td>' +
          '<td>' + targetTags + '</td>' +
          '<td>' + escHtml(job.created_at) + '</td>' +
          '<td><button class="button button--small cachewarmer-btn-details" data-job-id="' + escHtml(job.id) + '">' + Drupal.t('Details') + '</button> ' +
          '<button class="button button--small button--danger cachewarmer-btn-delete" data-job-id="' + escHtml(job.id) + '">' + Drupal.t('Delete') + '</button></td>' +
          '</tr>';
        tbody.append(row);
      });
    });
  }

  // Refresh status
  function refreshStatus() {
    if (!urls.status) return;

    $.getJSON(urls.status, function (response) {
      if (!response.success) return;
      var data = response.data;
      $('#cachewarmer-status-queued').text(data.queued || 0);
      $('#cachewarmer-status-running').text(data.running || 0);
      $('#cachewarmer-status-completed').text(data.completed || 0);
      $('#cachewarmer-status-failed').text(data.failed || 0);
      $('#cachewarmer-status-total').text(data.total_processed || 0);
    });
  }

  // Job details modal
  $(document).on('click', '.cachewarmer-btn-details', function () {
    var jobId = $(this).data('job-id');
    var modalBody = $('#cachewarmer-modal-body');
    modalBody.html('<p>' + Drupal.t('Loading...') + '</p>');
    $('#cachewarmer-modal').show();

    $.getJSON(urls.getJob + jobId, function (response) {
      if (!response.success) {
        modalBody.html('<p>' + Drupal.t('Failed to load job details.') + '</p>');
        return;
      }

      var job = response.data;
      var html = '<dl class="cachewarmer-detail-grid">';
      html += '<dt>' + Drupal.t('ID') + '</dt><dd>' + escHtml(job.id) + '</dd>';
      html += '<dt>' + Drupal.t('Status') + '</dt><dd><span class="cachewarmer-badge cachewarmer-badge--' + escHtml(job.status) + '">' + escHtml(job.status) + '</span></dd>';
      html += '<dt>' + Drupal.t('Sitemap') + '</dt><dd>' + escHtml(job.sitemap_url) + '</dd>';
      html += '<dt>' + Drupal.t('Progress') + '</dt><dd>' + job.processed_urls + ' / ' + job.total_urls + '</dd>';
      html += '<dt>' + Drupal.t('Started') + '</dt><dd>' + escHtml(job.started_at || '-') + '</dd>';
      html += '<dt>' + Drupal.t('Completed') + '</dt><dd>' + escHtml(job.completed_at || '-') + '</dd>';

      if (job.error) {
        html += '<dt>' + Drupal.t('Error') + '</dt><dd style="color:#d63638">' + escHtml(job.error) + '</dd>';
      }
      html += '</dl>';

      // Stats by target
      if (job.stats && Object.keys(job.stats).length > 0) {
        html += '<h4>' + Drupal.t('Results by Target') + '</h4>';
        html += '<div class="cachewarmer-stats-grid">';
        $.each(job.stats, function (target, counts) {
          html += '<div class="cachewarmer-stat-card">';
          html += '<div class="cachewarmer-stat-card__title">' + capitalize(escHtml(target)) + '</div>';
          html += '<div class="cachewarmer-stat-card__counts">';
          html += '<span class="cachewarmer-stat-count--success">' + counts.success + ' ok</span> ';
          html += '<span class="cachewarmer-stat-count--failed">' + counts.failed + ' fail</span> ';
          html += '<span class="cachewarmer-stat-count--skipped">' + counts.skipped + ' skip</span>';
          html += '</div></div>';
        });
        html += '</div>';
      }

      // Export buttons
      html += '<div style="margin-top:16px;display:flex;gap:8px">';
      html += '<button class="button button--small cachewarmer-btn-export" data-job-id="' + escHtml(job.id) + '" data-format="csv">' + Drupal.t('Export CSV') + '</button>';
      html += '<button class="button button--small cachewarmer-btn-export" data-job-id="' + escHtml(job.id) + '" data-format="json">' + Drupal.t('Export JSON') + '</button>';
      html += '</div>';

      modalBody.html(html);
    });
  });

  // Close modal
  $(document).on('click', '.cachewarmer-modal__close, .cachewarmer-modal__overlay', function () {
    $('#cachewarmer-modal').hide();
  });

  // Delete job
  $(document).on('click', '.cachewarmer-btn-delete', function () {
    var jobId = $(this).data('job-id');
    if (!confirm(Drupal.t('Delete this job?'))) return;

    $.ajax({
      url: urls.deleteJob + jobId + '/delete',
      method: 'POST',
      success: function (response) {
        if (response.success) {
          $('tr[data-job-id="' + jobId + '"]').fadeOut(300, function () { $(this).remove(); });
          refreshStatus();
        }
      }
    });
  });

  // --- Sitemaps – cron helpers ---

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
    if (!cron) return 'None';
    if (cron === '0 * * * *') return 'Hourly';
    var m = cron.match(/^0 (\S+) \* \* \*$/);
    if (m) {
      var parts = m[1].split(',');
      var pad = function (n) { return ('0' + n).slice(-2); };
      if (parts.length === 1) return 'Daily at ' + pad(parts[0]) + ':00';
      if (parts.length === 2) return 'Every 12h (from ' + pad(Math.min.apply(null, parts)) + ':00)';
      if (parts.length === 4) return 'Every 6h (from ' + pad(Math.min.apply(null, parts)) + ':00)';
    }
    return cron;
  }

  // Show/hide time dropdown based on frequency selection.
  $(document).on('change', '#cachewarmer-new-sitemap-frequency', function () {
    var freq = $(this).val();
    if (freq !== 'none' && freq !== 'hourly') {
      $('#cachewarmer-start-time-wrap').show();
    } else {
      $('#cachewarmer-start-time-wrap').hide();
    }
  });

  // --- Sitemaps ---

  // Add sitemap
  $(document).on('click', '#cachewarmer-add-sitemap', function () {
    var btn = $(this);
    var url = $('#cachewarmer-new-sitemap-url').val().trim();
    if (!url) {
      $('#cachewarmer-sitemap-status').text(Drupal.t('Please enter a sitemap URL.'));
      return;
    }

    var freq = $('#cachewarmer-new-sitemap-frequency').val();
    var hour = $('#cachewarmer-new-sitemap-hour').val();
    var cronExpr = buildCronExpression(freq, hour);
    btn.prop('disabled', true);
    $('#cachewarmer-sitemap-status').text(Drupal.t('Adding...'));

    $.ajax({
      url: urls.addSitemap,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ url: url, cron_expression: cronExpr || null }),
      success: function (response) {
        if (response.success) {
          var s = response.data;
          var cronDisplay = formatCronLabel(s.cron_expression);
          var row = '<tr data-sitemap-id="' + escHtml(s.id) + '">' +
            '<td>' + escHtml(s.domain) + '</td>' +
            '<td><a href="' + escHtml(s.url) + '" target="_blank" rel="noopener">' + escHtml(s.url) + '</a></td>' +
            '<td>' + escHtml(cronDisplay) + '</td>' +
            '<td>' + escHtml(s.last_warmed_at || 'Never') + '</td>' +
            '<td><button class="button button--small button--primary cachewarmer-btn-warm-sitemap" data-sitemap-id="' + escHtml(s.id) + '">' + Drupal.t('Warm Now') + '</button> ' +
            '<button class="button button--small button--danger cachewarmer-btn-delete-sitemap" data-sitemap-id="' + escHtml(s.id) + '">' + Drupal.t('Delete') + '</button></td>' +
            '</tr>';
          $('#cachewarmer-sitemaps-tbody').append(row);
          $('#cachewarmer-new-sitemap-url').val('');
          $('#cachewarmer-new-sitemap-frequency').val('none');
          $('#cachewarmer-new-sitemap-hour').val('3');
          $('#cachewarmer-start-time-wrap').hide();
          $('#cachewarmer-sitemap-status').text(Drupal.t('Sitemap added!'));
        } else {
          $('#cachewarmer-sitemap-status').text(response.error || Drupal.t('Failed to add sitemap.'));
        }
      },
      error: function () {
        $('#cachewarmer-sitemap-status').text(Drupal.t('Failed to add sitemap.'));
      },
      complete: function () {
        btn.prop('disabled', false);
      }
    });
  });

  // Delete sitemap
  $(document).on('click', '.cachewarmer-btn-delete-sitemap', function () {
    var sitemapId = $(this).data('sitemap-id');
    if (!confirm(Drupal.t('Delete this sitemap?'))) return;

    $.ajax({
      url: urls.deleteSitemap + sitemapId + '/delete',
      method: 'POST',
      success: function (response) {
        if (response.success) {
          $('tr[data-sitemap-id="' + sitemapId + '"]').fadeOut(300, function () { $(this).remove(); });
        }
      }
    });
  });

  // Warm sitemap
  $(document).on('click', '.cachewarmer-btn-warm-sitemap', function () {
    var btn = $(this);
    var sitemapId = btn.data('sitemap-id');
    btn.prop('disabled', true).text(Drupal.t('Starting...'));

    $.ajax({
      url: urls.warmSitemap + sitemapId + '/warm',
      method: 'POST',
      success: function (response) {
        if (response.success) {
          btn.text(Drupal.t('Warming started!'));
          setTimeout(function () {
            btn.prop('disabled', false).text(Drupal.t('Warm Now'));
          }, 5000);
        } else {
          btn.prop('disabled', false).text(Drupal.t('Warm Now'));
        }
      },
      error: function () {
        btn.prop('disabled', false).text(Drupal.t('Warm Now'));
      }
    });
  });

  // --- Bulk Import ---

  // Bulk Import handler
  $(document).on('click', '#cachewarmer-bulk-import', function () {
    var $btn = $(this);
    var $status = $('#cachewarmer-bulk-status');
    var bulkUrls = $('#cachewarmer-bulk-urls').val();

    if (!bulkUrls || !bulkUrls.trim()) {
      $status.text(Drupal.t('Please enter at least one URL.'));
      return;
    }

    $btn.prop('disabled', true);
    $status.text(Drupal.t('Importing...'));

    $.ajax({
      url: urls.bulkAddSitemapsUrl,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ urls: bulkUrls }),
      success: function (data) {
        var msg = data.added.length + ' ' + Drupal.t('sitemap(s) added.');
        if (data.errors.length > 0) {
          msg += ' ' + data.errors.length + ' ' + Drupal.t('invalid URL(s) skipped.');
        }
        $status.text(msg);
        if (data.added.length > 0) {
          location.reload();
        }
      },
      error: function () {
        $status.text(Drupal.t('Import failed.'));
      },
      complete: function () {
        $btn.prop('disabled', false);
      }
    });
  });

  // Auto-Detect
  $(document).on('click', '#cachewarmer-detect-sitemaps', function () {
    var $btn = $(this);
    var $textarea = $('#cachewarmer-bulk-urls');

    $btn.prop('disabled', true).text(Drupal.t('Detecting...'));

    $.ajax({
      url: urls.detectSitemapsUrl,
      method: 'POST',
      contentType: 'application/json',
      data: '{}',
      success: function (data) {
        if (data.sitemaps && data.sitemaps.length > 0) {
          var existing = $textarea.val().trim();
          var newUrls = data.sitemaps.join('\n');
          $textarea.val(existing ? existing + '\n' + newUrls : newUrls);
          $btn.text(Drupal.t('Found') + ' ' + data.sitemaps.length + '!');
        } else {
          $btn.text(Drupal.t('None found'));
        }
      },
      error: function () {
        $btn.text(Drupal.t('Error'));
      },
      complete: function () {
        setTimeout(function () {
          $btn.prop('disabled', false).text(Drupal.t('Auto-Detect Local Sitemaps'));
        }, 2000);
      }
    });
  });

  // --- Export ---

  // Export handler
  $(document).on('click', '.cachewarmer-btn-export', function () {
    var jobId = $(this).data('job-id');
    var format = $(this).data('format') || 'csv';

    $.ajax({
      url: urls.exportResultsUrl,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ job_id: jobId, format: format }),
      success: function (data) {
        var content = format === 'csv' ? data.content : JSON.stringify(data.content, null, 2);
        var blob = new Blob([content], { type: format === 'csv' ? 'text/csv' : 'application/json' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = data.filename;
        link.click();
      }
    });
  });

  // Auto-refresh on dashboard
  if ($('.cachewarmer-dashboard').length) {
    setInterval(function () {
      refreshJobsTable();
      refreshStatus();
    }, 10000);
  }

})(jQuery, Drupal, drupalSettings);
