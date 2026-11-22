<?php
/**
 * Nahian POS Orders Header & Auto-Print Controls
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! nrtok_current_user_can_pos() ) {
	return;
}

if ( ! isset( $s ) || ! is_array( $s ) ) {
	$s = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
}

$nrtok_svg_back    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>';
$nrtok_svg_printer = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>';
$nrtok_orders_url  = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=orders' );
$nrtok_ap_nonce    = wp_create_nonce( 'nrtok_admin_nonce' );
?>
<div class="nrtok-header">
	<div>
		<h1><?php esc_html_e( 'Orders & Kitchen Auto-Print', 'nahian-restaurant-ordering-system' ); ?></h1>
		<p><?php esc_html_e( 'Track incoming table orders, update kitchen progress, and print KOT or Customer Bills.', 'nahian-restaurant-ordering-system' ); ?></p>
	</div>

	<div class="nrtok-ord-header-actions">
		<?php if ( isset( $view_order ) && $view_order ) : ?>
			<a href="<?php echo esc_url( $nrtok_orders_url ); ?>" class="nrtok-btn-secondary nrtok-ord-back-btn">
				<?php echo $nrtok_svg_back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Back to All Orders', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
		<?php endif; ?>

		<div class="nrtok-autoprint-box">
			<span class="pulse-dot" id="nrtok-ap-dot"></span>
			<?php echo $nrtok_svg_printer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span id="nrtok-ap-label"><?php esc_html_e( 'Kitchen Auto-Print: ON (3s)', 'nahian-restaurant-ordering-system' ); ?></span>
			<button type="button" id="nrtok-toggle-autoprint" class="nrtok-autoprint-toggle-btn"><?php esc_html_e( 'Pause', 'nahian-restaurant-ordering-system' ); ?></button>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	const apNonce     = <?php echo wp_json_encode( $nrtok_ap_nonce ); ?>;
	const restName    = <?php echo wp_json_encode( $s['restaurant_name'] ?? 'NAHIAN' ); ?>;
	const lblOn       = <?php echo wp_json_encode( __( 'Kitchen Auto-Print: ON (3s)', 'nahian-restaurant-ordering-system' ) ); ?>;
	const lblPaused   = <?php echo wp_json_encode( __( 'Kitchen Auto-Print: PAUSED', 'nahian-restaurant-ordering-system' ) ); ?>;
	const btnPause    = <?php echo wp_json_encode( __( 'Pause', 'nahian-restaurant-ordering-system' ) ); ?>;
	const btnResume   = <?php echo wp_json_encode( __( 'Resume', 'nahian-restaurant-ordering-system' ) ); ?>;

	let apEnabled     = localStorage.getItem('nrtok_autoprint_paused') !== '1';
	let lastPrintedId = parseInt(localStorage.getItem('nrtok_last_printed_order_id') || '0', 10);

	function escHtml(str) {
		return String(str === null || str === undefined ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function updateAutoPrintUI() {
		if (apEnabled) {
			$('#nrtok-ap-dot').removeClass('nrtok-ap-dot-paused');
			$('#nrtok-ap-label').text(lblOn);
			$('#nrtok-toggle-autoprint').text(btnPause);
		} else {
			$('#nrtok-ap-dot').addClass('nrtok-ap-dot-paused');
			$('#nrtok-ap-label').text(lblPaused);
			$('#nrtok-toggle-autoprint').text(btnResume);
		}
	}

	updateAutoPrintUI();

	$(document).on('click', '#nrtok-toggle-autoprint', function() {
		apEnabled = !apEnabled;
		localStorage.setItem('nrtok_autoprint_paused', apEnabled ? '0' : '1');
		updateAutoPrintUI();
	});

	function triggerAutoKOT(order) {
		const items = typeof order.items === 'string' ? JSON.parse(order.items) : (order.items || []);
		let rows = '';
		items.forEach(function(i) {
			const extraStr = (i.extras && i.extras.length)
				? '<br><span style="font-size:12px; font-weight:normal;">  + ' + i.extras.map(escHtml).join(', ') + '</span>'
				: '';
			rows += '<tr><td style="padding:4px 0;">' + escHtml(i.name) + extraStr + '</td><td style="text-align:right; vertical-align:top;">x' + parseInt(i.qty || 1, 10) + '</td></tr>';
		});

		const receipt =
			'<div style="text-align:center;">' +
				'<h2 style="margin:0; font-size:20px;">' + escHtml(restName) + '</h2>' +
				'<p style="margin:2px 0; font-size:12px;">*** KITCHEN TICKET (KOT) ***</p>' +
				'<h1 style="margin:6px 0; border:2px solid #000; padding:4px;">TABLE: ' + escHtml(order.table_no) + '</h1>' +
				'<p style="margin:2px 0;">Order #' + parseInt(order.id, 10) + ' | ' + escHtml(order.created_at) + '</p>' +
			'</div>' +
			'<hr style="border-top:1px dashed #000;">' +
			'<table style="width:100%; font-size:14px; font-weight:bold;">' + rows + '</table>' +
			'<hr style="border-top:1px dashed #000;">' +
			(order.note ? '<p><strong>NOTE:</strong> ' + escHtml(order.note) + '</p><hr style="border-top:1px dashed #000;">' : '');

		if ($('#nrtok-print-area').length === 0) {
			$('body').append('<div id="nrtok-print-area"></div>');
		}
		$('#nrtok-print-area').html(receipt);
		window.print();
	}

	function checkNewKitchenOrders() {
		if (!apEnabled || typeof ajaxurl === 'undefined') {
			return;
		}
		$.post(ajaxurl, {
			action: 'nrtok_poll_new_orders',
			nonce: apNonce,
			last_id: lastPrintedId
		}, function(res) {
			if (res && res.success && res.data && res.data.order) {
				const ord   = res.data.order;
				const ordId = parseInt(ord.id, 10) || 0;
				if (ordId > lastPrintedId) {
					lastPrintedId = ordId;
					localStorage.setItem('nrtok_last_printed_order_id', String(ordId));
					triggerAutoKOT(ord);
				}
			}
		});
	}

	setInterval(checkNewKitchenOrders, 3000);
});
</script>