<?php
/**
 * Nahian POS Sales & Tax Reports View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate YYYY-MM-DD date string safely.
 *
 * @param string $date_str Input date string.
 * @param string $fallback Fallback date string.
 * @return string Validated date string.
 */
function nrtok_validate_report_date( $date_str, $fallback ) {
	if ( ! is_string( $date_str ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_str ) ) {
		return $fallback;
	}
	$parts = explode( '-', $date_str );
	return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] ) ? $date_str : $fallback;
}

/**
 * Render Sales & Tax Reports View.
 *
 * @param array $s Plugin settings array.
 */
function nrtok_view_reports( $s = array() ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;

	if ( empty( $s ) && function_exists( 'nrtok_get_settings' ) ) {
		$s = nrtok_get_settings();
	}
	$cur       = $s['currency'] ?? '৳';
	$rest_name = $s['restaurant_name'] ?? 'NAHIAN';
	$rest_addr = $s['address'] ?? '';
	$rest_phn  = $s['phone'] ?? '';
	$t_orders  = $wpdb->prefix . 'nrtok_orders';
	$t_tables  = $wpdb->prefix . 'nrtok_tables';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_orders ) ) !== $t_orders ) {
		echo '<div class="nrtok-alert" style="background:#fef2f2; color:#b91c1c; padding:16px; border-radius:12px;">' . esc_html__( 'Orders database table not found.', 'nahian-restaurant-ordering-system' ) . '</div>';
		return;
	}

	// Reusable SVG Icons.
	$svg_download = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
	$svg_print    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>';
	$svg_chart    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>';
	$svg_calendar = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
	$svg_award    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>';
	$svg_eye      = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
	$svg_back     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>';
	$svg_table    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 10h16M6 10v8M18 10v8M9 14h6"/></svg>';
	$svg_coins    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/></svg>';
	$svg_sort     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 16 4 4 4-4"/><path d="M7 20V4"/><path d="m21 8-4-4-4 4"/><path d="M17 4v16"/></svg>';
	$svg_reset    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';

	$now_ts       = time();
	$today        = wp_date( 'Y-m-d', $now_ts );
	$month_start  = wp_date( 'Y-m-01', $now_ts );
	$last7_start  = wp_date( 'Y-m-d', strtotime( '-6 days', $now_ts ) );
	$prev_m_start = wp_date( 'Y-m-01', strtotime( 'first day of last month', $now_ts ) );
	$prev_m_end   = wp_date( 'Y-m-t', strtotime( 'last day of last month', $now_ts ) );

	// Filter inputs.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only report filtering.
	$raw_from      = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : $month_start;
	$raw_to        = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : $today;
	$raw_view_date = isset( $_GET['view_date'] ) ? sanitize_text_field( wp_unslash( $_GET['view_date'] ) ) : '';
	$filter_table  = isset( $_GET['filter_table'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_table'] ) ) : 'all';
	$min_amount    = isset( $_GET['min_amount'] ) && '' !== $_GET['min_amount'] ? max( 0.0, (float) $_GET['min_amount'] ) : 0.0;
	$allowed_sorts = array( 'date_desc', 'date_asc', 'rev_desc', 'orders_desc' );
	$raw_sort      = isset( $_GET['sort_by'] ) ? sanitize_key( wp_unslash( $_GET['sort_by'] ) ) : 'date_desc';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$sort_by       = in_array( $raw_sort, $allowed_sorts, true ) ? $raw_sort : 'date_desc';

	$from      = nrtok_validate_report_date( $raw_from, $month_start );
	$to        = nrtok_validate_report_date( $raw_to, $today );
	$view_date = ! empty( $raw_view_date ) ? nrtok_validate_report_date( $raw_view_date, '' ) : '';

	if ( $from > $to ) {
		$tmp  = $from;
		$from = $to;
		$to   = $tmp;
	}

	// Fetch available table names for dropdown.
	$table_options = array();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_tables ) ) === $t_tables ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_options = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT table_name FROM %i ORDER BY id ASC', $t_tables ) ) ?: array();
	}
	if ( empty( $table_options ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_options = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT table_no FROM %i ORDER BY table_no ASC', $t_orders ) ) ?: array();
	}

	$base_reports_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=reports' );
	$base_orders_url  = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=orders' );

	// Shared query args so presets retain active secondary filters.
	$shared_filter_args = array();
	if ( 'all' !== $filter_table && '' !== $filter_table ) {
		$shared_filter_args['filter_table'] = $filter_table;
	}
	if ( $min_amount > 0 ) {
		$shared_filter_args['min_amount'] = $min_amount;
	}
	if ( 'date_desc' !== $sort_by ) {
		$shared_filter_args['sort_by'] = $sort_by;
	}

	// Dynamic WHERE clauses for Table & Min Amount filters.
	$where_sql    = 'DATE(created_at) BETWEEN %s AND %s AND status = %s';
	$where_params = array( $from, $to, 'completed' );

	if ( 'all' !== $filter_table && '' !== $filter_table ) {
		$where_sql     .= ' AND table_no = %s';
		$where_params[] = $filter_table;
	}
	if ( $min_amount > 0 ) {
		$where_sql     .= ' AND total_amount >= %f';
		$where_params[] = $min_amount;
	}

	// 1. Summary Metrics.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$summary = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COUNT(*) as total_orders, COALESCE(SUM(subtotal), 0) as sub_rev, COALESCE(SUM(total_amount), 0) as revenue, COALESCE(SUM(tax_amount), 0) as total_tax
			 FROM %i WHERE {$where_sql}",
			array_merge( array( $t_orders ), $where_params )
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$total_orders = $summary ? absint( $summary->total_orders ) : 0;
	$sub_rev      = $summary ? (float) $summary->sub_rev : 0.0;
	$revenue      = $summary ? (float) $summary->revenue : 0.0;
	$total_tax    = $summary ? (float) $summary->total_tax : 0.0;
	$avg_ticket   = ( $total_orders > 0 ) ? ( $revenue / $total_orders ) : 0.0;

	// 2. Daily Breakdown with Sort Order.
	$order_by_map = array(
		'date_desc'   => 'sale_date DESC',
		'date_asc'    => 'sale_date ASC',
		'rev_desc'    => 'gross_sales DESC',
		'orders_desc' => 'ord_count DESC',
	);
	$order_clause = $order_by_map[ $sort_by ];

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$daily_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DATE(created_at) as sale_date, COUNT(*) as ord_count, COALESCE(SUM(subtotal), 0) as net_sales, COALESCE(SUM(tax_amount), 0) as tax_sum, COALESCE(SUM(total_amount), 0) as gross_sales
			 FROM %i WHERE {$where_sql}
			 GROUP BY DATE(created_at) ORDER BY {$order_clause}",
			array_merge( array( $t_orders ), $where_params )
		)
	) ?: array();

	// 3. Top Selling Dishes in Selected Period.
	$period_orders = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT items FROM %i WHERE {$where_sql}",
			array_merge( array( $t_orders ), $where_params )
		)
	) ?: array();
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$dish_stats = array();
	foreach ( $period_orders as $po ) {
		$items = json_decode( $po->items, true );
		if ( is_array( $items ) ) {
			foreach ( $items as $it ) {
				$name = isset( $it['name'] ) ? sanitize_text_field( $it['name'] ) : __( 'Unknown Item', 'nahian-restaurant-ordering-system' );
				$qty  = absint( $it['qty'] ?? 0 );
				$line = (float) ( $it['unitPrice'] ?? $it['price'] ?? 0 ) * $qty;
				if ( ! isset( $dish_stats[ $name ] ) ) {
					$dish_stats[ $name ] = array(
						'qty' => 0,
						'rev' => 0.0,
					);
				}
				$dish_stats[ $name ]['qty'] += $qty;
				$dish_stats[ $name ]['rev'] += $line;
			}
		}
	}
	uasort(
		$dish_stats,
		function( $a, $b ) {
			return $b['qty'] <=> $a['qty'];
		}
	);
	$top_dishes   = array_slice( $dish_stats, 0, 6, true );
	$max_dish_rev = 1.0;
	foreach ( $top_dishes as $ds ) {
		if ( $ds['rev'] > $max_dish_rev ) {
			$max_dish_rev = (float) $ds['rev'];
		}
	}

	$has_extra_filters = ( 'all' !== $filter_table || $min_amount > 0 || 'date_desc' !== $sort_by );
	$active_filter_txt = array();
	if ( 'all' !== $filter_table ) {
		$active_filter_txt[] = __( 'Table:', 'nahian-restaurant-ordering-system' ) . ' ' . $filter_table;
	}
	if ( $min_amount > 0 ) {
		$active_filter_txt[] = __( 'Min Bill:', 'nahian-restaurant-ordering-system' ) . ' ' . $cur . number_format( $min_amount, 2 );
	}
	?>

	<style>
		.nrtok-rep-full-wrap { width: 100%; box-sizing: border-box; }
		.nrtok-full-card { width: 100%; max-width: 100%; box-sizing: border-box; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03); margin-bottom: 24px; }
		.nrtok-full-table { width: 100%; max-width: 100%; }
		.nrtok-rep-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 12px; }

		.nrtok-rep-header-actions { display: flex; gap: 10px; flex-wrap: wrap; }

		/* Date & Multi-Filter Grid */
		.nrtok-rep-presets-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
		.nrtok-rep-presets { display: flex; gap: 8px; flex-wrap: wrap; }
		.nrtok-rep-preset-btn {
			padding: 7px 16px; border-radius: 20px; font-size: 12.5px; font-weight: 700;
			text-decoration: none; border: 1.5px solid #e2e8f0; background: #fff; color: #475569; transition: all 0.2s;
		}
		.nrtok-rep-preset-btn:hover { border-color: #f97316; color: #f97316; background: #fff7ed; }
		.nrtok-rep-preset-btn.active { background: #0f172a; color: #fff; border-color: #0f172a; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15); }

		.nrtok-rep-filter-grid {
			display: grid; grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
			gap: 14px; align-items: end; width: 100%;
		}
		.nrtok-rep-filter-actions { display: flex; gap: 8px; align-items: center; }
		.nrtok-rep-generate-btn { height: 44px; padding: 0 20px; border-radius: 12px; white-space: nowrap; flex: 1; justify-content: center; }

		/* Premium KPI Cards */
		.nrtok-rep-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px; margin-bottom: 24px; width: 100%; }
		.nrtok-rep-kpi-card {
			background: #fff; padding: 22px 24px; border-radius: 18px; border: 1px solid #e2e8f0;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03); position: relative; overflow: hidden;
			transition: transform 0.2s, box-shadow 0.2s;
		}
		.nrtok-rep-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08); }
		.nrtok-rep-kpi-day { background: #f8fafc; }

		.nrtok-rep-border-green { border-left: 5px solid #10b981; }
		.nrtok-rep-border-blue { border-left: 5px solid #3b82f6; }
		.nrtok-rep-border-amber { border-left: 5px solid #f59e0b; }
		.nrtok-rep-border-orange { border-left: 5px solid #f97316; }

		.nrtok-rep-kpi-lbl { font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; }
		.nrtok-rep-kpi-num { font-size: 28px; font-weight: 900; color: #0f172a; margin-top: 8px; letter-spacing: -0.5px; }
		.nrtok-rep-num-green { color: #059669; }
		.nrtok-rep-kpi-sub { font-size: 12.5px; color: #64748b; margin-top: 6px; font-weight: 500; }

		/* Split Grid & Card Headers */
		.nrtok-rep-split-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; width: 100%; }
		@media (max-width: 960px) { .nrtok-rep-split-grid { grid-template-columns: 1fr; } }

		.nrtok-rep-card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
		.nrtok-rep-card-title { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
		.nrtok-rep-title-ico { color: #f97316; display: inline-flex; }
		.nrtok-rep-days-cnt { font-size: 12.5px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 3px 10px; border-radius: 12px; }

		/* Polished Elevated Input Elements */
		.nrtok-form-group { margin: 0; width: 100%; }
		.nrtok-label {
			display: flex; align-items: center; justify-content: space-between;
			font-size: 12.5px; font-weight: 750; color: #1e293b; margin-bottom: 7px; letter-spacing: 0.2px;
		}
		.nrtok-input-wrap {
			position: relative; display: flex; align-items: center; width: 100%;
			background: #ffffff; border-radius: 12px;
			border: 1.5px solid #e2e8f0;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
			transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
		}
		.nrtok-input-wrap:hover { border-color: #cbd5e1; box-shadow: 0 3px 6px rgba(15, 23, 42, 0.04); }
		.nrtok-input-wrap:focus-within {
			border-color: #f97316; background-color: #fff;
			box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12), 0 2px 4px rgba(0, 0, 0, 0.04);
		}
		.nrtok-input-prefix {
			position: absolute; left: 14px; font-weight: 700; color: #94a3b8; font-size: 14px;
			pointer-events: none; display: flex; align-items: center; justify-content: center;
			transition: color 0.2s ease;
		}
		.nrtok-input-wrap:focus-within .nrtok-input-prefix { color: #f97316; }
		.nrtok-input {
			width: 100% !important; max-width: 100% !important;
			padding: 10px 14px; border: none !important; border-radius: 12px;
			font-size: 13.5px; font-weight: 600; color: #0f172a; background: transparent !important;
			box-shadow: none !important; outline: none !important; box-sizing: border-box;
			min-height: 44px;
		}
		.nrtok-input.has-prefix { padding-left: 40px; }
		select.nrtok-input {
			appearance: none; -webkit-appearance: none; padding-right: 36px; cursor: pointer;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
			background-repeat: no-repeat !important; background-position: right 12px center !important; background-size: 15px !important;
		}

		.nrtok-rep-btn-icon { display: inline-flex; align-items: center; gap: 7px; font-weight: 700; }
		.nrtok-rep-act-btn {
			padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700;
			text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
			background: #eff6ff; color: #1d4ed8; transition: 0.2s;
		}
		.nrtok-rep-act-btn:hover { background: #dbeafe; }

		/* Tables & Columns */
		.nrtok-rep-inner-table { box-shadow: none; border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden; }
		.nrtok-rep-day-table { box-shadow: none; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
		.nrtok-rep-table-empty { text-align: center; padding: 40px; color: #94a3b8; font-size: 14px; }

		.nrtok-col-rep-details { width: 100px; text-align: right; }
		.nrtok-col-do-id { width: 100px; }
		.nrtok-col-do-time { width: 110px; }
		.nrtok-col-do-tbl { width: 110px; }
		.nrtok-col-do-sub { width: 130px; }
		.nrtok-col-do-tax { width: 110px; }
		.nrtok-col-do-tot { width: 140px; }
		.nrtok-col-do-act { width: 110px; text-align: right; }

		.nrtok-rep-date-link { font-weight: 700; color: #0f172a; text-decoration: none; }
		.nrtok-rep-date-link:hover { color: #f97316; }
		.nrtok-rep-gross-val { color: #059669; font-family: 'Courier New', Courier, monospace; }
		.nrtok-rep-do-time { color: #64748b; }
		.nrtok-rep-do-tbl-badge {
			background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; padding: 3px 10px;
			border-radius: 8px; font-weight: 800; font-size: 12px; display: inline-block;
		}
		.nrtok-rep-do-items { font-size: 13px; color: #334155; }

		/* Top Selling Dishes List */
		.nrtok-rep-empty-dishes { color: #94a3b8; text-align: center; padding: 40px 0; margin: 0; font-size: 14px; }
		.nrtok-rep-top-list { display: flex; flex-direction: column; gap: 13px; }
		.nrtok-rep-top-row { padding-bottom: 11px; border-bottom: 1px dashed #e2e8f0; }
		.nrtok-rep-top-row:last-child { border-bottom: none; padding-bottom: 0; }
		.nrtok-rep-top-main { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
		.nrtok-rep-top-left { display: flex; align-items: center; gap: 11px; }
		.nrtok-rep-rank-badge {
			width: 26px; height: 26px; border-radius: 8px; background: #f1f5f9; color: #475569;
			font-size: 12px; font-weight: 900; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
		}
		.nrtok-rep-rank-badge.rank-1 { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
		.nrtok-rep-dish-name { font-size: 13.5px; font-weight: 700; color: #0f172a; display: block; }
		.nrtok-rep-dish-qty { color: #64748b; font-size: 11.5px; }
		.nrtok-rep-dish-rev { font-size: 14px; font-weight: 800; color: #f97316; font-family: 'Courier New', Courier, monospace; }
		.nrtok-rep-dish-bar-bg { width: 100%; height: 5px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
		.nrtok-rep-dish-bar-fill { height: 100%; background: linear-gradient(90deg, #fb923c 0%, #f97316 100%); border-radius: 999px; }

		/* Dedicated Daily View Hero */
		.nrtok-rep-day-hero {
			display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
			gap: 16px; padding-bottom: 20px; border-bottom: 1.5px solid #f1f5f9; margin-bottom: 24px;
		}
		.nrtok-rep-day-hero-left { display: flex; align-items: center; gap: 16px; }
		.nrtok-rep-day-hero-ico {
			width: 56px; height: 56px; border-radius: 16px; background: #fff7ed; color: #f97316;
			border: 1.5px solid #fed7aa; display: flex; align-items: center; justify-content: center;
			box-shadow: 0 4px 12px rgba(249, 115, 22, 0.1);
		}
		.nrtok-rep-day-sub { font-size: 11.5px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
		.nrtok-rep-day-title { margin: 2px 0 0; font-size: 24px; font-weight: 900; color: #0f172a; }
		.nrtok-rep-day-section-title { margin: 0 0 16px; font-size: 17px; font-weight: 800; color: #0f172a; }

		/* Full-page A4/Letter Print Mode Override for Reports */
		@media print {
			body.nrtok-printing-report #nrtok-print-area {
				width: 100% !important;
				max-width: 100% !important;
				position: static !important;
				padding: 16px 24px !important;
				font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
				font-size: 12px !important;
				color: #0f172a !important;
				box-sizing: border-box !important;
			}
			body.nrtok-printing-report #nrtok-print-area table {
				width: 100% !important;
				border-collapse: collapse !important;
				margin-top: 12px !important;
			}
			body.nrtok-printing-report #nrtok-print-area th,
			body.nrtok-printing-report #nrtok-print-area td {
				border: 1px solid #cbd5e1 !important;
				padding: 8px 10px !important;
				text-align: left !important;
			}
			body.nrtok-printing-report #nrtok-print-area th {
				background: #f1f5f9 !important;
				font-weight: 800 !important;
			}
		}
	</style>

	<div class="nrtok-rep-full-wrap">
		<!-- Page Header -->
		<div class="nrtok-header">
			<div>
				<h1><?php esc_html_e( 'Sales & Tax Reports', 'nahian-restaurant-ordering-system' ); ?></h1>
				<?php if ( ! empty( $view_date ) ) : ?>
					<p>
						<?php
						printf(
							/* translators: %s: formatted date */
							esc_html__( 'Detailed daily transaction breakdown for %s', 'nahian-restaurant-ordering-system' ),
							'<strong>' . esc_html( wp_date( 'l, M d, Y', strtotime( $view_date ) ) ) . '</strong>'
						);
						?>
					</p>
				<?php else : ?>
					<p>
						<?php
						printf(
							/* translators: 1: start date, 2: end date */
							esc_html__( 'Financial analytics and revenue breakdown from %1$s to %2$s', 'nahian-restaurant-ordering-system' ),
							'<strong>' . esc_html( wp_date( 'M d, Y', strtotime( $from ) ) ) . '</strong>',
							'<strong>' . esc_html( wp_date( 'M d, Y', strtotime( $to ) ) ) . '</strong>'
						);
						?>
					</p>
				<?php endif; ?>
			</div>
			<div class="nrtok-rep-header-actions">
				<?php
				if ( ! empty( $view_date ) ) :
					$back_rep_url = add_query_arg(
						array_merge(
							array(
								'from' => $from,
								'to'   => $to,
							),
							$shared_filter_args
						),
						$base_reports_url
					);
					?>
					<a href="<?php echo esc_url( $back_rep_url ); ?>" class="nrtok-btn-secondary nrtok-rep-btn-icon">
						<?php echo $svg_back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Back to Report Overview', 'nahian-restaurant-ordering-system' ); ?></span>
					</a>
				<?php endif; ?>
				<button type="button" id="nrtok-rep-export-csv" class="nrtok-btn-secondary nrtok-rep-btn-icon" data-target="<?php echo ! empty( $view_date ) ? '#nrtok-rep-day-table' : '#nrtok-rep-daily-table'; ?>">
					<?php echo $svg_download; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Export CSV', 'nahian-restaurant-ordering-system' ); ?></span>
				</button>
				<button type="button" id="nrtok-rep-print-btn" class="nrtok-btn nrtok-rep-btn-icon">
					<?php echo $svg_print; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Print Report', 'nahian-restaurant-ordering-system' ); ?></span>
				</button>
			</div>
		</div>

		<?php
		if ( ! empty( $view_date ) ) :
			// ==============================================================
			// DEDICATED SINGLE-DAY SALES REPORT VIEW PAGE
			// ==============================================================
			$day_where_sql    = 'DATE(created_at) = %s AND status = %s';
			$day_where_params = array( $view_date, 'completed' );
			if ( 'all' !== $filter_table && '' !== $filter_table ) {
				$day_where_sql     .= ' AND table_no = %s';
				$day_where_params[] = $filter_table;
			}
			if ( $min_amount > 0 ) {
				$day_where_sql     .= ' AND total_amount >= %f';
				$day_where_params[] = $min_amount;
			}

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$day_summary = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) as total_orders, COALESCE(SUM(subtotal), 0) as sub_rev, COALESCE(SUM(total_amount), 0) as revenue, COALESCE(SUM(tax_amount), 0) as total_tax
					 FROM %i WHERE {$day_where_sql}",
					array_merge( array( $t_orders ), $day_where_params )
				)
			);
			$d_orders_cnt = $day_summary ? absint( $day_summary->total_orders ) : 0;
			$d_sub_rev    = $day_summary ? (float) $day_summary->sub_rev : 0.0;
			$d_tax        = $day_summary ? (float) $day_summary->total_tax : 0.0;
			$d_rev        = $day_summary ? (float) $day_summary->revenue : 0.0;

			$day_orders = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE {$day_where_sql} ORDER BY id DESC",
					array_merge( array( $t_orders ), $day_where_params )
				)
			) ?: array();
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			?>
			<div class="nrtok-card nrtok-full-card">
				<div class="nrtok-rep-day-hero">
					<div class="nrtok-rep-day-hero-left">
						<div class="nrtok-rep-day-hero-ico">
							<?php echo $svg_calendar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div>
							<div class="nrtok-rep-day-sub"><?php esc_html_e( 'Daily Sales Statement', 'nahian-restaurant-ordering-system' ); ?></div>
							<h2 class="nrtok-rep-day-title"><?php echo esc_html( wp_date( 'F d, Y (l)', strtotime( $view_date ) ) ); ?></h2>
						</div>
					</div>
				</div>

				<!-- Day KPIs -->
				<div class="nrtok-rep-kpi-grid" id="nrtok-rep-kpi-source">
					<div class="nrtok-rep-kpi-card nrtok-rep-kpi-day nrtok-rep-border-green">
						<div class="nrtok-rep-kpi-lbl"><?php esc_html_e( 'Day Gross Revenue', 'nahian-restaurant-ordering-system' ); ?></div>
						<div class="nrtok-rep-kpi-num nrtok-rep-num-green"><?php echo esc_html( $cur . number_format( $d_rev, 2 ) ); ?></div>
					</div>
					<div class="nrtok-rep-kpi-card nrtok-rep-kpi-day nrtok-rep-border-blue">
						<div class="nrtok-rep-kpi-lbl"><?php esc_html_e( 'Net Food Sales', 'nahian-restaurant-ordering-system' ); ?></div>
						<div class="nrtok-rep-kpi-num"><?php echo esc_html( $cur . number_format( $d_sub_rev, 2 ) ); ?></div>
					</div>
					<div class="nrtok-rep-kpi-card nrtok-rep-kpi-day nrtok-rep-border-amber">
						<div class="nrtok-rep-kpi-lbl"><?php esc_html_e( 'Tax Collected', 'nahian-restaurant-ordering-system' ); ?></div>
						<div class="nrtok-rep-kpi-num"><?php echo esc_html( $cur . number_format( $d_tax, 2 ) ); ?></div>
					</div>
					<div class="nrtok-rep-kpi-card nrtok-rep-kpi-day nrtok-rep-border-orange">
						<div class="nrtok-rep-kpi-lbl"><?php esc_html_e( 'Paid Orders', 'nahian-restaurant-ordering-system' ); ?></div>
						<div class="nrtok-rep-kpi-num"><?php echo absint( $d_orders_cnt ); ?></div>
					</div>
				</div>

				<!-- Day Completed Orders Table -->
				<h3 class="nrtok-rep-day-section-title">
					<?php
					printf(
						/* translators: %s: formatted date */
						esc_html__( 'Completed Orders on %s', 'nahian-restaurant-ordering-system' ),
						esc_html( wp_date( 'M d, Y', strtotime( $view_date ) ) )
					);
					?>
				</h3>
				<div class="nrtok-rep-table-wrap">
					<table class="nrtok-table nrtok-full-table nrtok-rep-day-table" id="nrtok-rep-day-table">
						<thead>
							<tr style="background:#f8fafc; border-bottom:1.5px solid #e2e8f0;">
								<th class="nrtok-col-do-id" style="padding:15px 18px;"><?php esc_html_e( 'Order #', 'nahian-restaurant-ordering-system' ); ?></th>
								<th class="nrtok-col-do-time" style="padding:15px 14px;"><?php esc_html_e( 'Time', 'nahian-restaurant-ordering-system' ); ?></th>
								<th class="nrtok-col-do-tbl" style="padding:15px 14px;"><?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?></th>
								<th style="padding:15px 14px;"><?php esc_html_e( 'Items Sold', 'nahian-restaurant-ordering-system' ); ?></th>
								<th class="nrtok-col-do-sub" style="padding:15px 14px;"><?php esc_html_e( 'Subtotal', 'nahian-restaurant-ordering-system' ); ?></th>
								<th class="nrtok-col-do-tax" style="padding:15px 14px;"><?php esc_html_e( 'Tax', 'nahian-restaurant-ordering-system' ); ?></th>
								<th class="nrtok-col-do-tot" style="padding:15px 14px;"><?php esc_html_e( 'Total Paid', 'nahian-restaurant-ordering-system' ); ?></th>
								<th class="nrtok-col-do-act" style="padding:15px 18px;"><?php esc_html_e( 'Action', 'nahian-restaurant-ordering-system' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $day_orders ) ) : ?>
								<tr><td colspan="8" class="nrtok-rep-table-empty"><?php esc_html_e( 'No completed orders recorded on this date.', 'nahian-restaurant-ordering-system' ); ?></td></tr>
							<?php endif; ?>
							<?php
							foreach ( $day_orders as $do ) :
								$do_items     = json_decode( $do->items, true );
								$item_summary = array();
								if ( is_array( $do_items ) ) {
									foreach ( $do_items as $it ) {
										$item_summary[] = absint( $it['qty'] ?? 1 ) . 'x ' . ( $it['name'] ?? '' );
									}
								}
								$inv_url = add_query_arg( 'view_order', absint( $do->id ), $base_orders_url );
								?>
							<tr style="transition:background 0.15s;">
								<td style="padding:14px 18px;"><strong>#<?php echo absint( $do->id ); ?></strong></td>
								<td style="padding:14px 14px;" class="nrtok-rep-do-time"><?php echo esc_html( wp_date( 'h:i A', strtotime( $do->created_at ) ) ); ?></td>
								<td style="padding:14px 14px;"><span class="nrtok-rep-do-tbl-badge"><?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?> <?php echo esc_html( $do->table_no ); ?></span></td>
								<td style="padding:14px 14px;" class="nrtok-rep-do-items"><?php echo esc_html( implode( ', ', $item_summary ) ); ?></td>
								<td style="padding:14px 14px; font-family:monospace;"><?php echo esc_html( $cur . number_format( (float) $do->subtotal, 2 ) ); ?></td>
								<td style="padding:14px 14px; font-family:monospace;"><?php echo esc_html( $cur . number_format( (float) $do->tax_amount, 2 ) ); ?></td>
								<td style="padding:14px 14px;"><strong class="nrtok-rep-gross-val"><?php echo esc_html( $cur . number_format( (float) $do->total_amount, 2 ) ); ?></strong></td>
								<td style="padding:14px 18px; text-align:right;">
									<a href="<?php echo esc_url( $inv_url ); ?>" class="nrtok-rep-act-btn">
										<?php echo $svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<span><?php esc_html_e( 'Invoice', 'nahian-restaurant-ordering-system' ); ?></span>
									</a>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

		<?php else : ?>
			<!-- ============================================================== -->
			<!-- MAIN REPORTS OVERVIEW (FULL WIDTH) -->
			<!-- ============================================================== -->
			<div class="nrtok-card nrtok-full-card">
				<div class="nrtok-rep-presets-row">
					<div class="nrtok-rep-presets" style="margin:0;">
						<a href="<?php echo esc_url( add_query_arg( array_merge( array( 'from' => $today, 'to' => $today ), $shared_filter_args ), $base_reports_url ) ); ?>" class="nrtok-rep-preset-btn <?php echo ( $from === $today && $to === $today ) ? 'active' : ''; ?>"><?php esc_html_e( 'Today', 'nahian-restaurant-ordering-system' ); ?></a>
						<a href="<?php echo esc_url( add_query_arg( array_merge( array( 'from' => $last7_start, 'to' => $today ), $shared_filter_args ), $base_reports_url ) ); ?>" class="nrtok-rep-preset-btn <?php echo ( $from === $last7_start && $to === $today ) ? 'active' : ''; ?>"><?php esc_html_e( 'Last 7 Days', 'nahian-restaurant-ordering-system' ); ?></a>
						<a href="<?php echo esc_url( add_query_arg( array_merge( array( 'from' => $month_start, 'to' => $today ), $shared_filter_args ), $base_reports_url ) ); ?>" class="nrtok-rep-preset-btn <?php echo ( $from === $month_start && $to === $today ) ? 'active' : ''; ?>"><?php esc_html_e( 'This Month', 'nahian-restaurant-ordering-system' ); ?></a>
						<a href="<?php echo esc_url( add_query_arg( array_merge( array( 'from' => $prev_m_start, 'to' => $prev_m_end ), $shared_filter_args ), $base_reports_url ) ); ?>" class="nrtok-rep-preset-btn <?php echo ( $from === $prev_m_start && $to === $prev_m_end ) ? 'active' : ''; ?>"><?php esc_html_e( 'Last Month', 'nahian-restaurant-ordering-system' ); ?></a>
					</div>

					<?php if ( $has_extra_filters ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array( 'from' => $from, 'to' => $to ), $base_reports_url ) ); ?>" class="nrtok-btn-secondary nrtok-rep-btn-icon" style="padding:6px 12px; font-size:12px;">
							<?php echo $svg_reset; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'Reset Filters', 'nahian-restaurant-ordering-system' ); ?></span>
						</a>
					<?php endif; ?>
				</div>

				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="nrtok-rep-filter-grid">
					<input type="hidden" name="page" value="nahian-restaurant-ordering-system">
					<input type="hidden" name="tab" value="reports">

					<div class="nrtok-form-group">
						<label class="nrtok-label"><?php esc_html_e( 'From Date', 'nahian-restaurant-ordering-system' ); ?></label>
						<div class="nrtok-input-wrap">
							<span class="nrtok-input-prefix"><?php echo $svg_calendar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<input type="date" name="from" class="nrtok-input has-prefix" value="<?php echo esc_attr( $from ); ?>" required>
						</div>
					</div>

					<div class="nrtok-form-group">
						<label class="nrtok-label"><?php esc_html_e( 'To Date', 'nahian-restaurant-ordering-system' ); ?></label>
						<div class="nrtok-input-wrap">
							<span class="nrtok-input-prefix"><?php echo $svg_calendar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<input type="date" name="to" class="nrtok-input has-prefix" value="<?php echo esc_attr( $to ); ?>" required>
						</div>
					</div>

					<div class="nrtok-form-group">
						<label class="nrtok-label"><?php esc_html_e( 'Filter by Table', 'nahian-restaurant-ordering-system' ); ?></label>
						<div class="nrtok-input-wrap">
							<span class="nrtok-input-prefix"><?php echo $svg_table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<select name="filter_table" class="nrtok-input has-prefix">
								<option value="all" <?php selected( $filter_table, 'all' ); ?>><?php esc_html_e( 'All Tables', 'nahian-restaurant-ordering-system' ); ?></option>
								<?php foreach ( $table_options as $t_opt ) : ?>
									<option value="<?php echo esc_attr( $t_opt ); ?>" <?php selected( $filter_table, $t_opt ); ?>>
										<?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?> <?php echo esc_html( $t_opt ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="nrtok-form-group">
						<label class="nrtok-label"><?php esc_html_e( 'Min Bill Amount', 'nahian-restaurant-ordering-system' ); ?></label>
						<div class="nrtok-input-wrap">
							<span class="nrtok-input-prefix"><?php echo $svg_coins; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<input type="number" step="0.01" min="0" name="min_amount" class="nrtok-input has-prefix" value="<?php echo $min_amount > 0 ? esc_attr( $min_amount ) : ''; ?>" placeholder="0.00">
						</div>
					</div>

					<div class="nrtok-form-group">
						<label class="nrtok-label"><?php esc_html_e( 'Sort Breakdown', 'nahian-restaurant-ordering-system' ); ?></label>
						<div class="nrtok-input-wrap">
							<span class="nrtok-input-prefix"><?php echo $svg_sort; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<select name="sort_by" class="nrtok-input has-prefix">
								<option value="date_desc" <?php selected( $sort_by, 'date_desc' ); ?>><?php esc_html_e( 'Latest Date First', 'nahian-restaurant-ordering-system' ); ?></option>
								<option value="date_asc" <?php selected( $sort_by, 'date_asc' ); ?>><?php esc_html_e( 'Oldest Date First', 'nahian-restaurant-ordering-system' ); ?></option>
								<option value="rev_desc" <?php selected( $sort_by, 'rev_desc' ); ?>><?php esc_html_e( 'Highest Revenue', 'nahian-restaurant-ordering-system' ); ?></option>
								<option value="orders_desc" <?php selected( $sort_by, 'orders_desc' ); ?>><?php esc_html_e( 'Most Orders', 'nahian-restaurant-ordering-system' ); ?></option>
							</select>
						</div>
					</div>

					<div class="nrtok-rep-filter-actions">
						<button type="submit" class="nrtok-btn nrtok-rep-btn-icon nrtok-rep-generate-btn">
							<?php echo $svg_chart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'Apply', 'nahian-restaurant-ordering-system' ); ?></span>
						</button>
					</div>
				</form>
			</div>

			<!-- Financial Summary KPIs -->
			<div class="nrtok-rep-kpi-grid" id="nrtok-rep-kpi-source">
				<div class="nrtok-rep-kpi-card nrtok-rep-border-green">
					<div class="nrtok-rep-kpi-lbl"><?php esc_html_e( 'Gross Revenue', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-rep-kpi-num nrtok-rep-num-green"><?php echo esc_html( $cur . number_format( $revenue, 2 ) ); ?></div>
					<div class="nrtok-rep-kpi-sub"><?php esc_html_e( 'Total paid bills collected', 'nahian-restaurant-ordering-system' ); ?></div>
				</div>

				<div class="nrtok-rep-kpi-card nrtok-rep-border-blue">
					<div class="nrtok-rep-kpi-lbl"><?php esc_html_e( 'Net Food Sales', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-rep-kpi-num" style="font-family:monospace;"><?php echo esc_html( $cur . number_format( $sub_rev, 2 ) ); ?></div>
					<div class="nrtok-rep-kpi-sub"><?php esc_html_e( 'Excluding VAT / tax', 'nahian-restaurant-ordering-system' ); ?></div>
				</div>

				<div class="nrtok-rep-kpi-card nrtok-rep-border-amber">
					<div class="nrtok-rep-kpi-lbl"><?php esc_html_e( 'VAT / Tax Collected', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-rep-kpi-num" style="font-family:monospace;"><?php echo esc_html( $cur . number_format( $total_tax, 2 ) ); ?></div>
					<div class="nrtok-rep-kpi-sub">
						<?php
						printf(
							/* translators: %s: tax rate percentage */
							esc_html__( 'Tax rate: %s%%', 'nahian-restaurant-ordering-system' ),
							esc_html( (float) ( $s['tax_percent'] ?? 0 ) )
						);
						?>
					</div>
				</div>

				<div class="nrtok-rep-kpi-card nrtok-rep-border-orange">
					<div class="nrtok-rep-kpi-lbl"><?php esc_html_e( 'Completed Orders', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-rep-kpi-num"><?php echo absint( $total_orders ); ?></div>
					<div class="nrtok-rep-kpi-sub">
						<?php esc_html_e( 'Avg. ticket:', 'nahian-restaurant-ordering-system' ); ?>
						<strong style="font-family:monospace;"><?php echo esc_html( $cur . number_format( $avg_ticket, 2 ) ); ?></strong>
					</div>
				</div>
			</div>

			<!-- Daily Sales Breakdown & Top Dishes -->
			<div class="nrtok-rep-split-grid">
				<!-- Daily Breakdown Table -->
				<div class="nrtok-card nrtok-full-card" style="margin:0;">
					<div class="nrtok-rep-card-head">
						<h3 class="nrtok-rep-card-title">
							<span class="nrtok-rep-title-ico"><?php echo $svg_calendar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span><?php esc_html_e( 'Daily Sales Breakdown', 'nahian-restaurant-ordering-system' ); ?></span>
						</h3>
						<span class="nrtok-rep-days-cnt">
							<?php
							printf(
								/* translators: %d: number of active days */
								esc_html__( '%d active day(s)', 'nahian-restaurant-ordering-system' ),
								absint( count( $daily_rows ) )
							);
							?>
						</span>
					</div>

					<div class="nrtok-rep-table-wrap">
						<table class="nrtok-table nrtok-full-table nrtok-rep-inner-table" id="nrtok-rep-daily-table">
							<thead>
								<tr style="background:#f8fafc; border-bottom:1.5px solid #e2e8f0;">
									<th style="padding:15px 18px;"><?php esc_html_e( 'Date', 'nahian-restaurant-ordering-system' ); ?></th>
									<th style="padding:15px 14px;"><?php esc_html_e( 'Orders', 'nahian-restaurant-ordering-system' ); ?></th>
									<th style="padding:15px 14px;"><?php esc_html_e( 'Net Sales', 'nahian-restaurant-ordering-system' ); ?></th>
									<th style="padding:15px 14px;"><?php esc_html_e( 'Tax', 'nahian-restaurant-ordering-system' ); ?></th>
									<th style="padding:15px 14px;"><?php esc_html_e( 'Gross Total', 'nahian-restaurant-ordering-system' ); ?></th>
									<th class="nrtok-col-rep-details" style="padding:15px 18px;"><?php esc_html_e( 'Details', 'nahian-restaurant-ordering-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $daily_rows ) ) : ?>
									<tr><td colspan="6" class="nrtok-rep-table-empty"><?php esc_html_e( 'No completed orders in this date range.', 'nahian-restaurant-ordering-system' ); ?></td></tr>
								<?php endif; ?>
								<?php
								foreach ( $daily_rows as $dr ) :
									$day_view_url = add_query_arg(
										array_merge(
											array(
												'from'      => $from,
												'to'        => $to,
												'view_date' => $dr->sale_date,
											),
											$shared_filter_args
										),
										$base_reports_url
									);
									?>
								<tr>
									<td style="padding:14px 18px;">
										<a href="<?php echo esc_url( $day_view_url ); ?>" class="nrtok-rep-date-link">
											<?php echo esc_html( wp_date( 'M d, Y (D)', strtotime( $dr->sale_date ) ) ); ?>
										</a>
									</td>
									<td style="padding:14px 14px;"><strong><?php echo absint( $dr->ord_count ); ?></strong></td>
									<td style="padding:14px 14px; font-family:monospace;"><?php echo esc_html( $cur . number_format( (float) $dr->net_sales, 2 ) ); ?></td>
									<td style="padding:14px 14px; font-family:monospace;"><?php echo esc_html( $cur . number_format( (float) $dr->tax_sum, 2 ) ); ?></td>
									<td style="padding:14px 14px;"><strong class="nrtok-rep-gross-val"><?php echo esc_html( $cur . number_format( (float) $dr->gross_sales, 2 ) ); ?></strong></td>
									<td style="padding:14px 18px; text-align:right;">
										<a href="<?php echo esc_url( $day_view_url ); ?>" class="nrtok-rep-act-btn">
											<?php echo $svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											<span><?php esc_html_e( 'View', 'nahian-restaurant-ordering-system' ); ?></span>
										</a>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Top Selling Items in Period -->
				<div class="nrtok-card nrtok-full-card" style="margin:0;">
					<div class="nrtok-rep-card-head">
						<h3 class="nrtok-rep-card-title">
							<span class="nrtok-rep-title-ico"><?php echo $svg_award; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span><?php esc_html_e( 'Top Dishes (Selected Period)', 'nahian-restaurant-ordering-system' ); ?></span>
						</h3>
					</div>

					<?php if ( empty( $top_dishes ) ) : ?>
						<p class="nrtok-rep-empty-dishes"><?php esc_html_e( 'No item sales data available.', 'nahian-restaurant-ordering-system' ); ?></p>
					<?php else : ?>
						<div class="nrtok-rep-top-list">
							<?php
							$rank = 1;
							foreach ( $top_dishes as $d_name => $st ) :
								$rev_pct = max( 8, round( ( (float) $st['rev'] / $max_dish_rev ) * 100 ) );
								?>
							<div class="nrtok-rep-top-row">
								<div class="nrtok-rep-top-main">
									<div class="nrtok-rep-top-left">
										<span class="nrtok-rep-rank-badge <?php echo ( 1 === $rank ) ? 'rank-1' : ''; ?>">
											#<?php echo absint( $rank++ ); ?>
										</span>
										<div>
											<strong class="nrtok-rep-dish-name"><?php echo esc_html( $d_name ); ?></strong>
											<small class="nrtok-rep-dish-qty">
												<?php
												printf(
													/* translators: %d: units sold */
													esc_html__( '%d units sold', 'nahian-restaurant-ordering-system' ),
													absint( $st['qty'] )
												);
												?>
											</small>
										</div>
									</div>
									<strong class="nrtok-rep-dish-rev"><?php echo esc_html( $cur . number_format( (float) $st['rev'], 2 ) ); ?></strong>
								</div>
								<div class="nrtok-rep-dish-bar-bg">
									<div class="nrtok-rep-dish-bar-fill" style="width: <?php echo absint( $rev_pct ); ?>%;"></div>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<script>
	jQuery(document).ready(function($) {
		const restName     = <?php echo wp_json_encode( $rest_name ); ?>;
		const restAddr     = <?php echo wp_json_encode( $rest_addr ); ?>;
		const restPhone    = <?php echo wp_json_encode( $rest_phn ); ?>;
		const reportPeriod = <?php echo wp_json_encode( ! empty( $view_date ) ? wp_date( 'F d, Y (l)', strtotime( $view_date ) ) : wp_date( 'M d, Y', strtotime( $from ) ) . ' - ' . wp_date( 'M d, Y', strtotime( $to ) ) ); ?>;
		const filterNotes  = <?php echo wp_json_encode( implode( ' | ', $active_filter_txt ) ); ?>;
		const csvFilename  = <?php echo wp_json_encode( ! empty( $view_date ) ? 'daily-sales-' . $view_date . '.csv' : 'sales-report-' . $from . '-to-' . $to . '.csv' ); ?>;

		function escHtml(str) {
			return String(str === null || str === undefined ? '' : str)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		}

		// Clean A4/Letter Financial Report Print Handler
		$('#nrtok-rep-print-btn').on('click', function() {
			const tableSelector = $('#nrtok-rep-export-csv').data('target') || '#nrtok-rep-daily-table';

			// Build KPI summary strip
			let kpiHtml = '<div style="display:flex; gap:12px; margin:14px 0;">';
			$('#nrtok-rep-kpi-source .nrtok-rep-kpi-card').each(function() {
				const lbl = $(this).find('.nrtok-rep-kpi-lbl').text().trim();
				const num = $(this).find('.nrtok-rep-kpi-num').text().trim();
				kpiHtml += '<div style="flex:1; border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px;">' +
					'<div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase;">' + escHtml(lbl) + '</div>' +
					'<div style="font-size:16px; font-weight:800; color:#0f172a; margin-top:4px;">' + escHtml(num) + '</div>' +
				'</div>';
			});
			kpiHtml += '</div>';

			// Clone table without the last Action/Details column
			const $clonedTable = $(tableSelector).clone();
			$clonedTable.find('tr').each(function() {
				$(this).find('th:last-child, td:last-child').remove();
			});

			const contactLine = [restAddr, restPhone].filter(Boolean).join(' • ');
			const filterLine  = filterNotes ? '<div style="font-size:11px; color:#475569; margin-top:3px;">Filters: ' + escHtml(filterNotes) + '</div>' : '';

			const printMarkup =
				'<div style="border-bottom:2px solid #0f172a; padding-bottom:12px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:flex-end;">' +
					'<div>' +
						'<h2 style="margin:0; font-size:20px; font-weight:900;">' + escHtml(restName) + '</h2>' +
						(contactLine ? '<div style="font-size:11px; color:#475569; margin-top:2px;">' + escHtml(contactLine) + '</div>' : '') +
					'</div>' +
					'<div style="text-align:right;">' +
						'<div style="font-size:14px; font-weight:800;">SALES &amp; TAX STATEMENT</div>' +
						'<div style="font-size:11.5px; color:#334155;">Period: <strong>' + escHtml(reportPeriod) + '</strong></div>' +
						filterLine +
					'</div>' +
				'</div>' +
				kpiHtml +
				$clonedTable.prop('outerHTML') +
				'<div style="margin-top:18px; font-size:10.5px; color:#64748b; text-align:right;">Generated on ' + escHtml(new Date().toLocaleString()) + '</div>';

			if ($('#nrtok-print-area').length === 0) {
				$('body').append('<div id="nrtok-print-area"></div>');
			}
			$('body').addClass('nrtok-printing-report');
			$('#nrtok-print-area').html(printMarkup);

			window.print();

			setTimeout(function() {
				$('body').removeClass('nrtok-printing-report');
			}, 500);
		});

		$('#nrtok-rep-export-csv').on('click', function() {
			const tableSelector = $(this).data('target') || '#nrtok-rep-daily-table';
			const csv = [];
			$(tableSelector + ' tr').each(function() {
				const row = [];
				$(this).find('th, td').not(':last-child').each(function() {
					const txt = $(this).text().trim().replace(/\s+/g, ' ').replace(/"/g, '""');
					row.push('"' + txt + '"');
				});
				if (row.length > 0) {
					csv.push(row.join(','));
				}
			});

			// Prepend UTF-8 BOM (\uFEFF) so Excel renders currency symbols (৳, €, £, ₹) cleanly.
			const blob = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
			const url  = URL.createObjectURL(blob);
			const a    = document.createElement('a');
			a.href     = url;
			a.download = csvFilename;
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);

			if (window.nrtokToast) {
				window.nrtokToast(<?php echo wp_json_encode( __( 'CSV report exported', 'nahian-restaurant-ordering-system' ) ); ?>, 'success');
			}
		});
	});
	</script>
	<?php
}