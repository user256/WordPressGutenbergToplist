(function (blocks, element, blockEditor, components, i18n) {
	console.log('Toplist: Initializing block script...');
	console.log('Dependencies check:', { blocks, element, blockEditor, components, i18n });

	const el = element.createElement;
	const __ = i18n.__;

	const useBlockProps = blockEditor.useBlockProps;
	const InspectorControls = blockEditor.InspectorControls;

	const PanelBody = components.PanelBody;
	const TextControl = components.TextControl;
	const TextareaControl = components.TextareaControl;
	const Button = components.Button;
	const Notice = components.Notice;

	console.log('Toplist: All dependencies loaded successfully');

	function parseLinesToItems(text) {
		// operator|product|offer|href|logo|year|ctaText|terms|bullet1;bullet2;bullet3
		const lines = (text || '').split(/\r?\n/).map(l => l.trim()).filter(Boolean);
		return lines.map((line) => {
			const parts = line.split('|');
			const bulletsRaw = (parts[8] || '').trim();
			return {
				operator: (parts[0] || '').trim(),
				product: (parts[1] || '').trim(),
				offer: (parts[2] || '').trim(),
				href: (parts[3] || '').trim(),
				logo: (parts[4] || '').trim(),
				year: (parts[5] || '').trim(),
				ctaText: (parts[6] || 'Visit').trim(),
				terms: (parts[7] || '').trim(),
				bullets: bulletsRaw ? bulletsRaw.split(';').map(s => s.trim()).filter(Boolean) : []
			};
		});
	}

	function itemsToLines(items) {
		return (items || []).map((it) => {
			const bullets = (it.bullets || []).join(';');
			return [
				it.operator || '',
				it.product || '',
				it.offer || '',
				it.href || '',
				it.logo || '',
				it.year || '',
				it.ctaText || 'Visit',
				it.terms || '',
				bullets
			].join('|');
		}).join('\n');
	}

	console.log('Toplist: Registering block "toplist/rankings"...');

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
			showYear: { type: 'boolean', default: true },
			showLogo: { type: 'boolean', default: true },
			showTerms: { type: 'boolean', default: true },
			showBullets: { type: 'boolean', default: true },
			_lines: { type: 'string', default: '' }
		},
		edit: function (props) {
			const attrs = props.attributes;
			const setAttributes = props.setAttributes;
			const blockProps = useBlockProps();

			const lines = attrs._lines || itemsToLines(attrs.items);

			function syncFromLines(nextLines) {
				setAttributes({
					_lines: nextLines,
					items: parseLinesToItems(nextLines)
				});
			}

			function addExample() {
				const example =
					"Mr Vegas|Mr Vegas Casino|100% Welcome Bonus up to £200 + 11 Free Spins|https://example.com|https://via.placeholder.com/150x100.png?text=Logo|2020|Visit Casino|Full Terms Apply.|Extensive slots;Weekly cashback;Fast payouts";
				const next = (lines ? (lines + "\n") : "") + example;
				syncFromLines(next);
			}

			return el(
				'div',
				blockProps,
				attrs.customCSS && el('style', {}, attrs.customCSS),
				el(InspectorControls, {},
					el(PanelBody, { title: __('Toplist settings', 'toplist'), initialOpen: true },
						el(TextControl, {
							label: __('List ID', 'toplist'),
							type: 'number',
							value: attrs.listId,
							onChange: (v) => setAttributes({ listId: parseInt(v || '1', 10) })
						}),
						el(TextControl, {
							label: __('List Type', 'toplist'),
							value: attrs.listType,
							onChange: (v) => setAttributes({ listType: v })
						}),
						el(TextareaControl, {
							label: __('Disclaimer (shown above terms)', 'toplist'),
							value: attrs.disclaimer,
							onChange: (v) => setAttributes({ disclaimer: v })
						})
					),
					el(PanelBody, { title: __('Field Visibility', 'toplist'), initialOpen: false },
						el('p', { style: { marginTop: 0, fontSize: '13px', color: '#666' } },
							__('Control which fields are displayed. Empty fields are automatically hidden.', 'toplist')
						),
						el(components.CheckboxControl, {
							label: __('Show Logo', 'toplist'),
							checked: attrs.showLogo,
							onChange: (v) => setAttributes({ showLogo: v })
						}),
						el(components.CheckboxControl, {
							label: __('Show Launch Year', 'toplist'),
							checked: attrs.showYear,
							onChange: (v) => setAttributes({ showYear: v })
						}),
						el(components.CheckboxControl, {
							label: __('Show Terms & Conditions', 'toplist'),
							checked: attrs.showTerms,
							onChange: (v) => setAttributes({ showTerms: v })
						}),
						el(components.CheckboxControl, {
							label: __('Show Bullet Points', 'toplist'),
							checked: attrs.showBullets,
							onChange: (v) => setAttributes({ showBullets: v })
						})
					),
					el(PanelBody, { title: __('Custom CSS', 'toplist'), initialOpen: false },
						el(TextareaControl, {
							label: __('CSS Override', 'toplist'),
							value: attrs.customCSS,
							onChange: (v) => setAttributes({ customCSS: v }),
							rows: 10,
							help: __('Add custom CSS to style this toplist. Use .toplist as the parent selector.', 'toplist')
						})
					)
				),

				el('h3', { style: { marginTop: 0 } }, __('Toplist', 'toplist')),

				el(Notice, { status: 'info', isDismissible: false },
					el('div', {},
						el('div', {}, __('Edit your toplist using one line per item:', 'toplist')),
						el('code', { style: { display: 'block', marginTop: 8, whiteSpace: 'pre-wrap' } },
							"operator|product|offer|href|logo|year|ctaText|terms|bullet1;bullet2;bullet3"
						)
					)
				),

				el(TextareaControl, {
					label: __('Items', 'toplist'),
					value: lines,
					onChange: syncFromLines,
					rows: 8,
					help: __('Tip: separate fields with | and bullets with ;. URLs should be absolute (https://...).', 'toplist')
				}),

				el('div', { style: { display: 'flex', gap: 8 } },
					el(Button, { variant: 'secondary', onClick: addExample }, __('Add example row', 'toplist')),
					el(Button, { variant: 'tertiary', onClick: function () { syncFromLines(''); } }, __('Clear', 'toplist'))
				),

				el('hr', {}),

				el('strong', {}, __('Preview (simplified)', 'toplist')),

				el('ol', { className: 'toplist-preview', style: { marginTop: 10 } },
					(attrs.items || []).map((it, idx) =>
						el('li', { key: idx, style: { marginBottom: 10, padding: 10, border: '1px solid #ddd' } },
							el('div', {}, el('strong', {}, `#${idx + 1} ${it.product || it.operator || ''}`)),
							el('div', {}, it.offer || ''),
							el('div', { style: { fontSize: 12, opacity: 0.8 } }, (it.href || ''))
						)
					)
				)
			);
		},
		save: function () { return null; }
	});

	console.log('Toplist: Block "toplist/rankings" registered successfully!');
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor || window.wp.editor,
	window.wp.components,
	window.wp.i18n
);
