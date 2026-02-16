/**
 * WP Schema Manager — Bulk Assignment JavaScript
 *
 * Handles taxonomy term loading and AJAX bulk assignment.
 *
 * @package WPSchemaManager
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		var $form = $('#wpschema-bulk-form');
		var $taxonomySelect = $('#wpschema-bulk-taxonomy');
		var $termSelect = $('#wpschema-bulk-term');
		var $submitBtn = $('#wpschema-bulk-submit');
		var $status = $('#wpschema-bulk-status');
		var $statusText = $('#wpschema-bulk-status-text');
		var $result = $('#wpschema-bulk-result');
		var $resultText = $('#wpschema-bulk-result-text');

		if (!$form.length) {
			return;
		}

		// Load terms when taxonomy changes.
		$taxonomySelect.on('change', function () {
			var taxonomy = $(this).val();

			$termSelect.prop('disabled', true).empty();

			if (!taxonomy) {
				$termSelect.append(
					$('<option>', { value: '', text: wpschemaBulk.strings.selectTaxFirst || '— Select a taxonomy first —' })
				);
				return;
			}

			$termSelect.append(
				$('<option>', { value: '', text: '— Loading... —' })
			);

			$.ajax({
				url: wpschemaBulk.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpschema_get_terms',
					nonce: wpschemaBulk.nonce,
					taxonomy: taxonomy
				},
				success: function (response) {
					$termSelect.empty();

					if (response.success && response.data && response.data.length > 0) {
						$termSelect.append(
							$('<option>', { value: '', text: '— Select Term —' })
						);
						$.each(response.data, function (i, term) {
							$termSelect.append(
								$('<option>', { value: term.id, text: term.name + ' (' + term.count + ')' })
							);
						});
						$termSelect.prop('disabled', false);
					} else {
						$termSelect.append(
							$('<option>', { value: '', text: '— No terms found —' })
						);
					}
				},
				error: function () {
					$termSelect.empty().append(
						$('<option>', { value: '', text: '— Error loading terms —' })
					);
				}
			});
		});

		// Handle form submission.
		$form.on('submit', function (e) {
			e.preventDefault();

			var taxonomy = $taxonomySelect.val();
			var termId = $termSelect.val();
			var schemaType = $('#wpschema-bulk-schema-type').val();

			if (!taxonomy || !termId || !schemaType) {
				alert('Please select a taxonomy, term, and schema type.');
				return;
			}

			if (!confirm(wpschemaBulk.strings.confirm)) {
				return;
			}

			$submitBtn.prop('disabled', true);
			$result.hide();
			$status.show();
			$statusText.text(wpschemaBulk.strings.processing);

			$.ajax({
				url: wpschemaBulk.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpschema_bulk_assign',
					nonce: wpschemaBulk.nonce,
					taxonomy: taxonomy,
					term_id: termId,
					schema_type: schemaType,
					enable_schema: $('input[name="enable_schema"]').is(':checked') ? 1 : 0
				},
				success: function (response) {
					$status.hide();

					if (response.success) {
						$resultText.text(response.data.message);
						$result.show();
					} else {
						alert(response.data || wpschemaBulk.strings.error);
					}
				},
				error: function () {
					$status.hide();
					alert(wpschemaBulk.strings.error);
				},
				complete: function () {
					$submitBtn.prop('disabled', false);
				}
			});
		});
	});
})(jQuery);
