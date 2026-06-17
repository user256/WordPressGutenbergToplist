(function () {
	const tabButtons = document.querySelectorAll('[data-tab-target]');
	const tabPanels = document.querySelectorAll('.toplist-settings-panel');
	const textarea = document.getElementById('toplist_global_css');
	const msg = document.getElementById('toplist-theme-msg');
	const appendBox = document.getElementById('toplist-append-mode');

	function setActiveTab(targetId) {
		tabButtons.forEach((button) => {
			const isActive = button.getAttribute('data-tab-target') === targetId;
			button.classList.toggle('nav-tab-active', isActive);
			button.setAttribute('aria-selected', isActive ? 'true' : 'false');
		});

		tabPanels.forEach((panel) => {
			panel.hidden = panel.id !== targetId;
		});
	}

	tabButtons.forEach((button) => {
		button.addEventListener('click', () => {
			const targetId = button.getAttribute('data-tab-target');
			setActiveTab(targetId);
		});
	});

	if (tabButtons.length) {
		setActiveTab(tabButtons[0].getAttribute('data-tab-target'));
	}

	const toggleCheckboxes = document.querySelectorAll('.toplist-toggle-checkbox');
	const enableAllBtn = document.getElementById('toplist-toggle-enable-all');
	const disableAllBtn = document.getElementById('toplist-toggle-disable-all');

	if (enableAllBtn) {
		enableAllBtn.addEventListener('click', () => {
			toggleCheckboxes.forEach((cb) => cb.checked = true);
		});
	}

	if (disableAllBtn) {
		disableAllBtn.addEventListener('click', () => {
			toggleCheckboxes.forEach((cb) => cb.checked = false);
		});
	}

	if (!textarea) {
		return;
	}

	function setMessage(text) {
		if (!msg) return;
		msg.textContent = text;
		window.clearTimeout(setMessage._t);
		setMessage._t = window.setTimeout(() => msg.textContent = '', 2500);
	}

	function applyCss(css, { append = false } = {}) {
		const trimmed = css.trim();
		if (!trimmed) return;

		if (append && textarea.value.trim()) {
			textarea.value = textarea.value.replace(/\s+$/, '') + "\n\n" + trimmed + "\n";
		} else {
			textarea.value = trimmed + "\n";
		}
		textarea.focus();
		setMessage(append ? 'Appended CSS ✅' : 'Applied CSS ✅');
	}

	// Click-to-apply themes
	document.querySelectorAll('.toplist-theme-btn').forEach(btn => {
		btn.addEventListener('click', () => {
			const css = btn.getAttribute('data-css') || '';
			applyCss(css, { append: false });
		});
	});

	// Clear button
	const clearBtn = document.getElementById('toplist-clear-css');
	if (clearBtn) {
		clearBtn.addEventListener('click', () => {
			textarea.value = '';
			setMessage('Cleared ✅');
			textarea.focus();
		});
	}

	// Custom colour generator
	function val(id) {
		const el = document.getElementById(id);
		return el ? el.value : '';
	}

	const applyCustomBtn = document.getElementById('toplist-apply-custom');
	if (applyCustomBtn) {
		applyCustomBtn.addEventListener('click', () => {
			const primary = val('toplist-color-primary');
			const secondary = val('toplist-color-secondary');
			const hover = val('toplist-color-hover');
			const cardbg = val('toplist-color-cardbg');
			const text = val('toplist-color-text');

			// Keep it simple + safe: generate only the selectors you already documented.
			const css =
				`.toplist .operator-column-ranking-v2{
background: linear-gradient(135deg, ${primary} 0%, ${secondary} 100%);
}

.toplist .operator-playnow-column-v2 .button-blue-v2{
background: linear-gradient(135deg, ${primary} 0%, ${secondary} 100%);
}

.toplist .operator-item:hover{
border-color: ${hover};
}

.toplist .operator-item{
background: ${cardbg};
color: ${text};
}`;

			applyCss(css, { append: !!appendBox && appendBox.checked });
		});
	}
})();
