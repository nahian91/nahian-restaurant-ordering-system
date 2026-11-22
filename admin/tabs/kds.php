<?php
/**
 * Nahian POS Live Kitchen Display System (KDS) Bump Screen - 100% Dynamic Dine-In & Takeaway
 *
 * @package NRTOK_POS
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Dynamic Live KDS Bump Board with Dine-In / Takeaway Filters, Stage Filters & KOT Print.
 *
 * @return void
 */
function nrtok_view_kds() {
	if ( ! nrtok_current_user_can_pos() ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	$s             = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
	$rest_name     = ! empty( $s['restaurant_name'] ) ? $s['restaurant_name'] : get_bloginfo( 'name' );
	$ord_prefix    = ! empty( $s['order_prefix'] ) ? $s['order_prefix'] : 'ORD-';
	$poll_interval = ! empty( $s['poll_interval_ms'] ) ? max( 2000, absint( $s['poll_interval_ms'] ) ) : 3000;
	$sound_enabled = isset( $s['sound_alerts'] ) ? (int) $s['sound_alerts'] : 1;
	$nonce         = wp_create_nonce( 'nrtok_admin_nonce' );
	$sync_seconds  = round( $poll_interval / 1000, 1 );
	?>
	<div class="nrtok-header nrtok-kds-header">
		<div>
			<h1><?php esc_html_e( 'Kitchen Display System (KDS)', 'nahian-restaurant-ordering-system' ); ?></h1>
			<p><?php esc_html_e( 'Live Dine-In & Takeaway order queue with dish checkoff and one-tap stage bumping.', 'nahian-restaurant-ordering-system' ); ?></p>
		</div>

		<div class="nrtok-kds-controls">
			<button type="button" id="nrtok-kds-sound-btn" class="nrtok-kds-ctrl-btn" title="<?php esc_attr_e( 'Toggle New Order Chime', 'nahian-restaurant-ordering-system' ); ?>">
				<svg id="nrtok-kds-bell-on" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
				<svg id="nrtok-kds-bell-off" class="nrtok-hidden" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
				<span id="nrtok-kds-sound-lbl"><?php esc_html_e( 'Chime: On', 'nahian-restaurant-ordering-system' ); ?></span>
			</button>

			<button type="button" id="nrtok-kds-refresh-btn" class="nrtok-kds-ctrl-btn" title="<?php esc_attr_e( 'Refresh Now', 'nahian-restaurant-ordering-system' ); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
				<span><?php esc_html_e( 'Refresh', 'nahian-restaurant-ordering-system' ); ?></span>
			</button>

			<div class="nrtok-kds-sync-pill">
				<span class="pulse-dot"></span>
				<span>
					<?php
					printf(
						/* translators: %s: polling interval in seconds */
						esc_html__( 'Live: %ss', 'nahian-restaurant-ordering-system' ),
						esc_html( (string) $sync_seconds )
					);
					?>
				</span>
			</div>
		</div>
	</div>

	<!-- Filter Bar: Stage Filters + Dine-In / Takeaway Mode Filters -->
	<div class="nrtok-kds-filter-bar">
		<div class="nrtok-kds-filters" role="tablist" aria-label="<?php esc_attr_e( 'Order Stage Filter', 'nahian-restaurant-ordering-system' ); ?>">
			<button type="button" class="nrtok-kds-filter active" data-stage="all">
				<span><?php esc_html_e( 'All Active', 'nahian-restaurant-ordering-system' ); ?></span>
				<span class="nrtok-kds-cnt" id="nrtok-kds-cnt-all">0</span>
			</button>
			<button type="button" class="nrtok-kds-filter" data-stage="pending">
				<span class="nrtok-kds-dot dot-pending"></span>
				<span><?php esc_html_e( 'New / Pending', 'nahian-restaurant-ordering-system' ); ?></span>
				<span class="nrtok-kds-cnt" id="nrtok-kds-cnt-pending">0</span>
			</button>
			<button type="button" class="nrtok-kds-filter" data-stage="preparing">
				<span class="nrtok-kds-dot dot-preparing"></span>
				<span><?php esc_html_e( 'Cooking', 'nahian-restaurant-ordering-system' ); ?></span>
				<span class="nrtok-kds-cnt" id="nrtok-kds-cnt-preparing">0</span>
			</button>
			<button type="button" class="nrtok-kds-filter" data-stage="served">
				<span class="nrtok-kds-dot dot-served"></span>
				<span><?php esc_html_e( 'Ready / Served', 'nahian-restaurant-ordering-system' ); ?></span>
				<span class="nrtok-kds-cnt" id="nrtok-kds-cnt-served">0</span>
			</button>
		</div>

		<div class="nrtok-kds-type-filters" role="tablist" aria-label="<?php esc_attr_e( 'Order Type Filter', 'nahian-restaurant-ordering-system' ); ?>">
			<button type="button" class="nrtok-kds-type-btn active" data-otype="all"><?php esc_html_e( 'All Modes', 'nahian-restaurant-ordering-system' ); ?></button>
			<button type="button" class="nrtok-kds-type-btn" data-otype="dine_in">
				<span><?php esc_html_e( 'Dine-In', 'nahian-restaurant-ordering-system' ); ?></span>
				<span class="nrtok-kds-cnt" id="nrtok-kds-cnt-dine">0</span>
			</button>
			<button type="button" class="nrtok-kds-type-btn" data-otype="takeaway">
				<span><?php esc_html_e( 'Takeaway', 'nahian-restaurant-ordering-system' ); ?></span>
				<span class="nrtok-kds-cnt" id="nrtok-kds-cnt-take">0</span>
			</button>
		</div>
	</div>

	<div id="nrtok-kds-board" class="nrtok-kds-board"></div>

	<style>
		.nrtok-hidden { display: none !important; }
		.nrtok-kds-header button, .nrtok-kds-filter-bar button, .nrtok-kds-board button {
			touch-action: manipulation;
			-webkit-tap-highlight-color: transparent;
		}

		/* Header Controls */
		.nrtok-kds-controls { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
		.nrtok-kds-ctrl-btn {
			height: 42px; padding: 0 15px; border-radius: 12px; border: 1.5px solid #cbd5e1;
			background: #fff; color: #0f172a; font-weight: 800; font-size: 13px;
			display: inline-flex; align-items: center; gap: 7px; cursor: pointer; transition: 0.15s;
		}
		.nrtok-kds-ctrl-btn:hover { border-color: #94a3b8; background: #f8fafc; }
		.nrtok-kds-ctrl-btn.is-muted { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
		.nrtok-kds-sync-pill {
			height: 42px; padding: 0 15px; border-radius: 12px; background: #ecfdf5;
			border: 1.5px solid #a7f3d0; color: #065f46; font-weight: 800; font-size: 13px;
			display: inline-flex; align-items: center;
		}

		/* Combined Filter Bar */
		.nrtok-kds-filter-bar {
			display: flex; justify-content: space-between; align-items: center;
			flex-wrap: wrap; gap: 12px; margin-bottom: 22px;
		}
		.nrtok-kds-filters, .nrtok-kds-type-filters {
			display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
		}
		.nrtok-kds-type-filters {
			background: #e2e8f0; padding: 4px; border-radius: 14px;
		}
		.nrtok-kds-filter {
			min-height: 44px; padding: 0 15px; border-radius: 13px; border: 1.5px solid #e2e8f0;
			background: #fff; color: #475569; font-weight: 800; font-size: 13.5px;
			display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.15s;
		}
		.nrtok-kds-filter:hover { border-color: #cbd5e1; color: #0f172a; }
		.nrtok-kds-filter.active { background: #0f172a; color: #fff; border-color: #0f172a; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.16); }

		.nrtok-kds-type-btn {
			min-height: 36px; padding: 0 14px; border-radius: 10px; border: none;
			background: transparent; color: #475569; font-weight: 800; font-size: 13px;
			display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: 0.15s;
		}
		.nrtok-kds-type-btn.active {
			background: #fff; color: #0f172a; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.1);
		}

		.nrtok-kds-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
		.dot-pending { background: #f59e0b; }
		.dot-preparing { background: #3b82f6; }
		.dot-served { background: #10b981; }
		.nrtok-kds-cnt {
			background: rgba(15, 23, 42, 0.08); padding: 2px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 900;
		}
		.nrtok-kds-filter.active .nrtok-kds-cnt { background: rgba(255, 255, 255, 0.22); color: #fff; }

		/* Ticket Grid */
		.nrtok-kds-board {
			display: grid; grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
			gap: 18px; align-items: stretch;
		}
		.nrtok-kds-empty {
			grid-column: 1 / -1; text-align: center; padding: 64px 20px; background: #fff;
			border-radius: 20px; border: 1.5px dashed #cbd5e1; color: #64748b;
		}
		.nrtok-kds-empty h3 { margin: 12px 0 4px; font-size: 18px; font-weight: 850; color: #0f172a; }
		.nrtok-kds-empty p { margin: 0; font-size: 13.5px; font-weight: 500; }

		/* Ticket Card */
		.nrtok-kds-card {
			background: #fff; border-radius: 20px; border: 2.5px solid #e2e8f0; overflow: hidden;
			display: flex; flex-direction: column; justify-content: space-between;
			box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05); transition: transform 0.15s ease;
		}
		.nrtok-kds-card.st-pending { border-color: #f59e0b; }
		.nrtok-kds-card.st-preparing { border-color: #3b82f6; }
		.nrtok-kds-card.st-served { border-color: #10b981; }

		/* Card Top Banner */
		.nrtok-kds-head {
			padding: 14px 16px; color: #fff; display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;
		}
		.st-pending .nrtok-kds-head { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); }
		.st-preparing .nrtok-kds-head { background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); }
		.st-served .nrtok-kds-head { background: linear-gradient(135deg, #047857 0%, #10b981 100%); }

		.nrtok-kds-head-left { display: flex; flex-direction: column; gap: 4px; }
		.nrtok-kds-mode-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
		.nrtok-kds-mode-pill {
			padding: 2px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 900;
			text-transform: uppercase; letter-spacing: 0.5px;
		}
		.mode-dine { background: rgba(255, 255, 255, 0.24); color: #fff; }
		.mode-takeaway { background: #0f172a; color: #fb923c; }

		.nrtok-kds-tbl { font-size: 17.5px; font-weight: 950; letter-spacing: 0.2px; line-height: 1.2; }
		.nrtok-kds-meta { font-size: 11.5px; font-weight: 700; opacity: 0.92; text-transform: uppercase; letter-spacing: 0.4px; }

		.nrtok-kds-head-right { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
		.nrtok-kds-timer {
			padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: 900;
			background: rgba(15, 23, 42, 0.28); color: #fff; white-space: nowrap;
		}
		.nrtok-kds-timer.late {
			background: #dc2626; color: #fff; box-shadow: 0 0 0 2px #fff;
			animation: nrtokLatePulse 1.4s infinite;
		}
		@keyframes nrtokLatePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.72; } }

		.nrtok-kds-print-ico {
			width: 34px; height: 34px; border-radius: 10px; border: none;
			background: rgba(255, 255, 255, 0.22); color: #fff; cursor: pointer;
			display: inline-flex; align-items: center; justify-content: center;
		}
		.nrtok-kds-print-ico:hover { background: rgba(255, 255, 255, 0.35); }

		/* Dish Items List with Tap-to-Checkoff */
		.nrtok-kds-body { padding: 14px 16px; flex: 1; }
		.nrtok-kds-cust-bar {
			background: #f1f5f9; color: #334155; padding: 7px 11px; border-radius: 9px;
			font-size: 12px; font-weight: 750; margin-bottom: 10px;
		}
		.nrtok-kds-item {
			display: flex; align-items: flex-start; gap: 10px; padding: 10px 8px;
			border-bottom: 1px dashed #e2e8f0; cursor: pointer; border-radius: 10px;
			transition: background 0.12s ease; user-select: none;
		}
		.nrtok-kds-item:last-child { border-bottom: none; }
		.nrtok-kds-item:hover { background: #f8fafc; }

		.nrtok-kds-qty-pill {
			min-width: 30px; height: 28px; padding: 0 7px; border-radius: 8px;
			background: #0f172a; color: #fff; font-size: 13.5px; font-weight: 900;
			display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
		}
		.nrtok-kds-item-txt { flex: 1; font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.3; }
		.nrtok-kds-item-extras { font-size: 12.5px; color: #ea580c; font-weight: 700; margin-top: 3px; }

		/* Checked-off / Plated Item State */
		.nrtok-kds-item.is-checked .nrtok-kds-qty-pill { background: #10b981; }
		.nrtok-kds-item.is-checked .nrtok-kds-item-txt { text-decoration: line-through; color: #94a3b8; }
		.nrtok-kds-item.is-checked .nrtok-kds-item-extras { text-decoration: line-through; opacity: 0.6; }

		.nrtok-kds-note {
			margin-top: 12px; background: #fef3c7; border: 1.5px solid #fde68a; color: #92400e;
			padding: 10px 12px; border-radius: 12px; font-size: 13px; font-weight: 800;
		}

		/* Bottom Bump Button */
		.nrtok-kds-foot { padding: 12px 16px 16px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
		.nrtok-kds-bump {
			width: 100%; height: 52px; border-radius: 14px; border: none; font-weight: 900;
			font-size: 14.5px; cursor: pointer; color: #fff; text-transform: uppercase; letter-spacing: 0.5px;
			display: inline-flex; align-items: center; justify-content: center; gap: 8px;
			transition: transform 0.1s ease, opacity 0.15s ease;
		}
		.nrtok-kds-bump:active { transform: scale(0.97); }
		.nrtok-kds-bump.to-cook { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3); }
		.nrtok-kds-bump.to-serve { background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%); box-shadow: 0 6px 16px rgba(124, 58, 237, 0.3); }
		.nrtok-kds-bump.to-done { background: linear-gradient(135deg, #047857 0%, #10b981 100%); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3); }
	</style>

	<script>
	jQuery(document).ready(function($) {
		const nonce        = <?php echo wp_json_encode( $nonce ); ?>;
		const restName     = <?php echo wp_json_encode( $rest_name ); ?>;
		const ordPrefix    = <?php echo wp_json_encode( $ord_prefix ); ?>;
		const pollInterval = <?php echo wp_json_encode( $poll_interval ); ?>;
		const defaultSound = <?php echo wp_json_encode( $sound_enabled ); ?>;

		const txtStart    = <?php echo wp_json_encode( __( 'Start Cooking', 'nahian-restaurant-ordering-system' ) ); ?>;
		const txtServe    = <?php echo wp_json_encode( __( 'Mark Ready / Served', 'nahian-restaurant-ordering-system' ) ); ?>;
		const txtDone     = <?php echo wp_json_encode( __( 'Complete & Bump', 'nahian-restaurant-ordering-system' ) ); ?>;
		const txtEmptyH   = <?php echo wp_json_encode( __( 'All Caught Up!', 'nahian-restaurant-ordering-system' ) ); ?>;
		const txtEmptyP   = <?php echo wp_json_encode( __( 'No active kitchen tickets matching this filter.', 'nahian-restaurant-ordering-system' ) ); ?>;
		const txtDineIn   = <?php echo wp_json_encode( __( 'Dine-In', 'nahian-restaurant-ordering-system' ) ); ?>;
		const txtTakeaway = <?php echo wp_json_encode( __( 'Takeaway', 'nahian-restaurant-ordering-system' ) ); ?>;
		const txtTable    = <?php echo wp_json_encode( __( 'Table', 'nahian-restaurant-ordering-system' ) ); ?>;

		let activeStage   = 'all';
		let activeType    = 'all';
		let allCardsCache = [];
		let checkedItems  = {};
		let knownMaxId    = null;
		let chimeMuted    = localStorage.getItem('nrtok_kds_muted') === '1' || defaultSound === 0;

		function updateChimeUI() {
			const $btn = $('#nrtok-kds-sound-btn');
			$btn.toggleClass('is-muted', chimeMuted);
			$('#nrtok-kds-bell-on').toggleClass('nrtok-hidden', chimeMuted);
			$('#nrtok-kds-bell-off').toggleClass('nrtok-hidden', !chimeMuted);
			$('#nrtok-kds-sound-lbl').text(chimeMuted ? <?php echo wp_json_encode( __( 'Chime: Muted', 'nahian-restaurant-ordering-system' ) ); ?> : <?php echo wp_json_encode( __( 'Chime: On', 'nahian-restaurant-ordering-system' ) ); ?>);
		}
		updateChimeUI();

		$('#nrtok-kds-sound-btn').on('click', function() {
			chimeMuted = !chimeMuted;
			localStorage.setItem('nrtok_kds_muted', chimeMuted ? '1' : '0');
			updateChimeUI();
		});

		function playKitchenBell() {
			if (chimeMuted) return;
			try {
				const AudioCtx = window.AudioContext || window.webkitAudioContext;
				const ctx  = new AudioCtx();
				const osc  = ctx.createOscillator();
				const gain = ctx.createGain();
				osc.type = 'triangle';
				osc.frequency.setValueAtTime(1046.5, ctx.currentTime);
				gain.gain.setValueAtTime(0.28, ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.55);
				osc.connect(gain);
				gain.connect(ctx.destination);
				osc.start();
				osc.stop(ctx.currentTime + 0.55);
			} catch (e) {}
		}

		function escHtml(s) {
			return String(s === null || s === undefined ? '' : s)
				.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		function normalizeOrderType(card) {
			const rawType = String(card.order_type || '').toLowerCase();
			const rawTbl  = String(card.table_no || '').toLowerCase();
			if (rawType === 'takeaway' || rawType === 'delivery' || rawTbl === 'takeaway' || rawTbl === 'delivery') {
				return 'takeaway';
			}
			return 'dine_in';
		}

		function printKotTicket(card) {
			const mode = normalizeOrderType(card);
			const destinationLabel = (mode === 'takeaway')
				? ('*** ' + txtTakeaway.toUpperCase() + ' ***')
				: (txtTable.toUpperCase() + ': ' + String(card.table_no || ''));

			let rows = '';
			(card.items || []).forEach(function(i) {
				const ex = (i.extras && i.extras.length) ? '<br><span style="font-size:11px;font-weight:normal;">  + ' + i.extras.map(escHtml).join(', ') + '</span>' : '';
				rows += '<tr><td style="padding:4px 0;">' + escHtml(i.name) + ex + '</td><td style="text-align:right;vertical-align:top;">x' + parseInt(i.qty || 1, 10) + '</td></tr>';
			});

			const html =
				'<div style="text-align:center;">' +
					'<h2 style="margin:0;font-size:18px;">' + escHtml(restName) + '</h2>' +
					'<p style="margin:2px 0;font-size:11px;">*** KITCHEN TICKET (KOT) ***</p>' +
					'<h1 style="margin:5px 0;border:2px solid #000;padding:4px;font-size:18px;">' + escHtml(destinationLabel) + '</h1>' +
					'<p style="margin:2px 0;font-size:11px;">' + escHtml(ordPrefix) + parseInt(card.id, 10) + ' | ' + escHtml(card.time_label) + '</p>' +
				'</div>' +
				'<hr style="border-top:1px dashed #000;">' +
				'<table style="width:100%;font-size:14px;font-weight:bold;">' + rows + '</table>' +
				(card.note ? '<hr style="border-top:1px dashed #000;"><p><strong>NOTE:</strong> ' + escHtml(card.note) + '</p>' : '');

			if ($('#nrtok-print-area').length === 0) $('body').append('<div id="nrtok-print-area"></div>');
			$('#nrtok-print-area').html(html);
			window.print();
		}

		function renderKds() {
			const counts = { all: allCardsCache.length, pending: 0, preparing: 0, served: 0, dine_in: 0, takeaway: 0 };
			allCardsCache.forEach(function(c) {
				if (counts[c.status] !== undefined) counts[c.status]++;
				const mode = normalizeOrderType(c);
				counts[mode]++;
			});

			$('#nrtok-kds-cnt-all').text(counts.all);
			$('#nrtok-kds-cnt-pending').text(counts.pending);
			$('#nrtok-kds-cnt-preparing').text(counts.preparing);
			$('#nrtok-kds-cnt-served').text(counts.served);
			$('#nrtok-kds-cnt-dine').text(counts.dine_in);
			$('#nrtok-kds-cnt-take').text(counts.takeaway);

			const filtered = allCardsCache.filter(function(c) {
				const stageOk = (activeStage === 'all') || (c.status === activeStage);
				const typeOk  = (activeType === 'all') || (normalizeOrderType(c) === activeType);
				return stageOk && typeOk;
			});

			const $board = $('#nrtok-kds-board').empty();
			if (filtered.length === 0) {
				$board.append(
					'<div class="nrtok-kds-empty">' +
						'<svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
						'<h3>' + escHtml(txtEmptyH) + '</h3>' +
						'<p>' + escHtml(txtEmptyP) + '</p>' +
					'</div>'
				);
				return;
			}

			filtered.forEach(function(c) {
				const mode     = normalizeOrderType(c);
				const isTake   = (mode === 'takeaway');
				const modeText = isTake ? txtTakeaway : txtDineIn;
				const titleStr = isTake
					? (ordPrefix + c.id + ' • ' + txtTakeaway)
					: (ordPrefix + c.id + ' • ' + txtTable + ' ' + c.table_no);

				const $card = $('<div class="nrtok-kds-card"></div>').addClass('st-' + c.status);

				// Header
				const $head = $('<div class="nrtok-kds-head"></div>');
				const $hLeft = $('<div class="nrtok-kds-head-left"></div>');
				const $modeRow = $('<div class="nrtok-kds-mode-row"></div>');
				$modeRow.append(
					$('<span class="nrtok-kds-mode-pill"></span>')
						.addClass(isTake ? 'mode-takeaway' : 'mode-dine')
						.text(modeText)
				);
				$modeRow.append($('<span class="nrtok-kds-meta"></span>').text(c.status + ' • ' + c.time_label));

				$hLeft.append($modeRow);
				$hLeft.append($('<div class="nrtok-kds-tbl"></div>').text(titleStr));

				const $hRight = $('<div class="nrtok-kds-head-right"></div>');
				const $timer  = $('<span class="nrtok-kds-timer"></span>').text(c.elapsed + 'm');
				if (c.elapsed >= 15) $timer.addClass('late');

				const $printBtn = $('<button type="button" class="nrtok-kds-print-ico" title="<?php echo esc_js( __( 'Print KOT', 'nahian-restaurant-ordering-system' ) ); ?>"></button>')
					.append('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>')
					.on('click', function() { printKotTicket(c); });

				$hRight.append($timer, $printBtn);
				$head.append($hLeft, $hRight);

				// Body
				const $body = $('<div class="nrtok-kds-body"></div>');
				if (c.customer_name || c.customer_phone) {
					const custInfo = [c.customer_name, c.customer_phone].filter(Boolean).join(' • ');
					$body.append($('<div class="nrtok-kds-cust-bar"></div>').text(custInfo));
				}

				(c.items || []).forEach(function(it, idx) {
					const checkKey = c.id + '_' + idx;
					const $itRow   = $('<div class="nrtok-kds-item"></div>').attr('data-ck', checkKey);
					if (checkedItems[checkKey]) $itRow.addClass('is-checked');

					$itRow.append($('<span class="nrtok-kds-qty-pill"></span>').text((it.qty || 1) + '×'));
					const $txtWrap = $('<div class="nrtok-kds-item-txt"></div>').text(it.name || '');
					if (it.extras && it.extras.length) {
						$txtWrap.append($('<div class="nrtok-kds-item-extras"></div>').text('+ ' + it.extras.join(', ')));
					}
					$itRow.append($txtWrap);
					$body.append($itRow);
				});

				if (c.note) {
					$body.append($('<div class="nrtok-kds-note"></div>').text('NOTE: ' + c.note));
				}

				// Footer Bump Action
				const $foot = $('<div class="nrtok-kds-foot"></div>');
				let nextSt = 'preparing', btnCls = 'to-cook', btnTxt = txtStart;
				if (c.status === 'preparing') {
					nextSt = 'served';
					btnCls = 'to-serve';
					btnTxt = txtServe;
				} else if (c.status === 'served') {
					nextSt = 'completed';
					btnCls = 'to-done';
					btnTxt = txtDone;
				}

				const $btn = $('<button type="button" class="nrtok-kds-bump"></button>')
					.addClass(btnCls)
					.attr('data-id', c.id)
					.attr('data-next', nextSt)
					.text(btnTxt);

				$foot.append($btn);
				$card.append($head, $body, $foot);
				$board.append($card);
			});
		}

		function fetchKds(bumpId, nextStatus) {
			const payload = { action: 'nrtok_kds_feed', nonce: nonce };
			if (bumpId) {
				payload.bump_id     = bumpId;
				payload.next_status = nextStatus;
			}
			$.post(ajaxurl, payload, function(res) {
				if (res && res.success && Array.isArray(res.data)) {
					const maxId = res.data.reduce(function(m, item) { return Math.max(m, item.id); }, 0);
					if (knownMaxId !== null && maxId > knownMaxId && !bumpId) {
						playKitchenBell();
					}
					knownMaxId    = maxId;
					allCardsCache = res.data;
					renderKds();
				}
			});
		}

		// Stage & Order Type Filter Pills
		$('.nrtok-kds-filter').on('click', function() {
			$('.nrtok-kds-filter').removeClass('active');
			$(this).addClass('active');
			activeStage = $(this).data('stage');
			renderKds();
		});

		$('.nrtok-kds-type-btn').on('click', function() {
			$('.nrtok-kds-type-btn').removeClass('active');
			$(this).addClass('active');
			activeType = $(this).data('otype');
			renderKds();
		});

		// Tap Dish Row to Mark Plated / Checked Off
		$(document).on('click', '.nrtok-kds-item', function() {
			const ck = $(this).data('ck');
			checkedItems[ck] = !checkedItems[ck];
			$(this).toggleClass('is-checked', checkedItems[ck]);
		});

		// Bump Ticket Button
		$(document).on('click', '.nrtok-kds-bump', function() {
			$(this).prop('disabled', true);
			fetchKds($(this).data('id'), $(this).data('next'));
		});

		$('#nrtok-kds-refresh-btn').on('click', function() { fetchKds(); });

		fetchKds();
		setInterval(function() { fetchKds(); }, pollInterval);
	});
	</script>
	<?php
}