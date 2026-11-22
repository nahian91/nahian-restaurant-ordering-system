<?php
/**
 * Nahian POS AJAX Endpoints & Handlers
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_nrtok_place_order', 'nrtok_ajax_place_order' );
add_action( 'wp_ajax_nopriv_nrtok_place_order', 'nrtok_ajax_place_order' );
/**
 * 1. Place Order (Customer Tablet - Supports Multiple Rounds per Table).
 */
function nrtok_ajax_place_order() {
	global $wpdb;

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$table_no = sanitize_text_field( wp_unslash( $_POST['table_no'] ?? '' ) );
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$raw_items = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();

	if ( empty( $table_no ) || empty( $raw_items ) || ! is_array( $raw_items ) ) {
		wp_send_json_error( __( 'Invalid order data.', 'nahian-restaurant-ordering-system' ) );
	}

	$clean_items = array();
	foreach ( $raw_items as $it ) {
		if ( ! is_array( $it ) ) {
			continue;
		}
		$extras = array();
		if ( ! empty( $it['extras'] ) && is_array( $it['extras'] ) ) {
			foreach ( $it['extras'] as $ex ) {
				$extras[] = sanitize_text_field( $ex );
			}
		}
		$clean_items[] = array(
			'id'        => absint( $it['id'] ?? 0 ),
			'name'      => sanitize_text_field( $it['name'] ?? __( 'Item', 'nahian-restaurant-ordering-system' ) ),
			'basePrice' => (float) ( $it['basePrice'] ?? $it['unitPrice'] ?? 0 ),
			'unitPrice' => (float) ( $it['unitPrice'] ?? $it['price'] ?? 0 ),
			'extras'    => $extras,
			'qty'       => max( 1, absint( $it['qty'] ?? 1 ) ),
		);
	}

	if ( empty( $clean_items ) ) {
		wp_send_json_error( __( 'No valid items in order.', 'nahian-restaurant-ordering-system' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$subtotal = (float) ( $_POST['subtotal'] ?? 0 );
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$tax_amount = (float) ( $_POST['tax'] ?? 0 );
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$total_amount = (float) ( $_POST['total'] ?? 0 );
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$note = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

	$t_orders = $wpdb->prefix . 'nrtok_orders';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert(
		$t_orders,
		array(
			'table_no'     => $table_no,
			'items'        => wp_json_encode( $clean_items ),
			'subtotal'     => $subtotal,
			'tax_amount'   => $tax_amount,
			'total_amount' => $total_amount,
			'note'         => $note,
			'status'       => 'pending',
			'is_printed'   => 0,
		),
		array( '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%d' )
	);

	if ( false === $inserted ) {
		wp_send_json_error( __( 'Failed to save order.', 'nahian-restaurant-ordering-system' ) );
	}

	wp_send_json_success( array( 'id' => absint( $wpdb->insert_id ) ) );
}

add_action( 'wp_ajax_nrtok_fetch_unprinted', 'nrtok_ajax_fetch_unprinted' );
/**
 * 2. Fetch Unprinted Order (Kitchen Auto-Print - Prints Only New Round Items).
 */
function nrtok_ajax_fetch_unprinted() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Unauthorized', 'nahian-restaurant-ordering-system' ), 403 );
	}

	global $wpdb;
	$t_orders = $wpdb->prefix . 'nrtok_orders';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$order = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE is_printed = 0 ORDER BY id ASC LIMIT 1', $t_orders ) );

	if ( $order ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$t_orders,
			array( 'is_printed' => 1 ),
			array( 'id' => absint( $order->id ) ),
			array( '%d' ),
			array( '%d' )
		);
		wp_send_json_success( $order );
	}

	wp_send_json_error();
}

add_action( 'wp_ajax_nrtok_call_waiter', 'nrtok_ajax_call_waiter' );
add_action( 'wp_ajax_nopriv_nrtok_call_waiter', 'nrtok_ajax_call_waiter' );
/**
 * 3. Call Waiter / Request Bill (Customer Tablet).
 */
function nrtok_ajax_call_waiter() {
	global $wpdb;

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$table_no = sanitize_text_field( wp_unslash( $_POST['table_no'] ?? '' ) );
	if ( empty( $table_no ) ) {
		wp_send_json_error( __( 'Missing table number', 'nahian-restaurant-ordering-system' ), 400 );
	}

	$t_calls = $wpdb->prefix . 'nrtok_waiter_calls';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$exists = (int) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT id FROM %i WHERE table_no = %s AND status = "active" LIMIT 1',
			$t_calls,
			$table_no
		)
	);

	if ( 0 === $exists ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$t_calls,
			array(
				'table_no' => $table_no,
				'status'   => 'active',
			),
			array( '%s', '%s' )
		);
	}

	wp_send_json_success();
}

add_action( 'wp_ajax_nrtok_check_waiter_calls', 'nrtok_ajax_check_waiter_calls' );
/**
 * 4. Check Active Waiter Calls (Admin).
 */
function nrtok_ajax_check_waiter_calls() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Unauthorized', 'nahian-restaurant-ordering-system' ), 403 );
	}

	check_ajax_referer( 'nrtok_admin_nonce', 'nonce' );

	global $wpdb;
	$t_calls = $wpdb->prefix . 'nrtok_waiter_calls';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$calls = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE status = "active" ORDER BY id DESC', $t_calls ) ) ?: array();

	wp_send_json_success( $calls );
}

add_action( 'wp_ajax_nrtok_dismiss_waiter_call', 'nrtok_ajax_dismiss_waiter_call' );
/**
 * 5. Dismiss Waiter Call (Admin).
 */
function nrtok_ajax_dismiss_waiter_call() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Unauthorized', 'nahian-restaurant-ordering-system' ), 403 );
	}

	check_ajax_referer( 'nrtok_admin_nonce', 'nonce' );

	global $wpdb;
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$call_id = absint( $_POST['id'] ?? 0 );

	if ( $call_id > 0 ) {
		$t_calls = $wpdb->prefix . 'nrtok_waiter_calls';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$t_calls,
			array( 'status' => 'resolved' ),
			array( 'id' => $call_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	wp_send_json_success();
}

add_action( 'wp_ajax_nrtok_get_table_orders', 'nrtok_ajax_get_table_orders' );
add_action( 'wp_ajax_nopriv_nrtok_get_table_orders', 'nrtok_ajax_get_table_orders' );
/**
 * 6. Live Order Status Tracking (Customer Tablet - Returns Full Itemized Price Breakdown).
 */
function nrtok_ajax_get_table_orders() {
	global $wpdb;

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$table_no = sanitize_text_field( wp_unslash( $_POST['table_no'] ?? '' ) );
	if ( empty( $table_no ) ) {
		wp_send_json_error( __( 'Missing table number', 'nahian-restaurant-ordering-system' ), 400 );
	}

	$t_orders = $wpdb->prefix . 'nrtok_orders';
	$t_menu   = $wpdb->prefix . 'nrtok_menu';
	$t_extras = $wpdb->prefix . 'nrtok_extras';
	$t_calls  = $wpdb->prefix . 'nrtok_waiter_calls';

	// Check if there is an active waiter call for this table so the tablet UI can sync button state.
	$waiter_pending = false;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_calls ) ) === $t_calls ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active_call    = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE table_no = %s AND status = "active" LIMIT 1',
				$t_calls,
				$table_no
			)
		);
		$waiter_pending = $active_call > 0;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$orders = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT id, items, subtotal, tax_amount, total_amount, status, created_at FROM %i WHERE table_no = %s AND status != "completed" ORDER BY id ASC',
			$t_orders,
			$table_no
		)
	) ?: array();

	// Extra prices lookup map (for fallback if older orders lack unitPrice).
	$extra_prices = array();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$all_ex = $wpdb->get_results( $wpdb->prepare( 'SELECT name, price FROM %i', $t_extras ) ) ?: array();
	foreach ( $all_ex as $ex ) {
		$extra_prices[ $ex->name ] = (float) $ex->price;
	}

	$formatted = array();
	foreach ( $orders as $o ) {
		$items        = json_decode( $o->items, true ) ?: array();
		$count        = 0;
		$summary      = array();
		$items_detail = array();

		if ( is_array( $items ) ) {
			foreach ( $items as $i ) {
				$qty   = max( 1, absint( $i['qty'] ?? 1 ) );
				$u_prc = (float) ( $i['unitPrice'] ?? $i['price'] ?? 0 );

				// Fallback price lookup from menu table if missing.
				if ( $u_prc <= 0 && ! empty( $i['id'] ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$u_prc = (float) $wpdb->get_var(
						$wpdb->prepare( 'SELECT price FROM %i WHERE id = %d LIMIT 1', $t_menu, absint( $i['id'] ) )
					);
				}

				$ex_details = array();
				$ex_sum     = 0.0;
				if ( ! empty( $i['extras'] ) && is_array( $i['extras'] ) ) {
					foreach ( $i['extras'] as $ex_name ) {
						$ex_p         = $extra_prices[ $ex_name ] ?? 0.0;
						$ex_sum      += $ex_p;
						$ex_details[] = array(
							'name'  => $ex_name,
							'price' => number_format( $ex_p, 2, '.', '' ),
						);
					}
				}

				$base_prc = isset( $i['basePrice'] ) ? (float) $i['basePrice'] : max( 0.0, $u_prc - $ex_sum );
				$l_tot    = $u_prc * $qty;
				$count   += $qty;

				$ex_str    = ! empty( $i['extras'] ) ? ' (+' . implode( ', ', $i['extras'] ) . ')' : '';
				$summary[] = $qty . '× ' . ( $i['name'] ?? __( 'Item', 'nahian-restaurant-ordering-system' ) ) . $ex_str;

				$items_detail[] = array(
					'name'           => $i['name'] ?? __( 'Item', 'nahian-restaurant-ordering-system' ),
					'qty'            => $qty,
					'base_price'     => number_format( $base_prc, 2, '.', '' ),
					'unit_price'     => number_format( $u_prc, 2, '.', '' ),
					'line_total'     => number_format( $l_tot, 2, '.', '' ),
					'extras'         => ! empty( $i['extras'] ) ? $i['extras'] : array(),
					'extras_details' => $ex_details,
				);
			}
		}

		$formatted[] = array(
			'id'           => absint( $o->id ),
			'item_count'   => $count,
			'item_summary' => implode( ', ', $summary ),
			'items_detail' => $items_detail,
			'subtotal'     => number_format( (float) $o->subtotal, 2, '.', '' ),
			'tax_amount'   => number_format( (float) $o->tax_amount, 2, '.', '' ),
			'total_amount' => number_format( (float) $o->total_amount, 2, '.', '' ),
			'status'       => $o->status,
			'time'         => wp_date( 'h:i A', strtotime( $o->created_at ) ),
		);
	}

	wp_send_json(
		array(
			'success'        => true,
			'waiter_pending' => $waiter_pending,
			'data'           => $formatted,
		)
	);
}

add_action( 'wp_ajax_nrtok_settle_table_orders', 'nrtok_ajax_settle_table_orders' );
/**
 * 7. Settle & Merge All Active Orders for a Table (Admin - Combined Final Bill).
 */
function nrtok_ajax_settle_table_orders() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Unauthorized', 'nahian-restaurant-ordering-system' ), 403 );
	}

	global $wpdb;
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$table_no = sanitize_text_field( wp_unslash( $_POST['table_no'] ?? '' ) );
	if ( empty( $table_no ) ) {
		wp_send_json_error( __( 'Table number is required', 'nahian-restaurant-ordering-system' ), 400 );
	}

	$t_orders = $wpdb->prefix . 'nrtok_orders';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$active = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i WHERE table_no = %s AND status != "completed" ORDER BY id ASC',
			$t_orders,
			$table_no
		)
	) ?: array();

	if ( empty( $active ) ) {
		wp_send_json_error( __( 'No active orders found for this table.', 'nahian-restaurant-ordering-system' ), 404 );
	}

	$merged_items = array();
	$sub_total    = 0.0;
	$tax_total    = 0.0;
	$grand_total  = 0.0;
	$order_ids    = array();

	foreach ( $active as $ord ) {
		$order_ids[]  = absint( $ord->id );
		$sub_total   += (float) $ord->subtotal;
		$tax_total   += (float) $ord->tax_amount;
		$grand_total += (float) $ord->total_amount;

		$items = json_decode( $ord->items, true ) ?: array();
		foreach ( $items as $it ) {
			$merged_items[] = $it;
		}
	}

	// Mark all active orders for this table as completed (Paid).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			'UPDATE %i SET status = "completed" WHERE table_no = %s AND status != "completed"',
			$t_orders,
			$table_no
		)
	);

	wp_send_json_success(
		array(
			'table_no'     => $table_no,
			'order_ids'    => $order_ids,
			'items'        => $merged_items,
			'subtotal'     => number_format( $sub_total, 2, '.', '' ),
			'tax_amount'   => number_format( $tax_total, 2, '.', '' ),
			'total_amount' => number_format( $grand_total, 2, '.', '' ),
			'settled_at'   => current_time( 'mysql' ),
		)
	);
}