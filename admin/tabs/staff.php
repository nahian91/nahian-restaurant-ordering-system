<?php
/**
 * Nahian POS Staff Management Controller & Router
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Staff Management Controller & Sub-Tab Router.
 *
 * @param array $s Plugin settings array.
 */
function nrtok_view_staff( $s = array() ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;

	if ( empty( $s ) && function_exists( 'nrtok_get_settings' ) ) {
		$s = nrtok_get_settings();
	}

	$t_staff = $wpdb->prefix . 'nrtok_staff';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_staff ) ) !== $t_staff ) {
		echo '<div class="nrtok-alert nrtok-stf-db-err">' . esc_html__( 'Staff database table not found.', 'nahian-restaurant-ordering-system' ) . '</div>';
		return;
	}

	$nrtok_svg_check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
	$nrtok_svg_alert = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

	$roles = array( 'Manager', 'Head Chef', 'Kitchen Staff', 'Waiter', 'Cashier' );

	// Include Delete Handler Safely.
	$staff_dir = plugin_dir_path( __FILE__ ) . 'staff/';
	if ( is_readable( $staff_dir . 'staff-delete.php' ) ) {
		include_once $staff_dir . 'staff-delete.php';
	}

	// 1. Handle Save / Update Staff.
	if ( isset( $_POST['nrtok_save_staff'] ) && ! empty( $_POST['staff_name'] ) ) {
		check_admin_referer( 'nrtok_save_staff_action' );

		$edit_id      = ! empty( $_POST['edit_staff_id'] ) ? absint( $_POST['edit_staff_id'] ) : 0;
		$staff_name   = sanitize_text_field( wp_unslash( $_POST['staff_name'] ) );
		$raw_role     = sanitize_text_field( wp_unslash( $_POST['staff_role'] ?? 'Waiter' ) );
		$staff_role   = in_array( $raw_role, $roles, true ) ? $raw_role : 'Waiter';
		$staff_email  = sanitize_email( wp_unslash( $_POST['staff_email'] ?? '' ) );
		$staff_phone  = sanitize_text_field( wp_unslash( $_POST['staff_phone'] ?? '' ) );
		$raw_status   = sanitize_text_field( wp_unslash( $_POST['staff_status'] ?? 'Active' ) );
		$staff_status = in_array( $raw_status, array( 'Active', 'Off Duty' ), true ) ? $raw_status : 'Active';

		// Check duplicate email if provided.
		$duplicate_email = false;
		if ( '' !== $staff_email ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM %i WHERE email = %s AND id != %d LIMIT 1',
					$t_staff,
					$staff_email,
					$edit_id
				)
			);
			if ( $existing_id > 0 ) {
				$duplicate_email = true;
			}
		}

		if ( $duplicate_email ) {
			echo '<div class="nrtok-alert nrtok-stf-alert-flex" style="background:#fef2f2; border-color:#fecaca; color:#b91c1c;">' . $nrtok_svg_alert . '<span>' . esc_html__( 'Another staff member is already registered with this email address.', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			// Sanitize Granular Permissions Matrix.
			$clean_perms = array();
			if ( isset( $_POST['permissions'] ) && is_array( $_POST['permissions'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$raw_perms = wp_unslash( $_POST['permissions'] );
				foreach ( $raw_perms as $menu_slug => $acts ) {
					$safe_menu = sanitize_key( $menu_slug );
					if ( is_array( $acts ) ) {
						foreach ( $acts as $act_key => $val ) {
							$safe_act = sanitize_key( $act_key );
							if ( '1' === (string) $val ) {
								$clean_perms[ $safe_menu ][ $safe_act ] = '1';
							}
						}
					}
				}
			}
			$permissions_json = wp_json_encode( $clean_perms );

			$data    = array(
				'name'        => $staff_name,
				'role'        => $staff_role,
				'email'       => $staff_email,
				'phone'       => $staff_phone,
				'status'      => $staff_status,
				'permissions' => $permissions_json,
			);
			$formats = array( '%s', '%s', '%s', '%s', '%s', '%s' );

			// Handle Password Hashing (Only update if a new password is entered).
			$raw_pass = isset( $_POST['staff_password'] ) ? (string) wp_unslash( $_POST['staff_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( '' !== $raw_pass ) {
				$data['password'] = wp_hash_password( $raw_pass );
				$formats[]        = '%s';
			}

			if ( $edit_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $t_staff, $data, array( 'id' => $edit_id ), $formats, array( '%d' ) );
				echo '<div class="nrtok-alert nrtok-stf-alert-flex">' . $nrtok_svg_check . '<span>' . esc_html__( 'Staff profile updated successfully!', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert( $t_staff, $data, $formats );
				echo '<div class="nrtok-alert nrtok-stf-alert-flex">' . $nrtok_svg_check . '<span>' . esc_html__( 'New staff member added!', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	// 2. Handle Quick Shift Toggle.
	if ( isset( $_GET['toggle_staff'] ) ) {
		$toggle_id = absint( $_GET['toggle_staff'] );
		if ( $toggle_id > 0 ) {
			check_admin_referer( 'nrtok_toggle_staff_' . $toggle_id );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$cur_st = $wpdb->get_var(
				$wpdb->prepare( 'SELECT status FROM %i WHERE id = %d', $t_staff, $toggle_id )
			);
			if ( $cur_st ) {
				$new_st = ( 'Active' === $cur_st ) ? 'Off Duty' : 'Active';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $t_staff, array( 'status' => $new_st ), array( 'id' => $toggle_id ), array( '%s' ), array( '%d' ) );
				echo '<div class="nrtok-alert nrtok-stf-alert-flex">' . $nrtok_svg_check . '<span>' . sprintf( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					/* translators: %s: new shift status */
					esc_html__( 'Shift status updated to %s!', 'nahian-restaurant-ordering-system' ),
					'<strong>' . esc_html( $new_st ) . '</strong>'
				) . '</span></div>';
			}
		}
	}

	// Routing: 'all', 'add', or 'view'.
	$allowed_subtabs = array( 'all', 'add', 'view' );
	$raw_subtab      = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
	$subtab          = in_array( $raw_subtab, $allowed_subtabs, true ) ? $raw_subtab : 'all';
	$edit_staff      = null;
	$view_staff      = null;

	if ( isset( $_GET['edit_staff'] ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$edit_staff = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_staff, absint( $_GET['edit_staff'] ) )
		);
		$subtab     = 'add';
	} elseif ( isset( $_GET['view_staff'] ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$view_staff = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_staff, absint( $_GET['view_staff'] ) )
		);
		$subtab     = 'view';
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$staff_list   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC', $t_staff ) ) ?: array();
	$active_count = 0;
	foreach ( $staff_list as $member ) {
		if ( 'Active' === $member->status ) {
			$active_count++;
		}
	}
	?>

	<style>
		.nrtok-stf-db-err { background: #fef2f2; color: #b91c1c; padding: 16px; border-radius: 12px; font-weight: 600; }
		.nrtok-stf-alert-flex { display: flex; align-items: center; gap: 8px; }
		.nrtok-stf-full-wrap { width: 100%; box-sizing: border-box; }
		.nrtok-full-card { width: 100%; max-width: 100%; box-sizing: border-box; }
		.nrtok-full-table { width: 100%; max-width: 100%; }

		/* Form & Controls */
		.nrtok-form-header { border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 22px; }
		.nrtok-form-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
		.nrtok-form-header p { margin: 5px 0 0; font-size: 13px; color: #64748b; }
		.nrtok-form-group { margin-bottom: 18px; width: 100%; }
		.nrtok-label { display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 7px; }
		.nrtok-label small { font-weight: 500; color: #64748b; font-size: 12px; }
		.nrtok-input-wrap { position: relative; display: flex; align-items: center; width: 100%; }
		.nrtok-input-prefix { position: absolute; left: 14px; font-weight: 800; color: #64748b; font-size: 15px; pointer-events: none; display: inline-flex; align-items: center; }
		.nrtok-input {
			width: 100%; padding: 11px 15px; border: 1.5px solid #e2e8f0; border-radius: 11px;
			font-size: 14px; font-weight: 500; color: #0f172a; background-color: #f8fafc; box-sizing: border-box;
		}
		.nrtok-input.has-prefix { padding-left: 40px; }
		.nrtok-input:focus { outline: none; background-color: #fff; border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.14); }
		select.nrtok-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; }

		.nrtok-stf-form-grid { display: grid; grid-template-columns: 1.3fr 1fr; gap: 20px; width: 100%; }
		@media (max-width: 768px) { .nrtok-stf-form-grid { grid-template-columns: 1fr; } }
		.nrtok-stf-form-footer { margin-top: 22px; padding-top: 16px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; }

		/* Empty State */
		.nrtok-stf-empty-card { text-align: center; padding: 60px 20px; }
		.nrtok-stf-empty-ico { width: 56px; height: 56px; border-radius: 16px; background: #fff7ed; color: #f97316; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; }
		.nrtok-stf-empty-title { margin: 0 0 8px; font-size: 19px; }
		.nrtok-stf-empty-desc { color: #64748b; margin: 0 0 20px; font-size: 14px; }

		/* Table Columns */
		.nrtok-col-stf-role { width: 190px; }
		.nrtok-col-stf-phone { width: 180px; }
		.nrtok-col-stf-status { width: 160px; }
		.nrtok-col-stf-act { width: 295px; }

		.nrtok-stf-member-link { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; }
		.nrtok-stf-member-link:hover .nrtok-stf-member-name { color: #f97316; }
		.nrtok-stf-avatar { width: 38px; height: 38px; border-radius: 10px; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; font-weight: 800; font-size: 15px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
		.nrtok-stf-member-name { font-size: 14.5px; color: #0f172a; }
		.nrtok-stf-role-badge { background: #f1f5f9; color: #334155; padding: 4px 11px; border-radius: 8px; font-size: 12.5px; font-weight: 700; display: inline-block; }
		.nrtok-stf-phone-cell { color: #475569; font-weight: 500; }

		.nrtok-stf-status-pill { padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; }
		.nrtok-stf-pill-active { background: #d1fae5; color: #059669; }
		.nrtok-stf-pill-off { background: #f1f5f9; color: #64748b; }
		.nrtok-stf-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; margin-right: 5px; }
		.nrtok-stf-dot-active { background: #10b981; }
		.nrtok-stf-dot-off { background: #94a3b8; }

		.nrtok-stf-act-group { display: flex; gap: 7px; align-items: center; flex-wrap: wrap; }
		.nrtok-stf-act-btn { padding: 6px 11px; border-radius: 7px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
		.nrtok-act-view  { background: #eff6ff; color: #1d4ed8; }
		.nrtok-act-shift { background: #ecfdf5; color: #059669; }
		.nrtok-act-edit  { background: #f1f5f9; color: #334155; }
		.nrtok-act-del   { background: #fef2f2; color: #dc2626; }
		.nrtok-stf-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
	</style>

	<div class="nrtok-stf-full-wrap">
		<?php
		if ( is_readable( $staff_dir . 'staff-tabs.php' ) ) {
			include $staff_dir . 'staff-tabs.php';
		}

		if ( 'view' === $subtab && $view_staff && is_readable( $staff_dir . 'staff-view.php' ) ) {
			include $staff_dir . 'staff-view.php';
		} elseif ( 'add' === $subtab && is_readable( $staff_dir . 'staff-add-edit.php' ) ) {
			include $staff_dir . 'staff-add-edit.php';
		} elseif ( is_readable( $staff_dir . 'staff-list.php' ) ) {
			include $staff_dir . 'staff-list.php';
		}
		?>
	</div>
	<?php
}