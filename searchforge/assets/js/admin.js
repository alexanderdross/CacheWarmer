/* global jQuery, searchforge */
(function ($) {
	'use strict';

	// GSC Sync button.
	$(document).on('click', '#sf-sync-btn', function () {
		var $btn = $(this);
		$btn.prop('disabled', true).text('Syncing...');

		$.post(searchforge.ajax_url, {
			action: 'searchforge_sync_gsc',
			nonce: searchforge.nonce
		}, function (response) {
			if (response.success) {
				var d = response.data;
				alert('Sync complete: ' + d.pages_synced + ' pages, ' + d.keywords_synced + ' keywords.');
				location.reload();
			} else {
				alert('Sync failed: ' + (response.data && response.data.message || 'Unknown error'));
				$btn.prop('disabled', false).text('Sync Now');
			}
		}).fail(function () {
			alert('Network error. Please try again.');
			$btn.prop('disabled', false).text('Sync Now');
		});
	});

	// GSC Disconnect button.
	$(document).on('click', '#sf-disconnect-gsc', function () {
		if (!confirm('Disconnect Google Search Console?')) return;

		$.post(searchforge.ajax_url, {
			action: 'searchforge_disconnect_gsc',
			nonce: searchforge.nonce
		}, function (response) {
			if (response.success) {
				location.reload();
			} else {
				alert('Error: ' + (response.data && response.data.message || 'Unknown error'));
			}
		});
	});

	// Export brief for a page.
	$(document).on('click', '.sf-export-btn', function () {
		var $btn = $(this);
		var pagePath = $btn.data('page');
		$btn.prop('disabled', true);

		$.post(searchforge.ajax_url, {
			action: 'searchforge_export_brief',
			nonce: searchforge.nonce,
			page_path: pagePath,
			brief_type: 'page'
		}, function (response) {
			if (response.success) {
				showModal('Brief: ' + pagePath, response.data.markdown, response.data.filename);
			} else {
				alert('Export failed: ' + (response.data && response.data.message || 'Unknown error'));
			}
			$btn.prop('disabled', false);
		}).fail(function () {
			alert('Network error.');
			$btn.prop('disabled', false);
		});
	});

	// Export site brief.
	$(document).on('click', '#sf-export-site', function () {
		var $btn = $(this);
		$btn.prop('disabled', true).text('Generating...');

		$.get(searchforge.rest_url + 'export/site', {}, function (response) {
			if (response.markdown) {
				showModal('Site Brief', response.markdown, 'searchforge-site-brief.md');
			} else {
				alert('Export failed: ' + (response.error || 'Unknown error'));
			}
			$btn.prop('disabled', false).text('Export Site Brief (.md)');
		}).fail(function (xhr) {
			var msg = xhr.responseJSON && xhr.responseJSON.error || 'Network error';
			alert('Export failed: ' + msg);
			$btn.prop('disabled', false).text('Export Site Brief (.md)');
		});
	});

	// Modal.
	function showModal(title, content, filename) {
		$('#sf-modal-title').text(title);
		$('#sf-modal-body').text(content);
		$('#sf-modal-download').data('content', content).data('filename', filename);
		$('#sf-export-modal').show();
	}

	$(document).on('click', '.sf-modal-close', function () {
		$('#sf-export-modal').hide();
	});

	$(document).on('click', '#sf-modal-download', function () {
		var content = $(this).data('content');
		var filename = $(this).data('filename');
		var blob = new Blob([content], { type: 'text/markdown;charset=utf-8' });
		var url = URL.createObjectURL(blob);
		var a = document.createElement('a');
		a.href = url;
		a.download = filename;
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	});

	// AI Content Brief button.
	$(document).on('click', '.sf-ai-brief-btn', function () {
		var $btn = $(this);
		var pagePath = $btn.data('page');
		$btn.prop('disabled', true).text('Generating...');

		$.post(searchforge.ajax_url, {
			action: 'searchforge_generate_content_brief',
			nonce: searchforge.nonce,
			page_path: pagePath
		}, function (response) {
			if (response.success) {
				var title = 'AI Content Brief: ' + pagePath;
				if (response.data.method === 'heuristic') {
					title += ' (Heuristic)';
				}
				showModal(title, response.data.brief, response.data.filename);
			} else {
				alert('Brief generation failed: ' + (response.data && response.data.message || 'Unknown error'));
			}
			$btn.prop('disabled', false).text('AI Brief');
		}).fail(function () {
			alert('Network error.');
			$btn.prop('disabled', false).text('AI Brief');
		});
	});

	// Dismiss alert.
	$(document).on('click', '.sf-dismiss-alert', function () {
		var $btn = $(this);
		var alertId = $btn.data('alert-id');

		$.post(searchforge.ajax_url, {
			action: 'searchforge_dismiss_alert',
			nonce: searchforge.nonce,
			alert_id: alertId
		}, function (response) {
			if (response.success) {
				$btn.closest('.sf-alert').fadeOut();
			}
		});
	});

	// Data export (CSV/JSON).
	$(document).on('click', '.sf-data-export-btn', function () {
		var $btn = $(this);
		var type = $btn.data('type');
		var format = $btn.data('format');
		$btn.prop('disabled', true).text('Exporting...');

		$.post(searchforge.ajax_url, {
			action: 'searchforge_export_data',
			nonce: searchforge.nonce,
			export_type: type,
			export_format: format
		}, function (response) {
			if (response.success) {
				var blob = new Blob([response.data.data], { type: response.data.mime + ';charset=utf-8' });
				var url = URL.createObjectURL(blob);
				var a = document.createElement('a');
				a.href = url;
				a.download = response.data.filename;
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				URL.revokeObjectURL(url);
			} else {
				alert('Export failed: ' + (response.data && response.data.message || 'Unknown error'));
			}
			$btn.prop('disabled', false).text('Export ' + format.toUpperCase());
		}).fail(function () {
			alert('Network error.');
			$btn.prop('disabled', false).text('Export ' + format.toUpperCase());
		});
	});

	// Sitemap discovery.
	$(document).on('click', '#sf-discover-sitemaps', function () {
		var $btn = $(this);
		var $results = $('#sf-sitemap-results');
		$btn.prop('disabled', true).text('Discovering...');
		$results.html('<span class="sf-spinner"></span>');

		$.post(searchforge.ajax_url, {
			action: 'searchforge_discover_sitemaps',
			nonce: searchforge.nonce
		}, function (response) {
			if (response.success && response.data.sitemaps.length) {
				var html = '<ul>';
				response.data.sitemaps.forEach(function (s) {
					html += '<li><code>' + s.url + '</code> — ' + s.url_count + ' URLs</li>';
				});
				html += '</ul>';
				$results.html(html);
			} else {
				$results.html('<p>No sitemaps found.</p>');
			}
			$btn.prop('disabled', false).text('Discover Sitemaps');
		}).fail(function () {
			$results.html('<p class="sf-error">Network error.</p>');
			$btn.prop('disabled', false).text('Discover Sitemaps');
		});
	});

	// Broken link scan trigger.
	$(document).on('click', '#sf-scan-broken-links', function () {
		var $btn = $(this);
		var $results = $('#sf-broken-link-results');
		$btn.prop('disabled', true).text('Scanning...');
		$results.html('<span class="sf-spinner"></span> Scanning pages for broken links...');

		$.post(searchforge.ajax_url, {
			action: 'searchforge_scan_broken_links',
			nonce: searchforge.nonce
		}, function (response) {
			if (response.success) {
				if (response.data.count === 0) {
					$results.html('<p style="color:#155724;font-weight:500;">No broken links found.</p>');
				} else {
					$results.html('<p class="sf-error">' + response.data.count + ' broken link(s) found. Reload the page to see details.</p>');
				}
			} else {
				$results.html('<p class="sf-error">' + (response.data && response.data.message || 'Scan failed.') + '</p>');
			}
			$btn.prop('disabled', false).text('Scan Now');
		}).fail(function () {
			$results.html('<p class="sf-error">Network error.</p>');
			$btn.prop('disabled', false).text('Scan Now');
		});
	});

	// API key management.
	$(document).on('click', '#sf-generate-api-key, #sf-regenerate-api-key', function () {
		var $btn = $(this);
		var isRegen = $btn.attr('id') === 'sf-regenerate-api-key';
		if (isRegen && !confirm('This will invalidate the existing API key. Continue?')) {
			return;
		}
		$btn.prop('disabled', true);

		$.post(searchforge.ajax_url, {
			action: 'searchforge_generate_api_key',
			nonce: searchforge.nonce
		}, function (response) {
			if (response.success) {
				$('#sf-api-key-value').text(response.data.key);
				$('#sf-api-key-display').show();
			} else {
				alert(response.data && response.data.message || 'Failed to generate key.');
			}
			$btn.prop('disabled', false);
		});
	});

	$(document).on('click', '#sf-revoke-api-key', function () {
		if (!confirm('Revoke the API key? External tools will lose access.')) {
			return;
		}
		$.post(searchforge.ajax_url, {
			action: 'searchforge_revoke_api_key',
			nonce: searchforge.nonce
		}, function (response) {
			if (response.success) {
				location.reload();
			}
		});
	});

	// Close modal on outside click.
	$(document).on('click', '#sf-export-modal, #sf-bulk-modal', function (e) {
		if (e.target === this) {
			$(this).hide();
		}
	});

	// Close bulk modal.
	$(document).on('click', '#sf-bulk-modal .sf-modal-close', function () {
		$('#sf-bulk-modal').hide();
	});

	// Bulk select: checkboxes.
	function updateBulkButtons() {
		var count = $('.sf-page-check:checked').length;
		$('#sf-bulk-export, #sf-bulk-ai-brief').prop('disabled', count === 0);
		$('#sf-bulk-count').text(count > 0 ? count + ' selected' : '');
	}

	$(document).on('change', '#sf-select-all, .sf-select-all-th', function () {
		$('.sf-page-check').prop('checked', this.checked);
		updateBulkButtons();
	});

	$(document).on('change', '.sf-page-check', function () {
		var total = $('.sf-page-check').length;
		var checked = $('.sf-page-check:checked').length;
		$('#sf-select-all, .sf-select-all-th').prop('checked', checked === total);
		updateBulkButtons();
	});

	// Bulk export briefs.
	$(document).on('click', '#sf-bulk-export', function () {
		var paths = $('.sf-page-check:checked').map(function () { return $(this).val(); }).get();
		if (!paths.length) return;

		$('#sf-bulk-modal-title').text('Exporting ' + paths.length + ' briefs...');
		$('#sf-bulk-progress').show();
		$('#sf-bulk-output, #sf-bulk-download').hide();
		$('#sf-bulk-fill').css('width', '0%');
		$('#sf-bulk-status').text('Starting...');
		$('#sf-bulk-modal').show();

		var results = [];
		var completed = 0;

		function exportNext() {
			if (completed >= paths.length) {
				// All done — combine and offer download.
				var combined = results.join('\n\n---\n\n');
				$('#sf-bulk-status').text('Done! ' + paths.length + ' briefs exported.');
				$('#sf-bulk-output').text(combined).show();
				$('#sf-bulk-download').data('content', combined).data('filename', 'searchforge-bulk-briefs.md').show();
				return;
			}

			var path = paths[completed];
			$('#sf-bulk-status').text('Exporting: ' + path + ' (' + (completed + 1) + '/' + paths.length + ')');

			$.post(searchforge.ajax_url, {
				action: 'searchforge_export_brief',
				nonce: searchforge.nonce,
				page_path: path,
				brief_type: 'page'
			}, function (response) {
				if (response.success) {
					results.push(response.data.markdown);
				} else {
					results.push('# Error: ' + path + '\n\n' + (response.data && response.data.message || 'Failed'));
				}
				completed++;
				$('#sf-bulk-fill').css('width', (completed / paths.length * 100) + '%');
				exportNext();
			}).fail(function () {
				results.push('# Error: ' + path + '\n\nNetwork error');
				completed++;
				$('#sf-bulk-fill').css('width', (completed / paths.length * 100) + '%');
				exportNext();
			});
		}

		exportNext();
	});

	// Bulk AI brief.
	$(document).on('click', '#sf-bulk-ai-brief', function () {
		var paths = $('.sf-page-check:checked').map(function () { return $(this).val(); }).get();
		if (!paths.length) return;
		if (!confirm('Generate AI briefs for ' + paths.length + ' pages? This may take a while.')) return;

		$('#sf-bulk-modal-title').text('Generating ' + paths.length + ' AI briefs...');
		$('#sf-bulk-progress').show();
		$('#sf-bulk-output, #sf-bulk-download').hide();
		$('#sf-bulk-fill').css('width', '0%');
		$('#sf-bulk-status').text('Starting...');
		$('#sf-bulk-modal').show();

		var results = [];
		var completed = 0;

		function generateNext() {
			if (completed >= paths.length) {
				var combined = results.join('\n\n---\n\n');
				$('#sf-bulk-status').text('Done! ' + paths.length + ' AI briefs generated.');
				$('#sf-bulk-output').text(combined).show();
				$('#sf-bulk-download').data('content', combined).data('filename', 'searchforge-bulk-ai-briefs.md').show();
				return;
			}

			var path = paths[completed];
			$('#sf-bulk-status').text('Generating: ' + path + ' (' + (completed + 1) + '/' + paths.length + ')');

			$.post(searchforge.ajax_url, {
				action: 'searchforge_generate_content_brief',
				nonce: searchforge.nonce,
				page_path: path
			}, function (response) {
				if (response.success) {
					results.push(response.data.brief);
				} else {
					results.push('# Error: ' + path + '\n\n' + (response.data && response.data.message || 'Failed'));
				}
				completed++;
				$('#sf-bulk-fill').css('width', (completed / paths.length * 100) + '%');
				generateNext();
			}).fail(function () {
				results.push('# Error: ' + path + '\n\nNetwork error');
				completed++;
				$('#sf-bulk-fill').css('width', (completed / paths.length * 100) + '%');
				generateNext();
			});
		}

		generateNext();
	});

	// Bulk download button.
	$(document).on('click', '#sf-bulk-download', function () {
		var content = $(this).data('content');
		var filename = $(this).data('filename');
		var blob = new Blob([content], { type: 'text/markdown;charset=utf-8' });
		var url = URL.createObjectURL(blob);
		var a = document.createElement('a');
		a.href = url;
		a.download = filename;
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	});
})(jQuery);
