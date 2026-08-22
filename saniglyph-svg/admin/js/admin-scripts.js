/**
 * SaniGlyph SVG admin interactions.
 */
/* global sgsvgAdmin */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.getElementById('sgsvg-settings-form');
		var save = document.getElementById('sgsvg-save');
		var status = document.getElementById('sgsvg-status');

		document.querySelectorAll('.sgsvg-role input[type="checkbox"]').forEach(function (input) {
			var sync = function () {
				input.closest('.sgsvg-role').classList.toggle('is-active', input.checked);
			};
			input.addEventListener('change', sync);
			sync();
		});

		document.querySelectorAll('[data-sgsvg-copy]').forEach(function (button) {
			button.addEventListener('click', function () {
				var target = document.querySelector(button.getAttribute('data-sgsvg-copy'));
				if (!target || !navigator.clipboard) {
					return;
				}
				navigator.clipboard.writeText(target.textContent).then(function () {
					showStatus('success', sgsvgAdmin.i18n.copied);
				});
			});
		});

		if (!form || !window.fetch) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			setLoading(true);
			hideStatus();

			var data = new FormData(form);
			data.set('action', 'sgsvg_save_settings');
			data.set('nonce', sgsvgAdmin.nonce);

			fetch(sgsvgAdmin.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: data
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (response) {
					if (response && response.success) {
						showStatus('success', (response.data && response.data.message) || sgsvgAdmin.i18n.saved);
						return;
					}
					throw new Error((response && response.data && response.data.message) || sgsvgAdmin.i18n.error);
				})
				.catch(function (error) {
					showStatus('error', error.message || sgsvgAdmin.i18n.error);
				})
				.finally(function () {
					setLoading(false);
				});
		});

		function setLoading(loading) {
			if (!save) {
				return;
			}
			save.disabled = loading;
			save.classList.toggle('is-loading', loading);
		}

		function showStatus(type, message) {
			if (!status) {
				return;
			}
			status.className = 'sgsvg-status is-visible is-' + type;
			status.querySelector('.sgsvg-status-text').textContent = message;
			status.querySelector('.dashicons').className = 'dashicons ' + (type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning');
			window.clearTimeout(showStatus.timer);
			showStatus.timer = window.setTimeout(hideStatus, 4500);
		}

		function hideStatus() {
			if (status) {
				status.className = 'sgsvg-status';
			}
		}
	});
}());
