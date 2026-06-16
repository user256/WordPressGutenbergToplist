(function () {
	'use strict';

	var root = document.getElementById('toplist-live-preview-root');
	if (!root || !window.toplistEditorUx) {
		return;
	}

	var frame = document.getElementById('toplist-live-preview-frame');
	var postId = root.getAttribute('data-post-id');
	var textarea = document.getElementById('toplist_raw_content');
	var timer = null;

	function collectOverridePayload() {
		return {};
	}

	function requestPreview() {
		if (!textarea || !frame) {
			return;
		}
		var body = new URLSearchParams();
		body.append('action', 'toplist_preview_list');
		body.append('nonce', toplistEditorUx.nonce);
		body.append('post_id', postId);
		body.append('content', textarea.value);

		frame.innerHTML = '<p>Loading preview…</p>';
		fetch(toplistEditorUx.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (json) {
				if (json && json.success && json.data && json.data.html) {
					frame.innerHTML = json.data.html;
				} else {
					frame.innerHTML = '<p>Preview unavailable.</p>';
				}
			})
			.catch(function () {
				frame.innerHTML = '<p>Preview request failed.</p>';
			});
	}

	function schedulePreview() {
		if (timer) {
			window.clearTimeout(timer);
		}
		timer = window.setTimeout(requestPreview, 400);
	}

	if (textarea) {
		textarea.addEventListener('input', schedulePreview);
		requestPreview();
	}

	document.querySelectorAll('.toplist-override-inherit').forEach(function (inheritBox) {
		inheritBox.addEventListener('change', function () {
			var targetId = inheritBox.getAttribute('data-target');
			var target = targetId ? document.getElementById(targetId) : null;
			if (!target) {
				return;
			}
			if (inheritBox.checked) {
				target.disabled = true;
				target.parentElement.style.opacity = '0.55';
			} else {
				target.disabled = false;
				target.parentElement.style.opacity = '1';
			}
			schedulePreview();
		});
	});

	document.querySelectorAll('[name^="toplist_override_toggle"]').forEach(function (el) {
		el.addEventListener('change', schedulePreview);
	});

	var cssField = document.getElementById('toplist_list_css');
	if (cssField) {
		cssField.addEventListener('input', schedulePreview);
	}
})();
