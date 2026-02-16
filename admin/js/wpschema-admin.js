/**
 * WP Schema Manager — Admin JavaScript
 *
 * Handles the schema preview refresh functionality.
 *
 * @package WPSchemaManager
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		var $refreshBtn = $('#wpschema-refresh-preview');
		var $output = $('#wpschema-preview-output');
		var $status = $('.wpschema-preview-status');

		if (!$refreshBtn.length) {
			return;
		}

		$refreshBtn.on('click', function () {
			$refreshBtn.prop('disabled', true);
			$status.text(wpschemaPreview.refreshing || 'Refreshing...');

			$.ajax({
				url: wpschemaPreview.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpschema_preview',
					nonce: wpschemaPreview.nonce,
					post_id: wpschemaPreview.postId
				},
				success: function (response) {
					if (response.success && response.data && response.data.schema) {
						$output.text(response.data.schema);
						$status.text('Updated.');
					} else {
						$status.text('Error loading preview.');
					}
				},
				error: function () {
					$status.text('Request failed.');
				},
				complete: function () {
					$refreshBtn.prop('disabled', false);
					setTimeout(function () {
						$status.text('');
					}, 3000);
				}
			});
		});
	});
})(jQuery);
