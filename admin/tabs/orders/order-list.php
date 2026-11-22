<?php
/**
 * Nahian POS Orders List & Print Hub
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

$nrtok_svg_search  = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
$nrtok_svg_eye     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
$nrtok_svg_edit    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_print   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>';
$nrtok_svg_receipt = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1-2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>';
$nrtok_svg_trash   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';

$nrtok_base_orders_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=orders' );
$nrtok_filter_tabs     = array(
	'all'       => array(
		'label' => __( 'All Orders', 'nahian-restaurant-ordering-system' ),
		'dot'   => '',
	),
	'pending'   => array(
		'label' => __( 'Pending', 'nahian-restaurant-ordering-system' ),
		'dot'   => 'nrtok-dot-pending',
	),
	'preparing' => array(
		'label' => __( 'Preparing', 'nahian-restaurant-ordering-system' ),
		'dot'   => 'nrtok-dot-preparing',
	),
	'served'    => array(
		'label' => __( 'Served', 'nahian-restaurant-ordering-system' ),
		'dot'   => 'nrtok-dot-served',
	),
	'completed' => array(
		'label' => __( 'Paid', 'nahian-restaurant-ordering-system' ),
		'dot'   => 'nrtok-dot-completed',
	),
);
?>

<div class="nrtok-ord-toolbar-pro">
	<div class="nrtok-pills nrtok-ord-pills-modern">
		<?php
		foreach ( $nrtok_filter_tabs as $tab_key => $tab_meta ) :
			$tab_args = array( 'status_filter' => $tab_key );
			if ( ! empty( $search_query ) ) {
				$tab_args['s_ord'] = $search_query;
			}
			$tab_url = add_query_arg( $tab_args, $nrtok_base_orders_url );
			?>
			<a href="<?php echo esc_url( $tab_url ); ?>" class="nrtok-pill <?php echo ( $filter === $tab_key ) ? 'active' : ''; ?>">
				<?php if ( ! empty( $tab_meta['dot'] ) ) : ?>
					<span class="nrtok-st-indicator <?php echo esc_attr( $tab_meta['dot'] ); ?>"></span>
				<?php endif; ?>
				<span><?php echo esc_html( $tab_meta['label'] ); ?></span>
				<span class="nrtok-pill-badge"><?php echo absint( $counts[ $tab_key ] ?? 0 ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="nrtok-ord-search-box-pro">
		<input type="hidden" name="page" value="nahian-restaurant-ordering-system">
		<input type="hidden" name="tab" value="orders">
		<input type="hidden" name="status_filter" value="<?php echo esc_attr( $filter ); ?>">
		<div class="nrtok-search-input-wrap">
			<?php echo $nrtok_svg_search; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<input type="text" name="s_ord" id="nrtok-ord-live-search" class="nrtok-ord-search-input-pro" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search Order # or Table...', 'nahian-restaurant-ordering-system' ); ?>">
			<?php if ( ! empty( $search_query ) ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'status_filter', $filter, $nrtok_base_orders_url ) ); ?>" class="nrtok-search-clear-btn" title="<?php esc_attr_e( 'Clear Search', 'nahian-restaurant-ordering-system' ); ?>">&times;</a>
			<?php endif; ?>
		</div>
	</form>
</div>

<div class="nrtok-card nrtok-ord-table-card-pro">
	<div class="nrtok-table-responsive">
		<table class="nrtok-table nrtok-full-table" id="nrtok-orders-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Order ID & Time', 'nahian-restaurant-ordering-system' ); ?></th>
					<th><?php esc_html_e( 'Table No', 'nahian-restaurant-ordering-system' ); ?></th>
					<th><?php esc_html_e( 'Bill Total', 'nahian-restaurant-ordering-system' ); ?></th>
					<th><?php esc_html_e( 'Instant Status Switcher', 'nahian-restaurant-ordering-system' ); ?></th>
					<th style="text-align: right;"><?php esc_html_e( 'Actions & Print Hub', 'nahian-restaurant-ordering-system' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $orders ) ) : ?>
					<tr><td colspan="5" class="nrtok-ord-empty-cell-pro"><?php esc_html_e( 'No orders found matching your criteria.', 'nahian-restaurant-ordering-system' ); ?></td></tr>
				<?php endif; ?>

				<?php
				$cur = $s['currency'] ?? '৳';
				foreach ( $orders as $ord ) :
					$ord_id = absint( $ord->id );

					$state_args = array(
						'status_filter' => $filter,
						'paged'         => $paged,
						'order_id'      => $ord_id,
					);
					if ( ! empty( $search_query ) ) {
						$state_args['s_ord'] = $search_query;
					}

					$url_pending   = wp_nonce_url( add_query_arg( array_merge( $state_args, array( 'set_status' => 'pending' ) ), $nrtok_base_orders_url ), 'nrtok_order_status_' . $ord_id );
					$url_preparing = wp_nonce_url( add_query_arg( array_merge( $state_args, array( 'set_status' => 'preparing' ) ), $nrtok_base_orders_url ), 'nrtok_order_status_' . $ord_id );
					$url_served    = wp_nonce_url( add_query_arg( array_merge( $state_args, array( 'set_status' => 'served' ) ), $nrtok_base_orders_url ), 'nrtok_order_status_' . $ord_id );
					$url_completed = wp_nonce_url( add_query_arg( array_merge( $state_args, array( 'set_status' => 'completed' ) ), $nrtok_base_orders_url ), 'nrtok_order_status_' . $ord_id );

					$view_url = add_query_arg( 'view_order', $ord_id, $nrtok_base_orders_url );
					$edit_url = add_query_arg(
						array(
							'view_order' => $ord_id,
							'edit_order' => 1,
						),
						$nrtok_base_orders_url
					);

					$del_args = array(
						'status_filter' => $filter,
						'paged'         => $paged,
						'delete_order'  => $ord_id,
					);
					if ( ! empty( $search_query ) ) {
						$del_args['s_ord'] = $search_query;
					}
					$del_url = wp_nonce_url( add_query_arg( $del_args, $nrtok_base_orders_url ), 'nrtok_delete_order_' . $ord_id );
					?>
				<tr class="nrtok-ord-row-pro">
					<td>
						<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-ord-id-link-pro">
							#<?php echo absint( $ord_id ); ?>
						</a>
						<div class="nrtok-ord-time-pro"><?php echo esc_html( mysql2date( 'h:i A', $ord->created_at ) ); ?></div>
					</td>
					<td>
						<span class="nrtok-ord-table-badge-pro">
							<?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?> <?php echo esc_html( $ord->table_no ); ?>
						</span>
					</td>
					<td>
						<strong class="nrtok-ord-total-pro"><?php echo esc_html( $cur . number_format( (float) $ord->total_amount, 2 ) ); ?></strong>
					</td>
					<td>
						<!-- Highly Visible Status Switcher with SVG Icons -->
						<div class="nrtok-ord-fast-status-pro">
							<a href="<?php echo esc_url( $url_pending ); ?>" class="nrtok-pro-pill nrtok-pill-pending-btn <?php echo ( 'pending' === $ord->status ) ? 'active-state' : ''; ?>" title="<?php esc_attr_e( 'Set as Pending', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
								<span><?php esc_html_e( 'Pending', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $url_preparing ); ?>" class="nrtok-pro-pill nrtok-pill-preparing-btn <?php echo ( 'preparing' === $ord->status ) ? 'active-state' : ''; ?>" title="<?php esc_attr_e( 'Set as Cooking', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
								<span><?php esc_html_e( 'Cooking', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $url_served ); ?>" class="nrtok-pro-pill nrtok-pill-served-btn <?php echo ( 'served' === $ord->status ) ? 'active-state' : ''; ?>" title="<?php esc_attr_e( 'Set as Served', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
								<span><?php esc_html_e( 'Served', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $url_completed ); ?>" class="nrtok-pro-pill nrtok-pill-completed-btn <?php echo ( 'completed' === $ord->status ) ? 'active-state' : ''; ?>" title="<?php esc_attr_e( 'Set as Paid', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
								<span><?php esc_html_e( 'Paid', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
						</div>
					</td>
					<td style="text-align: right;">
						<div class="nrtok-ord-action-group-pro">
							<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-ord-act-btn-pro nrtok-act-view-pro" title="<?php esc_attr_e( 'View Details', 'nahian-restaurant-ordering-system' ); ?>">
								<?php echo $nrtok_svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'View', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-ord-act-btn-pro nrtok-act-edit-pro" title="<?php esc_attr_e( 'Edit Order', 'nahian-restaurant-ordering-system' ); ?>">
								<?php echo $nrtok_svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Edit', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<button type="button" class="nrtok-ord-act-btn-pro nrtok-act-kot-pro nrtok-print-kot" data-order="<?php echo esc_attr( wp_json_encode( $ord ) ); ?>" title="<?php esc_attr_e( 'Print Kitchen Ticket', 'nahian-restaurant-ordering-system' ); ?>">
								<?php echo $nrtok_svg_print; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'KOT', 'nahian-restaurant-ordering-system' ); ?></span>
							</button>
							<button type="button" class="nrtok-ord-act-btn-pro nrtok-act-bill-pro nrtok-print-bill" data-order="<?php echo esc_attr( wp_json_encode( $ord ) ); ?>" title="<?php esc_attr_e( 'Print Tax Invoice', 'nahian-restaurant-ordering-system' ); ?>">
								<?php echo $nrtok_svg_receipt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Bill', 'nahian-restaurant-ordering-system' ); ?></span>
							</button>
							<?php if ( current_user_can( 'manage_options' ) ) : ?>
								<a href="<?php echo esc_url( $del_url ); ?>" class="nrtok-ord-act-btn-pro nrtok-act-del-pro js-nrtok-delete-order" title="<?php esc_attr_e( 'Delete Order', 'nahian-restaurant-ordering-system' ); ?>">
									<?php echo $nrtok_svg_trash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							<?php endif; ?>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<!-- Enhanced Pagination Bar Pro -->
<?php
if ( $total_pages > 1 ) :
	$pag_args = array( 'status_filter' => $filter );
	if ( ! empty( $search_query ) ) {
		$pag_args['s_ord'] = $search_query;
	}
	$base_pagination_url = add_query_arg( $pag_args, $nrtok_base_orders_url );

	$per_page_num = isset( $per_page ) ? max( 1, absint( $per_page ) ) : 10;
	$range_start  = ( ( $paged - 1 ) * $per_page_num ) + 1;
	$range_end    = min( $total_items, $paged * $per_page_num );

	$svg_chev_l  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
	$svg_chev_r  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
	$svg_chev_ll = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="11 17 6 12 11 7"/><polyline points="18 17 13 12 18 7"/></svg>';
	$svg_chev_rr = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>';

	// Build smart page list with ellipses.
	$page_range = array();
	$window     = 2;
	for ( $i = 1; $i <= $total_pages; $i++ ) {
		if ( 1 === $i || $total_pages === $i || ( $i >= $paged - $window && $i <= $paged + $window ) ) {
			$page_range[] = $i;
		}
	}
	?>
	<nav class="nrtok-ord-pagination-pro" aria-label="<?php esc_attr_e( 'Orders Pagination', 'nahian-restaurant-ordering-system' ); ?>">
		<div class="nrtok-ord-pag-info">
			<span class="nrtok-pag-pill-summary">
				<?php
				printf(
					/* translators: 1: start item number, 2: end item number, 3: total items */
					esc_html__( 'Showing %1$s–%2$s of %3$s orders', 'nahian-restaurant-ordering-system' ),
					'<strong>' . absint( $range_start ) . '</strong>',
					'<strong>' . absint( $range_end ) . '</strong>',
					'<strong>' . absint( $total_items ) . '</strong>'
				);
				?>
			</span>
			<span class="nrtok-pag-page-badge">
				<?php
				printf(
					/* translators: 1: current page, 2: total pages */
					esc_html__( 'Page %1$d of %2$d', 'nahian-restaurant-ordering-system' ),
					absint( $paged ),
					absint( $total_pages )
				);
				?>
			</span>
		</div>

		<div class="nrtok-ord-pag-links">
			<!-- First Page -->
			<a href="<?php echo esc_url( add_query_arg( 'paged', 1, $base_pagination_url ) ); ?>" class="nrtok-ord-pag-btn nrtok-pag-icon-only <?php echo ( $paged <= 1 ) ? 'nrtok-ord-pag-disabled' : ''; ?>" title="<?php esc_attr_e( 'First Page', 'nahian-restaurant-ordering-system' ); ?>" aria-label="<?php esc_attr_e( 'First Page', 'nahian-restaurant-ordering-system' ); ?>">
				<?php echo $svg_chev_ll; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>

			<!-- Previous Page -->
			<a href="<?php echo esc_url( add_query_arg( 'paged', max( 1, $paged - 1 ), $base_pagination_url ) ); ?>" class="nrtok-ord-pag-btn nrtok-pag-nav-btn <?php echo ( $paged <= 1 ) ? 'nrtok-ord-pag-disabled' : ''; ?>">
				<?php echo $svg_chev_l; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Prev', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>

			<!-- Numbered Pages with Ellipses -->
			<div class="nrtok-ord-pag-numbers">
				<?php
				$prev_num = 0;
				foreach ( $page_range as $p ) :
					if ( $prev_num > 0 && ( $p - $prev_num ) > 1 ) :
						?>
						<span class="nrtok-ord-pag-ellipsis" aria-hidden="true">&hellip;</span>
						<?php
					endif;
					$prev_num = $p;
					?>
					<a href="<?php echo esc_url( add_query_arg( 'paged', $p, $base_pagination_url ) ); ?>" class="nrtok-ord-pag-btn nrtok-pag-num <?php echo ( $p === $paged ) ? 'current' : ''; ?>" <?php echo ( $p === $paged ) ? 'aria-current="page"' : ''; ?>>
						<?php echo absint( $p ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<!-- Next Page -->
			<a href="<?php echo esc_url( add_query_arg( 'paged', min( $total_pages, $paged + 1 ), $base_pagination_url ) ); ?>" class="nrtok-ord-pag-btn nrtok-pag-nav-btn <?php echo ( $paged >= $total_pages ) ? 'nrtok-ord-pag-disabled' : ''; ?>">
				<span><?php esc_html_e( 'Next', 'nahian-restaurant-ordering-system' ); ?></span>
				<?php echo $svg_chev_r; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>

			<!-- Last Page -->
			<a href="<?php echo esc_url( add_query_arg( 'paged', $total_pages, $base_pagination_url ) ); ?>" class="nrtok-ord-pag-btn nrtok-pag-icon-only <?php echo ( $paged >= $total_pages ) ? 'nrtok-ord-pag-disabled' : ''; ?>" title="<?php esc_attr_e( 'Last Page', 'nahian-restaurant-ordering-system' ); ?>" aria-label="<?php esc_attr_e( 'Last Page', 'nahian-restaurant-ordering-system' ); ?>">
				<?php echo $svg_chev_rr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
	</nav>
<?php endif; ?>

<style>
	/* Pro Toolbar Layout */
	.nrtok-ord-toolbar-pro {
		display: flex; justify-content: space-between; align-items: center;
		margin-bottom: 22px; flex-wrap: wrap; gap: 14px;
	}
	.nrtok-ord-pills-modern {
		display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 0 !important;
	}
	.nrtok-ord-pills-modern .nrtok-pill {
		background: #fff; border: 1.5px solid #e2e8f0; color: #475569; padding: 8px 16px;
		border-radius: 12px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px;
		box-shadow: 0 2px 4px rgba(15, 23, 42, 0.02); transition: all 0.2s ease; text-decoration: none;
	}
	.nrtok-ord-pills-modern .nrtok-pill:hover { border-color: #cbd5e1; background: #f8fafc; color: #0f172a; }
	.nrtok-ord-pills-modern .nrtok-pill.active {
		background: #0f172a; color: #fff; border-color: #0f172a;
		box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
	}
	.nrtok-st-indicator { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
	.nrtok-pill-badge {
		background: rgba(0, 0, 0, 0.08); padding: 1px 7px; border-radius: 10px; font-size: 11px; font-weight: 800;
	}
	.nrtok-pill.active .nrtok-pill-badge { background: rgba(255, 255, 255, 0.2); color: #fff; }

	/* Search Box Pro */
	.nrtok-ord-search-box-pro { width: 300px; max-width: 100%; }
	.nrtok-search-input-wrap { position: relative; display: flex; align-items: center; }
	.nrtok-search-input-wrap svg { position: absolute; left: 14px; pointer-events: none; }
	.nrtok-ord-search-input-pro {
		width: 100%; padding: 10px 36px 10px 42px; border: 1.5px solid #e2e8f0; border-radius: 12px;
		font-size: 13.5px; font-weight: 600; background: #fff; color: #0f172a; box-sizing: border-box;
		transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(15, 23, 42, 0.02);
	}
	.nrtok-ord-search-input-pro:focus {
		outline: none; border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12);
	}
	.nrtok-search-clear-btn {
		position: absolute; right: 10px; width: 22px; height: 22px; border-radius: 50%;
		background: #f1f5f9; color: #64748b; text-decoration: none; font-size: 15px; font-weight: 800;
		display: inline-flex; align-items: center; justify-content: center; line-height: 1;
	}
	.nrtok-search-clear-btn:hover { background: #e2e8f0; color: #0f172a; }

	.nrtok-ord-table-card-pro {
		padding: 0 !important; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 18px;
		box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
	}
	#nrtok-orders-table { margin: 0; border: none; border-radius: 0; box-shadow: none; }

	/* Table Typography & Rows */
	.nrtok-ord-row-pro { transition: background 0.15s ease; border-bottom: 1px solid #f1f5f9; }
	.nrtok-ord-row-pro:hover { background-color: #fafaf9; }
	.nrtok-ord-row-pro:last-child { border-bottom: none; }

	.nrtok-ord-id-link-pro { font-size: 15px; font-weight: 900; color: #0f172a; text-decoration: none; transition: color 0.15s; }
	.nrtok-ord-id-link-pro:hover { color: #f97316; }
	.nrtok-ord-time-pro { font-size: 12px; font-weight: 600; color: #64748b; margin-top: 3px; font-family: monospace; }
	.nrtok-ord-total-pro { font-size: 14.5px; font-weight: 850; color: #0f172a; }

	.nrtok-ord-table-badge-pro {
		background: #f1f5f9; color: #334155; padding: 6px 14px; border-radius: 20px;
		font-size: 12.5px; font-weight: 800; display: inline-block; border: 1px solid #e2e8f0;
	}

	/* Enhanced Status Switcher Buttons */
	.nrtok-ord-fast-status-pro { display: flex; gap: 5px; align-items: center; flex-wrap: wrap; }
	.nrtok-pro-pill {
		padding: 6px 10px; border-radius: 9px; font-size: 11px; font-weight: 800;
		text-decoration: none; color: #475569; background: #f8fafc; border: 1.5px solid #cbd5e1;
		transition: all 0.18s ease; display: inline-flex; align-items: center; gap: 5px;
		text-transform: uppercase; letter-spacing: 0.3px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
	}
	.nrtok-pro-pill:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; transform: translateY(-1px); }

	/* Active / Current Status State Highlights */
	.nrtok-pro-pill.active-state {
		box-shadow: 0 3px 10px rgba(15, 23, 42, 0.12); transform: scale(1.03); font-weight: 900;
	}
	.nrtok-pill-pending-btn.active-state { background: #fef3c7 !important; color: #b45309 !important; border-color: #f59e0b !important; }
	.nrtok-pill-preparing-btn.active-state { background: #eff6ff !important; color: #1d4ed8 !important; border-color: #3b82f6 !important; }
	.nrtok-pill-served-btn.active-state { background: #faf5ff !important; color: #7e22ce !important; border-color: #a855f7 !important; }
	.nrtok-pill-completed-btn.active-state { background: #ecfdf5 !important; color: #047857 !important; border-color: #10b981 !important; }

	/* Action Buttons Group Pro */
	.nrtok-ord-action-group-pro { display: flex; gap: 6px; align-items: center; justify-content: flex-end; }
	.nrtok-ord-act-btn-pro {
		padding: 6px 12px; border-radius: 9px; font-size: 12px; font-weight: 700;
		text-decoration: none; cursor: pointer; border: none; transition: all 0.18s ease;
		display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
	}
	.nrtok-act-view-pro { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
	.nrtok-act-view-pro:hover { background: #dbeafe; }
	.nrtok-act-edit-pro { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
	.nrtok-act-edit-pro:hover { background: #e2e8f0; color: #0f172a; }
	.nrtok-act-kot-pro { background: #0f172a; color: #fff; border: 1px solid #0f172a; }
	.nrtok-act-kot-pro:hover { background: #1e293b; }
	.nrtok-act-bill-pro { background: #f8fafc; color: #0f172a; border: 1.5px solid #cbd5e1; }
	.nrtok-act-bill-pro:hover { background: #f1f5f9; border-color: #94a3b8; }
	.nrtok-act-del-pro { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 6px 9px; }
	.nrtok-act-del-pro:hover { background: #fee2e2; }

	/* Enhanced Pagination Bar Pro */
	.nrtok-ord-pagination-pro {
		display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
		gap: 14px; margin-top: 22px; padding: 14px 20px; background: #ffffff; border-radius: 16px;
		border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
	}
	.nrtok-ord-pag-info {
		display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
		font-size: 13px; color: #64748b; font-weight: 600;
	}
	.nrtok-ord-pag-info strong { color: #0f172a; font-weight: 800; }
	.nrtok-pag-page-badge {
		background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;
		padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800;
	}
	.nrtok-ord-pag-links {
		display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
	}
	.nrtok-ord-pag-numbers {
		display: inline-flex; align-items: center; gap: 5px;
		background: #f8fafc; padding: 4px; border-radius: 12px; border: 1px solid #e2e8f0;
	}
	.nrtok-ord-pag-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 5px;
		min-width: 34px; height: 34px; padding: 0 11px; border-radius: 9px;
		font-size: 13px; font-weight: 750; text-decoration: none;
		background: #ffffff; color: #334155; border: 1px solid #e2e8f0;
		transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1); box-sizing: border-box;
	}
	.nrtok-ord-pag-numbers .nrtok-ord-pag-btn {
		background: transparent; border-color: transparent; min-width: 32px; height: 32px; padding: 0 8px;
	}
	.nrtok-ord-pag-btn:hover {
		background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; transform: translateY(-1px);
	}
	.nrtok-ord-pag-numbers .nrtok-ord-pag-btn:hover {
		background: #ffffff; border-color: #e2e8f0; box-shadow: 0 2px 5px rgba(15, 23, 42, 0.05);
	}
	.nrtok-ord-pag-btn.current,
	.nrtok-ord-pag-numbers .nrtok-ord-pag-btn.current {
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
		color: #ffffff !important; border-color: #f97316 !important;
		box-shadow: 0 4px 12px rgba(249, 115, 22, 0.35); transform: none;
	}
	.nrtok-pag-icon-only { padding: 0 9px; }
	.nrtok-ord-pag-ellipsis {
		padding: 0 6px; color: #94a3b8; font-weight: 800; font-size: 13px; user-select: none;
	}
	.nrtok-ord-pag-disabled {
		opacity: 0.42; pointer-events: none; background: #f8fafc; box-shadow: none;
	}

	.nrtok-ord-empty-cell-pro { text-align: center; padding: 50px 20px; color: #64748b; font-weight: 600; font-size: 14px; }
</style>

<script>
jQuery(document).ready(function($) {
	const restName  = <?php echo wp_json_encode( $s['restaurant_name'] ?? 'NAHIAN' ); ?>;
	const restPhone = <?php echo wp_json_encode( $s['phone'] ?? '' ); ?>;
	const restEmail = <?php echo wp_json_encode( $s['email'] ?? '' ); ?>;
	const currency  = <?php echo wp_json_encode( $s['currency'] ?? '৳' ); ?>;
	const footerTxt = <?php echo wp_json_encode( $s['receipt_footer'] ?? 'Thank you!' ); ?>;
	const ordPrefix = <?php echo wp_json_encode( $s['order_prefix'] ?? 'ORD-' ); ?>;

	// Escape HTML helper to prevent XSS in dynamic print markup
	function escHtml(str) {
		return String(str === null || str === undefined ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	// Confirm Delete Order
	$(document).on('click', '.js-nrtok-delete-order', function(e) {
		if (!confirm(<?php echo wp_json_encode( __( 'Are you sure you want to delete this order permanently?', 'nahian-restaurant-ordering-system' ) ); ?>)) {
			e.preventDefault();
		}
	});

	// 1. Kitchen Order Ticket (KOT) Print
	function printKitchenKOT(order) {
		const items = typeof order.items === 'string' ? JSON.parse(order.items) : (order.items || []);
		let rows = '';
		items.forEach(function(i) {
			const extraStr = (i.extras && i.extras.length)
				? `<br><span style="font-size:12px; font-weight:normal;">  + ${i.extras.map(escHtml).join(', ')}</span>`
				: '';
			rows += `<tr><td style="padding:4px 0;">${escHtml(i.name)}${extraStr}</td><td style="text-align:right; vertical-align:top;">x${parseInt(i.qty || 1, 10)}</td></tr>`;
		});

		const receipt = `
			<div style="text-align:center;">
				<h2 style="margin:0; font-size:20px;">${escHtml(restName)}</h2>
				<p style="margin:2px 0; font-size:12px;">*** KITCHEN TICKET (KOT) ***</p>
				<h1 style="margin:6px 0; border:2px solid #000; padding:4px;">TABLE: ${escHtml(order.table_no)}</h1>
				<p style="margin:2px 0;">Order #${parseInt(order.id, 10)} | ${escHtml(order.created_at)}</p>
			</div>
			<hr style="border-top:1px dashed #000;">
			<table style="width:100%; font-size:14px; font-weight:bold;">${rows}</table>
			<hr style="border-top:1px dashed #000;">
			${order.note ? `<p><strong>NOTE:</strong> ${escHtml(order.note)}</p><hr style="border-top:1px dashed #000;">` : ''}
		`;
		if ($('#nrtok-print-area').length === 0) {
			$('body').append('<div id="nrtok-print-area" style="display:none;"></div>');
		}
		$('#nrtok-print-area').html(receipt);
		window.print();
	}

	// 2. Customer Receipt / Tax Bill Print
	function printCustomerBill(order) {
		const items = typeof order.items === 'string' ? JSON.parse(order.items) : (order.items || []);
		let rows = '';
		items.forEach(function(i) {
			const unitPr = parseFloat(i.unitPrice || i.price || 0);
			const qty    = parseInt(i.qty || 1, 10);
			const line   = (unitPr * qty).toFixed(2);

			rows += `
				<div style="display:flex; justify-content:space-between; margin-bottom:3px;">
					<span>${qty}x ${escHtml(i.name)}</span>
					<span>${escHtml(currency)}${line}</span>
				</div>`;

			if (i.extras && i.extras.length) {
				i.extras.forEach(function(ex) {
					rows += `
						<div style="display:flex; justify-content:space-between; font-size:10.5px; color:#475569; padding-left:8px; margin-bottom:2px;">
							<span>  - ${escHtml(ex)}</span>
						</div>`;
				});
			}
		});

		let contactStr = '';
		if (restPhone && restEmail) contactStr = 'Ph: ' + escHtml(restPhone) + ' | ' + escHtml(restEmail);
		else if (restPhone) contactStr = 'Ph: ' + escHtml(restPhone);
		else if (restEmail) contactStr = escHtml(restEmail);

		const totalAmt     = parseFloat(order.total_amount || 0);
		const totalPayable = totalAmt.toFixed(2);
		const cashInput    = window.prompt(
			<?php echo wp_json_encode( __( 'Enter cash tendered amount (leave default for exact change):', 'nahian-restaurant-ordering-system' ) ); ?>,
			totalPayable
		);
		if (cashInput === null) {
			return;
		}
		const tenderedVal  = Math.max(totalAmt, parseFloat(cashInput) || totalAmt);
		const changeReturn = (tenderedVal - totalAmt).toFixed(2);

		const receipt = `
			<div style="font-family:'Courier New',Courier,monospace; font-size:11px; color:#111; width:280px; margin:0 auto; line-height:1.35;">
				<div style="text-align:center;">
					<h3 style="margin:0 0 2px; font-size:15px; font-weight:900; text-transform:uppercase; letter-spacing:0.5px;">${escHtml(restName)}</h3>
					<div style="font-size:9.5px; color:#334155;">${contactStr}</div>
					<div style="font-size:9.5px; font-weight:800; color:#475569; margin-bottom:4px;">*** TAX INVOICE ***</div>
					<div style="font-size:10px; color:#334155;">Table: <strong>${escHtml(order.table_no)}</strong> | Ord: <strong>${escHtml(ordPrefix)}${parseInt(order.id, 10)}</strong></div>
					<div style="font-size:9px; color:#555;">${escHtml(order.created_at)}</div>
				</div>

				<hr style="border:none; border-top:1px dashed #64748b; margin:8px 0;">

				<div style="display:flex; justify-content:space-between; font-weight:800; font-size:10px; border-bottom:1px solid #000; padding-bottom:2px; margin-bottom:4px;">
					<span>ITEM DESC</span>
					<span>TOTAL</span>
				</div>

				${rows}

				<hr style="border:none; border-top:1px dashed #64748b; margin:8px 0;">

				<div style="display:flex; justify-content:space-between; font-size:10.5px; color:#334155; margin-bottom:3px;">
					<span>Subtotal:</span>
					<span>${escHtml(currency)}${parseFloat(order.subtotal || 0).toFixed(2)}</span>
				</div>
				<div style="display:flex; justify-content:space-between; font-size:10.5px; color:#334155; margin-bottom:3px;">
					<span>VAT/Tax:</span>
					<span>${escHtml(currency)}${parseFloat(order.tax_amount || 0).toFixed(2)}</span>
				</div>

				<div style="display:flex; justify-content:space-between; font-size:13.5px; font-weight:900; margin-top:6px; border-top:1.5px solid #000; padding-top:5px;">
					<span>TOTAL PAYABLE:</span>
					<span>${escHtml(currency)}${totalPayable}</span>
				</div>

				<div style="display:flex; justify-content:space-between; font-size:10.5px; color:#334155; border-top:1px dotted #64748b; padding-top:3px; margin-top:3px;">
					<span>Method:</span>
					<strong>CASH</strong>
				</div>
				<div style="display:flex; justify-content:space-between; font-size:10.5px; color:#334155;">
					<span>Tendered:</span>
					<span>${escHtml(currency)}${tenderedVal.toFixed(2)}</span>
				</div>
				<div style="display:flex; justify-content:space-between; font-size:10.5px; font-weight:700; color:#059669;">
					<span>CHANGE:</span>
					<span>${escHtml(currency)}${changeReturn}</span>
				</div>

				<hr style="border:none; border-top:1px dashed #64748b; margin:8px 0;">

				<div style="text-align:center; font-size:10px; color:#334155; margin-top:8px; font-style:italic;">
					${escHtml(footerTxt)}
				</div>
			</div>
		`;

		if ($('#nrtok-print-area').length === 0) {
			$('body').append('<div id="nrtok-print-area" style="display:none;"></div>');
		}
		$('#nrtok-print-area').html(receipt);
		window.print();
	}

	$(document).on('click', '.nrtok-print-kot', function() { printKitchenKOT($(this).data('order')); });
	$(document).on('click', '.nrtok-print-bill', function() { printCustomerBill($(this).data('order')); });
});
</script>