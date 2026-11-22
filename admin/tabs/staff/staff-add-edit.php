<?php
/**
 * Nahian POS Add / Edit Staff Member Form View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$base_staff_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=staff' );
$all_staff_url  = add_query_arg( 'subtab', 'all', $base_staff_url );

$is_editing   = ! empty( $edit_staff );
$staff_id     = $is_editing ? absint( $edit_staff->id ) : 0;
$staff_name   = $is_editing ? ( $edit_staff->name ?? '' ) : '';
$staff_role   = $is_editing ? ( $edit_staff->role ?? 'Waiter' ) : 'Waiter';
$staff_email  = ( $is_editing && isset( $edit_staff->email ) ) ? $edit_staff->email : '';
$staff_phone  = $is_editing ? ( $edit_staff->phone ?? '' ) : '';
$staff_status = $is_editing ? ( $edit_staff->status ?? 'Active' ) : 'Active';

// Decode existing permissions if editing.
$saved_perms = ( $is_editing && ! empty( $edit_staff->permissions ) ) ? json_decode( $edit_staff->permissions, true ) : array();
if ( ! is_array( $saved_perms ) ) {
	$saved_perms = array();
}

// Define available system tabs and actions.
$pos_menus = array(
	'dashboard'  => __( 'Dashboard', 'nahian-restaurant-ordering-system' ),
	'orders'     => __( 'Orders & Kitchen KOT', 'nahian-restaurant-ordering-system' ),
	'tables'     => __( 'Table Management', 'nahian-restaurant-ordering-system' ),
	'items'      => __( 'Menu Items & Dishes', 'nahian-restaurant-ordering-system' ),
	'categories' => __( 'Categories', 'nahian-restaurant-ordering-system' ),
	'extras'     => __( 'Extras / Add-ons', 'nahian-restaurant-ordering-system' ),
	'reports'    => __( 'Sales Reports', 'nahian-restaurant-ordering-system' ),
	'staff'      => __( 'Staff Management', 'nahian-restaurant-ordering-system' ),
	'settings'   => __( 'System Settings', 'nahian-restaurant-ordering-system' ),
);
$actions   = array(
	'view'   => __( 'View', 'nahian-restaurant-ordering-system' ),
	'add'    => __( 'Add', 'nahian-restaurant-ordering-system' ),
	'edit'   => __( 'Edit', 'nahian-restaurant-ordering-system' ),
	'delete' => __( 'Delete', 'nahian-restaurant-ordering-system' ),
);

$roles = ( isset( $roles ) && is_array( $roles ) ) ? $roles : array( 'Manager', 'Head Chef', 'Kitchen Staff', 'Waiter', 'Cashier' );

// SVG Icons (using currentColor so they dynamically highlight on focus).
$svg_users = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$svg_edit  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$svg_user  = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
$svg_brief = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>';
$svg_email = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
$svg_phone = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
$svg_lock  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
$svg_pulse = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>';
$svg_check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$svg_eye   = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
?>

<div class="nrtok-card nrtok-full-card">
	<div class="nrtok-form-header">
		<h3>
			<span class="nrtok-stf-title-ico"><?php echo $is_editing ? $svg_edit : $svg_users; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php if ( $is_editing ) : ?>
				<span>
					<?php
					printf(
						/* translators: %s: staff member name */
						esc_html__( 'Edit Staff: %s', 'nahian-restaurant-ordering-system' ),
						esc_html( $staff_name )
					);
					?>
				</span>
			<?php else : ?>
				<span><?php esc_html_e( 'Add New Staff Member', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php endif; ?>
		</h3>
		<p><?php esc_html_e( 'Enter team member details, login credentials, role, duty status, and granular menu permissions.', 'nahian-restaurant-ordering-system' ); ?></p>
	</div>

	<form method="post" action="<?php echo esc_url( $all_staff_url ); ?>" id="nrtok-stf-staff-form" data-is-editing="<?php echo $is_editing ? '1' : '0'; ?>">
		<?php wp_nonce_field( 'nrtok_save_staff_action' ); ?>
		<input type="hidden" name="edit_staff_id" value="<?php echo $staff_id > 0 ? absint( $staff_id ) : ''; ?>">

		<!-- Row 1: Full Name & Role / Position -->
		<div class="nrtok-stf-form-grid">
			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Full Name', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $svg_user; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="text" name="staff_name" class="nrtok-input has-prefix" placeholder="<?php esc_attr_e( 'e.g. Rahim Uddin', 'nahian-restaurant-ordering-system' ); ?>" value="<?php echo esc_attr( $staff_name ); ?>" required>
				</div>
			</div>

			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Role / Position', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
					<button type="button" id="nrtok-apply-role-preset" class="nrtok-perm-toggle-all" title="<?php esc_attr_e( 'Auto-fill recommended permissions for this role', 'nahian-restaurant-ordering-system' ); ?>"><?php esc_html_e( 'Apply Role Preset', 'nahian-restaurant-ordering-system' ); ?></button>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $svg_brief; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<select name="staff_role" id="nrtok_staff_role" class="nrtok-input has-prefix" required aria-label="<?php esc_attr_e( 'Role / Position', 'nahian-restaurant-ordering-system' ); ?>">
						<?php foreach ( $roles as $r ) : ?>
							<option value="<?php echo esc_attr( $r ); ?>" <?php selected( $staff_role, $r ); ?>><?php echo esc_html( $r ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>

		<!-- Row 2: Email & Phone -->
		<div class="nrtok-stf-form-grid">
			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Email Address', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $svg_email; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="email" name="staff_email" class="nrtok-input has-prefix" placeholder="staff@restaurant.com" value="<?php echo esc_attr( $staff_email ); ?>" required>
				</div>
			</div>

			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Phone Number', 'nahian-restaurant-ordering-system' ); ?></span>
					<small><?php esc_html_e( '(Optional)', 'nahian-restaurant-ordering-system' ); ?></small>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $svg_phone; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="text" name="staff_phone" class="nrtok-input has-prefix" placeholder="017XXXXXXXX" value="<?php echo esc_attr( $staff_phone ); ?>">
				</div>
			</div>
		</div>

		<!-- Row 3: Password & Confirm Password -->
		<div class="nrtok-stf-form-grid">
			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Password', 'nahian-restaurant-ordering-system' ); ?></span>
					<?php if ( $is_editing ) : ?>
						<small><?php esc_html_e( '(Leave blank to keep current)', 'nahian-restaurant-ordering-system' ); ?></small>
					<?php else : ?>
						<span class="nrtok-required-star">*</span>
					<?php endif; ?>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $svg_lock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="password" name="staff_password" id="nrtok_staff_password" class="nrtok-input has-prefix has-suffix" placeholder="••••••••" <?php echo $is_editing ? '' : 'required'; ?>>
					<button type="button" class="nrtok-pass-eye js-nrtok-toggle-pass" data-target="#nrtok_staff_password" aria-label="<?php esc_attr_e( 'Show or hide password', 'nahian-restaurant-ordering-system' ); ?>">
						<?php echo $svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>
				<!-- Password Strength Meter Bar -->
				<div class="nrtok-stf-strength-wrap">
					<div class="nrtok-stf-strength-bars">
						<span class="bar bar-1"></span>
						<span class="bar bar-2"></span>
						<span class="bar bar-3"></span>
						<span class="bar bar-4"></span>
					</div>
					<span id="nrtok-stf-strength-text"><?php esc_html_e( 'Password Strength', 'nahian-restaurant-ordering-system' ); ?></span>
				</div>
			</div>

			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Confirm Password', 'nahian-restaurant-ordering-system' ); ?></span>
					<?php if ( ! $is_editing ) : ?>
						<span class="nrtok-required-star">*</span>
					<?php endif; ?>
				</label>
				<div class="nrtok-input-wrap" id="nrtok-stf-confirm-wrap">
					<span class="nrtok-input-prefix"><?php echo $svg_lock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="password" name="staff_confirm_password" id="nrtok_staff_confirm_password" class="nrtok-input has-prefix has-suffix" placeholder="••••••••" <?php echo $is_editing ? '' : 'required'; ?>>
					<button type="button" class="nrtok-pass-eye js-nrtok-toggle-pass" data-target="#nrtok_staff_confirm_password" aria-label="<?php esc_attr_e( 'Show or hide confirm password', 'nahian-restaurant-ordering-system' ); ?>">
						<?php echo $svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>
				<small id="nrtok-stf-match-error" class="nrtok-stf-match-err"><?php esc_html_e( 'Passwords do not match!', 'nahian-restaurant-ordering-system' ); ?></small>
			</div>
		</div>

		<!-- Row 4: Duty Status -->
		<div class="nrtok-form-group nrtok-stf-mb-24">
			<label class="nrtok-label"><span><?php esc_html_e( 'Duty Status', 'nahian-restaurant-ordering-system' ); ?></span></label>
			<div class="nrtok-input-wrap">
				<span class="nrtok-input-prefix"><?php echo $svg_pulse; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<select name="staff_status" class="nrtok-input has-prefix" aria-label="<?php esc_attr_e( 'Duty Status', 'nahian-restaurant-ordering-system' ); ?>">
					<option value="Active" <?php selected( $staff_status, 'Active' ); ?>><?php esc_html_e( 'Active (On Duty)', 'nahian-restaurant-ordering-system' ); ?></option>
					<option value="Off Duty" <?php selected( $staff_status, 'Off Duty' ); ?>><?php esc_html_e( 'Off Duty', 'nahian-restaurant-ordering-system' ); ?></option>
				</select>
			</div>
		</div>

		<!-- Granular Menu Permissions Matrix with iOS Switch -->
		<div class="nrtok-form-group nrtok-stf-mb-24">
			<div class="nrtok-perm-header">
				<label class="nrtok-label nrtok-perm-title"><?php esc_html_e( 'Menu Access & Action Permissions', 'nahian-restaurant-ordering-system' ); ?></label>
				<button type="button" id="nrtok-stf-select-all-perms" class="nrtok-perm-toggle-all"><?php esc_html_e( 'Select All / Deselect All', 'nahian-restaurant-ordering-system' ); ?></button>
			</div>

			<div class="nrtok-perm-table-wrapper">
				<table class="nrtok-table nrtok-perm-table">
					<thead>
						<tr>
							<th class="nrtok-perm-th-left"><?php esc_html_e( 'POS Module / Menu', 'nahian-restaurant-ordering-system' ); ?> <small style="font-weight:500;color:#94a3b8;">(<?php esc_html_e( 'Click row name to toggle all', 'nahian-restaurant-ordering-system' ); ?>)</small></th>
							<?php foreach ( $actions as $act_key => $act_label ) : ?>
								<th class="nrtok-perm-th-center"><?php echo esc_html( $act_label ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pos_menus as $menu_slug => $menu_name ) : ?>
							<tr data-menu="<?php echo esc_attr( $menu_slug ); ?>">
								<td class="nrtok-perm-td-name js-nrtok-toggle-row" title="<?php esc_attr_e( 'Click to toggle all permissions for this module', 'nahian-restaurant-ordering-system' ); ?>"><?php echo esc_html( $menu_name ); ?></td>
								<?php
								foreach ( $actions as $act_key => $act_label ) :
									$is_checked = isset( $saved_perms[ $menu_slug ][ $act_key ] ) && '1' === (string) $saved_perms[ $menu_slug ][ $act_key ];
									?>
									<td class="nrtok-perm-td-center">
										<label class="nrtok-switch" title="<?php echo esc_attr( sprintf( /* translators: %s: action label */ __( 'Toggle %s', 'nahian-restaurant-ordering-system' ), $act_label ) ); ?>">
											<input type="checkbox" data-menu="<?php echo esc_attr( $menu_slug ); ?>" data-action="<?php echo esc_attr( $act_key ); ?>" name="permissions[<?php echo esc_attr( $menu_slug ); ?>][<?php echo esc_attr( $act_key ); ?>]" value="1" <?php checked( $is_checked, true ); ?>>
											<span class="nrtok-slider"></span>
										</label>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="nrtok-stf-form-footer">
			<button type="submit" name="nrtok_save_staff" id="nrtok-stf-submit-btn" class="nrtok-btn nrtok-stf-btn-icon">
				<?php echo $svg_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo $is_editing ? esc_html__( 'Update Staff Member', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Save Staff Member', 'nahian-restaurant-ordering-system' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $all_staff_url ); ?>" class="nrtok-btn-secondary"><?php esc_html_e( 'Cancel', 'nahian-restaurant-ordering-system' ); ?></a>
		</div>
	</form>
</div>

<style>
	.nrtok-stf-title-ico { color: #f97316; display: inline-flex; }
	.nrtok-stf-mb-24 { margin-bottom: 24px; }
	.nrtok-stf-match-err { color: #dc2626; font-weight: 700; display: none; margin-top: 6px; }
	.nrtok-stf-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; width: 100%; }
	@media (max-width: 640px) { .nrtok-stf-form-grid { grid-template-columns: 1fr; gap: 0; } }

	.nrtok-form-group { margin-bottom: 20px; width: 100%; }
	.nrtok-label {
		display: flex; align-items: center; justify-content: space-between;
		font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; letter-spacing: 0.2px;
	}
	.nrtok-label small { font-weight: 500; color: #64748b; font-size: 12px; }

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
	.nrtok-input-wrap.has-error {
		border-color: #ef4444;
		box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
	}

	.nrtok-input-prefix {
		position: absolute; left: 14px; font-weight: 700; color: #94a3b8; font-size: 14px;
		pointer-events: none; display: flex; align-items: center; justify-content: center;
		transition: color 0.2s ease;
	}
	.nrtok-input-wrap:focus-within .nrtok-input-prefix { color: #f97316; }
	.nrtok-input-wrap.has-error .nrtok-input-prefix { color: #ef4444; }

	.nrtok-pass-eye {
		position: absolute; right: 12px; background: none; border: none; color: #94a3b8;
		cursor: pointer; padding: 4px; display: inline-flex; align-items: center; justify-content: center;
		transition: color 0.15s;
	}
	.nrtok-pass-eye:hover, .nrtok-pass-eye.is-visible { color: #f97316; }

	.nrtok-input {
		width: 100% !important; max-width: 100% !important;
		padding: 12px 16px; border: none !important; border-radius: 12px;
		font-size: 14px; font-weight: 500; color: #0f172a; background: transparent !important;
		box-shadow: none !important; outline: none !important; box-sizing: border-box;
	}
	.nrtok-input.has-prefix { padding-left: 44px; }
	.nrtok-input.has-suffix { padding-right: 40px; }
	.nrtok-input::placeholder { color: #94a3b8; opacity: 1; font-weight: 400; }

	select.nrtok-input {
		display: block; width: 100% !important; max-width: 100% !important;
		height: auto !important; line-height: 1.5 !important;
		appearance: none; -webkit-appearance: none; padding-right: 40px; cursor: pointer;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
		background-repeat: no-repeat !important; background-position: right 14px center !important; background-size: 16px !important;
	}

	.nrtok-required-star { color: #ef4444; font-weight: 800; margin-left: 3px; }

	/* Permissions Matrix Table */
	.nrtok-perm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
	.nrtok-perm-title { font-size: 14.5px; font-weight: 800; color: #0f172a; margin: 0; }
	.nrtok-perm-toggle-all { background: none; border: none; color: #f97316; font-size: 12px; font-weight: 750; cursor: pointer; padding: 0; }
	.nrtok-perm-toggle-all:hover { text-decoration: underline; }
	.nrtok-perm-table-wrapper { border: 1.5px solid #e2e8f0; border-radius: 12px; overflow-x: auto; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02); }
	.nrtok-perm-table { margin: 0; width: 100%; border-collapse: collapse; }
	.nrtok-perm-table thead tr { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
	.nrtok-perm-th-left { padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569; text-align: left; }
	.nrtok-perm-th-center { padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569; text-align: center; width: 110px; }
	.nrtok-perm-table tbody tr { border-bottom: 1px solid #f1f5f9; }
	.nrtok-perm-td-name { padding: 12px 16px; font-weight: 700; color: #1e293b; font-size: 13.5px; cursor: pointer; user-select: none; }
	.nrtok-perm-td-name:hover { color: #f97316; }
	.nrtok-perm-td-center { padding: 12px 16px; text-align: center; }

	/* iOS Toggle Switch */
	.nrtok-switch {
		position: relative; display: inline-block; width: 40px; height: 22px; vertical-align: middle;
	}
	.nrtok-switch input { opacity: 0; width: 0; height: 0; position: absolute; margin: 0; }
	.nrtok-slider {
		position: absolute; cursor: pointer; inset: 0;
		background-color: #cbd5e1; transition: .22s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 999px;
	}
	.nrtok-slider:before {
		position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
		background-color: white; transition: .22s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 50%;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.18);
	}
	.nrtok-switch input:checked + .nrtok-slider { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
	.nrtok-switch input:focus + .nrtok-slider { box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.18); }
	.nrtok-switch input:checked + .nrtok-slider:before { transform: translateX(18px); }

	/* Password Strength Meter */
	.nrtok-stf-strength-wrap { margin-top: 8px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
	.nrtok-stf-strength-bars { display: flex; gap: 4px; flex: 1; }
	.nrtok-stf-strength-bars .bar { height: 4px; flex: 1; background: #e2e8f0; border-radius: 2px; transition: 0.25s ease; }
	#nrtok-stf-strength-text { font-size: 11.5px; font-weight: 700; color: #64748b; white-space: nowrap; }

	.nrtok-stf-form-footer {
		margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9;
		display: flex; align-items: center; gap: 12px;
	}
	.nrtok-stf-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
</style>

<script>
jQuery(document).ready(function($) {
	const lblStrength = <?php echo wp_json_encode( __( 'Password Strength', 'nahian-restaurant-ordering-system' ) ); ?>;
	const lblVeryWeak = <?php echo wp_json_encode( __( 'Very Weak', 'nahian-restaurant-ordering-system' ) ); ?>;
	const lblWeak     = <?php echo wp_json_encode( __( 'Weak', 'nahian-restaurant-ordering-system' ) ); ?>;
	const lblGood     = <?php echo wp_json_encode( __( 'Good', 'nahian-restaurant-ordering-system' ) ); ?>;
	const lblStrong   = <?php echo wp_json_encode( __( 'Strong', 'nahian-restaurant-ordering-system' ) ); ?>;

	const rolePresets = {
		'Manager': {
			dashboard: ['view'],
			orders: ['view', 'add', 'edit', 'delete'],
			tables: ['view', 'add', 'edit', 'delete'],
			items: ['view', 'add', 'edit', 'delete'],
			categories: ['view', 'add', 'edit', 'delete'],
			extras: ['view', 'add', 'edit', 'delete'],
			reports: ['view'],
			staff: ['view', 'add', 'edit'],
			settings: ['view', 'edit']
		},
		'Head Chef': {
			dashboard: ['view'],
			orders: ['view', 'edit'],
			items: ['view', 'edit'],
			categories: ['view'],
			extras: ['view']
		},
		'Kitchen Staff': {
			orders: ['view', 'edit']
		},
		'Waiter': {
			orders: ['view', 'add', 'edit'],
			tables: ['view']
		},
		'Cashier': {
			dashboard: ['view'],
			orders: ['view', 'add', 'edit'],
			tables: ['view'],
			reports: ['view']
		}
	};

	function applyRolePreset(roleName) {
		const preset = rolePresets[roleName] || {};
		$('.nrtok-perm-table-wrapper input[type="checkbox"]').each(function() {
			const menu = $(this).data('menu');
			const act  = $(this).data('action');
			const allowed = Array.isArray(preset[menu]) && preset[menu].indexOf(act) !== -1;
			$(this).prop('checked', allowed);
		});
	}

	$('#nrtok-apply-role-preset').on('click', function() {
		applyRolePreset($('#nrtok_staff_role').val());
	});

	// Auto-apply preset when creating a new staff member
	if ($('#nrtok-stf-staff-form').data('is-editing') === 0) {
		applyRolePreset($('#nrtok_staff_role').val());
		$('#nrtok_staff_role').on('change', function() {
			applyRolePreset($(this).val());
		});
	}

	// Toggle all checkboxes in a single module row when clicking module name
	$(document).on('click', '.js-nrtok-toggle-row', function() {
		const $boxes = $(this).closest('tr').find('input[type="checkbox"]');
		const anyUnchecked = $boxes.filter(':not(:checked)').length > 0;
		$boxes.prop('checked', anyUnchecked);
	});

	// Select All / Deselect All Permissions
	let allChecked = false;
	$('#nrtok-stf-select-all-perms').on('click', function() {
		allChecked = !allChecked;
		$('.nrtok-perm-table-wrapper input[type="checkbox"]').prop('checked', allChecked);
	});

	// Toggle Password Visibility
	$(document).on('click', '.js-nrtok-toggle-pass', function() {
		const $input = $($(this).data('target'));
		if (!$input.length) return;
		const isPass = $input.attr('type') === 'password';
		$input.attr('type', isPass ? 'text' : 'password');
		$(this).toggleClass('is-visible', isPass);
	});

	// Password Strength Checker
	$('#nrtok_staff_password').on('input', function() {
		const val  = $(this).val();
		const bars = $('.nrtok-stf-strength-bars .bar');
		const txt  = $('#nrtok-stf-strength-text');

		bars.css('background', '#e2e8f0');
		if (val.length === 0) {
			txt.text(lblStrength).css('color', '#64748b');
			return;
		}

		let score = 0;
		if (val.length >= 6) score++;
		if (val.length >= 10) score++;
		if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
		if (/[^A-Za-z0-9]/.test(val)) score++;

		const colors = ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
		const labels = [lblVeryWeak, lblWeak, lblGood, lblStrong];

		for (let i = 0; i < score; i++) {
			bars.eq(i).css('background', colors[score - 1]);
		}
		txt.text(labels[score - 1] || lblWeak).css('color', colors[score - 1] || '#ef4444');
	});

	// Clear error state on typing in confirm password
	$('#nrtok_staff_confirm_password').on('input', function() {
		$('#nrtok-stf-match-error').hide();
		$('#nrtok-stf-confirm-wrap').removeClass('has-error');
	});

	// Confirm Password Matching Validation
	$('#nrtok-stf-staff-form').on('submit', function(e) {
		const pass        = $('#nrtok_staff_password').val();
		const confirmPass = $('#nrtok_staff_confirm_password').val();

		if (pass.length > 0 && pass !== confirmPass) {
			e.preventDefault();
			$('#nrtok-stf-match-error').show();
			$('#nrtok-stf-confirm-wrap').addClass('has-error');
			$('#nrtok_staff_confirm_password').focus();
		} else {
			$('#nrtok-stf-match-error').hide();
			$('#nrtok-stf-confirm-wrap').removeClass('has-error');
		}
	});
});
</script>