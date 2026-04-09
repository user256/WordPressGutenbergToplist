(function (blocks, element, blockEditor, components, i18n, apiFetch) {
	const el = element.createElement;
	const __ = i18n.__;

	const useState = element.useState;
	const useEffect = element.useEffect;
	const useBlockProps = blockEditor.useBlockProps;
	const InspectorControls = blockEditor.InspectorControls;

	const TextControl = components.TextControl;
	const TextareaControl = components.TextareaControl;
	const SelectControl = components.SelectControl;
	const RadioControl = components.RadioControl;
	const Button = components.Button;
	const Notice = components.Notice;
	const TabPanel = components.TabPanel;
	const CheckboxControl = components.CheckboxControl;
	const Spinner = components.Spinner;
	const globalSettings = window.toplistBlockSettings || {};
	const GLOBAL_DEFAULT_HEADER_ENABLED = !!globalSettings.globalDefaultHeaderEnabled;
	const GLOBAL_DEFAULT_HEADER_ROW = (globalSettings.globalDefaultHeaderRow || '').toString().trim();
	const GLOBAL_TOPLIST_HEADING = (globalSettings.globalToplistHeading || '').toString().trim();

	const FIELD_ORDER = [
		'operator',
		'product',
		'offer',
		'href',
		'logo',
		'year',
		'ctaText',
		'terms',
		'bullets',
		'payout',
		'code',
		'rating',
		'regulator',
		'payments',
		'games',
		'liveGames',
		'smallPrint',
		'readReviewHref',
		'readReviewText',
		'withdrawals'
	];

	const MULTI_VALUE_FIELDS = {
		bullets: true,
		payments: true,
		games: true,
		withdrawals: true
	};

	const FIELD_LOOKUP = FIELD_ORDER.reduce(function (acc, field) {
		acc[field.toLowerCase()] = field;
		return acc;
	}, {});

	function splitList(value) {
		return (value || '').split(';').map(function (part) {
			return part.trim();
		}).filter(Boolean);
	}

	function normalizeDirectiveToken(token) {
		var raw = (token || '').trim();
		var excluded = false;
		var field;

		if (!raw) {
			return { field: '', excluded: false, recognized: false };
		}

		if (raw.charAt(0) === '-' || raw.charAt(0) === '!') {
			excluded = true;
			raw = raw.slice(1).trim();
		}

		field = FIELD_LOOKUP[raw.toLowerCase()] || '';
		return {
			field: field,
			excluded: excluded,
			recognized: field !== ''
		};
	}

	function looksLikeDataRow(parts) {
		var i;
		for (i = 0; i < parts.length; i += 1) {
			if (/https?:\/\//i.test(parts[i] || '')) {
				return true;
			}
		}
		return false;
	}

	function detectHeader(parts) {
		var recognized = 0;
		var i;

		for (i = 0; i < parts.length; i += 1) {
			if (normalizeDirectiveToken(parts[i]).recognized) {
				recognized += 1;
			}
		}

		// First row is treated as header directives only when it looks like schema keys.
		return recognized >= 3 && !looksLikeDataRow(parts);
	}

	function parseLineByFixedColumns(parts, defaults) {
		return {
			operator: (parts[0] || '').trim(),
			product: (parts[1] || '').trim(),
			offer: (parts[2] || '').trim(),
			href: (parts[3] || '').trim(),
			logo: (parts[4] || '').trim(),
			year: (parts[5] || '').trim(),
			ctaText: (parts[6] || defaults.defaultCtaText).trim(),
			terms: (parts[7] || '').trim(),
			bullets: splitList(parts[8] || ''),
			payout: (parts[9] || '').trim(),
			code: (parts[10] || '').trim(),
			rating: (parts[11] || '').trim(),
			regulator: (parts[12] || '').trim(),
			payments: splitList(parts[13] || ''),
			games: splitList(parts[14] || ''),
			liveGames: (parts[15] || '').trim(),
			smallPrint: (parts[16] || '').trim(),
			readReviewHref: (parts[17] || '').trim(),
			readReviewText: (parts[18] || defaults.defaultReadReviewText).trim(),
			withdrawals: splitList(parts[19] || '')
		};
	}

	function parseLineByHeader(parts, headerTokens, defaults) {
		var item = {
			operator: '',
			product: '',
			offer: '',
			href: '',
			logo: '',
			year: '',
			ctaText: '',
			terms: '',
			bullets: [],
			payout: '',
			code: '',
			rating: '',
			regulator: '',
			payments: [],
			games: [],
			liveGames: '',
			smallPrint: '',
			readReviewHref: '',
			readReviewText: '',
			withdrawals: []
		};
		var i;

		// Header-driven mapping: data columns map by header position.
		for (i = 0; i < headerTokens.length; i += 1) {
			var token = headerTokens[i];
			var rawValue = (parts[i] || '').trim();

			if (!token.recognized || token.excluded) {
				continue;
			}

			if (MULTI_VALUE_FIELDS[token.field]) {
				item[token.field] = splitList(rawValue);
			} else {
				item[token.field] = rawValue;
			}
		}

		if (!item.ctaText) {
			item.ctaText = defaults.defaultCtaText;
		}

		if (!item.readReviewText) {
			item.readReviewText = defaults.defaultReadReviewText;
		}

		return item;
	}

	function itemHasContent(item) {
		var scalarFields = ['operator', 'product', 'offer', 'href', 'logo', 'year', 'terms', 'payout', 'code', 'rating', 'regulator', 'liveGames', 'smallPrint', 'readReviewHref'];
		var listFields = ['bullets', 'payments', 'games', 'withdrawals'];
		var i;

		for (i = 0; i < scalarFields.length; i += 1) {
			if ((item[scalarFields[i]] || '').trim() !== '') {
				return true;
			}
		}

		for (i = 0; i < listFields.length; i += 1) {
			if (item[listFields[i]] && item[listFields[i]].length) {
				return true;
			}
		}

		return false;
	}

	function parseLinesToItems(text, options) {
		var defaults = {
			defaultCtaText: (options && options.defaultCtaText) || 'Visit',
			defaultReadReviewText: (options && options.defaultReadReviewText) || 'Read Review',
			defaultHeaderRow: (options && options.defaultHeaderRow) || ''
		};
		var lines = (text || '').split(/\r?\n/).map(function (line) {
			return line.trim();
		}).filter(Boolean);
		var headerTokens = [];
		var hasHeader = false;
		var includes = [];
		var excludes = [];
		var startIndex = 0;
		var items = [];
		var i;

		if (!lines.length) {
			return {
				items: [],
				directives: {
					hasHeader: false,
					includes: [],
					excludes: []
				}
			};
		}

		headerTokens = lines[0].split('|').map(normalizeDirectiveToken);
		hasHeader = detectHeader(lines[0].split('|'));

		if (!hasHeader && defaults.defaultHeaderRow.trim()) {
			headerTokens = defaults.defaultHeaderRow.split('|').map(normalizeDirectiveToken);
			hasHeader = true;
			startIndex = 0;
		}

		if (hasHeader) {
			startIndex = 1;
			for (i = 0; i < headerTokens.length; i += 1) {
				if (!headerTokens[i].recognized) {
					continue;
				}
				if (headerTokens[i].excluded) {
					excludes.push(headerTokens[i].field);
				} else {
					includes.push(headerTokens[i].field);
				}
			}
		}

		for (i = startIndex; i < lines.length; i += 1) {
			var parts = lines[i].split('|');
			var item = hasHeader
				? parseLineByHeader(parts, headerTokens, defaults)
				: parseLineByFixedColumns(parts, defaults);

			if (itemHasContent(item)) {
				items.push(item);
			}
		}

		return {
			items: items,
			directives: {
				hasHeader: hasHeader,
				includes: includes,
				excludes: excludes
			}
		};
	}

	function itemsToLines(items, defaults) {
		var ctaFallback = (defaults && defaults.defaultCtaText) || 'Visit';
		var readFallback = (defaults && defaults.defaultReadReviewText) || 'Read Review';

		return (items || []).map(function (it) {
			function joinList(v) {
				return Array.isArray(v) ? v.join(';') : ((v || '').toString().trim());
			}

			return [
				it.operator || '',
				it.product || '',
				it.offer || '',
				it.href || '',
				it.logo || '',
				it.year || '',
				it.ctaText || ctaFallback,
				it.terms || '',
				joinList(it.bullets),
				it.payout || '',
				it.code || '',
				it.rating || '',
				it.regulator || '',
				joinList(it.payments),
				joinList(it.games),
				it.liveGames || '',
				it.smallPrint || '',
				it.readReviewHref || '',
				it.readReviewText || readFallback,
				joinList(it.withdrawals)
			].join('|');
		}).join('\n');
	}

	blocks.registerBlockType('toplist/rankings', {
		apiVersion: 2,
		title: __('Toplist', 'toplist'),
		icon: 'list-view',
		category: 'widgets',
		attributes: {
			items: { type: 'array', default: [] },
			listId: { type: 'number', default: 1 },
			listType: { type: 'string', default: 'product-ranking-best' },
			disclaimer: { type: 'string', default: '#ad. 18+. Gamble Responsibly. GambleAware.org.' },
			customCSS: { type: 'string', default: '' },
			defaultCtaText: { type: 'string', default: 'Visit' },
			defaultReadReviewText: { type: 'string', default: 'Read Review' },
			showYear: { type: 'boolean', default: true },
			showLogo: { type: 'boolean', default: true },
			showTerms: { type: 'boolean', default: true },
			showBullets: { type: 'boolean', default: true },
			showOffer: { type: 'boolean', default: true },
			showPayout: { type: 'boolean', default: true },
			showCode: { type: 'boolean', default: true },
			showRating: { type: 'boolean', default: true },
			showRegulator: { type: 'boolean', default: true },
			showPayments: { type: 'boolean', default: true },
			showGames: { type: 'boolean', default: true },
			showLiveGames: { type: 'boolean', default: true },
			showSmallPrint: { type: 'boolean', default: true },
			showReadReview: { type: 'boolean', default: true },
			showWithdrawals: { type: 'boolean', default: true },
			fieldIncludes: { type: 'array', default: [] },
			fieldExcludes: { type: 'array', default: [] },
			savedToplistId: { type: 'number', default: 0 },
			savedToplistMode: { type: 'string', default: 'linked' },
			defaultHeaderMode: { type: 'string', default: 'global' },
			defaultHeaderRow: { type: 'string', default: '' },
			headingMode: { type: 'string', default: 'global' },
			headingText: { type: 'string', default: '' },
			_lines: { type: 'string', default: '' }
		},
		edit: function (props) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var lines = attrs._lines || itemsToLines(attrs.items, {
				defaultCtaText: attrs.defaultCtaText,
				defaultReadReviewText: attrs.defaultReadReviewText
			});
			var [savedLists, setSavedLists] = useState([]);
			var [isLoadingLists, setIsLoadingLists] = useState(false);
			var [isLoadingSaved, setIsLoadingSaved] = useState(false);
			var [newListName, setNewListName] = useState('');
			var [statusText, setStatusText] = useState('');

			function getEffectiveDefaultHeaderRow(overrides) {
				var mode = (overrides && overrides.defaultHeaderMode) || attrs.defaultHeaderMode || 'global';
				var customRow = ((overrides && overrides.defaultHeaderRow) || attrs.defaultHeaderRow || '').trim();
				if (mode === 'custom') {
					return customRow;
				}
				if (mode === 'global') {
					return GLOBAL_DEFAULT_HEADER_ENABLED ? GLOBAL_DEFAULT_HEADER_ROW : '';
				}
				return '';
			}

			function parseAndSet(nextLines, defaultsOverride, extraAttrs) {
				var defaults = {
					defaultCtaText: (defaultsOverride && defaultsOverride.defaultCtaText) || attrs.defaultCtaText,
					defaultReadReviewText: (defaultsOverride && defaultsOverride.defaultReadReviewText) || attrs.defaultReadReviewText,
					defaultHeaderRow: (defaultsOverride && Object.prototype.hasOwnProperty.call(defaultsOverride, 'defaultHeaderRow'))
						? defaultsOverride.defaultHeaderRow
						: getEffectiveDefaultHeaderRow(extraAttrs)
				};
				var parsed = parseLinesToItems(nextLines, defaults);
				setAttributes(Object.assign({
					_lines: nextLines,
					items: parsed.items,
					fieldIncludes: parsed.directives.includes,
					fieldExcludes: parsed.directives.excludes
				}, extraAttrs || {}));
			}

			function fetchSavedLists() {
				setIsLoadingLists(true);
				apiFetch({ path: '/toplist-block/v1/toplists' }).then(function (data) {
					setSavedLists(Array.isArray(data) ? data : []);
				}).catch(function () {
					setSavedLists([]);
				}).finally(function () {
					setIsLoadingLists(false);
				});
			}

			function loadSavedToplist(id, options) {
				var settings = options || {};
				if (!id) {
					return;
				}

				setIsLoadingSaved(true);
				apiFetch({ path: '/toplist-block/v1/toplists/' + id }).then(function (data) {
					var content = (data && data.content) ? String(data.content) : '';
					var differs = lines.trim() !== content.trim();
					if (!settings.force && differs) {
						var ok = window.confirm(__('Replace current block content with the saved toplist?', 'toplist'));
						if (!ok) {
							return;
						}
					}
					if (!differs && settings.skipIfSame) {
						return;
					}
					parseAndSet(content, null, {
						savedToplistId: id,
						savedToplistMode: settings.mode || attrs.savedToplistMode || 'linked'
					});
					setStatusText(__('Loaded saved toplist.', 'toplist'));
				}).catch(function () {
					setStatusText(__('Unable to load saved toplist.', 'toplist'));
				}).finally(function () {
					setIsLoadingSaved(false);
				});
			}

			function handleSavedToplistSelect(value) {
				var id = parseInt(value || '0', 10) || 0;
				if (!id) {
					setAttributes({ savedToplistId: 0, savedToplistMode: 'copied' });
					setStatusText(__('Using local custom content.', 'toplist'));
					return;
				}
				setAttributes({ savedToplistId: id, savedToplistMode: 'linked' });
				loadSavedToplist(id, { mode: 'linked' });
			}

			function saveCurrentAsNewToplist() {
				var name = (newListName || '').trim();
				if (!name) {
					window.alert(__('Please enter a name for the saved toplist.', 'toplist'));
					return;
				}
				if (!lines.trim()) {
					window.alert(__('Cannot save an empty toplist.', 'toplist'));
					return;
				}

				setIsLoadingSaved(true);
				apiFetch({
					path: '/toplist-block/v1/toplists',
					method: 'POST',
					data: {
						name: name,
						content: lines
					}
				}).then(function (created) {
					var id = created && created.id ? parseInt(created.id, 10) : 0;
					setNewListName('');
					if (id) {
						setAttributes({ savedToplistId: id, savedToplistMode: 'linked' });
						fetchSavedLists();
						setStatusText(__('Saved new toplist to library.', 'toplist'));
					}
				}).catch(function () {
					setStatusText(__('Unable to save toplist to library.', 'toplist'));
				}).finally(function () {
					setIsLoadingSaved(false);
				});
			}

			function updateDefaultCtaText(value) {
				var next = (value || '').trim() || 'Visit';
				var parsed = parseLinesToItems(lines, {
					defaultCtaText: next,
					defaultReadReviewText: attrs.defaultReadReviewText,
					defaultHeaderRow: getEffectiveDefaultHeaderRow()
				});
				setAttributes({
					defaultCtaText: next,
					items: parsed.items,
					fieldIncludes: parsed.directives.includes,
					fieldExcludes: parsed.directives.excludes
				});
			}

			function updateDefaultReadReviewText(value) {
				var next = (value || '').trim() || 'Read Review';
				var parsed = parseLinesToItems(lines, {
					defaultCtaText: attrs.defaultCtaText,
					defaultReadReviewText: next,
					defaultHeaderRow: getEffectiveDefaultHeaderRow()
				});
				setAttributes({
					defaultReadReviewText: next,
					items: parsed.items,
					fieldIncludes: parsed.directives.includes,
					fieldExcludes: parsed.directives.excludes
				});
			}

			function updateDefaultHeaderMode(value) {
				var mode = value || 'global';
				var parsed = parseLinesToItems(lines, {
					defaultCtaText: attrs.defaultCtaText,
					defaultReadReviewText: attrs.defaultReadReviewText,
					defaultHeaderRow: getEffectiveDefaultHeaderRow({ defaultHeaderMode: mode })
				});
				setAttributes({
					defaultHeaderMode: mode,
					items: parsed.items,
					fieldIncludes: parsed.directives.includes,
					fieldExcludes: parsed.directives.excludes
				});
			}

			function updateDefaultHeaderRow(value) {
				var row = (value || '').trim();
				var parsed = parseLinesToItems(lines, {
					defaultCtaText: attrs.defaultCtaText,
					defaultReadReviewText: attrs.defaultReadReviewText,
					defaultHeaderRow: getEffectiveDefaultHeaderRow({ defaultHeaderMode: attrs.defaultHeaderMode, defaultHeaderRow: row })
				});
				setAttributes({
					defaultHeaderRow: row,
					items: parsed.items,
					fieldIncludes: parsed.directives.includes,
					fieldExcludes: parsed.directives.excludes
				});
			}

			function updateHeadingMode(value) {
				setAttributes({ headingMode: value || 'global' });
			}

			function updateHeadingText(value) {
				setAttributes({ headingText: (value || '').trim() });
			}

			function addExample() {
				var example = 'Mr Vegas|Mr Vegas Casino|100% Welcome Bonus up to £200 + 11 Free Spins|https://example.com|https://via.placeholder.com/150x100.png?text=Logo|2020|Visit Casino|Full Terms Apply.|Extensive slots;Weekly cashback;Fast payouts|Instant|LUCKY26|4.9|UK Gambling Commission|Visa;PayPal;Skrill|Slots;Roulette;Blackjack|400+|18+. Wagering applies.|https://example.com/review|Read Review|Instant;Bank transfer';
				var next = (lines ? (lines + '\n') : '') + example;
				parseAndSet(next);
			}

			useEffect(function () {
				fetchSavedLists();
			}, []);

			useEffect(function () {
				if (attrs.savedToplistMode === 'linked' && (attrs.savedToplistId || 0) > 0) {
					loadSavedToplist(attrs.savedToplistId, { force: true, skipIfSame: true, mode: 'linked' });
				}
			}, [attrs.savedToplistId, attrs.savedToplistMode]);

			function renderLibraryTab() {
				var options = [{ label: __('— None (custom) —', 'toplist'), value: '0' }].concat(savedLists.map(function (list) {
					return { label: list.name + ' (#' + list.id + ')', value: String(list.id) };
				}));

				return el('div', {},
					isLoadingLists && el(Spinner, {}),
					el(SelectControl, {
						label: __('Use saved toplist', 'toplist'),
						value: String(attrs.savedToplistId || 0),
						options: options,
						onChange: handleSavedToplistSelect
					}),
					el(RadioControl, {
						label: __('Saved toplist mode', 'toplist'),
						selected: attrs.savedToplistMode || 'linked',
						options: [
							{ label: __('Linked (live updates)', 'toplist'), value: 'linked' },
							{ label: __('Copied (local only)', 'toplist'), value: 'copied' }
						],
						onChange: function (mode) {
							setAttributes({ savedToplistMode: mode });
						}
					}),
					el('div', { style: { display: 'flex', gap: 8, flexWrap: 'wrap', marginBottom: 10 } },
						el(Button, {
							variant: 'secondary',
							disabled: !(attrs.savedToplistId > 0) || isLoadingSaved,
							onClick: function () { loadSavedToplist(attrs.savedToplistId, { mode: attrs.savedToplistMode || 'linked' }); }
						}, __('Load into block', 'toplist')),
						el(Button, {
							variant: 'secondary',
							disabled: !(attrs.savedToplistId > 0) || isLoadingSaved,
							onClick: function () { loadSavedToplist(attrs.savedToplistId, { force: true, mode: attrs.savedToplistMode || 'linked' }); }
						}, __('Refresh from saved', 'toplist')),
						el(Button, {
							variant: 'tertiary',
							onClick: function () {
								setAttributes({ savedToplistId: 0, savedToplistMode: 'copied' });
								setStatusText(__('Detached from saved toplist.', 'toplist'));
							}
						}, __('Detach to custom', 'toplist'))
					),
					el(TextControl, {
						label: __('Save current as new named toplist', 'toplist'),
						value: newListName,
						onChange: setNewListName,
						placeholder: __('e.g. UK Casino Top 10', 'toplist')
					}),
					el(Button, {
						variant: 'primary',
						disabled: isLoadingSaved,
						onClick: saveCurrentAsNewToplist
					}, __('Save to library', 'toplist')),
					statusText && el('p', { style: { marginTop: 10 } }, statusText),
					el('p', { style: { fontSize: '12px', color: '#666' } },
						__('Linked mode renders from the saved toplist on the frontend. Copied mode uses local block content.', 'toplist')
					)
				);
			}

			function renderThemeTab() {
				return el('div', {},
					el(TextareaControl, {
						label: __('Custom CSS', 'toplist'),
						value: attrs.customCSS,
						onChange: function (v) { setAttributes({ customCSS: v }); },
						rows: 10,
						help: __('Use .toplist as parent selector.', 'toplist')
					}),
					el('p', { style: { marginTop: 8, fontSize: '12px', color: '#666' } },
						__('Global CSS is available in Settings -> Toplist Block and applies to all lists.', 'toplist')
					)
				);
			}

			function renderDefaultsTab() {
				var globalHeaderStatus = GLOBAL_DEFAULT_HEADER_ENABLED
					? __('Global default header is enabled in plugin settings.', 'toplist')
					: __('Global default header is disabled in plugin settings.', 'toplist');
				var globalHeadingStatus = GLOBAL_TOPLIST_HEADING
					? __('Global H2 heading is set in plugin settings.', 'toplist')
					: __('Global H2 heading is empty in plugin settings.', 'toplist');
				return el('div', {},
					el(TextControl, {
						label: __('List ID', 'toplist'),
						type: 'number',
						value: attrs.listId,
						onChange: function (v) { setAttributes({ listId: parseInt(v || '1', 10) }); }
					}),
					el(TextControl, {
						label: __('List Type', 'toplist'),
						value: attrs.listType,
						onChange: function (v) { setAttributes({ listType: v }); }
					}),
					el(TextareaControl, {
						label: __('Disclaimer (shown above terms)', 'toplist'),
						value: attrs.disclaimer,
						onChange: function (v) { setAttributes({ disclaimer: v }); }
					}),
					el(TextControl, {
						label: __('Default CTA Text', 'toplist'),
						value: attrs.defaultCtaText,
						onChange: updateDefaultCtaText,
						help: __('Used when a row ctaText value is empty.', 'toplist')
					}),
					el(TextControl, {
						label: __('Default Read Review Text', 'toplist'),
						value: attrs.defaultReadReviewText,
						onChange: updateDefaultReadReviewText,
						help: __('Used when a row readReviewText value is empty.', 'toplist')
					}),
					el(SelectControl, {
						label: __('Default Toplist Header', 'toplist'),
						value: attrs.defaultHeaderMode || 'global',
						options: [
							{ label: __('Use global setting', 'toplist'), value: 'global' },
							{ label: __('Off', 'toplist'), value: 'off' },
							{ label: __('Custom header row', 'toplist'), value: 'custom' }
						],
						onChange: updateDefaultHeaderMode,
						help: globalHeaderStatus
					}),
					(attrs.defaultHeaderMode === 'custom') && el(TextControl, {
						label: __('Custom Header Row', 'toplist'),
						value: attrs.defaultHeaderRow || '',
						onChange: updateDefaultHeaderRow,
						placeholder: 'operator|product|offer|href|logo|year|ctaText|terms|bullets'
					}),
					el(SelectControl, {
						label: __('Toplist H2 Heading', 'toplist'),
						value: attrs.headingMode || 'global',
						options: [
							{ label: __('Use global heading', 'toplist'), value: 'global' },
							{ label: __('Off', 'toplist'), value: 'off' },
							{ label: __('Custom heading', 'toplist'), value: 'custom' }
						],
						onChange: updateHeadingMode,
						help: globalHeadingStatus
					}),
					(attrs.headingMode === 'custom') && el(TextControl, {
						label: __('Custom H2 Heading Text', 'toplist'),
						value: attrs.headingText || '',
						onChange: updateHeadingText,
						placeholder: __('Top 10 Casinos', 'toplist')
					})
				);
			}

			function renderToggleTab() {
				return el('div', {},
					el('p', { style: { marginTop: 0, fontSize: '13px', color: '#666' } },
						__('Hide fields without removing values from your data.', 'toplist')
					),
					el(CheckboxControl, { label: __('Show Logo', 'toplist'), checked: attrs.showLogo, onChange: function (v) { setAttributes({ showLogo: v }); } }),
					el(CheckboxControl, { label: __('Show Launch Year', 'toplist'), checked: attrs.showYear, onChange: function (v) { setAttributes({ showYear: v }); } }),
					el(CheckboxControl, { label: __('Show Offer', 'toplist'), checked: attrs.showOffer, onChange: function (v) { setAttributes({ showOffer: v }); } }),
					el(CheckboxControl, { label: __('Show Terms & Conditions', 'toplist'), checked: attrs.showTerms, onChange: function (v) { setAttributes({ showTerms: v }); } }),
					el(CheckboxControl, { label: __('Show Bullet Points', 'toplist'), checked: attrs.showBullets, onChange: function (v) { setAttributes({ showBullets: v }); } }),
					el(CheckboxControl, { label: __('Show Payout', 'toplist'), checked: attrs.showPayout, onChange: function (v) { setAttributes({ showPayout: v }); } }),
					el(CheckboxControl, { label: __('Show Code', 'toplist'), checked: attrs.showCode, onChange: function (v) { setAttributes({ showCode: v }); } }),
					el(CheckboxControl, { label: __('Show Rating', 'toplist'), checked: attrs.showRating, onChange: function (v) { setAttributes({ showRating: v }); } }),
					el(CheckboxControl, { label: __('Show Regulator', 'toplist'), checked: attrs.showRegulator, onChange: function (v) { setAttributes({ showRegulator: v }); } }),
					el(CheckboxControl, { label: __('Show Payments', 'toplist'), checked: attrs.showPayments, onChange: function (v) { setAttributes({ showPayments: v }); } }),
					el(CheckboxControl, { label: __('Show Games', 'toplist'), checked: attrs.showGames, onChange: function (v) { setAttributes({ showGames: v }); } }),
					el(CheckboxControl, { label: __('Show Live Games', 'toplist'), checked: attrs.showLiveGames, onChange: function (v) { setAttributes({ showLiveGames: v }); } }),
					el(CheckboxControl, { label: __('Show Small Print', 'toplist'), checked: attrs.showSmallPrint, onChange: function (v) { setAttributes({ showSmallPrint: v }); } }),
					el(CheckboxControl, { label: __('Show Read Review', 'toplist'), checked: attrs.showReadReview, onChange: function (v) { setAttributes({ showReadReview: v }); } }),
					el(CheckboxControl, { label: __('Show Withdrawals', 'toplist'), checked: attrs.showWithdrawals, onChange: function (v) { setAttributes({ showWithdrawals: v }); } })
				);
			}

			function renderSettingsTab(tab) {
				if (tab.name === 'theme') {
					return renderThemeTab();
				}
				if (tab.name === 'defaults') {
					return renderDefaultsTab();
				}
				return renderToggleTab();
			}

			return el(
				'div',
				blockProps,
				attrs.customCSS && el('style', {}, attrs.customCSS),
				el(InspectorControls, {},
					el(TabPanel, {
						className: 'toplist-inspector-tabs',
						activeClass: 'is-active',
						tabs: [
							{ name: 'theme', title: __('Theme', 'toplist') },
							{ name: 'defaults', title: __('Defaults', 'toplist') },
							{ name: 'toggle', title: __('Toggle', 'toplist') }
						]
					}, renderSettingsTab)
				),

				el('h3', { style: { marginTop: 0 } }, __('Toplist', 'toplist')),
				el('div', { style: { border: '1px solid #dcdcde', borderRadius: 4, padding: 12, marginBottom: 12, background: '#fff' } },
					el('strong', {}, __('Saved Toplist', 'toplist')),
					el('div', { style: { marginTop: 8 } }, renderLibraryTab())
				),

				el(Notice, { status: 'info', isDismissible: false },
					el('div', {},
						el('div', {}, __('Edit your toplist using one line per item:', 'toplist')),
						el('code', { style: { display: 'block', marginTop: 8, whiteSpace: 'pre-wrap' } },
							'operator|product|offer|href|logo|year|ctaText|terms|bullets|payout|code|rating|regulator|payments|games|liveGames|smallPrint|readReviewHref|readReviewText|withdrawals'
						),
						el('p', { style: { marginBottom: 0 } },
							__('Optional first row: use field names as header directives. Prefix with - or ! to exclude (example: operator|product|-terms|-bullets|href).', 'toplist')
						)
					)
				),

				el(TextareaControl, {
					label: __('Items', 'toplist'),
					value: lines,
					onChange: function (nextLines) {
						parseAndSet(nextLines);
						if ((attrs.savedToplistId || 0) > 0 && attrs.savedToplistMode === 'linked') {
							setAttributes({ savedToplistMode: 'copied' });
						}
					},
					rows: 10,
					help: __('Use | between columns and ; for bullets/payments/games/withdrawals.', 'toplist')
				}),

				el('div', { style: { display: 'flex', gap: 8 } },
					el(Button, { variant: 'secondary', onClick: addExample }, __('Add example row', 'toplist')),
					el(Button, {
						variant: 'tertiary',
						onClick: function () { parseAndSet(''); }
					}, __('Clear', 'toplist'))
				),

				el('hr', {}),
				el('strong', {}, __('Preview (simplified)', 'toplist')),
				el('ol', { className: 'toplist-preview', style: { marginTop: 10 } },
					(attrs.items || []).map(function (it, idx) {
						return el('li', { key: idx, style: { marginBottom: 10, padding: 10, border: '1px solid #ddd' } },
							el('div', {}, el('strong', {}, '#' + (idx + 1) + ' ' + (it.product || it.operator || ''))),
							el('div', {}, it.offer || ''),
							el('div', { style: { fontSize: 12, opacity: 0.8 } }, (it.href || ''))
						);
					})
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor || window.wp.editor,
	window.wp.components,
	window.wp.i18n,
	window.wp.apiFetch
);
