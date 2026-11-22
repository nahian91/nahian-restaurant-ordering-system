<?php
/**
 * Nahian POS Add / Edit Restaurant Table Form View
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

$nrtok_base_tables_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=tables' );
$nrtok_all_tables_url  = add_query_arg( 'subtab', 'all', $nrtok_base_tables_url );

$is_editing   = ! empty( $edit_tbl );
$table_id     = $is_editing ? absint( $edit_tbl->id ) : 0;
$table_name   = $is_editing ? $edit_tbl->table_name : '';
$table_seats  = $is_editing ? absint( $edit_tbl->seats ) : 4;
$table_waiter = $is_editing ? $edit_tbl->assigned_waiter : '';
$table_login  = $is_editing ? $edit_tbl->table_login : '';
$table_pass   = $is_editing ? $edit_tbl->table_password : '';
$staff_list   = isset( $staff_list ) && is_array( $staff_list ) ? $staff_list : array();

// Reusable SVG Icons.
$nrtok_svg_table = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18"/><path d="M5 7v13"/><path d="M19 7v13"/><path d="M3 11h18"/></svg>';
$nrtok_svg_edit  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_users = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$nrtok_svg_user  = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
$nrtok_svg_lock  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
$nrtok_svg_key   = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>';
$nrtok_svg_check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
?>

<div class="nrtok-card nrtok-full-card nrtok-tbl-card-no-margin">
	<div class="nrtok-form-header">
		<h3>
			<span class="nrtok-tbl-title-ico"><?php echo $is_editing ? $nrtok_svg_edit : $nrtok_svg_table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php if ( $is_editing ) : ?>
				<span>
					<?php
					printf(
						/* translators: %s: table name/number */
						esc_html__( 'Edit Table #%s', 'nahian-restaurant-ordering-system' ),
						esc_html( $table_name )
					);
					?>
				</span>
			<?php else : ?>
				<span><?php esc_html_e( 'Add New Restaurant Table', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php endif; ?>
		</h3>
		<p><?php esc_html_e( 'Configure table details and assign a Waiter from staff list.', 'nahian-restaurant-ordering-system' ); ?></p>
	</div>

	<form method="post" action="<?php echo esc_url( $nrtok_all_tables_url ); ?>">
		<?php wp_nonce_field( 'nrtok_save_table_action' ); ?>
		<input type="hidden" name="edit_table_id" value="<?php echo $table_id > 0 ? absint( $table_id ) : ''; ?>">

		<div class="nrtok-tbl-two-col">
			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Table Name / Number', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $nrtok_svg_table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="text" name="table_name" id="nrtok-inp-tbl-name" class="nrtok-input has-prefix" placeholder="01" value="<?php echo esc_attr( $table_name ); ?>" required>
				</div>
			</div>

			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Seating Capacity', 'nahian-restaurant-ordering-system' ); ?></span>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $nrtok_svg_users; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="number" name="seats" class="nrtok-input has-prefix" value="<?php echo absint( $table_seats ); ?>" min="1" max="50" required>
				</div>
			</div>
		</div>

		<!-- Assign Waiter Dropdown -->
		<div class="nrtok-form-group">
			<label class="nrtok-label">
				<span><?php esc_html_e( 'Assign Waiter', 'nahian-restaurant-ordering-system' ); ?></span>
				<small><?php esc_html_e( '(From Active Staff)', 'nahian-restaurant-ordering-system' ); ?></small>
			</label>
			<div class="nrtok-input-wrap">
				<span class="nrtok-input-prefix"><?php echo $nrtok_svg_user; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<select name="assigned_waiter" class="nrtok-input has-prefix" aria-label="<?php esc_attr_e( 'Assign Waiter', 'nahian-restaurant-ordering-system' ); ?>">
					<option value=""><?php esc_html_e( '-- None / Unassigned --', 'nahian-restaurant-ordering-system' ); ?></option>
					<?php
					foreach ( $staff_list as $st ) :
						$st_name = $st->name ?? '';
						$st_role = $st->role ?? '';
						?>
						<option value="<?php echo esc_attr( $st_name ); ?>" <?php selected( $table_waiter, $st_name ); ?>>
							<?php echo esc_html( $st_name ); ?> (<?php echo esc_html( $st_role ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<!-- Login Credentials Panel -->
		<div class="nrtok-tbl-cred-panel">
			<div class="nrtok-tbl-cred-head">
				<span class="nrtok-tbl-cred-title"><?php echo $nrtok_svg_key; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php esc_html_e( 'Tablet Login Credentials', 'nahian-restaurant-ordering-system' ); ?></span></span>
				<button type="button" id="nrtok-btn-gen-tbl-cred" class="nrtok-btn-secondary nrtok-tbl-gen-btn"><?php esc_html_e( 'Auto-Generate', 'nahian-restaurant-ordering-system' ); ?></button>
			</div>
			<div class="nrtok-tbl-two-col">
				<div class="nrtok-form-group" style="margin-bottom: 0;">
					<label class="nrtok-label" style="font-size: 12px;"><?php esc_html_e( 'Username / Email', 'nahian-restaurant-ordering-system' ); ?></label>
					<div class="nrtok-input-wrap">
						<span class="nrtok-input-prefix"><?php echo $nrtok_svg_user; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<input type="text" name="table_login" id="nrtok-inp-tbl-login" class="nrtok-input has-prefix" value="<?php echo esc_attr( $table_login ); ?>" placeholder="table_01" required>
					</div>
				</div>
				<div class="nrtok-form-group" style="margin-bottom: 0;">
					<label class="nrtok-label" style="font-size: 12px;"><?php esc_html_e( 'Password', 'nahian-restaurant-ordering-system' ); ?></label>
					<div class="nrtok-input-wrap">
						<span class="nrtok-input-prefix"><?php echo $nrtok_svg_lock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<input type="text" name="table_password" id="nrtok-inp-tbl-pass" class="nrtok-input has-prefix" style="font-family: monospace;" value="<?php echo esc_attr( $table_pass ); ?>" placeholder="pos1234" required>
					</div>
				</div>
			</div>
		</div>

		<div class="nrtok-tbl-form-footer">
			<button type="submit" name="nrtok_save_table" class="nrtok-btn nrtok-tbl-btn-icon">
				<?php echo $nrtok_svg_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo $is_editing ? esc_html__( 'Update Table', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Save Table', 'nahian-restaurant-ordering-system' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $nrtok_all_tables_url ); ?>" class="nrtok-btn-secondary"><?php esc_html_e( 'Cancel', 'nahian-restaurant-ordering-system' ); ?></a>
		</div>
	</form>
</div>

<style>
	.nrtok-tbl-title-ico { color: #f97316; display: inline-flex; }
	.nrtok-required-star { color: #ef4444; font-weight: 800; margin-left: 3px; }

	.nrtok-tbl-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; width: 100%; }
	@media (max-width: 640px) {
		.nrtok-tbl-two-col { grid-template-columns: 1fr; gap: 14px; }
	}

	.nrtok-form-group { margin-bottom: 20px; width: 100%; }
	.nrtok-label {
		display: flex; align-items: center; justify-content: space-between;
		font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; letter-spacing: 0.2px;
		gap: 8px; flex-wrap: wrap;
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

	/* Credentials Panel */
	.nrtok-tbl-cred-panel {
		background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px;
		padding: 18px; margin-top: 4px;
	}
	.nrtok-tbl-cred-head {
		display: flex; justify-content: space-between; align-items: center;
		margin-bottom: 14px; gap: 10px;
	}
	.nrtok-tbl-cred-title {
		display: inline-flex; align-items: center; gap: 7px;
		font-size: 13.5px; font-weight: 800; color: #0f172a;
	}
	.nrtok-tbl-cred-title svg { color: #f97316; }
	.nrtok-tbl-gen-btn { padding: 5px 12px; font-size: 11.5px; border-radius: 8px; font-weight: 700; }

	.nrtok-tbl-form-footer {
		margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9;
		display: flex; align-items: center; gap: 12px;
	}
	.nrtok-tbl-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
</style>

<script>
jQuery(document).ready(function($) {
	$('#nrtok-btn-gen-tbl-cred').on('click', function() {
		const tName = ($('#nrtok-inp-tbl-name').val() || '01').toLowerCase().replace(/[^a-z0-9]/g, '');
		const randNum = Math.floor(1000 + Math.random() * 9000);
		$('#nrtok-inp-tbl-login').val('table_' + tName);
		$('#nrtok-inp-tbl-pass').val('pos' + randNum);
	});
});
</script>