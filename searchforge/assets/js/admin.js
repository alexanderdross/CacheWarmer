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

	// Close modal on outside click.
	$(document).on('click', '#sf-export-modal', function (e) {
		if (e.target === this) {
			$(this).hide();
		}
	});
})(jQuery);
