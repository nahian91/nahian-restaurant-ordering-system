<?php
/**
 * Nahian POS Single Restaurant Table Details & QR Tent View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) || empty( $view_tbl ) ) {
	return;
}

global $wpdb;
$t_orders = $wpdb->prefix . 'nrtok_orders';

// Safe currency & restaurant name fallback.
if ( ! isset( $s ) || ! is_array( $s ) ) {
	$s = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
}
if ( ! isset( $cur ) ) {
	$cur = $s['currency'] ?? '৳';
}
$rest_name = ! empty( $s['restaurant_name'] ) ? $s['restaurant_name'] : 'NAHIAN';

$tbl_id        = absint( $view_tbl->id );
$tbl_name      = $view_tbl->table_name ?? '';
$v_is_busy     = isset( $active_rows[ $tbl_name ] );
$v_active_info = $v_is_busy ? $active_rows[ $tbl_name ] : null;
$base_url      = isset( $base_url ) ? $base_url : home_url( '/' );
$v_link        = add_query_arg( 'table', rawurlencode( $tbl_name ), $base_url );
$v_qr          = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . rawurlencode( html_entity_decode( $v_link, ENT_QUOTES, 'UTF-8' ) );

$v_orders = array();
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_orders ) ) === $t_orders ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$v_orders = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i WHERE table_no = %s ORDER BY id DESC LIMIT 10',
			$t_orders,
			$tbl_name
		)
	) ?: array();
}

$nrtok_base_tables_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=tables' );
$all_tables_url        = add_query_arg( 'subtab', 'all', $nrtok_base_tables_url );
$edit_url              = add_query_arg( 'edit_table', $tbl_id, $nrtok_base_tables_url );
$del_url               = wp_nonce_url(
	add_query_arg(
		array(
			'subtab'    => 'all',
			'del_table' => $tbl_id,
		),
		$nrtok_base_tables_url
	),
	'nrtok_delete_table_' . $tbl_id
);
$clean_cur             = html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' );

// Reusable SVG Icons.
$svg_back    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>';
$svg_edit    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$svg_trash   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
$svg_ext     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';
$svg_printer = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>';
$svg_qr      = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>';
$svg_users   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$svg_wallet  = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>';
$svg_key     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>';
$svg_eye     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
$svg_receipt = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1-2-1 2 1-2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>';
?>

<style>
	.nrtok-tv-grid {
		display: grid; grid-template-columns: 320px 1fr; gap: 28px; align-items: start;
	}
	@media (max-width: 920px) {
		.nrtok-tv-grid { grid-template-columns: 1fr; }
	}

	/* Left QR Tent Card */
	.nrtok-tv-qr-card {
		background: linear-gradient(180deg, #fff7ed 0%, #f8fafc 45%, #ffffff 100%);
		border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 24px 20px;
		text-align: center; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
	}
	.nrtok-tv-qr-badge {
		display: inline-flex; align-items: center; gap: 6px;
		background: #ffedd5; color: #c2410c; padding: 5px 12px; border-radius: 20px;
		font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px;
		margin-bottom: 16px;
	}
	.nrtok-tv-qr-frame {
		background: #ffffff; padding: 14px; border-radius: 18px;
		border: 1.5px solid #e2e8f0; display: inline-block;
		box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
	}
	.nrtok-tv-qr-frame img {
		width: 196px; height: 196px; display: block; border-radius: 8px;
	}
	.nrtok-tv-qr-caption {
		margin: 12px 0 16px; font-size: 12px; font-weight: 600; color: #64748b;
	}
	.nrtok-tv-qr-actions {
		display: flex; flex-direction: column; gap: 9px;
	}
	.nrtok-tv-btn-full {
		width: 100%; justify-content: center; box-sizing: border-box;
		display: inline-flex; align-items: center; gap: 7px;
	}
	.nrtok-tv-print-btn {
		padding: 11px 16px; border-radius: 12px; font-size: 13.5px;
	}

	/* Right Column Header */
	.nrtok-tv-header {
		display: flex; justify-content: space-between; align-items: center;
		flex-wrap: wrap; gap: 14px; padding-bottom: 18px;
		border-bottom: 1.5px solid #f1f5f9;
	}
	.nrtok-tv-id-pill {
		font-size: 11px; font-weight: 800; color: #f97316;
		letter-spacing: 0.7px; text-transform: uppercase; margin-bottom: 3px;
	}
	.nrtok-tv-title {
		margin: 0; font-size: 26px; font-weight: 900; color: #0f172a; letter-spacing: -0.4px;
	}
	.nrtok-tv-top-btns {
		display: flex; align-items: center; gap: 9px; flex-wrap: wrap;
	}
	.nrtok-tv-btn-ico {
		display: inline-flex; align-items: center; gap: 6px;
	}
	.nrtok-tv-del-btn {
		padding: 10px 15px; border-radius: 11px; text-decoration: none;
		background: #fef2f2; color: #dc2626; border: 1.5px solid #fecaca;
		font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;
		transition: all 0.15s ease;
	}
	.nrtok-tv-del-btn:hover { background: #fee2e2; color: #b91c1c; }

	/* KPI Stat Cards */
	.nrtok-tv-stats {
		display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin: 20px 0;
	}
	@media (max-width: 640px) {
		.nrtok-tv-stats { grid-template-columns: 1fr; }
	}
	.nrtok-tv-stat-box {
		background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 15px;
		padding: 16px 18px; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
	}
	.nrtok-tv-stat-busy { border-left: 4px solid #f59e0b; }
	.nrtok-tv-stat-free { border-left: 4px solid #10b981; }
	.nrtok-tv-stat-blue { border-left: 4px solid #3b82f6; }
	.nrtok-tv-stat-orange { border-left: 4px solid #f97316; }

	.nrtok-tv-stat-lbl {
		font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;
		letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;
	}
	.nrtok-tv-stat-val {
		font-size: 19px; font-weight: 900; color: #0f172a; margin-top: 7px;
		display: flex; align-items: center; gap: 7px;
	}
	.nrtok-tv-val-busy { color: #d97706; }
	.nrtok-tv-val-free { color: #059669; }
	.nrtok-tv-val-orange { color: #f97316; font-family: 'SFMono-Regular', Consolas, monospace; }
	.nrtok-tv-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
	.nrtok-tv-dot-busy { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2); }
	.nrtok-tv-dot-free { background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); }

	/* Credentials Box */
	.nrtok-tv-cred-card {
		background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 15px;
		padding: 16px 18px; margin-bottom: 22px; display: flex;
		justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;
	}
	.nrtok-tv-cred-title {
		font-size: 13px; font-weight: 800; color: #1e293b;
		display: flex; align-items: center; gap: 7px;
	}
	.nrtok-tv-cred-title svg { color: #f97316; }
	.nrtok-tv-cred-items {
		display: flex; align-items: center; gap: 16px; flex-wrap: wrap; font-size: 13.5px; color: #475569;
	}
	.nrtok-tv-cred-pill {
		background: #ffffff; border: 1px solid #cbd5e1; padding: 5px 11px;
		border-radius: 9px; display: inline-flex; align-items: center; gap: 7px;
	}
	.nrtok-tv-pass-txt {
		font-family: 'SFMono-Regular', Consolas, monospace; font-weight: 800; color: #ea580c;
	}
	.nrtok-tv-pass-toggle {
		background: none; border: none; color: #64748b; cursor: pointer;
		padding: 2px; display: inline-flex; align-items: center;
	}
	.nrtok-tv-pass-toggle:hover { color: #0f172a; }

	/* Recent Orders Section */
	.nrtok-tv-orders-head {
		margin: 0 0 12px; font-size: 15.5px; font-weight: 800; color: #0f172a;
		display: flex; align-items: center; gap: 7px;
	}
	.nrtok-tv-orders-head svg { color: #f97316; }
	.nrtok-tv-orders-wrap {
		border: 1.5px solid #e2e8f0; border-radius: 14px; overflow-x: auto; background: #fff;
	}
	.nrtok-tv-orders-table { width: 100%; border: none; box-shadow: none; margin: 0; }
	.nrtok-tv-empty-orders { text-align: center; color: #94a3b8; padding: 26px 16px; font-size: 13.5px; }
	.nrtok-tv-ord-amt { font-family: 'SFMono-Regular', Consolas, monospace; font-weight: 800; color: #0f172a; }
	.nrtok-tv-ord-time { color: #64748b; font-size: 12.5px; }

	.nrtok-tv-st-pill {
		display: inline-block; padding: 3px 9px; border-radius: 7px;
		font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px;
	}
	.nrtok-st-completed { background: #ecfdf5; color: #059669; }
	.nrtok-st-served { background: #eff6ff; color: #1d4ed8; }
	.nrtok-st-preparing { background: #fff7ed; color: #c2410c; }
	.nrtok-st-pending { background: #fef3c7; color: #b45309; }
</style>

<div class="nrtok-card nrtok-full-card">
	<div class="nrtok-tv-grid">
		<!-- Left Column: QR Tent Preview & Print -->
		<div class="nrtok-tv-qr-card">
			<div class="nrtok-tv-qr-badge">
				<?php echo $svg_qr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Table QR Code', 'nahian-restaurant-ordering-system' ); ?></span>
			</div>

			<div class="nrtok-tv-qr-frame">
				<img id="nrtok-v-tbl-qr-img" src="<?php echo esc_url( $v_qr ); ?>" alt="<?php echo esc_attr( 'QR for Table ' . $tbl_name ); ?>">
			</div>

			<p class="nrtok-tv-qr-caption">
				<?php esc_html_e( 'Scan with smartphone or open on table tablet to order.', 'nahian-restaurant-ordering-system' ); ?>
			</p>

			<div class="nrtok-tv-qr-actions">
				<a href="<?php echo esc_url( $v_link ); ?>" target="_blank" rel="noopener noreferrer" class="nrtok-btn nrtok-tv-btn-full">
					<?php echo $svg_ext; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Launch Tablet Menu', 'nahian-restaurant-ordering-system' ); ?></span>
				</a>
				<button type="button" id="nrtok-v-print-qr-btn" data-tname="<?php echo esc_attr( $tbl_name ); ?>" class="nrtok-btn-dark nrtok-tv-btn-full nrtok-tv-print-btn">
					<?php echo $svg_printer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Print QR Tent', 'nahian-restaurant-ordering-system' ); ?></span>
				</button>
			</div>
		</div>

		<!-- Right Column: Table Details, Stats & Orders -->
		<div>
			<div class="nrtok-tv-header">
				<div>
					<div class="nrtok-tv-id-pill">
						<?php
						printf(
							/* translators: %d: table ID */
							esc_html__( 'TABLE ID #%d', 'nahian-restaurant-ordering-system' ),
							absint( $tbl_id )
						);
						?>
					</div>
					<h2 class="nrtok-tv-title">
						<?php
						printf(
							/* translators: %s: table name/number */
							esc_html__( 'Table %s', 'nahian-restaurant-ordering-system' ),
							esc_html( $tbl_name )
						);
						?>
					</h2>
				</div>

				<div class="nrtok-tv-top-btns">
					<a href="<?php echo esc_url( $all_tables_url ); ?>" class="nrtok-btn-secondary nrtok-tv-btn-ico">
						<?php echo $svg_back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Back', 'nahian-restaurant-ordering-system' ); ?></span>
					</a>
					<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-btn nrtok-tv-btn-ico">
						<?php echo $svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Edit Table', 'nahian-restaurant-ordering-system' ); ?></span>
					</a>
					<a href="<?php echo esc_url( $del_url ); ?>" class="nrtok-tv-del-btn js-nrtok-confirm-del" data-tname="<?php echo esc_attr( $tbl_name ); ?>">
						<?php echo $svg_trash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Delete', 'nahian-restaurant-ordering-system' ); ?></span>
					</a>
				</div>
			</div>

			<!-- KPI Cards -->
			<div class="nrtok-tv-stats">
				<div class="nrtok-tv-stat-box <?php echo $v_is_busy ? 'nrtok-tv-stat-busy' : 'nrtok-tv-stat-free'; ?>">
					<div class="nrtok-tv-stat-lbl"><?php esc_html_e( 'Current Status', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-tv-stat-val <?php echo $v_is_busy ? 'nrtok-tv-val-busy' : 'nrtok-tv-val-free'; ?>">
						<span class="nrtok-tv-dot <?php echo $v_is_busy ? 'nrtok-tv-dot-busy' : 'nrtok-tv-dot-free'; ?>"></span>
						<span><?php echo $v_is_busy ? esc_html__( 'Occupied', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Available', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>
				</div>

				<div class="nrtok-tv-stat-box nrtok-tv-stat-blue">
					<div class="nrtok-tv-stat-lbl">
						<?php echo $svg_users; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Seating Capacity', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>
					<div class="nrtok-tv-stat-val">
						<?php
						printf(
							/* translators: %d: seating capacity */
							esc_html__( '%d Persons', 'nahian-restaurant-ordering-system' ),
							absint( $view_tbl->seats )
						);
						?>
					</div>
				</div>

				<div class="nrtok-tv-stat-box nrtok-tv-stat-orange">
					<div class="nrtok-tv-stat-lbl">
						<?php echo $svg_wallet; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Running Bill', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>
					<div class="nrtok-tv-stat-val nrtok-tv-val-orange">
						<?php echo esc_html( $clean_cur . number_format( ( $v_is_busy ? (float) $v_active_info->running_total : 0.0 ), 2 ) ); ?>
					</div>
				</div>
			</div>

			<!-- Tablet Login Credentials -->
			<div class="nrtok-tv-cred-card">
				<div class="nrtok-tv-cred-title">
					<?php echo $svg_key; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Tablet Login Credentials', 'nahian-restaurant-ordering-system' ); ?></span>
				</div>

				<div class="nrtok-tv-cred-items">
					<div class="nrtok-tv-cred-pill">
						<span><?php esc_html_e( 'Username:', 'nahian-restaurant-ordering-system' ); ?></span>
						<strong><?php echo esc_html( ! empty( $view_tbl->table_login ) ? $view_tbl->table_login : '—' ); ?></strong>
					</div>

					<div class="nrtok-tv-cred-pill">
						<span><?php esc_html_e( 'Password:', 'nahian-restaurant-ordering-system' ); ?></span>
						<?php if ( ! empty( $view_tbl->table_password ) ) : ?>
							<code id="nrtok-tv-pass-val" class="nrtok-tv-pass-txt" data-pass="<?php echo esc_attr( $view_tbl->table_password ); ?>" data-visible="0">••••••••</code>
							<button type="button" id="nrtok-tv-toggle-pass" class="nrtok-tv-pass-toggle" title="<?php esc_attr_e( 'Show / Hide Password', 'nahian-restaurant-ordering-system' ); ?>">
								<?php echo $svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</button>
						<?php else : ?>
							<strong>—</strong>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Recent Orders Section -->
			<h3 class="nrtok-tv-orders-head">
				<?php echo $svg_receipt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span>
					<?php
					printf(
						/* translators: %s: table name/number */
						esc_html__( 'Recent Orders on Table %s', 'nahian-restaurant-ordering-system' ),
						esc_html( $tbl_name )
					);
					?>
				</span>
			</h3>

			<div class="nrtok-tv-orders-wrap">
				<table class="nrtok-table nrtok-tv-orders-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Order #', 'nahian-restaurant-ordering-system' ); ?></th>
							<th><?php esc_html_e( 'Total', 'nahian-restaurant-ordering-system' ); ?></th>
							<th><?php esc_html_e( 'Status', 'nahian-restaurant-ordering-system' ); ?></th>
							<th><?php esc_html_e( 'Time', 'nahian-restaurant-ordering-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $v_orders ) ) : ?>
							<tr>
								<td colspan="4" class="nrtok-tv-empty-orders"><?php esc_html_e( 'No recent orders for this table yet.', 'nahian-restaurant-ordering-system' ); ?></td>
							</tr>
						<?php else : ?>
							<?php
							foreach ( $v_orders as $vo ) :
								$st_key   = sanitize_key( $vo->status ?? 'pending' );
								$st_class = 'nrtok-st-' . $st_key;
								?>
								<tr>
									<td><strong>#<?php echo absint( $vo->id ); ?></strong></td>
									<td><span class="nrtok-tv-ord-amt"><?php echo esc_html( $clean_cur . number_format( (float) $vo->total_amount, 2 ) ); ?></span></td>
									<td><span class="nrtok-tv-st-pill <?php echo esc_attr( $st_class ); ?>"><?php echo esc_html( $vo->status ); ?></span></td>
									<td class="nrtok-tv-ord-time"><?php echo esc_html( wp_date( 'M d, h:i A', strtotime( $vo->created_at ) ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	const restName      = <?php echo wp_json_encode( $rest_name ); ?>;
	const confirmDelMsg = <?php echo wp_json_encode( __( 'Are you sure you want to delete Table "%s"?', 'nahian-restaurant-ordering-system' ) ); ?>;
	const scanText      = <?php echo wp_json_encode( __( 'Scan QR Code to View Menu & Order', 'nahian-restaurant-ordering-system' ); ?>;

	function escHtml(str) {
		return String(str === null || str === undefined ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	// Toggle Password Visibility.
	$('#nrtok-tv-toggle-pass').on('click', function() {
		const $el = $('#nrtok-tv-pass-val');
		if ($el.attr('data-visible') === '0') {
			$el.text($el.attr('data-pass')).attr('data-visible', '1');
		} else {
			$el.text('••••••••').attr('data-visible', '0');
		}
	});

	$(document).on('click', '.js-nrtok-confirm-del', function(e) {
		const tName = $(this).data('tname') || <?php echo wp_json_encode( __( 'this table', 'nahian-restaurant-ordering-system' ) ); ?>;
		const msg   = confirmDelMsg.replace('%s', tName);
		if (!confirm(msg)) {
			e.preventDefault();
		}
	});

	$('#nrtok-v-print-qr-btn').on('click', function() {
		const tName = $(this).data('tname') || '';
		const qrSrc = $('#nrtok-v-tbl-qr-img').attr('src') || '';
		const w = window.open('', '_blank', 'width=440,height=580');
		if (w) {
			w.document.write(
				'<html><head><title>Table ' + escHtml(tName) + ' QR Tent</title>' +
				'<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;text-align:center;padding:30px;background:#fff;color:#0f172a;}' +
				'.tent{border:2px dashed #cbd5e1;border-radius:20px;padding:28px 20px;max-width:320px;margin:0 auto;}' +
				'.brand{font-size:20px;font-weight:900;letter-spacing:1px;text-transform:uppercase;margin:0 0 6px;}' +
				'.sub{font-size:12px;color:#64748b;margin:0 0 18px;}' +
				'.qr{width:210px;height:210px;padding:10px;border:1.5px solid #e2e8f0;border-radius:14px;}' +
				'.tbl{margin:18px 0 0;font-size:28px;font-weight:900;color:#ea580c;}</style></head>' +
				'<body><div class="tent">' +
				'<div class="brand">' + escHtml(restName) + '</div>' +
				'<div class="sub">' + escHtml(scanText) + '</div>' +
				'<img class="qr" src="' + escHtml(qrSrc) + '" alt="QR Code">' +
				'<div class="tbl">TABLE ' + escHtml(tName) + '</div>' +
				'</div><script>window.onload = function() { window.print(); };<\/script></body></html>'
			);
			w.document.close();
		}
	});
});
</script>