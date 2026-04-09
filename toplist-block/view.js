
(function () {
	function setDetailsState(item, expanded) {
		var btn = item.querySelector('[data-toplist-toggle="details"]');
		var details = item.querySelector('[data-toplist-details]');
		if (!btn || !details) return;

		details.style.display = expanded ? 'block' : 'none';
		btn.textContent = expanded ? 'Hide Details' : 'Show Details';
		btn.classList.toggle('is-collapsed', !expanded);
		btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
	}

	function initExistingState() {
		var lists = document.querySelectorAll('.toplist');
		lists.forEach(function (list) {
			var items = list.querySelectorAll('li.operator-item');
			items.forEach(function (item) {
				var btn = item.querySelector('[data-toplist-toggle="details"]');
				if (!btn) return;
				setDetailsState(item, btn.getAttribute('aria-expanded') === 'true');
			});
		});
	}

	initExistingState();

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-toplist-toggle="details"]');
		if (!btn) return;
		var item = btn.closest('li');
		if (!item) return;
		var details = item.querySelector('[data-toplist-details]');
		if (!details) return;

		var isHidden = details.style.display === 'none';
		setDetailsState(item, isHidden);
	});
})();
