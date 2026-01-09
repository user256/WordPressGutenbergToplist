
(function () {
	console.log('Toplist: Frontend view.js loaded');

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-toplist-toggle="details"]');
		if (!btn) return;
		var item = btn.closest('li');
		if (!item) return;
		var details = item.querySelector('[data-toplist-details]');
		if (!details) return;

		var isHidden = details.style.display === 'none';
		details.style.display = isHidden ? 'block' : 'none';
		btn.textContent = isHidden ? 'Hide Details' : 'Show Details';

		console.log('Toplist: Toggled details, now ' + (isHidden ? 'visible' : 'hidden'));
	});

	console.log('Toplist: Click handler registered');
})();
