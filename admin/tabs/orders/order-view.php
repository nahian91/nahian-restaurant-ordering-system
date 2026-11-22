<?php
/**
 * Nahian POS Single Order Invoice & Details View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! nrtok_current_user_can_pos() ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
}

if ( ! isset( $view_order ) || ! is_object( $view_order ) ) {
	return;
}

// Safe fallback for settings and currency if not passed.
if ( ! isset( $s ) || ! is_array( $s ) ) {
	$s = nrtok_get_settings();
}
$cur = isset( $s['currency'] ) ? $s['currency'] : '৳';

$ord_id  = absint( $view_order->id );
$v_items = json_decode( $view_order->items, true );
if ( ! is_array( $v_items ) ) {
	$v_items = array();
}
$st_class = 'nrtok-ord-st-' . sanitize_html_class( $view_order->status );

$base_orders_url = add_query_arg(
	array(
		'page' => 'nahian-restaurant-ordering-system',
		'tab'  => 'orders',
	),
	admin_url( 'admin.php' )
);

$edit_url = add_query_arg(
	array(
		'view_order' => $ord_id,
		'edit_order' => 1,
	),
	$base_orders_url
);

// Nonce-protected status redirect base URL.
$status_base_url = add_query_arg(
	'set_status',
	'',
	wp_nonce_url(
		add_query_arg(
			array(
				'view_order' => $ord_id,
				'order_id'   => $ord_id,
			),
			$base_orders_url
		),
		'nrtok_order_status_' . $ord_id
	)
);
?>

<style>
	.nrtok-ord-view-pro-shell { padding: 4px; max-width: 1400px; margin: 0 auto; }
	.nrtok-ord-select-w-195 { width: 195px; }
	.nrtok-ord-pro-th { padding: 14px 18px; }
	.nrtok-ord-pro-td { padding: 14px 18px; vertical-align: middle; }
	.nrtok-col-w-140 { width: 140px; }
	.nrtok-col-w-90 { width: 90px; }
	.nrtok-col-w-150 { width: 150px; }
	.nrtok-text-right { text-align: right; }

	.nrtok-ord-back-bar { margin-bottom: 16px; }
	.nrtok-ord-back-link {
		display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700;
		color: #64748b; text-decoration: none; transition: color 0.15s;
	}
	.nrtok-ord-back-link:hover { color: #f97316; }

	/* Top Pro Header */
	.nrtok-ord-pro-header-card {
		background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 24px;
		display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
		gap: 16px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
	}
	.nrtok-ord-pro-meta-date {
		display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 800;
		color: #64748b; margin-bottom: 6px; letter-spacing: 0.3px;
	}
	.nrtok-ord-pro-title { margin: 0; font-size: 24px; font-weight: 900; color: #0f172a; letter-spacing: -0.4px; }
	.nrtok-ord-pro-controls { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

	.nrtok-ord-pro-btn {
		padding: 10px 16px; font-size: 13px; font-weight: 700; border-radius: 11px; cursor: pointer;
		display: inline-flex; align-items: center; gap: 7px; border: none; text-decoration: none;
		transition: all 0.18s ease; box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
	}
	.nrtok-ord-pro-btn-secondary { background: #f1f5f9; color: #334155; border: 1.5px solid #cbd5e1; }
	.nrtok-ord-pro-btn-secondary:hover { background: #e2e8f0; color: #0f172a; }
	.nrtok-ord-pro-btn-dark { background: #0f172a; color: #fff; }
	.nrtok-ord-pro-btn-dark:hover { background: #1e293b; transform: translateY(-1px); }
	.nrtok-ord-pro-btn-primary { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #fff; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3); }
	.nrtok-ord-pro-btn-primary:hover { opacity: 0.95; transform: translateY(-1px); }

	/* Stats Grid Pro */
	.nrtok-ord-pro-stats-grid {
		display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
		gap: 16px; margin-bottom: 20px;
	}
	.nrtok-ord-pro-stat-card {
		background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px;
		box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
	}
	.nrtok-stat-border-orange { border-left: 4.5px solid #f97316; }
	.nrtok-stat-border-blue { border-left: 4.5px solid #3b82f6; }
	.nrtok-stat-border-green { border-left: 4.5px solid #10b981; }
	.nrtok-ord-pro-stat-lbl { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
	.nrtok-ord-pro-stat-val { font-size: 18.5px; font-weight: 900; color: #0f172a; margin-top: 8px; display: flex; align-items: center; gap: 8px; }
	.nrtok-ord-stat-val-status { text-transform: uppercase; color: #1d4ed8; }
	.nrtok-ord-stat-val-total { color: #059669; font-family: "Courier New", Courier, monospace; }

	/* Note Box Pro */
	.nrtok-ord-pro-note-box {
		background: #fffbeb; border: 1.5px solid #fef3c7; color: #92400e;
		padding: 16px 20px; border-radius: 16px; margin-bottom: 20px; font-size: 13.5px;
		display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.04);
	}

	/* Content Box & Table Pro */
	.nrtok-ord-pro-content-box {
		background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 24px;
		box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
	}
	.nrtok-ord-pro-sec-title { font-size: 17px; font-weight: 900; color: #0f172a; margin: 0 0 16px; }
	.nrtok-ord-pro-table-wrapper { border: 1px solid #e2e8f0; border-radius: 14px; overflow-x: auto; }
	.nrtok-ord-pro-items-table { border: none; border-radius: 0; box-shadow: none; width: 100%; }
	.nrtok-ord-pro-items-table th { background: #f8fafc; font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; }
	.nrtok-ord-pro-item-row { transition: background 0.15s; border-bottom: 1px solid #f1f5f9; }
	.nrtok-ord-pro-item-row:hover { background: #fafaf9; }
	.nrtok-ord-pro-item-row:last-child { border-bottom: none; }
	.nrtok-ord-pro-dish-name { font-size: 14.5px; color: #0f172a; }

	.nrtok-ord-pro-addons-list { display: flex; flex-direction: column; gap: 4px; margin-top: 4px; }
	.nrtok-ord-pro-addon-pill {
		display: inline-block; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;
		padding: 2px 9px; border-radius: 6px; font-size: 11.5px; font-weight: 700; width: fit-content;
	}
	.nrtok-ord-pro-no-addons { color: #94a3b8; font-size: 13px; font-weight: 500; }
	.nrtok-ord-pro-qty-badge { background: #0f172a; color: #fff; padding: 3px 9px; border-radius: 7px; font-size: 12px; }
	.nrtok-ord-mono { font-family: "Courier New", Courier, monospace; font-weight: 800; }
	.nrtok-ord-pro-line-tot { color: #0f172a; }

	/* Totals Summary Card Pro */
	.nrtok-ord-pro-totals-card {
		max-width: 380px; margin-left: auto; margin-top: 22px; background: #f8fafc;
		border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 20px 24px;
		box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
	}
	.nrtok-ord-pro-tot-row { display: flex; justify-content: space-between; font-size: 14px; color: #64748b; margin-bottom: 10px; font-weight: 600; }
	.nrtok-ord-pro-tot-tax {
		display: flex; justify-content: space-between; font-size: 14px; color: #64748b;
		padding-bottom: 14px; border-bottom: 1.5px dashed #cbd5e1; font-weight: 600;
	}
	.nrtok-ord-pro-tot-grand {
		display: flex; justify-content: space-between; font-size: 17px; font-weight: 900;
		color: #0f172a; margin-top: 14px; align-items: center;
	}
	.nrtok-ord-pro-tot-grand-val { color: #f97316; font-size: 22px; font-family: "Courier New", Courier, monospace; }
</style>

<div class="nrtok-ord-view-pro-shell">
	<!-- Back to Orders Bar -->
	<div class="nrtok-ord-back-bar">
		<a href="<?php echo esc_url( $base_orders_url ); ?>" class="nrtok-ord-back-link">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
			<span><?php esc_html_e( 'Back to Orders List', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	</div>

	<!-- Top Header Banner Pro -->
	<div class="nrtok-ord-pro-header-card">
		<div class="nrtok-ord-pro-title-wrap">
			<div class="nrtok-ord-pro-meta">
				<span class="nrtok-ord-pro-meta-date">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					<span>
						<?php
						printf(
							/* translators: %s: formatted order date and time */
							esc_html__( 'PLACED ON %s', 'nahian-restaurant-ordering-system' ),
							esc_html( strtoupper( mysql2date( 'M d, Y • h:i A', $view_order->created_at ) ) )
						);
						?>
					</span>
				</span>
			</div>
			<h2 class="nrtok-ord-pro-title">
				<?php
				printf(
					/* translators: %d: order ID */
					esc_html__( 'Order #%d', 'nahian-restaurant-ordering-system' ),
					absint( $ord_id )
				);
				?>
			</h2>
		</div>

		<div class="nrtok-ord-pro-controls">
			<div class="nrtok-ord-select-wrapper nrtok-ord-select-w-195">
				<select aria-label="<?php esc_attr_e( 'Change Order Status', 'nahian-restaurant-ordering-system' ); ?>" class="nrtok-ord-status-select <?php echo esc_attr( $st_class ); ?> js-nrtok-ord-status-select" data-redirect="<?php echo esc_url( $status_base_url ); ?>">
					<option value="pending" <?php selected( $view_order->status, 'pending' ); ?>><?php esc_html_e( 'Status: Pending', 'nahian-restaurant-ordering-system' ); ?></option>
					<option value="preparing" <?php selected( $view_order->status, 'preparing' ); ?>><?php esc_html_e( 'Status: Cooking', 'nahian-restaurant-ordering-system' ); ?></option>
					<option value="served" <?php selected( $view_order->status, 'served' ); ?>><?php esc_html_e( 'Status: Served', 'nahian-restaurant-ordering-system' ); ?></option>
					<option value="completed" <?php selected( $view_order->status, 'completed' ); ?>><?php esc_html_e( 'Status: Paid / Done', 'nahian-restaurant-ordering-system' ); ?></option>
				</select>
			</div>

			<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-ord-pro-btn nrtok-ord-pro-btn-secondary">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
				<span><?php esc_html_e( 'Edit Order', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>

			<button type="button" class="nrtok-ord-pro-btn nrtok-ord-pro-btn-dark nrtok-print-kot" data-order="<?php echo esc_attr( wp_json_encode( $view_order ) ); ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
				<span><?php esc_html_e( 'Print KOT', 'nahian-restaurant-ordering-system' ); ?></span>
			</button>

			<button type="button" class="nrtok-ord-pro-btn nrtok-ord-pro-btn-primary nrtok-print-bill" data-order="<?php echo esc_attr( wp_json_encode( $view_order ) ); ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>
				<span><?php esc_html_e( 'Customer Bill', 'nahian-restaurant-ordering-system' ); ?></span>
			</button>
		</div>
	</div>

	<!-- Quick Stats Cards Pro -->
	<div class="nrtok-ord-pro-stats-grid">
		<div class="nrtok-ord-pro-stat-card nrtok-stat-border-orange">
			<div class="nrtok-ord-pro-stat-lbl"><?php esc_html_e( 'Assigned Table', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-ord-pro-stat-val">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 10h16M6 10v8M18 10v8M9 14h6"/></svg>
				<span><?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?> <?php echo esc_html( $view_order->table_no ); ?></span>
			</div>
		</div>

		<div class="nrtok-ord-pro-stat-card nrtok-stat-border-blue">
			<div class="nrtok-ord-pro-stat-lbl"><?php esc_html_e( 'Current Status', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-ord-pro-stat-val nrtok-ord-stat-val-status"><?php echo esc_html( $view_order->status ); ?></div>
		</div>

		<div class="nrtok-ord-pro-stat-card nrtok-stat-border-green">
			<div class="nrtok-ord-pro-stat-lbl"><?php esc_html_e( 'Total Payable Amount', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-ord-pro-stat-val nrtok-ord-stat-val-total"><?php echo esc_html( $cur . number_format( (float) $view_order->total_amount, 2 ) ); ?></div>
		</div>
	</div>

	<!-- Kitchen Note Box Pro -->
	<?php if ( ! empty( $view_order->note ) ) : ?>
		<div class="nrtok-ord-pro-note-box">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
			<div><strong><?php esc_html_e( 'Special Kitchen Note:', 'nahian-restaurant-ordering-system' ); ?></strong> <?php echo esc_html( $view_order->note ); ?></div>
		</div>
	<?php endif; ?>

	<!-- Itemized Breakdown Table Pro Container -->
	<div class="nrtok-ord-pro-content-box">
		<h3 class="nrtok-ord-pro-sec-title"><?php esc_html_e( 'Itemized Order Breakdown', 'nahian-restaurant-ordering-system' ); ?></h3>
		<div class="nrtok-ord-pro-table-wrapper">
			<table class="nrtok-table nrtok-full-table nrtok-ord-pro-items-table">
				<thead>
					<tr>
						<th class="nrtok-ord-pro-th"><?php esc_html_e( 'Dish / Item Name', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-ord-pro-th"><?php esc_html_e( 'Selected Add-ons / Extras', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-ord-pro-th nrtok-col-w-140"><?php esc_html_e( 'Unit Price', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-ord-pro-th nrtok-col-w-90"><?php esc_html_e( 'Qty', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-ord-pro-th nrtok-col-w-150 nrtok-text-right"><?php esc_html_e( 'Line Total', 'nahian-restaurant-ordering-system' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $v_items as $vi ) :
						$u_price = isset( $vi['unitPrice'] ) ? (float) $vi['unitPrice'] : ( isset( $vi['price'] ) ? (float) $vi['price'] : 0.0 );
						$qty     = isset( $vi['qty'] ) ? absint( $vi['qty'] ) : 1;
						$l_total = $u_price * $qty;
						?>
					<tr class="nrtok-ord-pro-item-row">
						<td class="nrtok-ord-pro-td">
							<strong class="nrtok-ord-pro-dish-name"><?php echo esc_html( isset( $vi['name'] ) ? $vi['name'] : '' ); ?></strong>
						</td>
						<td class="nrtok-ord-pro-td">
							<?php if ( ! empty( $vi['extras'] ) && is_array( $vi['extras'] ) ) : ?>
								<div class="nrtok-ord-pro-addons-list">
									<?php foreach ( $vi['extras'] as $ex ) : ?>
										<span class="nrtok-ord-pro-addon-pill">+ <?php echo esc_html( $ex ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php else : ?>
								<span class="nrtok-ord-pro-no-addons"><?php esc_html_e( 'None', 'nahian-restaurant-ordering-system' ); ?></span>
							<?php endif; ?>
						</td>
						<td class="nrtok-ord-pro-td"><span class="nrtok-ord-mono"><?php echo esc_html( $cur . number_format( $u_price, 2 ) ); ?></span></td>
						<td class="nrtok-ord-pro-td"><strong class="nrtok-ord-pro-qty-badge"><?php echo absint( $qty ); ?>&times;</strong></td>
						<td class="nrtok-ord-pro-td nrtok-text-right"><strong class="nrtok-ord-mono nrtok-ord-pro-line-tot"><?php echo esc_html( $cur . number_format( $l_total, 2 ) ); ?></strong></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- Final Invoice Totals Summary Box Pro -->
		<div class="nrtok-ord-pro-totals-card">
			<div class="nrtok-ord-pro-tot-row">
				<span><?php esc_html_e( 'Subtotal', 'nahian-restaurant-ordering-system' ); ?></span>
				<strong class="nrtok-ord-mono"><?php echo esc_html( $cur . number_format( (float) $view_order->subtotal, 2 ) ); ?></strong>
			</div>
			<div class="nrtok-ord-pro-tot-tax">
				<span><?php esc_html_e( 'VAT / Tax Included', 'nahian-restaurant-ordering-system' ); ?></span>
				<strong class="nrtok-ord-mono"><?php echo esc_html( $cur . number_format( (float) $view_order->tax_amount, 2 ) ); ?></strong>
			</div>
			<div class="nrtok-ord-pro-tot-grand">
				<span><?php esc_html_e( 'Total Payable', 'nahian-restaurant-ordering-system' ); ?></span>
				<span class="nrtok-ord-pro-tot-grand-val nrtok-ord-mono"><?php echo esc_html( $cur . number_format( (float) $view_order->total_amount, 2 ) ); ?></span>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	const restName  = <?php echo wp_json_encode( $s['restaurant_name'] ?? 'NAHIAN' ); ?>;
	const restPhone = <?php echo wp_json_encode( $s['phone'] ?? '' ); ?>;
	const restEmail = <?php echo wp_json_encode( $s['email'] ?? '' ); ?>;
	const ordPrefix = <?php echo wp_json_encode( $s['order_prefix'] ?? 'ORD-' ); ?>;
	const currency  = <?php echo wp_json_encode( $s['currency'] ?? '৳' ); ?>;
	const footerTxt = <?php echo wp_json_encode( $s['receipt_footer'] ?? __( 'Thank you for dining with us!', 'nahian-restaurant-ordering-system' ) ); ?>;

	function escHtml(str) {
		return String(str === null || str === undefined ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	// Status Select Dropdown Change
	$(document).on('change', '.js-nrtok-ord-status-select', function() {
		const baseUrl = $(this).data('redirect');
		if (baseUrl) {
			window.location.href = baseUrl + encodeURIComponent($(this).val());
		}
	});

	// 1. Kitchen Order Ticket (KOT) Print Handler
	$(document).on('click', '.nrtok-print-kot', function() {
		const order = $(this).data('order');
		if (!order) return;

		const items = typeof order.items === 'string' ? JSON.parse(order.items) : (order.items || []);
		let rows = '';
		items.forEach(function(i) {
			const extraStr = (i.extras && i.extras.length)
				? '<br><span style="font-size:12px; font-weight:normal;">  + ' + i.extras.map(escHtml).join(', ') + '</span>'
				: '';
			rows += '<tr><td style="padding:4px 0;">' + escHtml(i.name) + extraStr + '</td><td style="text-align:right; vertical-align:top;">x' + parseInt(i.qty || 1, 10) + '</td></tr>';
		});

		const noteHtml = order.note
			? '<p><strong>' + escHtml(<?php echo wp_json_encode( __( 'NOTE:', 'nahian-restaurant-ordering-system' ) ); ?>) + '</strong> ' + escHtml(order.note) + '</p><hr style="border-top:1px dashed #000;">'
			: '';

		const receipt =
			'<div style="text-align:center;">' +
				'<h2 style="margin:0; font-size:20px;">' + escHtml(restName) + '</h2>' +
				'<p style="margin:2px 0; font-size:12px;">' + escHtml(<?php echo wp_json_encode( __( '*** KITCHEN TICKET (KOT) ***', 'nahian-restaurant-ordering-system' ) ); ?>) + '</p>' +
				'<h1 style="margin:6px 0; border:2px solid #000; padding:4px;">' + escHtml(<?php echo wp_json_encode( __( 'TABLE:', 'nahian-restaurant-ordering-system' ) ); ?>) + ' ' + escHtml(order.table_no) + '</h1>' +
				'<p style="margin:2px 0;">' + escHtml(<?php echo wp_json_encode( __( 'Order #', 'nahian-restaurant-ordering-system' ) ); ?>) + parseInt(order.id, 10) + ' | ' + escHtml(order.created_at) + '</p>' +
			'</div>' +
			'<hr style="border-top:1px dashed #000;">' +
			'<table style="width:100%; font-size:14px; font-weight:bold;">' + rows + '</table>' +
			'<hr style="border-top:1px dashed #000;">' +
			noteHtml;

		if ($('#nrtok-print-area').length === 0) {
			$('body').append('<div id="nrtok-print-area"></div>');
		}
		$('#nrtok-print-area').html(receipt);
		window.print();
	});

	// 2. Customer Receipt / Tax Bill Print Handler
	$(document).on('click', '.nrtok-print-bill', function() {
		const order = $(this).data('order');
		if (!order) return;

		const items = typeof order.items === 'string' ? JSON.parse(order.items) : (order.items || []);
		let rows = '';
		items.forEach(function(i) {
			const unitPr   = parseFloat(i.unitPrice || i.price || 0);
			const qty      = parseInt(i.qty || 1, 10);
			const line     = (unitPr * qty).toFixed(2);
			const extraStr = (i.extras && i.extras.length)
				? '<br><span style="font-size:11px; color:#444;">  + ' + i.extras.map(escHtml).join(', ') + '</span>'
				: '';
			rows += '<tr><td style="padding:3px 0;">' + qty + 'x ' + escHtml(i.name) + extraStr + '</td><td style="text-align:right; vertical-align:top;">' + escHtml(currency) + line + '</td></tr>';
		});

		let contactStr = '';
		if (restPhone && restEmail) contactStr = 'Ph: ' + escHtml(restPhone) + ' | ' + escHtml(restEmail);
		else if (restPhone) contactStr = 'Ph: ' + escHtml(restPhone);
		else if (restEmail) contactStr = escHtml(restEmail);

		const contactHtml = contactStr ? '<p style="margin:2px 0; font-size:10px; color:#333;">' + contactStr + '</p>' : '';

		const receipt =
			'<div style="text-align:center;">' +
				'<h2 style="margin:0; font-size:20px;">' + escHtml(restName) + '</h2>' +
				contactHtml +
				'<p style="margin:2px 0; font-size:12px;">' + escHtml(<?php echo wp_json_encode( __( '*** TAX INVOICE ***', 'nahian-restaurant-ordering-system' ) ); ?>) + '</p>' +
				'<p style="margin:2px 0;">Table: <strong>' + escHtml(order.table_no) + '</strong> | Ord: <strong>' + escHtml(ordPrefix) + parseInt(order.id, 10) + '</strong></p>' +
				'<p style="margin:2px 0; font-size:11px;">' + escHtml(order.created_at) + '</p>' +
			'</div>' +
			'<hr style="border-top:1px dashed #000;">' +
			'<table style="width:100%; font-size:13px;">' + rows + '</table>' +
			'<hr style="border-top:1px dashed #000;">' +
			'<div style="display:flex; justify-content:space-between;"><span>' + escHtml(<?php echo wp_json_encode( __( 'Subtotal:', 'nahian-restaurant-ordering-system' ) ); ?>) + '</span><span>' + escHtml(currency) + parseFloat(order.subtotal || 0).toFixed(2) + '</span></div>' +
			'<div style="display:flex; justify-content:space-between;"><span>' + escHtml(<?php echo wp_json_encode( __( 'VAT/Tax:', 'nahian-restaurant-ordering-system' ) ); ?>) + '</span><span>' + escHtml(currency) + parseFloat(order.tax_amount || 0).toFixed(2) + '</span></div>' +
			'<div style="display:flex; justify-content:space-between; font-size:16px; font-weight:bold; margin-top:4px;"><span>' + escHtml(<?php echo wp_json_encode( __( 'TOTAL PAYABLE:', 'nahian-restaurant-ordering-system' ) ); ?>) + '</span><span>' + escHtml(currency) + parseFloat(order.total_amount || 0).toFixed(2) + '</span></div>' +
			'<hr style="border-top:1px dashed #000;">' +
			'<p style="text-align:center; font-size:11px;">' + escHtml(footerTxt) + '</p>';

		if ($('#nrtok-print-area').length === 0) {
			$('body').append('<div id="nrtok-print-area"></div>');
		}
		$('#nrtok-print-area').html(receipt);
		window.print();
	});
});
</script>