<?php
/**
 * Nahian POS Dashboard Tab View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the POS Dashboard overview tab.
 *
 * @param array $s Plugin settings array.
 */
function nrtok_view_dashboard( $s ) {
	if ( ! nrtok_current_user_can_pos() ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;
	$cur      = $s['currency'] ?? '৳';
	$today    = current_time( 'Y-m-d' );
	$t_orders = $wpdb->prefix . 'nrtok_orders';
	$t_tables = $wpdb->prefix . 'nrtok_tables';

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_orders_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_orders ) ) === $t_orders );
	$has_tables_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_tables ) ) === $t_tables );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Reusable SVG Icons.
	$svg_calendar  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
	$svg_monitor   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
	$svg_kds       = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>';
	$svg_wallet    = '<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>';
	$svg_flame     = '<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>';
	$svg_grid      = '<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>';
	$svg_grid_sm   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>';
	$svg_trend     = '<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>';
	$svg_award     = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>';
	$svg_receipt   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>';
	$svg_bar_chart = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>';
	$svg_arrow_rt  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
	$svg_copy      = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';

	// Dynamic Time-based Greeting & Staff/Admin Name.
	$current_user = wp_get_current_user();
	$admin_name   = ! empty( $current_user->display_name ) ? $current_user->display_name : __( 'Admin', 'nahian-restaurant-ordering-system' );
	$hour         = (int) current_time( 'G' );
	if ( $hour < 12 ) {
		$greeting = __( 'Good Morning', 'nahian-restaurant-ordering-system' );
	} elseif ( $hour < 18 ) {
		$greeting = __( 'Good Afternoon', 'nahian-restaurant-ordering-system' );
	} else {
		$greeting = __( 'Good Evening', 'nahian-restaurant-ordering-system' );
	}

	// 1. Core KPI Metrics.
	$today_sales    = 0.0;
	$today_paid_cnt = 0;
	$active_orders  = 0;
	$occupied_list  = array();

	if ( $has_orders_table ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$today_sales    = (float) ( $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(total_amount) FROM %i WHERE DATE(created_at) = %s AND status = %s', $t_orders, $today, 'completed' ) ) ?: 0 );
		$today_paid_cnt = (int) ( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE DATE(created_at) = %s AND status = %s', $t_orders, $today, 'completed' ) ) ?: 0 );
		$active_orders  = (int) ( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status IN (%s, %s, %s)', $t_orders, 'pending', 'preparing', 'served' ) ) ?: 0 );
		$occupied_list  = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT table_no FROM %i WHERE status IN (%s, %s, %s)', $t_orders, 'pending', 'preparing', 'served' ) ) ?: array();
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	$avg_order_val = $today_paid_cnt > 0 ? ( $today_sales / $today_paid_cnt ) : 0;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$total_tables   = $has_tables_table ? (int) ( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $t_tables ) ) ?: 0 ) : 0;
	$occupied_count = count( $occupied_list );
	$free_tables    = max( 0, $total_tables - $occupied_count );

	// 2. Last 7 Days Revenue Data.
	$chart_days  = array();
	$max_rev     = 1;
	$now_ts      = time();
	$start_date  = wp_date( 'Y-m-d', strtotime( '-6 days', $now_ts ) );
	$rev_by_date = array();

	if ( $has_orders_table ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$daily_rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DATE(created_at) AS order_day, SUM(total_amount) AS day_total
				 FROM %i
				 WHERE DATE(created_at) >= %s AND DATE(created_at) <= %s AND status = %s
				 GROUP BY DATE(created_at)',
				$t_orders,
				$start_date,
				$today,
				'completed'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( is_array( $daily_rows ) ) {
			foreach ( $daily_rows as $dr ) {
				$rev_by_date[ $dr->order_day ] = (float) $dr->day_total;
			}
		}
	}

	for ( $i = 6; $i >= 0; $i-- ) {
		$day_ts = strtotime( "-{$i} days", $now_ts );
		$d      = wp_date( 'Y-m-d', $day_ts );
		$rev    = isset( $rev_by_date[ $d ] ) ? $rev_by_date[ $d ] : 0.0;

		if ( $rev > $max_rev ) {
			$max_rev = $rev;
		}
		$chart_days[] = array(
			'label'    => wp_date( 'D', $day_ts ),
			'date'     => wp_date( 'M d', $day_ts ),
			'raw_date' => $d,
			'rev'      => $rev,
			'is_today' => ( $d === $today ),
		);
	}

	// 3. Top Selling Items (From last 100 orders).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$recent_all  = $has_orders_table ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT items FROM %i WHERE status != %s ORDER BY id DESC LIMIT 100', $t_orders, 'cancelled' ) ) ?: array() ) : array();
	$item_counts = array();
	foreach ( $recent_all as $row ) {
		$decoded = json_decode( $row->items, true );
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $it ) {
				$name = isset( $it['name'] ) ? sanitize_text_field( $it['name'] ) : __( 'Unknown Item', 'nahian-restaurant-ordering-system' );
				$qty  = isset( $it['qty'] ) ? absint( $it['qty'] ) : 0;
				if ( ! isset( $item_counts[ $name ] ) ) {
					$item_counts[ $name ] = 0;
				}
				$item_counts[ $name ] += $qty;
			}
		}
	}
	arsort( $item_counts );
	$top_items     = array_slice( $item_counts, 0, 5, true );
	$max_dish_sold = ! empty( $top_items ) ? max( 1, (int) reset( $top_items ) ) : 1;

	// 4. Recent Orders & Tables.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$recent_orders = $has_orders_table ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT 6', $t_orders ) ) ?: array() ) : array();
	$all_tables    = $has_tables_table ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id ASC', $t_tables ) ) ?: array() ) : array();
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Action URLs.
	$pos_url     = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=pos' );
	$kds_url     = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=kds' );
	$orders_url  = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=orders' );
	$reports_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=reports' );
	$tables_url  = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=tables' );
	$is_admin    = current_user_can( 'manage_options' );
	?>

	<style>
		.nrtok-dash-full-wrap { width: 100%; box-sizing: border-box; }
		.nrtok-full-card { width: 100%; max-width: 100%; box-sizing: border-box; margin: 0; }

		/* Welcome Banner & Live Clock */
		.nrtok-dash-welcome-card {
			background: linear-gradient(135deg, #090d16 0%, #1e293b 100%);
			color: #fff; padding: 26px 30px; border-radius: 20px; margin-bottom: 26px;
			display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;
			box-shadow: 0 12px 30px -6px rgba(9, 13, 22, 0.25);
			border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; box-sizing: border-box;
			position: relative; overflow: hidden;
		}
		.nrtok-dash-welcome-card::after {
			content: ""; position: absolute; right: -40px; top: -60px; width: 240px; height: 240px;
			background: radial-gradient(circle, rgba(249, 115, 22, 0.16) 0%, transparent 70%);
			pointer-events: none;
		}
		.nrtok-dash-welcome-title { margin: 0; font-size: 24px; font-weight: 850; color: #fff; letter-spacing: -0.5px; }
		.nrtok-dash-welcome-title span { color: #f97316; }
		.nrtok-dash-welcome-sub { margin: 6px 0 0; color: #94a3b8; font-size: 14px; font-weight: 500; }
		.nrtok-dash-welcome-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; z-index: 1; }
		.nrtok-dash-clock-box {
			background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12);
			padding: 10px 18px; border-radius: 14px; text-align: right; backdrop-filter: blur(8px);
		}
		.nrtok-dash-clock-time {
			font-family: 'Courier New', monospace; font-size: 21px; font-weight: 800;
			color: #f8fafc; letter-spacing: 1px; line-height: 1.1;
		}
		.nrtok-dash-clock-date {
			font-size: 11.5px; color: #94a3b8; font-weight: 600; margin-top: 4px;
			display: flex; align-items: center; justify-content: flex-end; gap: 6px;
		}
		.nrtok-dash-quick-btn {
			padding: 13px 18px; display: inline-flex; align-items: center; gap: 8px; font-weight: 700;
			border-radius: 12px; text-decoration: none; font-size: 13.5px; transition: all 0.2s ease;
		}
		.nrtok-dash-quick-ghost {
			background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); color: #f8fafc;
		}
		.nrtok-dash-quick-ghost:hover {
			background: rgba(255, 255, 255, 0.15); color: #fff; transform: translateY(-1.5px);
		}

		/* Top 4 KPI Cards */
		.nrtok-dash-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(235px, 1fr)); gap: 20px; margin-bottom: 26px; width: 100%; }
		.nrtok-dash-kpi {
			background: #fff; padding: 22px 24px; border-radius: 18px; border: 1px solid #e2e8f0;
			box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03); display: flex; justify-content: space-between; align-items: flex-start;
			transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;
		}
		.nrtok-dash-kpi:hover { transform: translateY(-3px); box-shadow: 0 12px 24px -6px rgba(15, 23, 42, 0.08); }
		.nrtok-dash-kpi-title { font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; }
		.nrtok-dash-kpi-val { font-size: 27px; font-weight: 850; color: #0f172a; margin: 8px 0 6px; letter-spacing: -0.6px; }
		.nrtok-dash-kpi-total { font-size: 16px; color: #94a3b8; font-weight: 700; }
		.nrtok-dash-kpi-sub { font-size: 12.5px; font-weight: 650; }
		.nrtok-dash-sub-green { color: #10b981; }
		.nrtok-dash-sub-amber { color: #f59e0b; }
		.nrtok-dash-sub-orange { color: #f97316; }
		.nrtok-dash-sub-blue { color: #3b82f6; }

		.nrtok-dash-icon-box { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
		.nrtok-dash-ico-green { background: #ecfdf5; color: #10b981; border: 1px solid #d1fae5; }
		.nrtok-dash-ico-amber { background: #fffbeb; color: #f59e0b; border: 1px solid #fef3c7; }
		.nrtok-dash-ico-orange { background: #fff7ed; color: #f97316; border: 1px solid #ffedd5; }
		.nrtok-dash-ico-blue { background: #eff6ff; color: #3b82f6; border: 1px solid #dbeafe; }

		/* Split Grids & Card Headers */
		.nrtok-dash-split-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 22px; width: 100%; }
		.nrtok-dash-split-mb { margin-bottom: 22px; }
		@media (max-width: 960px) { .nrtok-dash-split-grid { grid-template-columns: 1fr; } }

		.nrtok-dash-card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
		.nrtok-dash-card-head-chart { margin-bottom: 12px; }
		.nrtok-dash-card-head-floor { margin-bottom: 16px; }
		.nrtok-dash-card-title { margin: 0; font-size: 16.5px; font-weight: 850; color: #0f172a; display: flex; align-items: center; gap: 9px; }
		.nrtok-dash-title-ico { color: #f97316; display: inline-flex; }
		.nrtok-dash-card-sub { font-size: 12.5px; color: #64748b; display: block; margin-top: 2px; }
		.nrtok-dash-link-orange { font-size: 13px; color: #f97316; font-weight: 750; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: color 0.15s; }
		.nrtok-dash-link-orange:hover { color: #ea580c; text-decoration: underline; }
		.nrtok-dash-badge-popular { font-size: 11.5px; color: #475569; background: #f1f5f9; padding: 4px 10px; border-radius: 8px; font-weight: 750; }

		/* 7-Day Bar Chart */
		.nrtok-dash-chart-wrap { display: flex; align-items: flex-end; justify-content: space-between; height: 190px; padding-top: 24px; gap: 14px; border-bottom: 1.5px solid #e2e8f0; }
		.nrtok-dash-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end; text-decoration: none; }
		.nrtok-dash-bar {
			width: 100%; max-width: 46px; border-radius: 10px 10px 0 0; background: #cbd5e1;
			transition: height 0.45s cubic-bezier(0.16, 1, 0.3, 1), background 0.2s; position: relative; min-height: 6px;
		}
		.nrtok-dash-bar.today { background: linear-gradient(180deg, #f97316 0%, #ea580c 100%); box-shadow: 0 4px 14px rgba(249, 115, 22, 0.35); }
		.nrtok-dash-bar-col:hover .nrtok-dash-bar { background: #0f172a; }
		.nrtok-dash-bar-val { font-size: 11px; font-weight: 750; color: #475569; margin-bottom: 6px; }
		.nrtok-dash-bar-lbl { font-size: 12px; font-weight: 750; color: #64748b; margin-top: 10px; }
		.nrtok-dash-bar-lbl.is-today { color: #f97316; font-weight: 850; }

		/* Top Selling Dishes List */
		.nrtok-dash-empty-msg { color: #94a3b8; text-align: center; padding: 44px 0; margin: 0; font-weight: 500; }
		.nrtok-dash-top-list { display: flex; flex-direction: column; gap: 12px; }
		.nrtok-dash-top-row { padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0; }
		.nrtok-dash-top-row:last-child { border-bottom: none; padding-bottom: 0; }
		.nrtok-dash-top-main { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
		.nrtok-dash-top-left { display: flex; align-items: center; gap: 10px; }
		.nrtok-dash-rank-badge {
			width: 24px; height: 24px; border-radius: 7px; background: #f1f5f9; color: #475569;
			font-size: 11.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
		}
		.nrtok-dash-rank-badge.rank-1 { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
		.nrtok-dash-dish-name { font-size: 13.5px; color: #1e293b; font-weight: 700; }
		.nrtok-dash-sold-pill { font-size: 12px; font-weight: 750; color: #f97316; background: #fff7ed; padding: 3px 9px; border-radius: 20px; border: 1px solid #ffedd5; white-space: nowrap; }
		.nrtok-dash-dish-bar-bg { width: 100%; height: 5px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
		.nrtok-dash-dish-bar-fill { height: 100%; background: linear-gradient(90deg, #fb923c 0%, #f97316 100%); border-radius: 999px; }

		/* Recent Orders Table */
		.nrtok-dash-table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 14px; border: 1px solid #e2e8f0; }
		.nrtok-dash-recent-table { width: 100%; box-shadow: none; border: none; border-radius: 0; }
		.nrtok-dash-table-empty { text-align: center; color: #94a3b8; padding: 28px; font-weight: 500; }
		.nrtok-dash-ord-link { font-weight: 850; color: #0f172a; text-decoration: none; transition: color 0.15s; }
		.nrtok-dash-ord-link:hover { color: #f97316; }
		.nrtok-dash-tbl-name { font-weight: 700; color: #334155; }
		.nrtok-dash-ord-time { color: #64748b; font-size: 13px; font-weight: 500; }

		/* Status Pills */
		.nrtok-st-pill { padding: 4px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 750; text-transform: capitalize; display: inline-block; }
		.nrtok-st-pending { background: #fef3c7; color: #b45309; }
		.nrtok-st-preparing { background: #dbeafe; color: #1d4ed8; }
		.nrtok-st-served { background: #f3e8ff; color: #7e22ce; }
		.nrtok-st-completed { background: #d1fae5; color: #047857; }
		.nrtok-st-cancelled { background: #fee2e2; color: #b91c1c; }

		/* Floor Table Pills */
		.nrtok-dash-no-tables { color: #94a3b8; font-size: 13.5px; }
		.nrtok-floor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 11px; }
		.nrtok-floor-tile {
			padding: 13px 10px; border-radius: 14px; text-align: center; font-weight: 800; font-size: 13.5px;
			border: 1.5px solid #e2e8f0; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1); text-decoration: none; display: block;
		}
		.nrtok-floor-tile:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08); }
		.nrtok-floor-free { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
		.nrtok-floor-busy { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
		.nrtok-floor-status-row { font-size: 11px; font-weight: 750; margin-top: 5px; opacity: 0.95; display: flex; align-items: center; justify-content: center; gap: 5px; }
		.nrtok-dot-sm { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
		.nrtok-dot-free { background: #16a34a; }
		.nrtok-dot-busy { background: #ea580c; }

		.nrtok-dash-shortcode-box {
			margin-top: 20px; padding: 12px 16px; background: #f8fafc;
			border-radius: 12px; border: 1px solid #e2e8f0; font-size: 13px; color: #475569;
			display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
		}
		.nrtok-dash-shortcode-copy {
			display: inline-flex; align-items: center; gap: 7px; color: #f97316; font-weight: 800;
			background: #fff7ed; padding: 5px 10px; border-radius: 8px; border: 1px solid #ffedd5;
			cursor: pointer; font-family: monospace; font-size: 12.5px; transition: all 0.15s ease;
		}
		.nrtok-dash-shortcode-copy:hover { background: #ffedd5; }
		.nrtok-dash-shortcode-copy.copied { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
	</style>

	<div class="nrtok-dash-full-wrap">
		<!-- Welcome Message & Live Clock Banner -->
		<div class="nrtok-dash-welcome-card">
			<div>
				<h1 class="nrtok-dash-welcome-title"><?php echo esc_html( $greeting ); ?>, <span><?php echo esc_html( $admin_name ); ?></span></h1>
				<p class="nrtok-dash-welcome-sub">
					<?php
					printf(
						/* translators: %s: restaurant name */
						esc_html__( 'Welcome back to %s POS — Monitor live orders, tables, and daily revenue.', 'nahian-restaurant-ordering-system' ),
						'<strong>' . esc_html( $s['restaurant_name'] ?? 'NAHIAN' ) . '</strong>'
					);
					?>
				</p>
			</div>

			<div class="nrtok-dash-welcome-right">
				<div class="nrtok-dash-clock-box">
					<div class="nrtok-dash-clock-time" id="nrtok-dash-live-clock"><?php echo esc_html( current_time( 'h:i:s A' ) ); ?></div>
					<div class="nrtok-dash-clock-date">
						<?php echo $svg_calendar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( wp_date( 'l, F j, Y', $now_ts ) ); ?></span>
					</div>
				</div>
				<a href="<?php echo esc_url( $pos_url ); ?>" class="nrtok-btn nrtok-dash-quick-btn">
					<?php echo $svg_monitor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Counter POS', 'nahian-restaurant-ordering-system' ); ?></span>
				</a>
				<a href="<?php echo esc_url( $kds_url ); ?>" class="nrtok-dash-quick-btn nrtok-dash-quick-ghost">
					<?php echo $svg_kds; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Kitchen KDS', 'nahian-restaurant-ordering-system' ); ?></span>
				</a>
			</div>
		</div>

		<!-- Top 4 KPI Cards -->
		<div class="nrtok-dash-kpi-grid">
			<div class="nrtok-dash-kpi">
				<div>
					<div class="nrtok-dash-kpi-title"><?php esc_html_e( "Today's Paid Revenue", 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-dash-kpi-val"><?php echo esc_html( $cur . number_format( $today_sales, 2 ) ); ?></div>
					<div class="nrtok-dash-kpi-sub nrtok-dash-sub-green">
						<?php
						printf(
							/* translators: %d: number of paid bills */
							esc_html__( 'From %d paid bill(s)', 'nahian-restaurant-ordering-system' ),
							absint( $today_paid_cnt )
						);
						?>
					</div>
				</div>
				<div class="nrtok-dash-icon-box nrtok-dash-ico-green">
					<?php echo $svg_wallet; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>

			<div class="nrtok-dash-kpi">
				<div>
					<div class="nrtok-dash-kpi-title"><?php esc_html_e( 'Active Kitchen Orders', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-dash-kpi-val"><?php echo absint( $active_orders ); ?></div>
					<div class="nrtok-dash-kpi-sub nrtok-dash-sub-amber"><?php esc_html_e( 'Pending / Cooking / Served', 'nahian-restaurant-ordering-system' ); ?></div>
				</div>
				<div class="nrtok-dash-icon-box nrtok-dash-ico-amber">
					<?php echo $svg_flame; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>

			<div class="nrtok-dash-kpi">
				<div>
					<div class="nrtok-dash-kpi-title"><?php esc_html_e( 'Table Occupancy', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-dash-kpi-val"><?php echo absint( $occupied_count ); ?> <span class="nrtok-dash-kpi-total">/ <?php echo absint( $total_tables ); ?></span></div>
					<div class="nrtok-dash-kpi-sub nrtok-dash-sub-orange">
						<?php
						printf(
							/* translators: %d: number of available tables */
							esc_html__( '%d table(s) available', 'nahian-restaurant-ordering-system' ),
							absint( $free_tables )
						);
						?>
					</div>
				</div>
				<div class="nrtok-dash-icon-box nrtok-dash-ico-orange">
					<?php echo $svg_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>

			<div class="nrtok-dash-kpi">
				<div>
					<div class="nrtok-dash-kpi-title"><?php esc_html_e( 'Avg. Ticket Size', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-dash-kpi-val"><?php echo esc_html( $cur . number_format( $avg_order_val, 2 ) ); ?></div>
					<div class="nrtok-dash-kpi-sub nrtok-dash-sub-blue"><?php esc_html_e( 'Per completed order today', 'nahian-restaurant-ordering-system' ); ?></div>
				</div>
				<div class="nrtok-dash-icon-box nrtok-dash-ico-blue">
					<?php echo $svg_trend; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>

		<!-- Middle Section: 7-Day Revenue Chart + Top Selling Items -->
		<div class="nrtok-dash-split-grid nrtok-dash-split-mb">
			<!-- Sales Chart Card -->
			<div class="nrtok-card nrtok-full-card">
				<div class="nrtok-dash-card-head nrtok-dash-card-head-chart">
					<div>
						<h3 class="nrtok-dash-card-title">
							<span class="nrtok-dash-title-ico"><?php echo $svg_bar_chart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span><?php esc_html_e( '7-Day Revenue Trend', 'nahian-restaurant-ordering-system' ); ?></span>
						</h3>
						<span class="nrtok-dash-card-sub"><?php esc_html_e( 'Daily completed sales comparison', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>
					<?php if ( $is_admin ) : ?>
						<a href="<?php echo esc_url( $reports_url ); ?>" class="nrtok-dash-link-orange">
							<span><?php esc_html_e( 'Full Report', 'nahian-restaurant-ordering-system' ); ?></span>
							<?php echo $svg_arrow_rt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php endif; ?>
				</div>

				<div class="nrtok-dash-chart-wrap" id="nrtok-dash-revenue-chart">
					<?php
					foreach ( $chart_days as $cd ) :
						$height_pct = max( 4, round( ( $cd['rev'] / $max_rev ) * 100 ) );
						$bar_url    = $is_admin
							? add_query_arg( 'view_date', $cd['raw_date'], $reports_url )
							: add_query_arg( 'filter_date', $cd['raw_date'], $orders_url );
						?>
					<a href="<?php echo esc_url( $bar_url ); ?>" class="nrtok-dash-bar-col" title="<?php echo esc_attr( $cd['date'] . ': ' . $cur . number_format( $cd['rev'], 2 ) ); ?>">
						<div class="nrtok-dash-bar-val"><?php echo $cd['rev'] > 0 ? esc_html( $cur . number_format( $cd['rev'], 0 ) ) : ''; ?></div>
						<div class="nrtok-dash-bar <?php echo $cd['is_today'] ? 'today' : ''; ?>" data-height="<?php echo absint( $height_pct ); ?>"></div>
						<div class="nrtok-dash-bar-lbl <?php echo $cd['is_today'] ? 'is-today' : ''; ?>"><?php echo esc_html( $cd['label'] ); ?></div>
					</a>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Top Selling Dishes Card -->
			<div class="nrtok-card nrtok-full-card">
				<div class="nrtok-dash-card-head">
					<h3 class="nrtok-dash-card-title">
						<span class="nrtok-dash-title-ico"><?php echo $svg_award; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php esc_html_e( 'Top Selling Dishes', 'nahian-restaurant-ordering-system' ); ?></span>
					</h3>
					<span class="nrtok-dash-badge-popular"><?php esc_html_e( 'Popular', 'nahian-restaurant-ordering-system' ); ?></span>
				</div>

				<?php if ( empty( $top_items ) ) : ?>
					<p class="nrtok-dash-empty-msg"><?php esc_html_e( 'No order data yet.', 'nahian-restaurant-ordering-system' ); ?></p>
				<?php else : ?>
					<div class="nrtok-dash-top-list">
						<?php
						$rank = 1;
						foreach ( $top_items as $dish_name => $sold_qty ) :
							$bar_w = max( 8, round( ( $sold_qty / $max_dish_sold ) * 100 ) );
							?>
						<div class="nrtok-dash-top-row">
							<div class="nrtok-dash-top-main">
								<div class="nrtok-dash-top-left">
									<span class="nrtok-dash-rank-badge <?php echo 1 === $rank ? 'rank-1' : ''; ?>">
										#<?php echo absint( $rank++ ); ?>
									</span>
									<strong class="nrtok-dash-dish-name"><?php echo esc_html( $dish_name ); ?></strong>
								</div>
								<span class="nrtok-dash-sold-pill">
									<?php
									printf(
										/* translators: %d: quantity sold */
										esc_html__( '%d sold', 'nahian-restaurant-ordering-system' ),
										absint( $sold_qty )
									);
									?>
								</span>
							</div>
							<div class="nrtok-dash-dish-bar-bg">
								<div class="nrtok-dash-dish-bar-fill" style="width: <?php echo absint( $bar_w ); ?>%;"></div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Bottom Section: Recent Orders + Live Floor Overview -->
		<div class="nrtok-dash-split-grid">
			<!-- Recent Orders Table -->
			<div class="nrtok-card nrtok-full-card">
				<div class="nrtok-dash-card-head">
					<h3 class="nrtok-dash-card-title">
						<span class="nrtok-dash-title-ico"><?php echo $svg_receipt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php esc_html_e( 'Recent Table Orders', 'nahian-restaurant-ordering-system' ); ?></span>
					</h3>
					<a href="<?php echo esc_url( $orders_url ); ?>" class="nrtok-dash-link-orange">
						<span><?php esc_html_e( 'Manage All', 'nahian-restaurant-ordering-system' ); ?></span>
						<?php echo $svg_arrow_rt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</div>
				<div class="nrtok-dash-table-scroll">
					<table class="nrtok-table nrtok-dash-recent-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Order', 'nahian-restaurant-ordering-system' ); ?></th>
								<th><?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?></th>
								<th><?php esc_html_e( 'Time', 'nahian-restaurant-ordering-system' ); ?></th>
								<th><?php esc_html_e( 'Bill', 'nahian-restaurant-ordering-system' ); ?></th>
								<th><?php esc_html_e( 'Status', 'nahian-restaurant-ordering-system' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $recent_orders ) ) : ?>
								<tr><td colspan="5" class="nrtok-dash-table-empty"><?php esc_html_e( 'No orders placed yet.', 'nahian-restaurant-ordering-system' ); ?></td></tr>
							<?php endif; ?>
							<?php
							foreach ( $recent_orders as $ro ) :
								$st_class = 'nrtok-st-' . sanitize_html_class( $ro->status );
								$ord_url  = add_query_arg( 'view_order', absint( $ro->id ), $orders_url );
								?>
							<tr>
								<td>
									<a href="<?php echo esc_url( $ord_url ); ?>" class="nrtok-dash-ord-link">
										#<?php echo absint( $ro->id ); ?>
									</a>
								</td>
								<td><span class="nrtok-dash-tbl-name"><?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?> <?php echo esc_html( $ro->table_no ); ?></span></td>
								<td class="nrtok-dash-ord-time"><?php echo esc_html( wp_date( 'h:i A', strtotime( $ro->created_at ) ) ); ?></td>
								<td><strong><?php echo esc_html( $cur . number_format( (float) $ro->total_amount, 2 ) ); ?></strong></td>
								<td><span class="nrtok-st-pill <?php echo esc_attr( $st_class ); ?>"><?php echo esc_html( 'completed' === $ro->status ? __( 'Paid', 'nahian-restaurant-ordering-system' ) : ucfirst( $ro->status ) ); ?></span></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Live Floor Status Card -->
			<div class="nrtok-card nrtok-full-card">
				<div class="nrtok-dash-card-head nrtok-dash-card-head-floor">
					<h3 class="nrtok-dash-card-title">
						<span class="nrtok-dash-title-ico"><?php echo $svg_grid_sm; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php esc_html_e( 'Live Floor Status', 'nahian-restaurant-ordering-system' ); ?></span>
					</h3>
					<a href="<?php echo esc_url( $tables_url ); ?>" class="nrtok-dash-link-orange">
						<span><?php esc_html_e( 'Tables', 'nahian-restaurant-ordering-system' ); ?></span>
						<?php echo $svg_arrow_rt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</div>

				<?php if ( empty( $all_tables ) ) : ?>
					<p class="nrtok-dash-no-tables">
						<?php esc_html_e( 'No tables configured yet.', 'nahian-restaurant-ordering-system' ); ?>
						<a href="<?php echo esc_url( $tables_url ); ?>"><?php esc_html_e( 'Add tables', 'nahian-restaurant-ordering-system' ); ?></a>.
					</p>
				<?php else : ?>
					<div class="nrtok-floor-grid">
						<?php
						foreach ( $all_tables as $tbl ) :
							$busy    = in_array( $tbl->table_name, $occupied_list, true );
							$tbl_url = add_query_arg( 'view_table', absint( $tbl->id ), $tables_url );
							?>
						<a href="<?php echo esc_url( $tbl_url ); ?>" class="nrtok-floor-tile <?php echo $busy ? 'nrtok-floor-busy' : 'nrtok-floor-free'; ?>">
							<div>T-<?php echo esc_html( $tbl->table_name ); ?></div>
							<div class="nrtok-floor-status-row">
								<span class="nrtok-dot-sm <?php echo $busy ? 'nrtok-dot-busy' : 'nrtok-dot-free'; ?>"></span>
								<span><?php echo $busy ? esc_html__( 'Busy', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Free', 'nahian-restaurant-ordering-system' ); ?></span>
							</div>
						</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="nrtok-dash-shortcode-box">
					<strong><?php esc_html_e( 'Tablet Menu Shortcode:', 'nahian-restaurant-ordering-system' ); ?></strong>
					<button type="button" id="nrtok-copy-shortcode" class="nrtok-dash-shortcode-copy" data-code="[nrtok_restaurant_menu]" title="<?php esc_attr_e( 'Click to copy shortcode', 'nahian-restaurant-ordering-system' ); ?>">
						<?php echo $svg_copy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span id="nrtok-copy-shortcode-text">[nrtok_restaurant_menu]</span>
					</button>
				</div>
			</div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		function updateDashClock() {
			const now = new Date();
			$('#nrtok-dash-live-clock').text(now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
		}
		updateDashClock();
		setInterval(updateDashClock, 1000);

		$('#nrtok-dash-revenue-chart .nrtok-dash-bar').each(function() {
			const h = parseInt($(this).data('height'), 10) || 4;
			$(this).css('height', h + '%');
		});

		$(document).on('click', '#nrtok-copy-shortcode', function() {
			const $btn = $(this);
			const code = $btn.data('code');
			const $txt = $('#nrtok-copy-shortcode-text');

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(code);
			}
			$btn.addClass('copied');
			$txt.text(<?php echo wp_json_encode( __( 'Copied!', 'nahian-restaurant-ordering-system' ) ); ?>);
			if (window.nrtokToast) {
				window.nrtokToast(<?php echo wp_json_encode( __( 'Shortcode copied to clipboard', 'nahian-restaurant-ordering-system' ) ); ?>, 'success');
			}
			setTimeout(function() {
				$btn.removeClass('copied');
				$txt.text(code);
			}, 1800);
		});
	});
	</script>
	<?php
}