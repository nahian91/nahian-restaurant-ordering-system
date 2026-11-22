<?php
/**
 * Nahian POS Admin Panel Router & Framework
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Safely include all admin tab files.
$nrtok_tabs_path = NRTOK_PATH . 'admin/tabs/';
$nrtok_tab_files = array(
	'dashboard.php',
	'counter-pos.php',
	'kds.php',
	'orders.php',
	'tables.php',
	'items.php',
	'categories.php',
	'extras.php',
	'reports.php',
	'staff.php',
	'settings.php',
);

foreach ( $nrtok_tab_files as $nrtok_tab_file ) {
	$nrtok_full_path = $nrtok_tabs_path . $nrtok_tab_file;
	if ( is_readable( $nrtok_full_path ) ) {
		require_once $nrtok_full_path;
	}
}

/**
 * Register standalone admin menu with POS capability checks.
 *
 * @return void
 */
function nrtok_register_admin_menu() {
	$cap = current_user_can( 'manage_options' ) ? 'manage_options' : 'manage_nrtok_pos';

	add_menu_page(
		__( 'Nahian POS', 'nahian-restaurant-ordering-system' ),
		__( 'Nahian POS', 'nahian-restaurant-ordering-system' ),
		$cap,
		'nahian-restaurant-ordering-system',
		'nrtok_render_standalone_admin',
		'dashicons-food',
		25
	);
}
add_action( 'admin_menu', 'nrtok_register_admin_menu' );

/**
 * Enqueue WordPress Media Uploader, styles, and localized scripts on POS admin page only.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function nrtok_admin_enqueue_assets( $hook ) {
	if ( 'toplevel_page_nrtok-pos' !== $hook ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'jquery' );

	$s             = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
	$poll_interval = ! empty( $s['poll_interval_ms'] ) ? absint( $s['poll_interval_ms'] ) : 4000;
	if ( $poll_interval < 2000 ) {
		$poll_interval = 2000;
	}

	wp_register_style( 'nrtok-admin-shell-style', false, array(), NRTOK_VERSION );
	wp_enqueue_style( 'nrtok-admin-shell-style' );
	wp_add_inline_style( 'nrtok-admin-shell-style', nrtok_get_admin_shell_css() );

	wp_register_script( 'nrtok-admin-shell', false, array( 'jquery' ), NRTOK_VERSION, true );
	wp_enqueue_script( 'nrtok-admin-shell' );

	wp_localize_script(
		'nrtok-admin-shell',
		'nrtokAdminShell',
		array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'nrtok_admin_nonce' ),
			'pollInterval'   => $poll_interval,
			'soundEnabled'   => isset( $s['sound_alerts'] ) ? (int) $s['sound_alerts'] : 1,
			'labelShowMenu'  => __( 'Show Menu', 'nahian-restaurant-ordering-system' ),
			'labelHideMenu'  => __( 'Hide Menu', 'nahian-restaurant-ordering-system' ),
			'labelSoundOn'   => __( 'Alerts On', 'nahian-restaurant-ordering-system' ),
			'labelSoundOff'  => __( 'Muted', 'nahian-restaurant-ordering-system' ),
			'txtWaiterReq'   => __( ' WAITER REQUEST: ', 'nahian-restaurant-ordering-system' ),
			'txtTable'       => __( 'Table #', 'nahian-restaurant-ordering-system' ),
			'txtCalling'     => __( ' is calling for assistance!', 'nahian-restaurant-ordering-system' ),
			'txtAttend'      => __( ' Attend & Dismiss', 'nahian-restaurant-ordering-system' ),
		)
	);

	wp_add_inline_script( 'nrtok-admin-shell', nrtok_get_admin_shell_js() );
}
add_action( 'admin_enqueue_scripts', 'nrtok_admin_enqueue_assets' );

/**
 * Retrieve cached active orders count for sidebar badge.
 *
 * @return int
 */
function nrtok_get_sidebar_pending_count() {
	$cache_key = 'nrtok_active_orders_count';
	$cached    = wp_cache_get( $cache_key, 'nrtok_pos' );

	if ( false !== $cached ) {
		return (int) $cached;
	}

	global $wpdb;
	$orders_table  = $wpdb->prefix . 'nrtok_orders';
	$pending_count = 0;

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders_table ) ) === $orders_table ) {
		$pending_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE status IN (%s, %s, %s)',
				$orders_table,
				'pending',
				'preparing',
				'served'
			)
		);
	}

	wp_cache_set( $cache_key, $pending_count, 'nrtok_pos', 15 );

	return $pending_count;
}

/**
 * Render standalone admin panel with refined UI/UX and role-based tab access.
 *
 * @return void
 */
function nrtok_render_standalone_admin() {
	if ( ! nrtok_current_user_can_pos() ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	$s        = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
	$is_admin = current_user_can( 'manage_options' );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab navigation routing.
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';

	$rest_name     = ! empty( $s['restaurant_name'] ) ? $s['restaurant_name'] : __( 'Nahian POS', 'nahian-restaurant-ordering-system' );
	$rest_logo     = ! empty( $s['restaurant_logo'] ) ? $s['restaurant_logo'] : '';
	$menu_page_url = ! empty( $s['menu_page_url'] ) ? $s['menu_page_url'] : home_url( '/menu/' );
	$pending_count = nrtok_get_sidebar_pending_count();

	$nav_items = array(
		'dashboard'  => array(
			'group' => 'operations',
			'label' => __( 'Dashboard', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_nrtok_pos',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>',
		),
		'pos'        => array(
			'group' => 'operations',
			'label' => __( 'Counter POS', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_nrtok_pos',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="18" x2="12" y2="21"/></svg>',
		),
		'kds'        => array(
			'group' => 'operations',
			'label' => __( 'Kitchen KDS', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_nrtok_pos',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>',
			'badge' => $pending_count,
		),
		'orders'     => array(
			'group' => 'operations',
			'label' => __( 'Orders', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_nrtok_pos',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
			'badge' => $pending_count,
		),
		'tables'     => array(
			'group' => 'operations',
			'label' => __( 'Tables & QR', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_nrtok_pos',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 10h16M6 10v8M18 10v8M9 14h6"/></svg>',
		),
		'items'      => array(
			'group' => 'management',
			'label' => __( 'Menu Items', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_options',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
		),
		'categories' => array(
			'group' => 'management',
			'label' => __( 'Categories', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_options',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>',
		),
		'extras'     => array(
			'group' => 'management',
			'label' => __( 'Extras & Add-ons', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_options',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>',
		),
		'reports'    => array(
			'group' => 'management',
			'label' => __( 'Sales Reports', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_options',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>',
		),
		'staff'      => array(
			'group' => 'management',
			'label' => __( 'Staff & Roles', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_options',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
		),
		'settings'   => array(
			'group' => 'management',
			'label' => __( 'Settings', 'nahian-restaurant-ordering-system' ),
			'cap'   => 'manage_options',
			'svg'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
		),
	);

	// Enforce whitelist & per-tab capability check so non-admin POS staff stay in operational views.
	if ( ! array_key_exists( $tab, $nav_items ) ) {
		$tab = 'dashboard';
	} elseif ( ! $is_admin && 'manage_options' === $nav_items[ $tab ]['cap'] ) {
		$tab = 'dashboard';
	}
	?>
	<div class="nrtok-shell">
		<div id="nrtok-sidebar-backdrop" class="nrtok-sidebar-backdrop"></div>

		<!-- Sidebar Toggle Button with Text and Dynamic SVG Icon -->
		<button type="button" id="nrtok-toggle-sidebar" class="nrtok-sidebar-toggle" title="<?php esc_attr_e( 'Toggle Menu', 'nahian-restaurant-ordering-system' ); ?>">
			<span id="nrtok-toggle-icon-wrap">
				<svg id="nrtok-icon-close" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
				<svg id="nrtok-icon-menu" class="nrtok-hidden-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
			</span>
			<span id="nrtok-toggle-text"><?php esc_html_e( 'Hide Menu', 'nahian-restaurant-ordering-system' ); ?></span>
		</button>

		<aside class="nrtok-sidebar" id="nrtok-sidebar">
			<div class="nrtok-brand">
				<?php if ( ! empty( $rest_logo ) ) : ?>
					<div class="nrtok-brand-logo has-img">
						<img src="<?php echo esc_url( $rest_logo ); ?>" alt="<?php echo esc_attr( $rest_name ); ?>">
					</div>
				<?php else : ?>
					<div class="nrtok-brand-logo"><?php echo esc_html( mb_substr( $rest_name, 0, 1 ) ); ?></div>
				<?php endif; ?>
				<div>
					<h2 class="nrtok-brand-title"><?php echo esc_html( $rest_name ); ?></h2>
					<div class="nrtok-brand-sub">
						<span class="pulse-dot"></span><?php esc_html_e( 'Live POS Terminal', 'nahian-restaurant-ordering-system' ); ?>
					</div>
				</div>
			</div>

			<nav class="nrtok-nav" aria-label="<?php esc_attr_e( 'POS Navigation', 'nahian-restaurant-ordering-system' ); ?>">
				<div class="nrtok-nav-label"><?php esc_html_e( 'Live Operations', 'nahian-restaurant-ordering-system' ); ?></div>
				<?php
				$rendered_mgmt_label = false;
				foreach ( $nav_items as $slug => $item ) :
					if ( ! $is_admin && 'manage_options' === $item['cap'] ) {
						continue;
					}
					if ( 'management' === $item['group'] && ! $rendered_mgmt_label ) :
						$rendered_mgmt_label = true;
						?>
						<div class="nrtok-nav-label nrtok-nav-label-spaced"><?php esc_html_e( 'Menu & Setup', 'nahian-restaurant-ordering-system' ); ?></div>
						<?php
					endif;

					$active = ( $tab === $slug ) ? 'active' : '';
					$url    = add_query_arg(
						array(
							'page' => 'nahian-restaurant-ordering-system',
							'tab'  => $slug,
						),
						admin_url( 'admin.php' )
					);
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $active ); ?>" data-tab="<?php echo esc_attr( $slug ); ?>">
						<span class="nrtok-nav-left">
							<?php echo $item['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG markup. ?>
							<span><?php echo esc_html( $item['label'] ); ?></span>
						</span>
						<?php if ( isset( $item['badge'] ) ) : ?>
							<span class="nrtok-nav-badge js-nrtok-active-badge" style="<?php echo ( $item['badge'] > 0 ) ? '' : 'display:none;'; ?>"><?php echo absint( $item['badge'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="nrtok-sidebar-footer">
				<button type="button" id="nrtok-sound-toggle" class="nrtok-sidebar-util-btn" title="<?php esc_attr_e( 'Toggle Alert Sound', 'nahian-restaurant-ordering-system' ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
					<span id="nrtok-sound-label"><?php esc_html_e( 'Alerts On', 'nahian-restaurant-ordering-system' ); ?></span>
				</button>
				<a href="<?php echo esc_url( $menu_page_url ); ?>" target="_blank" rel="noopener noreferrer" class="nrtok-sidebar-util-btn" title="<?php esc_attr_e( 'Open Customer Menu', 'nahian-restaurant-ordering-system' ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
					<span><?php esc_html_e( 'Tablet Menu', 'nahian-restaurant-ordering-system' ); ?></span>
				</a>
			</div>
		</aside>

		<div class="nrtok-content-wrap" id="nrtok-content-wrap">
			<main class="nrtok-main">
				<div id="nrtok-waiter-alerts" aria-live="polite"></div>
				<?php
				switch ( $tab ) {
					case 'pos':
						if ( function_exists( 'nrtok_view_counter_pos' ) ) {
							nrtok_view_counter_pos( $s );
						}
						break;
					case 'kds':
						if ( function_exists( 'nrtok_view_kds' ) ) {
							nrtok_view_kds();
						}
						break;
					case 'orders':
						if ( function_exists( 'nrtok_view_orders' ) ) {
							nrtok_view_orders( $s );
						}
						break;
					case 'tables':
						if ( function_exists( 'nrtok_view_tables' ) ) {
							nrtok_view_tables( $s );
						}
						break;
					case 'items':
						if ( function_exists( 'nrtok_view_items' ) ) {
							nrtok_view_items( $s );
						}
						break;
					case 'categories':
						if ( function_exists( 'nrtok_view_categories' ) ) {
							nrtok_view_categories();
						}
						break;
					case 'extras':
						if ( function_exists( 'nrtok_view_extras' ) ) {
							nrtok_view_extras( $s );
						}
						break;
					case 'reports':
						if ( function_exists( 'nrtok_view_reports' ) ) {
							nrtok_view_reports( $s );
						}
						break;
					case 'staff':
						if ( function_exists( 'nrtok_view_staff' ) ) {
							nrtok_view_staff( $s );
						}
						break;
					case 'settings':
						if ( function_exists( 'nrtok_view_settings' ) ) {
							nrtok_view_settings( $s );
						}
						break;
					default:
						if ( function_exists( 'nrtok_view_dashboard' ) ) {
							nrtok_view_dashboard( $s );
						}
						break;
				}
				?>
			</main>
		</div>
	</div>

	<div id="nrtok-toast-container" class="nrtok-toast-container" aria-live="polite"></div>
	<div id="nrtok-print-area"></div>
	<?php
}

/**
 * Return standalone admin shell CSS.
 *
 * @return string
 */
function nrtok_get_admin_shell_css() {
	return '
		#adminmenumain, #wpadminbar, #wpfooter, .update-nag, .notice { display: none !important; }
		html { scroll-behavior: smooth; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
		html.wp-toolbar { padding-top: 0 !important; }
		#wpcontent, #wpbody-content { margin-left: 0 !important; padding: 0 !important; }
		body { background: #f1f5f9 !important; font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; margin: 0; -webkit-font-smoothing: antialiased; }

		/* Ultra-Thin Smooth Custom Scrollbars */
		::-webkit-scrollbar { width: 5px; height: 5px; }
		::-webkit-scrollbar-track { background: transparent; }
		::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.45); border-radius: 999px; transition: background 0.2s ease; }
		::-webkit-scrollbar-thumb:hover { background: rgba(100, 116, 139, 0.75); }

		.nrtok-nav { scroll-behavior: smooth; scrollbar-width: thin; scrollbar-color: rgba(255, 255, 255, 0.14) transparent; -webkit-overflow-scrolling: touch; }
		.nrtok-nav::-webkit-scrollbar { width: 4px; }
		.nrtok-nav::-webkit-scrollbar-track { background: transparent; }
		.nrtok-nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.14); border-radius: 999px; }
		.nrtok-nav::-webkit-scrollbar-thumb:hover { background: rgba(249, 115, 22, 0.65); }

		.nrtok-shell { display: flex; min-height: 100dvh; position: relative; }
		.nrtok-hidden-icon { display: none; }

		/* Modern Sidebar UI */
		.nrtok-sidebar {
			width: 270px; background: #070b14; color: #fff;
			display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 9999;
			border-right: 1px solid rgba(255,255,255,0.08);
			transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
			box-shadow: 10px 0 30px rgba(0,0,0,0.15);
		}
		.nrtok-sidebar.sidebar-hidden { transform: translateX(-100%); }
		.nrtok-brand {
			padding: 22px 20px; border-bottom: 1px solid rgba(255,255,255,0.06);
			display: flex; align-items: center; gap: 14px;
		}
		.nrtok-brand-logo {
			width: 42px; height: 42px; border-radius: 12px;
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
			display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 21px; color: #fff;
			box-shadow: 0 6px 16px rgba(249, 115, 22, 0.45); flex-shrink: 0; overflow: hidden;
		}
		.nrtok-brand-logo.has-img { background: #ffffff; padding: 3px; box-sizing: border-box; }
		.nrtok-brand-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: 9px; }
		.nrtok-brand-title { font-size: 17px; font-weight: 850; letter-spacing: 0.3px; color: #fff; margin: 0; line-height: 1.2; }
		.nrtok-brand-sub { font-size: 11.5px; color: #94a3b8; font-weight: 600; margin-top: 3px; display: flex; align-items: center; }

		.nrtok-nav { flex: 1; padding: 16px 14px 20px; overflow-y: auto; }
		.nrtok-nav-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: 1.2px; color: #64748b; font-weight: 800; padding: 0 14px 8px; }
		.nrtok-nav-label-spaced { margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.06); }
		.nrtok-nav a {
			display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; margin-bottom: 4px;
			color: #94a3b8; text-decoration: none; border-radius: 12px; font-size: 13.5px; font-weight: 600; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.nrtok-nav a:hover { background: rgba(255,255,255,0.06); color: #f8fafc; transform: translateX(3px); }
		.nrtok-nav a.active {
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #fff; font-weight: 700;
			box-shadow: 0 6px 18px rgba(249, 115, 22, 0.35);
		}
		.nrtok-nav-left { display: flex; align-items: center; gap: 12px; }
		.nrtok-nav-badge { background: #ef4444; color: #fff; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 20px; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4); }

		.nrtok-sidebar-footer {
			padding: 14px; border-top: 1px solid rgba(255,255,255,0.08);
			display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: rgba(0,0,0,0.2);
		}
		.nrtok-sidebar-util-btn {
			display: inline-flex; align-items: center; justify-content: center; gap: 6px;
			padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,0.06);
			border: 1px solid rgba(255,255,255,0.08); color: #cbd5e1; font-size: 12px; font-weight: 700;
			text-decoration: none; cursor: pointer; transition: all 0.2s ease;
		}
		.nrtok-sidebar-util-btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
		.nrtok-sidebar-util-btn.is-muted { color: #f87171; border-color: rgba(248, 113, 113, 0.3); }

		.nrtok-content-wrap { margin-left: 270px; flex: 1; display: flex; flex-direction: column; min-width: 0; transition: margin-left 0.28s cubic-bezier(0.16, 1, 0.3, 1); }
		.nrtok-content-wrap.sidebar-hidden { margin-left: 0 !important; }

		.nrtok-sidebar-backdrop {
			display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55);
			backdrop-filter: blur(2px); z-index: 9990;
		}
		.nrtok-sidebar-backdrop.active { display: block; }

		/* Professional Fixed Toggle Button with Glassmorphism */
		.nrtok-sidebar-toggle {
			position: fixed; bottom: 20px; right: 20px;
			background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px);
			border: 1.5px solid rgba(255, 255, 255, 0.14); color: #f8fafc;
			padding: 0 16px; height: 44px; border-radius: 14px; display: inline-flex;
			align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); z-index: 100000;
			font-size: 13px; font-weight: 800; letter-spacing: 0.3px;
		}
		.nrtok-sidebar-toggle:hover {
			background: #f97316; border-color: #f97316; color: #fff;
			transform: translateY(-2px);
			box-shadow: 0 14px 28px rgba(249, 115, 22, 0.45);
		}

		.pulse-dot { height: 7px; width: 7px; background-color: #10b981; border-radius: 50%; display: inline-block; margin-right: 7px; animation: nrtokPulse 2s infinite; }
		@keyframes nrtokPulse { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }

		.nrtok-main { padding: 32px 38px 80px; max-width: 1520px; width: 100%; box-sizing: border-box; }
		.nrtok-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 26px; flex-wrap: wrap; gap: 14px; }
		.nrtok-header h1 { margin: 0; font-size: 25px; color: #0f172a; font-weight: 850; letter-spacing: -0.6px; }
		.nrtok-header p { margin: 5px 0 0; color: #64748b; font-size: 14px; font-weight: 500; }

		/* Modern Refined Card UI */
		.nrtok-card {
			background: #fff; padding: 26px; border-radius: 20px; border: 1px solid #e2e8f0;
			box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02); margin-bottom: 24px;
			transition: box-shadow 0.2s ease;
		}
		.nrtok-card:hover { box-shadow: 0 10px 30px -4px rgba(15, 23, 42, 0.07); }

		/* Buttons with Gradient & Soft Glow */
		.nrtok-btn {
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #fff; border: none;
			padding: 11px 20px; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer;
			text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;
			box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
		}
		.nrtok-btn:hover { opacity: 0.95; color: #fff; transform: translateY(-1.5px); box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4); }

		.nrtok-btn-secondary { background: #f1f5f9; color: #334155; border: 1.5px solid #cbd5e1; padding: 9px 16px; border-radius: 11px; font-weight: 700; font-size: 13.5px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
		.nrtok-btn-secondary:hover { background: #e2e8f0; color: #0f172a; border-color: #94a3b8; }

		.nrtok-btn-green { background: #10b981; color: #fff; border: none; padding: 8px 14px; border-radius: 9px; font-weight: 700; font-size: 13px; cursor: pointer; text-decoration: none; box-shadow: 0 3px 10px rgba(16, 185, 129, 0.25); }
		.nrtok-btn-dark { background: #0f172a; color: #fff; border: none; padding: 8px 14px; border-radius: 9px; font-weight: 700; font-size: 13px; cursor: pointer; }

		.nrtok-label { display: block; font-size: 13px; font-weight: 750; color: #334155; margin-bottom: 7px; letter-spacing: 0.2px; }
		.nrtok-input {
			width: 100%; padding: 12px 16px; border: 1.5px solid #cbd5e1; border-radius: 12px;
			font-size: 14.5px; font-weight: 500; transition: all 0.2s ease; box-sizing: border-box; background: #fff; color: #0f172a;
		}
		.nrtok-input:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12); background: #fff; }

		/* Modern Clean Table */
		.nrtok-table {
			width: 100%; border-collapse: collapse; background: #fff; border-radius: 18px; overflow: hidden;
			border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
		}
		.nrtok-table th { background: #f8fafc; text-align: left; padding: 15px 18px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; border-bottom: 1.5px solid #e2e8f0; }
		.nrtok-table td { padding: 15px 18px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; vertical-align: middle; }
		.nrtok-table tr:last-child td { border-bottom: none; }
		.nrtok-table tr:hover td { background: #f8fafc; }

		.nrtok-alert { background: #ecfdf5; border: 1.5px solid #a7f3d0; color: #065f46; padding: 14px 20px; border-radius: 14px; margin-bottom: 24px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08); }

		/* Global Toast Notification */
		.nrtok-toast-container { position: fixed; top: 20px; right: 20px; z-index: 100005; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
		.nrtok-toast {
			pointer-events: auto; background: #0f172a; color: #fff; padding: 13px 18px; border-radius: 12px;
			font-size: 13.5px; font-weight: 700; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.25);
			border-left: 4px solid #f97316; display: flex; align-items: center; gap: 10px;
			animation: nrtokSlideIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.nrtok-toast.is-success { border-left-color: #10b981; }
		.nrtok-toast.is-error { border-left-color: #ef4444; }
		@keyframes nrtokSlideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }

		#nrtok-waiter-alerts { margin-bottom: 24px; display: flex; flex-direction: column; gap: 12px; }
		.nrtok-waiter-call-box {
			background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: #fff; padding: 16px 24px;
			border-radius: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
			box-shadow: 0 10px 25px rgba(220, 38, 38, 0.4); animation: nrtokShake 0.45s ease-in-out;
		}
		.nrtok-waiter-info { font-size: 15px; font-weight: 750; display: inline-flex; align-items: center; gap: 9px; }
		.nrtok-waiter-badge { background: #fff; color: #dc2626; padding: 3px 10px; border-radius: 6px; margin: 0 4px; font-weight: 800; }
		.nrtok-dismiss-call {
			background: #fff; color: #dc2626; border: none; padding: 8px 16px; border-radius: 9px;
			font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); transition: 0.15s;
		}
		.nrtok-dismiss-call:hover { background: #fef2f2; transform: translateY(-1px); }
		@keyframes nrtokShake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-6px); } 75% { transform: translateX(6px); } }

		@media (max-width: 960px) {
			.nrtok-content-wrap { margin-left: 0 !important; }
			.nrtok-main { padding: 24px 18px 80px; }
		}

		#nrtok-print-area { display: none; }
		@media print {
			body * { visibility: hidden; }
			#nrtok-print-area, #nrtok-print-area * { visibility: visible; }
			#nrtok-print-area {
				display: block !important; position: fixed; left: 0; top: 0; width: 76mm;
				font-family: "Courier New", monospace; font-size: 13px; color: #000; background: #fff;
			}
			@page { margin: 0; }
		}
	';
}

/**
 * Return standalone admin shell JavaScript.
 *
 * @return string
 */
function nrtok_get_admin_shell_js() {
	return '
	jQuery(document).ready(function($) {
		const cfg = window.nrtokAdminShell || {};
		let soundMuted = localStorage.getItem("nrtok_sound_muted") === "1" || cfg.soundEnabled === 0;
		let sharedAudioCtx = null;

		// Global Toast Utility available to all POS tabs.
		window.nrtokToast = function(message, type) {
			const $container = $("#nrtok-toast-container");
			const $toast = $("<div class=\"nrtok-toast\"></div>");
			if (type === "success") $toast.addClass("is-success");
			if (type === "error") $toast.addClass("is-error");
			$toast.text(message);
			$container.append($toast);
			setTimeout(function() {
				$toast.fadeOut(250, function() { $(this).remove(); });
			}, 3200);
		};

		// Unlock Web Audio API on first user interaction.
		$(document).one("click touchstart", function() {
			try {
				const AudioCtx = window.AudioContext || window.webkitAudioContext;
				if (AudioCtx && !sharedAudioCtx) {
					sharedAudioCtx = new AudioCtx();
				}
				if (sharedAudioCtx && sharedAudioCtx.state === "suspended") {
					sharedAudioCtx.resume();
				}
			} catch (e) {}
		});

		function updateSoundUI() {
			const $btn = $("#nrtok-sound-toggle");
			if (soundMuted) {
				$btn.addClass("is-muted");
				$("#nrtok-sound-label").text(cfg.labelSoundOff || "Muted");
			} else {
				$btn.removeClass("is-muted");
				$("#nrtok-sound-label").text(cfg.labelSoundOn || "Alerts On");
			}
		}
		updateSoundUI();

		$(document).on("click", "#nrtok-sound-toggle", function() {
			soundMuted = !soundMuted;
			localStorage.setItem("nrtok_sound_muted", soundMuted ? "1" : "0");
			updateSoundUI();
			window.nrtokToast(soundMuted ? "Sound alerts muted" : "Sound alerts enabled", "success");
		});

		function updateToggleUI(isHidden) {
			if (isHidden) {
				$("#nrtok-icon-close").hide();
				$("#nrtok-icon-menu").show();
				$("#nrtok-toggle-text").text(cfg.labelShowMenu);
				$("#nrtok-sidebar-backdrop").removeClass("active");
			} else {
				$("#nrtok-icon-menu").hide();
				$("#nrtok-icon-close").show();
				$("#nrtok-toggle-text").text(cfg.labelHideMenu);
				if (window.innerWidth <= 960) {
					$("#nrtok-sidebar-backdrop").addClass("active");
				}
			}
		}

		if (localStorage.getItem("nrtok_sidebar_hidden") === "1" || window.innerWidth <= 960) {
			$("#nrtok-sidebar").addClass("sidebar-hidden");
			$("#nrtok-content-wrap").addClass("sidebar-hidden");
			updateToggleUI(true);
		} else {
			updateToggleUI(false);
		}

		$(document).on("click", "#nrtok-toggle-sidebar, #nrtok-sidebar-backdrop", function() {
			$("#nrtok-sidebar").toggleClass("sidebar-hidden");
			$("#nrtok-content-wrap").toggleClass("sidebar-hidden");
			const isHidden = $("#nrtok-sidebar").hasClass("sidebar-hidden");
			if (window.innerWidth > 960) {
				localStorage.setItem("nrtok_sidebar_hidden", isHidden ? "1" : "0");
			}
			updateToggleUI(isHidden);
		});

		let lastCallCount = 0;
		function playAlertBeep() {
			if (soundMuted) return;
			try {
				const AudioCtx = window.AudioContext || window.webkitAudioContext;
				const ctx = sharedAudioCtx || new AudioCtx();
				if (ctx.state === "suspended") ctx.resume();
				const osc = ctx.createOscillator();
				const gain = ctx.createGain();
				osc.type = "sine";
				osc.frequency.setValueAtTime(880, ctx.currentTime);
				gain.gain.setValueAtTime(0.25, ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
				osc.connect(gain);
				gain.connect(ctx.destination);
				osc.start();
				osc.stop(ctx.currentTime + 0.35);
			} catch (e) {}
		}

		function pollWaiterCalls() {
			$.post(cfg.ajaxUrl, {
				action: "nrtok_check_waiter_calls",
				nonce: cfg.nonce
			}, function(res) {
				if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
					if (res.data.length > lastCallCount) {
						playAlertBeep();
					}
					lastCallCount = res.data.length;

					const $container = $("#nrtok-waiter-alerts").empty();
					res.data.forEach(function(call) {
						const $box = $("<div class=\"nrtok-waiter-call-box\"></div>");
						const $info = $("<div class=\"nrtok-waiter-info\"></div>");
						$info.append("<svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9\"></path><path d=\"M13.73 21a2 2 0 0 1-3.46 0\"></path></svg>");
						$info.append(document.createTextNode(cfg.txtWaiterReq));

						const $badge = $("<span class=\"nrtok-waiter-badge\"></span>");
						$badge.text(cfg.txtTable + call.table_no);
						$info.append($badge);
						$info.append(document.createTextNode(cfg.txtCalling));

						const $btn = $("<button type=\"button\" class=\"nrtok-dismiss-call\"></button>");
						$btn.attr("data-id", call.id);
						$btn.append("<svg width=\"14\" height=\"14\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"3\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"20 6 9 17 4 12\"></polyline></svg>");
						$btn.append(document.createTextNode(cfg.txtAttend));

						$box.append($info, $btn);
						$container.append($box);
					});
				} else {
					lastCallCount = 0;
					$("#nrtok-waiter-alerts").empty();
				}
			});
		}

		$(document).on("click", ".nrtok-dismiss-call", function() {
			const $btn = $(this).prop("disabled", true);
			$.post(cfg.ajaxUrl, {
				action: "nrtok_dismiss_waiter_call",
				nonce: cfg.nonce,
				id: $btn.data("id")
			}, function() {
				pollWaiterCalls();
				window.nrtokToast("Waiter call dismissed", "success");
			});
		});

		pollWaiterCalls();
		setInterval(pollWaiterCalls, cfg.pollInterval || 4000);
	});
	';
}