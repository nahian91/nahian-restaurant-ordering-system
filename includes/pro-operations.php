<?php
/**
 * Nahian POS 10/10 Pro Operations: Counter POS, Table Transfer/Merge, Checkout, KDS & Stock Sync
 *
 * @package NRTOK_POS
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deduct stock when an order is placed and auto-disable item if stock hits 0.
 *
 * @param array<int, array<string, mixed>> $items Order items array.
 * @return void
 */
function nrtok_deduct_order_stock( $items ) {
	if ( ! is_array( $items ) ) {
		return;
	}

	global $wpdb;
	$t_menu = $wpdb->prefix . 'nrtok_menu';

	foreach ( $items as $item ) {
		$item_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
		$qty     = isset( $item['qty'] ) ? max( 1, absint( $item['qty'] ) ) : 1;

		if ( $item_id <= 0 ) {
			continue;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT id, stock_qty FROM %i WHERE id = %d', $t_menu, $item_id )
		);

		if ( $row && null !== $row->stock_qty ) {
			$new_stock = max( 0, (int) $row->stock_qty - $qty );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$t_menu,
				array(
					'stock_qty'    => $new_stock,
					'is_available' => ( $new_stock > 0 ) ? 1 : 0,
				),
				array( 'id' => $item_id ),
				array( '%d', '%d' ),
				array( '%d' )
			);
		}
	}
}

/**
 * AJAX: Create Walk-In / Takeaway / Dine-In Counter POS Order.
 *
 * @return void
 */
function nrtok_ajax_create_counter_order() {
	check_ajax_referer( 'nrtok_admin_nonce', 'nonce' );

	if ( ! nrtok_current_user_can_pos() ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'nahian-restaurant-ordering-system' ) ), 403 );
	}

	global $wpdb;
	$t_orders = $wpdb->prefix . 'nrtok_orders';
	$settings = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();

	$allowed_types = array( 'dine_in', 'takeaway', 'delivery' );
	$raw_type      = isset( $_POST['order_type'] ) ? sanitize_key( wp_unslash( $_POST['order_type'] ) ) : 'dine_in';
	$order_type    = in_array( $raw_type, $allowed_types, true ) ? $raw_type : 'dine_in';

	$table_no       = isset( $_POST['table_no'] ) ? sanitize_text_field( wp_unslash( $_POST['table_no'] ) ) : '';
	$customer_name  = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
	$customer_phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
	$note           = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$discount       = isset( $_POST['discount_amount'] ) ? max( 0.0, (float) wp_unslash( $_POST['discount_amount'] ) ) : 0.0;
	$pay_method     = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Cash';
	$mark_paid      = ! empty( $_POST['mark_paid'] );

	if ( 'dine_in' !== $order_type ) {
		$table_no = ( 'delivery' === $order_type ) ? 'DELIVERY' : 'TAKEAWAY';
	} elseif ( '' === $table_no ) {
		$table_no = '01';
	}

	$raw_items = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$decoded   = is_string( $raw_items ) ? json_decode( $raw_items, true ) : $raw_items;

	if ( ! is_array( $decoded ) || empty( $decoded ) ) {
		wp_send_json_error( array( 'message' => __( 'Cart is empty.', 'nahian-restaurant-ordering-system' ) ) );
	}

	$clean_items = array();
	$subtotal    = 0.0;

	foreach ( $decoded as $it ) {
		$unit_price = isset( $it['unitPrice'] ) ? (float) $it['unitPrice'] : (float) ( $it['price'] ?? 0 );
		$qty        = max( 1, absint( $it['qty'] ?? 1 ) );
		$extras     = isset( $it['extras'] ) && is_array( $it['extras'] ) ? array_map( 'sanitize_text_field', $it['extras'] ) : array();

		$clean_items[] = array(
			'id'        => absint( $it['id'] ?? 0 ),
			'name'      => sanitize_text_field( $it['name'] ?? __( 'Item', 'nahian-restaurant-ordering-system' ) ),
			'unitPrice' => $unit_price,
			'price'     => $unit_price,
			'qty'       => $qty,
			'extras'    => $extras,
		);
		$subtotal     += ( $unit_price * $qty );
	}

	$discount    = min( $subtotal, $discount );
	$taxable     = max( 0.0, $subtotal - $discount );
	$tax_percent = isset( $settings['tax_percent'] ) ? (float) $settings['tax_percent'] : 0.0;
	$tax_amount  = ( $taxable * $tax_percent ) / 100;
	$total_amt   = $taxable + $tax_amount;
	$status      = $mark_paid ? 'completed' : 'pending';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert(
		$t_orders,
		array(
			'order_type'      => $order_type,
			'table_no'        => $table_no,
			'customer_name'   => $customer_name,
			'customer_phone'  => $customer_phone,
			'items'           => wp_json_encode( $clean_items ),
			'subtotal'        => number_format( $subtotal, 2, '.', '' ),
			'discount_amount' => number_format( $discount, 2, '.', '' ),
			'tax_amount'      => number_format( $tax_amount, 2, '.', '' ),
			'total_amount'    => number_format( $total_amt, 2, '.', '' ),
			'payment_method'  => $pay_method,
			'paid_amount'     => $mark_paid ? number_format( $total_amt, 2, '.', '' ) : '0.00',
			'note'            => $note,
			'status'          => $status,
			'created_at'      => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( ! $inserted ) {
		wp_send_json_error( array( 'message' => __( 'Database error creating order.', 'nahian-restaurant-ordering-system' ) ) );
	}

	$new_id = (int) $wpdb->insert_id;
	nrtok_deduct_order_stock( $clean_items );

	if ( function_exists( 'nrtok_clear_order_caches' ) ) {
		nrtok_clear_order_caches();
	}
	wp_cache_delete( 'nrtok_active_orders_count', 'nrtok_pos' );

	wp_send_json_success(
		array(
			'order_id' => $new_id,
			'message'  => sprintf(
				/* translators: %d: new order ID */
				__( 'Order #%d created successfully!', 'nahian-restaurant-ordering-system' ),
				$new_id
			),
		)
	);
}
add_action( 'wp_ajax_nrtok_create_counter_order', 'nrtok_ajax_create_counter_order' );

/**
 * AJAX: Transfer Order to Another Table or Merge Into Target Active Order.
 *
 * @return void
 */
function nrtok_ajax_transfer_or_merge_order() {
	check_ajax_referer( 'nrtok_admin_nonce', 'nonce' );

	if ( ! nrtok_current_user_can_pos() ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'nahian-restaurant-ordering-system' ) ), 403 );
	}

	global $wpdb;
	$t_orders     = $wpdb->prefix . 'nrtok_orders';
	$order_id     = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$target_table = isset( $_POST['target_table'] ) ? sanitize_text_field( wp_unslash( $_POST['target_table'] ) ) : '';
	$mode         = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'transfer';

	if ( $order_id <= 0 || '' === $target_table ) {
		wp_send_json_error( array( 'message' => __( 'Invalid order or target table.', 'nahian-restaurant-ordering-system' ) ) );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$source = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_orders, $order_id ) );
	if ( ! $source ) {
		wp_send_json_error( array( 'message' => __( 'Source order not found.', 'nahian-restaurant-ordering-system' ) ) );
	}

	if ( 'merge' === $mode ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$target = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE table_no = %s AND status != %s AND id != %d ORDER BY id DESC LIMIT 1',
				$t_orders,
				$target_table,
				'completed',
				$order_id
			)
		);

		if ( ! $target ) {
			wp_send_json_error( array( 'message' => __( 'No active order on target table to merge into. Use Transfer instead.', 'nahian-restaurant-ordering-system' ) ) );
		}

		$src_items  = json_decode( $source->items, true );
		$tgt_items  = json_decode( $target->items, true );
		$merged     = array_merge( is_array( $tgt_items ) ? $tgt_items : array(), is_array( $src_items ) ? $src_items : array() );
		$new_sub    = (float) $target->subtotal + (float) $source->subtotal;
		$new_disc   = (float) $target->discount_amount + (float) $source->discount_amount;
		$new_tax    = (float) $target->tax_amount + (float) $source->tax_amount;
		$new_total  = (float) $target->total_amount + (float) $source->total_amount;
		$merged_not = trim( $target->note . ( $source->note ? ' | ' . $source->note : '' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$t_orders,
			array(
				'items'           => wp_json_encode( $merged ),
				'subtotal'        => number_format( $new_sub, 2, '.', '' ),
				'discount_amount' => number_format( $new_disc, 2, '.', '' ),
				'tax_amount'      => number_format( $new_tax, 2, '.', '' ),
				'total_amount'    => number_format( $new_total, 2, '.', '' ),
				'note'            => $merged_not,
			),
			array( 'id' => (int) $target->id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $t_orders, array( 'id' => $order_id ), array( '%d' ) );
		$msg = sprintf(
			/* translators: 1: source order ID, 2: target order ID, 3: target table */
			__( 'Merged Order #%1$d into Order #%2$d (Table %3$s)!', 'nahian-restaurant-ordering-system' ),
			$order_id,
			(int) $target->id,
			$target_table
		);
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$t_orders,
			array(
				'order_type' => 'dine_in',
				'table_no'   => $target_table,
			),
			array( 'id' => $order_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		$msg = sprintf(
			/* translators: 1: order ID, 2: target table */
			__( 'Order #%1$d transferred to Table %2$s!', 'nahian-restaurant-ordering-system' ),
			$order_id,
			$target_table
		);
	}

	if ( function_exists( 'nrtok_clear_order_caches' ) ) {
		nrtok_clear_order_caches();
	}
	wp_cache_delete( 'nrtok_active_orders_count', 'nrtok_pos' );

	wp_send_json_success( array( 'message' => $msg ) );
}
add_action( 'wp_ajax_nrtok_transfer_or_merge_order', 'nrtok_ajax_transfer_or_merge_order' );

/**
 * AJAX: Settle Bill with Payment Method, Discount & Paid Status.
 *
 * @return void
 */
function nrtok_ajax_settle_order_bill() {
	check_ajax_referer( 'nrtok_admin_nonce', 'nonce' );

	if ( ! nrtok_current_user_can_pos() ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'nahian-restaurant-ordering-system' ) ), 403 );
	}

	global $wpdb;
	$t_orders   = $wpdb->prefix . 'nrtok_orders';
	$settings   = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
	$order_id   = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$pay_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Cash';
	$discount   = isset( $_POST['discount_amount'] ) ? max( 0.0, (float) wp_unslash( $_POST['discount_amount'] ) ) : 0.0;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$order = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_orders, $order_id ) );
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => __( 'Order not found.', 'nahian-restaurant-ordering-system' ) ) );
	}

	$subtotal    = (float) $order->subtotal;
	$discount    = min( $subtotal, $discount );
	$taxable     = max( 0.0, $subtotal - $discount );
	$tax_percent = isset( $settings['tax_percent'] ) ? (float) $settings['tax_percent'] : 0.0;
	$tax_amount  = ( $taxable * $tax_percent ) / 100;
	$total_amt   = $taxable + $tax_amount;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->update(
		$t_orders,
		array(
			'discount_amount' => number_format( $discount, 2, '.', '' ),
			'tax_amount'      => number_format( $tax_amount, 2, '.', '' ),
			'total_amount'    => number_format( $total_amt, 2, '.', '' ),
			'payment_method'  => $pay_method,
			'paid_amount'     => number_format( $total_amt, 2, '.', '' ),
			'status'          => 'completed',
		),
		array( 'id' => $order_id ),
		array( '%s', '%s', '%s', '%s', '%s', '%s' ),
		array( '%d' )
	);

	if ( function_exists( 'nrtok_clear_order_caches' ) ) {
		nrtok_clear_order_caches();
	}
	wp_cache_delete( 'nrtok_active_orders_count', 'nrtok_pos' );

	wp_send_json_success(
		array(
			'message' => sprintf(
				/* translators: 1: order ID, 2: payment method */
				__( 'Order #%1$d settled via %2$s!', 'nahian-restaurant-ordering-system' ),
				$order_id,
				$pay_method
			),
		)
	);
}
add_action( 'wp_ajax_nrtok_settle_order_bill', 'nrtok_ajax_settle_order_bill' );

/**
 * AJAX: Live Kitchen Display System (KDS) Active Orders Feed & Bump Action.
 *
 * @return void
 */
function nrtok_ajax_kds_feed() {
	check_ajax_referer( 'nrtok_admin_nonce', 'nonce' );

	if ( ! nrtok_current_user_can_pos() ) {
		wp_send_json_error( array(), 403 );
	}

	global $wpdb;
	$t_orders = $wpdb->prefix . 'nrtok_orders';

	// Optional bump action before returning updated queue.
	if ( ! empty( $_POST['bump_id'] ) && ! empty( $_POST['next_status'] ) ) {
		$bump_id     = absint( $_POST['bump_id'] );
		$next_status = sanitize_key( wp_unslash( $_POST['next_status'] ) );
		if ( in_array( $next_status, array( 'preparing', 'served', 'completed' ), true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$t_orders,
				array( 'status' => $next_status ),
				array( 'id' => $bump_id ),
				array( '%s' ),
				array( '%d' )
			);
			if ( function_exists( 'nrtok_clear_order_caches' ) ) {
				nrtok_clear_order_caches();
			}
			wp_cache_delete( 'nrtok_active_orders_count', 'nrtok_pos' );
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT id, order_type, table_no, customer_name, customer_phone, items, note, status, created_at FROM %i WHERE status IN (%s, %s, %s) ORDER BY id ASC',
			$t_orders,
			'pending',
			'preparing',
			'served'
		)
	);

	$now_ts = current_datetime()->getTimestamp();
	$cards  = array();

	if ( is_array( $rows ) ) {
		foreach ( $rows as $r ) {
			$created_ts = strtotime( $r->created_at );
			$elapsed    = max( 0, (int) floor( ( $now_ts - $created_ts ) / 60 ) );
			$decoded    = json_decode( $r->items, true );

			$cards[] = array(
				'id'             => absint( $r->id ),
				'order_type'     => $r->order_type,
				'table_no'       => $r->table_no,
				'customer_name'  => isset( $r->customer_name ) ? $r->customer_name : '',
				'customer_phone' => isset( $r->customer_phone ) ? $r->customer_phone : '',
				'items'          => is_array( $decoded ) ? $decoded : array(),
				'note'           => $r->note,
				'status'         => $r->status,
				'time_label'     => mysql2date( 'h:i A', $r->created_at ),
				'elapsed'        => $elapsed,
			);
		}
	}

	wp_send_json_success( $cards );
}
add_action( 'wp_ajax_nrtok_kds_feed', 'nrtok_ajax_kds_feed' );