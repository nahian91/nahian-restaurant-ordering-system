<?php
/**
 * Nahian POS Staff Management Header & Sub-Tabs Bar
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

// Reusable SVG Icons for Tabs Header.
$svg_users  = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$svg_plus   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
$svg_edit   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$svg_eye    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
$svg_back   = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>';
$svg_search = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';

$base_staff_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=staff' );
$all_staff_url  = add_query_arg( 'subtab', 'all', $base_staff_url );
$add_staff_url  = add_query_arg( 'subtab', 'add', $base_staff_url );

$active_count = isset( $active_count ) ? absint( $active_count ) : 0;
$total_staff  = is_array( $staff_list ) ? count( $staff_list ) : 0;
$roles        = ( isset( $roles ) && is_array( $roles ) ) ? $roles : array( 'Manager', 'Head Chef', 'Kitchen Staff', 'Waiter', 'Cashier' );
?>

<div class="nrtok-header">
	<div>
		<h1><?php esc_html_e( 'Staff & Shift Management', 'nahian-restaurant-ordering-system' ); ?></h1>
		<p>
			<?php
			printf(
				/* translators: %s: active staff badge */
				esc_html__( 'Manage restaurant managers, chefs, waiters, and track active duty shifts (%s).', 'nahian-restaurant-ordering-system' ),
				'<strong>' . sprintf(
					/* translators: %d: active count number */
					esc_html__( '%d Active', 'nahian-restaurant-ordering-system' ),
					absint( $active_count )
				) . '</strong>'
			);
			?>
		</p>
	</div>
</div>

<div class="nrtok-stf-topbar-row">
	<div class="nrtok-stf-subtabs">
		<a href="<?php echo esc_url( $all_staff_url ); ?>" class="nrtok-stf-subtab <?php echo ( 'all' === $subtab ) ? 'active' : ''; ?>">
			<?php echo $svg_users; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'All Staff', 'nahian-restaurant-ordering-system' ); ?></span>
			<span class="nrtok-stf-count-pill"><?php echo absint( $total_staff ); ?></span>
		</a>
		<a href="<?php echo esc_url( $add_staff_url ); ?>" class="nrtok-stf-subtab <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
			<?php if ( ! empty( $edit_staff ) ) : ?>
				<?php echo $svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Staff', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php else : ?>
				<?php echo $svg_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Staff', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php endif; ?>
		</a>
		<?php if ( 'view' === $subtab && ! empty( $view_staff ) ) : ?>
			<span class="nrtok-stf-subtab active">
				<?php echo $svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span>
					<?php
					printf(
						/* translators: %d: staff profile ID */
						esc_html__( 'Profile #%d', 'nahian-restaurant-ordering-system' ),
						absint( $view_staff->id )
					);
					?>
				</span>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( 'all' === $subtab && ! empty( $staff_list ) ) : ?>
		<div class="nrtok-stf-filter-group">
			<select id="nrtok-stf-role-filter" class="nrtok-input nrtok-stf-role-select" aria-label="<?php esc_attr_e( 'Filter by Role', 'nahian-restaurant-ordering-system' ); ?>">
				<option value="all"><?php esc_html_e( 'All Roles', 'nahian-restaurant-ordering-system' ); ?></option>
				<?php foreach ( $roles as $r ) : ?>
					<option value="<?php echo esc_attr( strtolower( $r ) ); ?>"><?php echo esc_html( $r ); ?></option>
				<?php endforeach; ?>
			</select>
			<select id="nrtok-stf-status-filter" class="nrtok-input nrtok-stf-status-select" aria-label="<?php esc_attr_e( 'Filter by Shift Status', 'nahian-restaurant-ordering-system' ); ?>">
				<option value="all"><?php esc_html_e( 'All Shifts', 'nahian-restaurant-ordering-system' ); ?></option>
				<option value="active"><?php esc_html_e( 'Active Duty', 'nahian-restaurant-ordering-system' ); ?></option>
				<option value="off duty"><?php esc_html_e( 'Off Duty', 'nahian-restaurant-ordering-system' ); ?></option>
			</select>
			<div class="nrtok-stf-search-box">
				<?php echo $svg_search; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="text" id="nrtok-stf-live-search" class="nrtok-input" placeholder="<?php esc_attr_e( 'Search name, email or phone...', 'nahian-restaurant-ordering-system' ); ?>">
			</div>
		</div>
	<?php elseif ( 'view' === $subtab ) : ?>
		<a href="<?php echo esc_url( $all_staff_url ); ?>" class="nrtok-btn-secondary nrtok-stf-btn-icon">
			<?php echo $svg_back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Back to All Staff', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	<?php endif; ?>
</div>

<style>
	.nrtok-stf-topbar-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 24px; width: 100%; }
	.nrtok-stf-subtabs { display: inline-flex; background: #e2e8f0; padding: 4px; border-radius: 12px; gap: 4px; }
	.nrtok-stf-subtab {
		padding: 9px 18px; border-radius: 9px; font-size: 13.5px; font-weight: 700; text-decoration: none;
		color: #475569; display: inline-flex; align-items: center; gap: 7px; transition: all 0.18s ease;
	}
	.nrtok-stf-subtab:hover { color: #0f172a; }
	.nrtok-stf-subtab.active { background: #fff; color: #f97316; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08); }
	.nrtok-stf-count-pill { background: #f1f5f9; color: #334155; padding: 1px 8px; border-radius: 10px; font-size: 11px; font-weight: 800; }
	.nrtok-stf-subtab.active .nrtok-stf-count-pill { background: #fff7ed; color: #ea580c; }
	.nrtok-stf-filter-group { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
	.nrtok-stf-role-select { width: 170px; padding: 9px 36px 9px 14px; background-color: #fff; }
	.nrtok-stf-status-select { width: 155px; padding: 9px 36px 9px 14px; background-color: #fff; }
	.nrtok-stf-search-box { position: relative; width: 250px; max-width: 100%; }
	.nrtok-stf-search-box svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); pointer-events: none; }
	.nrtok-stf-search-box input { padding: 9px 14px 9px 38px; background-color: #fff; }
	.nrtok-stf-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
</style>

<script>
jQuery(document).ready(function($) {
	function filterStaffRows() {
		const roleVal   = ($('#nrtok-stf-role-filter').val() || 'all').toLowerCase();
		const statusVal = ($('#nrtok-stf-status-filter').val() || 'all').toLowerCase();
		const searchVal = ($('#nrtok-stf-live-search').val() || '').toLowerCase().trim();

		$('.nrtok-stf-row').each(function() {
			const $row      = $(this);
			const rowRole   = String($row.data('role') || '').toLowerCase();
			const rowStatus = String($row.data('status') || '').toLowerCase();
			const rowText   = $row.text().toLowerCase();

			const matchRole   = (roleVal === 'all' || rowRole === roleVal);
			const matchStatus = (statusVal === 'all' || rowStatus === statusVal);
			const matchSearch = (!searchVal || rowText.indexOf(searchVal) !== -1);

			$row.toggle(matchRole && matchStatus && matchSearch);
		});
	}

	$(document).on('change', '#nrtok-stf-role-filter, #nrtok-stf-status-filter', filterStaffRows);
	$(document).on('input', '#nrtok-stf-live-search', filterStaffRows);
});
</script>