<?php
/**
 * Nahian POS Menu Categories Controller & Router
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure extended category columns (e.g., category_order, image) exist on nrtok_categories.
 *
 * @return void
 */
function nrtok_ensure_category_cols() {
	global $wpdb;
	$t_cats = $wpdb->prefix . 'nrtok_categories';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_cats ) ) !== $t_cats ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$cols = $wpdb->get_col( "DESC {$t_cats}", 0 );

	if ( is_array( $cols ) && ! in_array( 'category_order', $cols, true ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "ALTER TABLE {$t_cats} ADD COLUMN category_order int(5) NOT NULL DEFAULT 0 AFTER name" );
	}

	if ( is_array( $cols ) && ! in_array( 'image', $cols, true ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "ALTER TABLE {$t_cats} ADD COLUMN image text AFTER category_order" );
	}
}

/**
 * Render Menu Categories Controller & Sub-Tab Router.
 *
 * @param array<string, mixed> $s Plugin settings array.
 * @return void
 */
function nrtok_view_categories( $s = array() ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;
	nrtok_ensure_category_cols();

	$t_cats = $wpdb->prefix . 'nrtok_categories';
	$t_menu = $wpdb->prefix . 'nrtok_menu';

	// Safe fallback for settings & currency if called without parameters.
	if ( empty( $s ) && function_exists( 'nrtok_get_settings' ) ) {
		$s = nrtok_get_settings();
	}
	$cur = $s['currency'] ?? '$';

	$svg_check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
	$svg_alert = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

	// Include Delete Handler Safely.
	$cats_dir = plugin_dir_path( __FILE__ ) . 'categories/';
	if ( is_readable( $cats_dir . 'category-delete.php' ) ) {
		include_once $cats_dir . 'category-delete.php';
	}

	// 1. Handle Save / Update Category.
	if ( isset( $_POST['nrtok_save_cat'] ) && ! empty( $_POST['cat_name'] ) ) {
		check_admin_referer( 'nrtok_save_cat_action' );

		$edit_id   = ! empty( $_POST['edit_cat_id'] ) ? absint( $_POST['edit_cat_id'] ) : 0;
		$new_name  = sanitize_text_field( wp_unslash( $_POST['cat_name'] ) );
		$cat_order = isset( $_POST['cat_order'] ) ? absint( $_POST['cat_order'] ) : 0;
		$cat_image = esc_url_raw( wp_unslash( $_POST['cat_image'] ?? '' ) );

		// Check duplicate category name.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$t_cats} WHERE name = %s AND id != %d LIMIT 1",
				$new_name,
				$edit_id
			)
		);

		if ( $exists > 0 ) {
			echo '<div class="nrtok-alert nrtok-alert-error nrtok-cat-alert-flex">' . $svg_alert . '<span>' . sprintf( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				/* translators: %s: category name */
				esc_html__( 'Category "%s" already exists!', 'nahian-restaurant-ordering-system' ),
				esc_html( $new_name )
			) . '</span></div>';
		} else {
			$data    = array(
				'name'           => $new_name,
				'category_order' => $cat_order,
				'image'          => $cat_image,
			);
			$formats = array( '%s', '%d', '%s' );

			if ( $edit_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$old_name = $wpdb->get_var(
					$wpdb->prepare( "SELECT name FROM {$t_cats} WHERE id = %d", $edit_id )
				);

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$t_cats,
					$data,
					array( 'id' => $edit_id ),
					$formats,
					array( '%d' )
				);

				if ( $old_name && $old_name !== $new_name ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->update(
						$t_menu,
						array( 'category' => $new_name ),
						array( 'category' => $old_name ),
						array( '%s' ),
						array( '%s' )
					);
				}
				echo '<div class="nrtok-alert nrtok-cat-alert-flex">' . $svg_check . '<span>' . esc_html__( 'Category updated successfully!', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert(
					$t_cats,
					$data,
					$formats
				);
				echo '<div class="nrtok-alert nrtok-cat-alert-flex">' . $svg_check . '<span>' . esc_html__( 'New category added!', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	// Routing: 'all', 'add', or 'view'.
	$allowed_subtabs = array( 'all', 'add', 'view' );
	$raw_subtab      = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
	$subtab          = in_array( $raw_subtab, $allowed_subtabs, true ) ? $raw_subtab : 'all';
	$edit_cat        = null;
	$view_cat        = null;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_cats_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_cats ) ) === $t_cats );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_menu_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_menu ) ) === $t_menu );

	if ( $has_cats_table ) {
		if ( isset( $_GET['edit_cat'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$edit_cat = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$t_cats} WHERE id = %d", absint( $_GET['edit_cat'] ) )
			);
			$subtab   = 'add';
		} elseif ( isset( $_GET['view_cat'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$view_cat = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$t_cats} WHERE id = %d", absint( $_GET['view_cat'] ) )
			);
			$subtab   = 'view';
		}
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$cats      = $has_cats_table ? ( $wpdb->get_results( "SELECT * FROM {$t_cats} ORDER BY category_order ASC, id DESC" ) ?: array() ) : array();
	$all_items = $has_menu_table ? ( $wpdb->get_results( "SELECT id, name, category, price, image_url, extras FROM {$t_menu} ORDER BY item_order ASC, name ASC" ) ?: array() ) : array();
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Group full dish objects by category.
	$items_by_cat = array();
	foreach ( $all_items as $itm ) {
		$items_by_cat[ $itm->category ][] = $itm;
	}
	?>

	<div class="nrtok-cat-full-wrap">
		<?php
		if ( is_readable( $cats_dir . 'category-tabs.php' ) ) {
			include $cats_dir . 'category-tabs.php';
		}

		if ( 'view' === $subtab && $view_cat && is_readable( $cats_dir . 'category-view.php' ) ) {
			include $cats_dir . 'category-view.php';
		} elseif ( 'add' === $subtab && is_readable( $cats_dir . 'category-add-edit.php' ) ) {
			include $cats_dir . 'category-add-edit.php';
		} elseif ( is_readable( $cats_dir . 'category-list.php' ) ) {
			include $cats_dir . 'category-list.php';
		}
		?>
	</div>
	<?php
}