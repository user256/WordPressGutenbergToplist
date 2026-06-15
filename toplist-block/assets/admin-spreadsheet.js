(function () {
	'use strict';

	var FIELDS = window.toplistSpreadsheetFields || [];
	var LIST_FIELDS = ['bullets', 'payments', 'games', 'withdrawals'];

	function $(id) {
		return document.getElementById(id);
	}

	function parseLines(text) {
		var lines = String(text || '').split(/\r\n|\r|\n/).map(function (l) { return l.trim(); }).filter(Boolean);
		if (!lines.length) {
			return [];
		}
		var header = lines[0].split('|').map(function (p) { return p.trim(); });
		var useHeader = header.length >= 3 && header.every(function (h) { return FIELDS.indexOf(h) !== -1; });
		var start = useHeader ? 1 : 0;
		var rows = [];
		for (var i = start; i < lines.length; i += 1) {
			var parts = lines[i].split('|');
			var row = {};
			FIELDS.forEach(function (field, idx) {
				var val = String(parts[idx] || '').trim();
				if (LIST_FIELDS.indexOf(field) !== -1) {
					row[field] = val ? val.split(';').map(function (x) { return x.trim(); }).filter(Boolean) : [];
				} else {
					row[field] = val;
				}
			});
			rows.push(row);
		}
		return rows;
	}

	function rowsToLines(rows) {
		if (!rows.length) {
			return '';
		}
		var headerLine = FIELDS.join('|');
		var body = rows.map(function (row) {
			return FIELDS.map(function (field) {
				var val = row[field];
				if (LIST_FIELDS.indexOf(field) !== -1) {
					return Array.isArray(val) ? val.join(';') : String(val || '');
				}
				return String(val || '');
			}).join('|');
		});
		return [headerLine].concat(body).join('\n');
	}

	function renderTable(tbody, rows) {
		tbody.innerHTML = '';
		rows.forEach(function (row, rowIndex) {
			var tr = document.createElement('tr');
			tr.dataset.rowIndex = String(rowIndex);
			FIELDS.forEach(function (field) {
				var td = document.createElement('td');
				var input = document.createElement('input');
				input.type = 'text';
				input.className = 'toplist-spreadsheet-input';
				input.dataset.field = field;
				var val = row[field];
				input.value = LIST_FIELDS.indexOf(field) !== -1
					? (Array.isArray(val) ? val.join('; ') : String(val || ''))
					: String(val || '');
				td.appendChild(input);
				tr.appendChild(td);
			});
			var actionTd = document.createElement('td');
			var removeBtn = document.createElement('button');
			removeBtn.type = 'button';
			removeBtn.className = 'button-link-delete';
			removeBtn.textContent = 'Remove';
			removeBtn.addEventListener('click', function () {
				rows.splice(rowIndex, 1);
				syncToTextarea(rows);
				renderTable(tbody, rows);
			});
			actionTd.appendChild(removeBtn);
			tr.appendChild(actionTd);
			tbody.appendChild(tr);
		});
	}

	function readRowsFromTable(tbody) {
		var rows = [];
		tbody.querySelectorAll('tr').forEach(function (tr) {
			var row = {};
			tr.querySelectorAll('.toplist-spreadsheet-input').forEach(function (input) {
				var field = input.dataset.field;
				var val = input.value.trim();
				if (LIST_FIELDS.indexOf(field) !== -1) {
					row[field] = val ? val.split(';').map(function (x) { return x.trim(); }).filter(Boolean) : [];
				} else {
					row[field] = val;
				}
			});
			rows.push(row);
		});
		return rows;
	}

	function syncToTextarea(rows) {
		var raw = $('toplist_raw_content');
		if (raw) {
			raw.value = rowsToLines(rows);
		}
	}

	function init() {
		var root = document.querySelector('[data-toplist-spreadsheet]');
		var raw = $('toplist_raw_content');
		var tbody = $('toplist-spreadsheet-body');
		if (!root || !raw || !tbody) {
			return;
		}

		var rows = parseLines(raw.value);
		renderTable(tbody, rows);

		root.querySelectorAll('[data-toplist-tab]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var tab = btn.getAttribute('data-toplist-tab');
				root.querySelectorAll('[data-toplist-tab]').forEach(function (b) {
					b.classList.toggle('nav-tab-active', b.getAttribute('data-toplist-tab') === tab);
				});
				root.querySelectorAll('[data-toplist-panel]').forEach(function (panel) {
					panel.style.display = panel.getAttribute('data-toplist-panel') === tab ? '' : 'none';
				});
				if (tab === 'spreadsheet') {
					rows = parseLines(raw.value);
					renderTable(tbody, rows);
				}
			});
		});

		tbody.addEventListener('input', function () {
			rows = readRowsFromTable(tbody);
			syncToTextarea(rows);
		});

		var addBtn = $('toplist-spreadsheet-add-row');
		if (addBtn) {
			addBtn.addEventListener('click', function () {
				var empty = {};
				FIELDS.forEach(function (field) {
					empty[field] = LIST_FIELDS.indexOf(field) !== -1 ? [] : '';
				});
				rows.push(empty);
				renderTable(tbody, rows);
				syncToTextarea(rows);
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
