<?php
/**
 * Nahian POS Order Status & Full Edit Handler
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle Single Order Status Quick-Update.
 *
 * @param string $orders_table Full orders table name.
 */
function nrtok_handle_order_status_update( $orders_table ) {
	if ( ! nrtok_current_user_can_pos() ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;
	$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
	if ( $order_id <= 0 ) {
		return;
	}

	check_admin_referer( 'nrtok_order_status_' . $order_id );

	$svg_check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
	$allowed   = array( 'pending', 'preparing', 'served', 'completed' );
	$new_st    = isset( $_GET['set_status'] ) ? sanitize_key( wp_unslash( $_GET['set_status'] ) ) : '';

	if ( in_array( $new_st, $allowed, true ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$orders_table,
			array( 'status' => $new_st ),
			array( 'id' => $order_id ),
			array( '%s' ),
			array( '%d' )
		);
		echo '<div class="nrtok-alert nrtok-ord-alert-flex">' . $svg_check . '<span>' . sprintf( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			/* translators: 1: order ID, 2: status label */
			esc_html__( 'Order #%1$d status updated to %2$s!', 'nahian-restaurant-ordering-system' ),
			absint( $order_id ),
			esc_html( strtoupper( $new_st ) )
		) . '</span></div>';
	}
}

/**
 * Handle Full Order Edit Form Submission (Quantities + New Items + Removal).
 *
 * @param string $orders_table Full orders table name.
 * @param array  $s            Plugin settings array.
 */
function nrtok_handle_full_order_edit( $orders_table, $s ) {
	if ( ! nrtok_current_user_can_pos() ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;
	$svg_check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';

	if ( ! isset( $_POST['nrtok_save_order_edit'] ) || empty( $_POST['edit_order_id'] ) ) {
		return;
	}

	$order_id = absint( $_POST['edit_order_id'] );
	check_admin_referer( 'nrtok_edit_order_' . $order_id );

	$allowed_statuses = array( 'pending', 'preparing', 'served', 'completed' );
	$raw_status       = isset( $_POST['edit_status'] ) ? sanitize_key( wp_unslash( $_POST['edit_status'] ) ) : 'pending';
	$status           = in_array( $raw_status, $allowed_statuses, true ) ? $raw_status : 'pending';
	$table_no         = isset( $_POST['edit_table_no'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_table_no'] ) ) : '1';
	$note             = isset( $_POST['edit_note'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_note'] ) ) : '';

	// Fetch existing order securely.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$existing = $wpdb->get_row(
		$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $orders_table, $order_id )
	);
	if ( ! $existing ) {
		echo '<div class="nrtok-alert" style="background:#fef2f2; border-color:#fecaca; color:#b91c1c;">' . esc_html__( 'Order not found!', 'nahian-restaurant-ordering-system' ) . '</div>';
		return;
	}

	$items_raw = json_decode( $existing->items, true );
	if ( ! is_array( $items_raw ) ) {
		$items_raw = array();
	}

	$new_items    = array();
	$subtotal     = 0.0;
	$remove_flags = isset( $_POST['remove_item'] ) && is_array( $_POST['remove_item'] ) ? wp_unslash( $_POST['remove_item'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$posted_qtys  = isset( $_POST['item_qty'] ) && is_array( $_POST['item_qty'] ) ? wp_unslash( $_POST['item_qty'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	// 1. Process Existing Items (Quantities & Removals via Delete Icon Flag).
	foreach ( $items_raw as $index => $item ) {
		if ( isset( $remove_flags[ $index ] ) && '1' === sanitize_text_field( $remove_flags[ $index ] ) ) {
			continue;
		}

		$qty = isset( $posted_qtys[ $index ] ) ? max( 1, absint( $posted_qtys[ $index ] ) ) : max( 1, absint( $item['qty'] ?? 1 ) );

		$unit = 0.0;
		if ( isset( $item['unitPrice'] ) ) {
			$unit = (float) $item['unitPrice'];
		} elseif ( isset( $item['price'] ) ) {
			$unit = (float) $item['price'];
		}

		$item['qty'] = $qty;
		$new_items[] = $item;
		$subtotal   += ( $unit * $qty );
	}

	// 2. Process Newly Added Items Loaded from Menu Table.
	if ( isset( $_POST['new_item_id'] ) && is_array( $_POST['new_item_id'] ) ) {
		$t_menu        = $wpdb->prefix . 'nrtok_menu';
		$new_item_ids  = wp_unslash( $_POST['new_item_id'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$new_item_qtys = isset( $_POST['new_item_qty'] ) && is_array( $_POST['new_item_qty'] ) ? wp_unslash( $_POST['new_item_qty'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		foreach ( $new_item_ids as $key => $raw_item_id ) {
			$item_id = absint( $raw_item_id );
			if ( $item_id <= 0 ) {
				continue;
			}

			$new_qty = max( 1, absint( $new_item_qtys[ $key ] ?? 1 ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$db_item = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_menu, $item_id )
			);

			if ( $db_item ) {
				$item_name  = $db_item->name ?? __( 'Dish', 'nahian-restaurant-ordering-system' );
				$unit_price = (float) ( $db_item->price ?? 0 );

				$new_items[] = array(
					'id'        => absint( $db_item->id ),
					'name'      => sanitize_text_field( $item_name ),
					'unitPrice' => $unit_price,
					'price'     => $unit_price,
					'qty'       => $new_qty,
					'extras'    => array(),
				);
				$subtotal   += ( $unit_price * $new_qty );
			}
		}
	}

	if ( empty( $new_items ) ) {
		echo '<div class="nrtok-alert" style="background:#fef2f2; border-color:#fecaca; color:#b91c1c;">' . esc_html__( 'An order must contain at least one dish. Use Delete Order if you wish to remove the entire order.', 'nahian-restaurant-ordering-system' ) . '</div>';
		return;
	}

	// Calculate Tax & Grand Total.
	$tax_percent = (float) ( $s['tax_percent'] ?? 0 );
	$tax_amount  = ( $subtotal * $tax_percent ) / 100;
	$total_amt   = $subtotal + $tax_amount;

	$update_data = array(
		'table_no'     => $table_no,
		'status'       => $status,
		'note'         => $note,
		'items'        => wp_json_encode( $new_items ),
		'subtotal'     => number_format( $subtotal, 2, '.', '' ),
		'tax_amount'   => number_format( $tax_amount, 2, '.', '' ),
		'total_amount' => number_format( $total_amt, 2, '.', '' ),
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->update(
		$orders_table,
		$update_data,
		array( 'id' => $order_id ),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
		array( '%d' )
	);

	echo '<div class="nrtok-alert nrtok-ord-alert-flex">' . $svg_check . '<span>' . sprintf( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		/* translators: %d: order ID */
		esc_html__( 'Order #%d updated successfully!', 'nahian-restaurant-ordering-system' ),
		absint( $order_id )
	) . '</span></div>';
}

/**
 * Render Full Edit Order Form UI with Add/Remove Item Support & Live Totals Preview.
 *
 * @param object $view_order Order database row object.
 * @param array  $s          Plugin settings array.
 */
function nrtok_render_order_edit_form( $view_order, $s ) {
	if ( ! nrtok_current_user_can_pos() ) {
		return;
	}

	global $wpdb;
	$cur         = $s['currency'] ?? '৳';
	$tax_percent = (float) ( $s['tax_percent'] ?? 0 );
	$ord_id      = absint( $view_order->id );
	$v_items     = json_decode( $view_order->items, true );
	if ( ! is_array( $v_items ) ) {
		$v_items = array();
	}

	$t_tables = $wpdb->prefix . 'nrtok_tables';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$all_tables = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_tables ) ) === $t_tables )
		? ( $wpdb->get_results( $wpdb->prepare( 'SELECT table_name FROM %i ORDER BY id ASC', $t_tables ) ) ?: array() ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		: array();

	// Fetch active restaurant menu items from nrtok_menu table.
	$t_menu = $wpdb->prefix . 'nrtok_menu';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$menu_items = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_menu ) ) === $t_menu )
		? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY item_order ASC, id DESC', $t_menu ) ) ?: array() ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		: array();

	$cancel_url = add_query_arg( 'view_order', $ord_id, admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=orders' ) );

	// Reusable SVG Icons.
	$svg_save     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
	$svg_back     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>';
	$svg_plus     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
	$svg_trash    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
	$svg_table    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18"/><path d="M5 7v13"/><path d="M19 7v13"/><path d="M3 11h18"/></svg>';
	$svg_pulse    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>';
	$svg_note     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>';
	$svg_utensils = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>';
	?>
	<div class="nrtok-card nrtok-full-card">
		<div class="nrtok-ord-view-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; flex-wrap:wrap; gap:12px;">
			<div>
				<div class="nrtok-ord-view-meta" style="font-size:11px; font-weight:800; color:#f97316; letter-spacing:0.8px;"><?php esc_html_e( 'EDITING ORDER RECORD', 'nahian-restaurant-ordering-system' ); ?></div>
				<h2 class="nrtok-ord-view-title" style="margin:4px 0 0; font-size:21px; font-weight:850; color:#0f172a;">
					<?php
					printf(
						/* translators: %d: order ID */
						esc_html__( 'Modify Order #%d', 'nahian-restaurant-ordering-system' ),
						absint( $ord_id )
					);
					?>
				</h2>
			</div>
			<div>
				<a href="<?php echo esc_url( $cancel_url ); ?>" class="nrtok-btn-secondary" style="display:inline-flex; align-items:center; gap:6px;">
					<?php echo $svg_back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Cancel & Return', 'nahian-restaurant-ordering-system' ); ?></span>
				</a>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( $cancel_url ); ?>">
			<?php wp_nonce_field( 'nrtok_edit_order_' . $ord_id ); ?>
			<input type="hidden" name="edit_order_id" value="<?php echo absint( $ord_id ); ?>">

			<div class="nrtok-ord-edit-grid">
				<div class="nrtok-form-group" style="margin:0;">
					<label class="nrtok-label"><?php esc_html_e( 'Table Number', 'nahian-restaurant-ordering-system' ); ?></label>
					<div class="nrtok-input-wrap">
						<span class="nrtok-input-prefix"><?php echo $svg_table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<select name="edit_table_no" class="nrtok-input has-prefix" required>
							<?php if ( ! empty( $all_tables ) ) : ?>
								<?php foreach ( $all_tables as $tbl ) : ?>
									<option value="<?php echo esc_attr( $tbl->table_name ); ?>" <?php selected( $view_order->table_no, $tbl->table_name ); ?>>
										<?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?> <?php echo esc_html( $tbl->table_name ); ?>
									</option>
								<?php endforeach; ?>
							<?php else : ?>
								<option value="<?php echo esc_attr( $view_order->table_no ); ?>"><?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?> <?php echo esc_html( $view_order->table_no ); ?></option>
							<?php endif; ?>
						</select>
					</div>
				</div>

				<div class="nrtok-form-group" style="margin:0;">
					<label class="nrtok-label"><?php esc_html_e( 'Order Status', 'nahian-restaurant-ordering-system' ); ?></label>
					<div class="nrtok-input-wrap">
						<span class="nrtok-input-prefix"><?php echo $svg_pulse; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<select name="edit_status" class="nrtok-input has-prefix" required>
							<option value="pending" <?php selected( $view_order->status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'nahian-restaurant-ordering-system' ); ?></option>
							<option value="preparing" <?php selected( $view_order->status, 'preparing' ); ?>><?php esc_html_e( 'Preparing', 'nahian-restaurant-ordering-system' ); ?></option>
							<option value="served" <?php selected( $view_order->status, 'served' ); ?>><?php esc_html_e( 'Served', 'nahian-restaurant-ordering-system' ); ?></option>
							<option value="completed" <?php selected( $view_order->status, 'completed' ); ?>><?php esc_html_e( 'Paid / Completed', 'nahian-restaurant-ordering-system' ); ?></option>
						</select>
					</div>
				</div>

				<div class="nrtok-form-group" style="margin:0;">
					<label class="nrtok-label"><?php esc_html_e( 'Kitchen Note', 'nahian-restaurant-ordering-system' ); ?></label>
					<div class="nrtok-input-wrap">
						<span class="nrtok-input-prefix"><?php echo $svg_note; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<input type="text" name="edit_note" class="nrtok-input has-prefix" value="<?php echo esc_attr( $view_order->note ); ?>" placeholder="<?php esc_attr_e( 'e.g. Less spicy', 'nahian-restaurant-ordering-system' ); ?>">
					</div>
				</div>
			</div>

			<h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:25px 0 10px;"><?php esc_html_e( 'Existing Order Items', 'nahian-restaurant-ordering-system' ); ?></h3>
			<div style="overflow-x:auto; border:1.5px solid #e2e8f0; border-radius:12px;">
				<table class="nrtok-table nrtok-full-table" style="box-shadow:none; border:none; border-radius:0;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Dish / Item Name', 'nahian-restaurant-ordering-system' ); ?></th>
							<th><?php esc_html_e( 'Selected Add-ons', 'nahian-restaurant-ordering-system' ); ?></th>
							<th width="130"><?php esc_html_e( 'Unit Price', 'nahian-restaurant-ordering-system' ); ?></th>
							<th width="120"><?php esc_html_e( 'Quantity', 'nahian-restaurant-ordering-system' ); ?></th>
							<th width="130" style="text-align:right;"><?php esc_html_e( 'Subtotal', 'nahian-restaurant-ordering-system' ); ?></th>
							<th width="80" style="text-align:center;"><?php esc_html_e( 'Action', 'nahian-restaurant-ordering-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $v_items as $idx => $vi ) :
							$u_price = (float) ( $vi['unitPrice'] ?? $vi['price'] ?? 0 );
							$qty     = absint( $vi['qty'] ?? 1 );
							$l_tot   = $u_price * $qty;
							?>
						<tr class="nrtok-existing-item-row" data-unit-price="<?php echo esc_attr( $u_price ); ?>">
							<td><strong><?php echo esc_html( $vi['name'] ?? '' ); ?></strong></td>
							<td>
								<?php if ( ! empty( $vi['extras'] ) && is_array( $vi['extras'] ) ) : ?>
									<span style="color:#f97316; font-size:12px; font-weight:700;">+ <?php echo esc_html( implode( ', ', $vi['extras'] ) ); ?></span>
								<?php else : ?>
									<span style="color:#94a3b8;"><?php esc_html_e( 'None', 'nahian-restaurant-ordering-system' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $cur . number_format( $u_price, 2 ) ); ?></td>
							<td>
								<div class="nrtok-input-wrap" style="width: 82px;">
									<input type="number" name="item_qty[<?php echo absint( $idx ); ?>]" value="<?php echo absint( $qty ); ?>" min="1" max="99" class="nrtok-input nrtok-item-qty-input" style="padding: 7px 10px; min-height: 36px; font-weight: 800; text-align: center;">
								</div>
							</td>
							<td style="text-align:right;"><strong class="nrtok-row-line-total"><?php echo esc_html( $cur . number_format( $l_tot, 2 ) ); ?></strong></td>
							<td style="text-align:center;">
								<input type="hidden" name="remove_item[<?php echo absint( $idx ); ?>]" class="nrtok-remove-flag" value="0">
								<button type="button" class="nrtok-delete-item-btn" title="<?php esc_attr_e( 'Remove / Restore item', 'nahian-restaurant-ordering-system' ); ?>" style="background:#fef2f2; color:#dc2626; border:1px solid #fee2e2; padding:7px; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition:0.2s;">
									<?php echo $svg_trash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Add New Items Section -->
			<h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:30px 0 10px;"><?php esc_html_e( 'Add New Dishes from Menu', 'nahian-restaurant-ordering-system' ); ?></h3>
			<div id="nrtok-new-items-container" style="display:flex; flex-direction:column; gap:12px; margin-bottom:15px;"></div>

			<button type="button" id="nrtok-add-row-btn" class="nrtok-btn-secondary" style="display:inline-flex; align-items:center; gap:6px; font-weight:700;">
				<?php echo $svg_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Another Dish', 'nahian-restaurant-ordering-system' ); ?></span>
			</button>

			<!-- Live Recalculated Bill Preview -->
			<div class="nrtok-edit-live-summary">
				<div class="nrtok-edit-sum-row">
					<span><?php esc_html_e( 'Updated Subtotal:', 'nahian-restaurant-ordering-system' ); ?></span>
					<strong id="nrtok-live-subtotal"><?php echo esc_html( $cur . number_format( (float) $view_order->subtotal, 2 ) ); ?></strong>
				</div>
				<div class="nrtok-edit-sum-row">
					<span><?php echo esc_html( sprintf( __( 'VAT / Tax (%s%%):', 'nahian-restaurant-ordering-system' ), $tax_percent ) ); ?></span>
					<strong id="nrtok-live-tax"><?php echo esc_html( $cur . number_format( (float) $view_order->tax_amount, 2 ) ); ?></strong>
				</div>
				<div class="nrtok-edit-sum-grand">
					<span><?php esc_html_e( 'Updated Grand Total:', 'nahian-restaurant-ordering-system' ); ?></span>
					<strong id="nrtok-live-grand"><?php echo esc_html( $cur . number_format( (float) $view_order->total_amount, 2 ) ); ?></strong>
				</div>
			</div>

			<div style="margin-top:24px; padding-top:20px; border-top:1.5px solid #e2e8f0; display:flex; gap:12px; flex-wrap:wrap;">
				<button type="submit" name="nrtok_save_order_edit" class="nrtok-btn" style="display:inline-flex; align-items:center; gap:7px; padding:12px 24px; font-size:15px;">
					<?php echo $svg_save; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Save Order Changes', 'nahian-restaurant-ordering-system' ); ?></span>
				</button>
				<a href="<?php echo esc_url( $cancel_url ); ?>" class="nrtok-btn-secondary" style="padding:12px 24px; font-size:15px;"><?php esc_html_e( 'Cancel', 'nahian-restaurant-ordering-system' ); ?></a>
			</div>
		</form>
	</div>

	<style>
		.nrtok-ord-edit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 22px; }
		.nrtok-label {
			display: flex; align-items: center; justify-content: space-between;
			font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; letter-spacing: 0.2px;
		}

		/* Modern Elevated Input Containers */
		.nrtok-input-wrap {
			position: relative; display: flex; align-items: center; width: 100%;
			background: #ffffff; border-radius: 12px;
			border: 1.5px solid #e2e8f0;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
			transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
		}
		.nrtok-input-wrap:hover {
			border-color: #cbd5e1;
			box-shadow: 0 3px 6px rgba(15, 23, 42, 0.04);
		}
		.nrtok-input-wrap:focus-within {
			border-color: #f97316;
			background-color: #fff;
			box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12), 0 2px 4px rgba(0, 0, 0, 0.04);
		}

		.nrtok-input-prefix {
			position: absolute; left: 14px; font-weight: 700; color: #94a3b8; font-size: 14px;
			pointer-events: none; display: flex; align-items: center; justify-content: center;
			transition: color 0.2s ease;
		}
		.nrtok-input-wrap:focus-within .nrtok-input-prefix {
			color: #f97316;
		}

		.nrtok-input {
			width: 100% !important; max-width: 100% !important;
			padding: 12px 16px; border: none !important; border-radius: 12px;
			font-size: 14px; font-weight: 500; color: #0f172a; background: transparent !important;
			box-shadow: none !important; outline: none !important; box-sizing: border-box;
			min-height: 44px;
		}
		.nrtok-input.has-prefix { padding-left: 44px; }
		.nrtok-input::placeholder { color: #94a3b8; opacity: 1; font-weight: 400; }

		/* Full-width Select Override for WP Admin */
		select.nrtok-input {
			display: block; width: 100% !important; max-width: 100% !important;
			height: auto !important; line-height: 1.5 !important;
			appearance: none; -webkit-appearance: none; padding-right: 40px; cursor: pointer;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
			background-repeat: no-repeat !important; background-position: right 14px center !important; background-size: 16px !important;
		}

		.nrtok-existing-item-row.row-removed {
			opacity: 0.4;
			background: #fef2f2;
			text-decoration: line-through;
		}
		.nrtok-delete-item-btn:hover {
			background: #fee2e2 !important;
			transform: scale(1.05);
		}
		.nrtok-new-item-row {
			display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
			background: #f8fafc; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 14px;
		}
		.nrtok-remove-new-row {
			background: #fef2f2; color: #dc2626; border: 1.5px solid #fee2e2;
			padding: 0 14px; height: 44px; border-radius: 11px; font-weight: 700; cursor: pointer;
			display: inline-flex; align-items: center; gap: 5px; transition: 0.2s;
		}
		.nrtok-remove-new-row:hover {
			background: #fee2e2;
		}
		.nrtok-edit-live-summary {
			max-width: 340px; margin-left: auto; margin-top: 22px; background: #f8fafc;
			border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 16px 20px;
		}
		.nrtok-edit-sum-row {
			display: flex; justify-content: space-between; font-size: 13.5px; color: #475569; margin-bottom: 8px;
		}
		.nrtok-edit-sum-grand {
			display: flex; justify-content: space-between; font-size: 16px; color: #0f172a;
			padding-top: 10px; border-top: 1.5px dashed #cbd5e1; margin-top: 6px;
		}
		.nrtok-edit-sum-grand strong { color: #f97316; font-size: 18px; }
	</style>

	<script>
	jQuery(document).ready(function($) {
		const dishIcon   = <?php echo wp_json_encode( $svg_utensils ); ?>;
		const trashIcon  = <?php echo wp_json_encode( $svg_trash ); ?>;
		const currency   = <?php echo wp_json_encode( $cur ); ?>;
		const taxPercent = parseFloat(<?php echo wp_json_encode( $tax_percent ); ?>) || 0;

		let menuItemsOptions = '<option value="" data-price="0"><?php echo esc_js( __( '-- Select Dish from Menu --', 'nahian-restaurant-ordering-system' ) ); ?></option>';
		<?php
		foreach ( $menu_items as $mi ) :
			$m_name  = $mi->name ?? __( 'Item', 'nahian-restaurant-ordering-system' );
			$m_price = (float) ( $mi->price ?? 0 );
			?>
			menuItemsOptions += '<option value="<?php echo absint( $mi->id ); ?>" data-price="<?php echo esc_attr( $m_price ); ?>"><?php echo esc_js( $m_name ); ?> (<?php echo esc_js( $cur . number_format( $m_price, 2 ) ); ?>)</option>';
		<?php endforeach; ?>

		function recalcLiveTotals() {
			let subtotal = 0;

			$('.nrtok-existing-item-row').each(function() {
				const $row = $(this);
				const unit = parseFloat($row.data('unit-price')) || 0;
				const qty  = Math.max(1, parseInt($row.find('.nrtok-item-qty-input').val(), 10) || 1);
				const line = unit * qty;
				$row.find('.nrtok-row-line-total').text(currency + line.toFixed(2));

				if ($row.find('.nrtok-remove-flag').val() !== '1') {
					subtotal += line;
				}
			});

			$('.nrtok-new-item-row').each(function() {
				const $row  = $(this);
				const price = parseFloat($row.find('select option:selected').data('price')) || 0;
				const qty   = Math.max(1, parseInt($row.find('input[type="number"]').val(), 10) || 1);
				subtotal += price * qty;
			});

			const tax   = (subtotal * taxPercent) / 100;
			const grand = subtotal + tax;

			$('#nrtok-live-subtotal').text(currency + subtotal.toFixed(2));
			$('#nrtok-live-tax').text(currency + tax.toFixed(2));
			$('#nrtok-live-grand').text(currency + grand.toFixed(2));
		}

		// Toggle Remove Row visually when delete icon is clicked
		$(document).on('click', '.nrtok-delete-item-btn', function() {
			const row = $(this).closest('.nrtok-existing-item-row');
			const flagInput = row.find('.nrtok-remove-flag');

			if (flagInput.val() === '0') {
				flagInput.val('1');
				row.addClass('row-removed');
				row.find('.nrtok-item-qty-input').prop('readonly', true);
				$(this).css('background', '#dc2626').css('color', '#fff');
			} else {
				flagInput.val('0');
				row.removeClass('row-removed');
				row.find('.nrtok-item-qty-input').prop('readonly', false);
				$(this).css('background', '#fef2f2').css('color', '#dc2626');
			}
			recalcLiveTotals();
		});

		$(document).on('input change', '.nrtok-item-qty-input, .nrtok-new-item-row select, .nrtok-new-item-row input', recalcLiveTotals);

		// Add new item row dynamically loaded from menu table options
		$('#nrtok-add-row-btn').on('click', function() {
			const rowHtml = `
				<div class="nrtok-new-item-row">
					<div class="nrtok-input-wrap" style="flex: 2; min-width: 220px;">
						<span class="nrtok-input-prefix">${dishIcon}</span>
						<select name="new_item_id[]" class="nrtok-input has-prefix" required>
							${menuItemsOptions}
						</select>
					</div>
					<div class="nrtok-input-wrap" style="width: 100px;">
						<input type="number" name="new_item_qty[]" value="1" min="1" max="99" class="nrtok-input" style="text-align: center; font-weight: 800;" placeholder="Qty">
					</div>
					<button type="button" class="nrtok-remove-new-row">
						${trashIcon}
						<span><?php echo esc_js( __( 'Remove', 'nahian-restaurant-ordering-system' ) ); ?></span>
					</button>
				</div>
			`;
			$('#nrtok-new-items-container').append(rowHtml);
		});

		$(document).on('click', '.nrtok-remove-new-row', function() {
			$(this).closest('.nrtok-new-item-row').remove();
			recalcLiveTotals();
		});
	});
	</script>
	<?php
}