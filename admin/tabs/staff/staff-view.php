<?php
/**
 * Nahian POS Single Staff Profile & Shift Details View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) || empty( $view_staff ) ) {
	return;
}

$staff_id     = absint( $view_staff->id );
$staff_name   = $view_staff->name ?? '';
$staff_role   = $view_staff->role ?? '';
$staff_status = $view_staff->status ?? 'Active';
$staff_phone  = $view_staff->phone ?? '';
$staff_email  = $view_staff->email ?? '';

$saved_perms = ! empty( $view_staff->permissions ) ? json_decode( $view_staff->permissions, true ) : array();
if ( ! is_array( $saved_perms ) ) {
	$saved_perms = array();
}

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

$v_initial   = strtoupper( mb_substr( $staff_name, 0, 1 ) );
$v_is_active = ( 'Active' === $staff_status );

$base_staff_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=staff' );
$all_staff_url  = add_query_arg( 'subtab', 'all', $base_staff_url );

// Security Nonces for Actions.
$toggle_url = wp_nonce_url(
	add_query_arg(
		array(
			'view_staff'   => $staff_id,
			'toggle_staff' => $staff_id,
		),
		$base_staff_url
	),
	'nrtok_toggle_staff_' . $staff_id
);
$edit_url   = add_query_arg( 'edit_staff', $staff_id, $base_staff_url );
$del_url    = wp_nonce_url(
	add_query_arg(
		array(
			'subtab'    => 'all',
			'del_staff' => $staff_id,
		),
		$base_staff_url
	),
	'nrtok_delete_staff_' . $staff_id
);

$svg_edit    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$svg_trash   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
$svg_refresh = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>';
$svg_back    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>';
$svg_shield  = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
$svg_check   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$svg_minus   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>';
?>

<div class="nrtok-card nrtok-full-card">
	<div class="nrtok-stf-view-hero">
		<div class="nrtok-stf-view-hero-left">
			<div class="nrtok-stf-view-avatar"><?php echo esc_html( $v_initial ); ?></div>
			<div>
				<div class="nrtok-stf-view-id">
					<?php
					printf(
						/* translators: %d: staff ID */
						esc_html__( 'STAFF ID #%d', 'nahian-restaurant-ordering-system' ),
						absint( $staff_id )
					);
					?>
				</div>
				<h2 class="nrtok-stf-view-title"><?php echo esc_html( $staff_name ); ?></h2>
				<span class="nrtok-stf-view-role-badge"><?php echo esc_html( $staff_role ); ?></span>
			</div>
		</div>

		<div class="nrtok-stf-view-btns">
			<a href="<?php echo esc_url( $all_staff_url ); ?>" class="nrtok-btn-secondary nrtok-stf-btn-icon" title="<?php esc_attr_e( 'Back to Staff List', 'nahian-restaurant-ordering-system' ); ?>">
				<?php echo $svg_back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Back', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
			<a href="<?php echo esc_url( $toggle_url ); ?>" class="nrtok-stf-act-btn nrtok-act-shift nrtok-stf-act-lg" title="<?php esc_attr_e( 'Switch Duty Status', 'nahian-restaurant-ordering-system' ); ?>">
				<?php echo $svg_refresh; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Switch Shift Status', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
			<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-btn nrtok-stf-btn-icon" title="<?php esc_attr_e( 'Edit Profile', 'nahian-restaurant-ordering-system' ); ?>">
				<?php echo $svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Profile', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
			<a href="<?php echo esc_url( $del_url ); ?>" class="nrtok-stf-act-btn nrtok-act-del nrtok-stf-act-lg nrtok-js-confirm-del" data-name="<?php echo esc_attr( $staff_name ); ?>" title="<?php esc_attr_e( 'Remove Staff', 'nahian-restaurant-ordering-system' ); ?>">
				<?php echo $svg_trash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Remove', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
		</div>
	</div>

	<!-- Staff Info Cards -->
	<div class="nrtok-stf-view-stats">
		<div class="nrtok-stf-stat-box <?php echo $v_is_active ? 'nrtok-stf-stat-active' : 'nrtok-stf-stat-off'; ?>">
			<div class="nrtok-stf-stat-lbl"><?php esc_html_e( 'Duty Shift Status', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-stf-stat-val nrtok-stf-stat-val-flex <?php echo $v_is_active ? 'nrtok-stf-val-active' : 'nrtok-stf-val-off'; ?>">
				<span class="nrtok-stf-dot nrtok-stf-dot-lg <?php echo $v_is_active ? 'nrtok-stf-dot-active' : 'nrtok-stf-dot-off'; ?>"></span>
				<span><?php echo esc_html( $staff_status ); ?></span>
			</div>
		</div>

		<div class="nrtok-stf-stat-box nrtok-stf-stat-orange">
			<div class="nrtok-stf-stat-lbl"><?php esc_html_e( 'Assigned Role', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-stf-stat-val"><?php echo esc_html( $staff_role ); ?></div>
		</div>

		<div class="nrtok-stf-stat-box nrtok-stf-stat-blue">
			<div class="nrtok-stf-stat-lbl"><?php esc_html_e( 'Contact Phone', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-stf-stat-val">
				<?php if ( ! empty( $staff_phone ) ) : ?>
					<a href="tel:<?php echo esc_attr( $staff_phone ); ?>" class="nrtok-stf-phone-link"><?php echo esc_html( $staff_phone ); ?></a>
				<?php else : ?>
					<span class="nrtok-stf-phone-empty"><?php esc_html_e( 'Not Provided', 'nahian-restaurant-ordering-system' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="nrtok-stf-stat-box nrtok-stf-stat-purple">
			<div class="nrtok-stf-stat-lbl"><?php esc_html_e( 'Login Email', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-stf-stat-val">
				<?php if ( ! empty( $staff_email ) ) : ?>
					<a href="mailto:<?php echo esc_attr( $staff_email ); ?>" class="nrtok-stf-phone-link"><?php echo esc_html( $staff_email ); ?></a>
				<?php else : ?>
					<span class="nrtok-stf-phone-empty"><?php esc_html_e( 'Not Provided', 'nahian-restaurant-ordering-system' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Assigned Access Permissions Summary -->
	<div class="nrtok-stf-perms-section">
		<div class="nrtok-stf-perms-head">
			<span class="nrtok-stf-perms-ico"><?php echo $svg_shield; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<h3><?php esc_html_e( 'Assigned Module Permissions', 'nahian-restaurant-ordering-system' ); ?></h3>
		</div>

		<div class="nrtok-stf-perm-table-wrap">
			<table class="nrtok-table nrtok-view-perm-table">
				<thead>
					<tr>
						<th class="nrtok-view-perm-th-left"><?php esc_html_e( 'POS Module / Menu', 'nahian-restaurant-ordering-system' ); ?></th>
						<?php foreach ( $actions as $act_key => $act_label ) : ?>
							<th class="nrtok-view-perm-th-center"><?php echo esc_html( $act_label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pos_menus as $menu_slug => $menu_name ) : ?>
						<tr>
							<td class="nrtok-view-perm-td-name"><?php echo esc_html( $menu_name ); ?></td>
							<?php
							foreach ( $actions as $act_key => $act_label ) :
								$has_perm = isset( $saved_perms[ $menu_slug ][ $act_key ] ) && '1' === (string) $saved_perms[ $menu_slug ][ $act_key ];
								?>
								<td class="nrtok-view-perm-td-center">
									<?php if ( $has_perm ) : ?>
										<span class="nrtok-stf-perm-badge nrtok-perm-yes" title="<?php esc_attr_e( 'Allowed', 'nahian-restaurant-ordering-system' ); ?>"><?php echo $svg_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php else : ?>
										<span class="nrtok-stf-perm-badge nrtok-perm-no" title="<?php esc_attr_e( 'Restricted', 'nahian-restaurant-ordering-system' ); ?>"><?php echo $svg_minus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<style>
	.nrtok-stf-view-hero {
		display: flex; justify-content: space-between; align-items: center;
		flex-wrap: wrap; gap: 18px; padding-bottom: 22px;
		border-bottom: 1.5px solid #f1f5f9; margin-bottom: 24px;
	}
	.nrtok-stf-view-hero-left { display: flex; align-items: center; gap: 16px; }
	.nrtok-stf-view-avatar {
		width: 62px; height: 62px; border-radius: 18px;
		background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
		color: #ea580c; border: 1.5px solid #fed7aa;
		font-size: 24px; font-weight: 900;
		display: flex; align-items: center; justify-content: center;
		box-shadow: 0 4px 12px rgba(249, 115, 22, 0.12); flex-shrink: 0;
	}
	.nrtok-stf-view-id {
		font-size: 11px; font-weight: 800; color: #f97316;
		letter-spacing: 0.8px; text-transform: uppercase;
	}
	.nrtok-stf-view-title { margin: 3px 0 6px; font-size: 22px; font-weight: 900; color: #0f172a; }
	.nrtok-stf-view-role-badge {
		display: inline-block; padding: 4px 12px; border-radius: 8px;
		background: #f1f5f9; color: #334155; font-size: 12px; font-weight: 700;
	}

	.nrtok-stf-view-btns { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
	.nrtok-stf-btn-icon { display: inline-flex; align-items: center; gap: 6px; }
	.nrtok-stf-act-btn {
		display: inline-flex; align-items: center; gap: 6px;
		border-radius: 10px; font-weight: 700; text-decoration: none;
		transition: all 0.2s ease;
	}
	.nrtok-stf-act-lg { padding: 9px 14px; font-size: 13px; }
	.nrtok-act-shift { background: #fff7ed; color: #c2410c; border: 1.5px solid #fed7aa; }
	.nrtok-act-shift:hover { background: #ffedd5; color: #9a3412; }
	.nrtok-act-del { background: #fef2f2; color: #dc2626; border: 1.5px solid #fee2e2; }
	.nrtok-act-del:hover { background: #fee2e2; color: #b91c1c; }

	/* Stat Cards Grid */
	.nrtok-stf-view-stats {
		display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
		gap: 16px; margin-bottom: 26px;
	}
	.nrtok-stf-stat-box {
		background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px;
		padding: 18px 20px; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
	}
	.nrtok-stf-stat-active { border-left: 4px solid #10b981; }
	.nrtok-stf-stat-off { border-left: 4px solid #94a3b8; }
	.nrtok-stf-stat-orange { border-left: 4px solid #f97316; }
	.nrtok-stf-stat-blue { border-left: 4px solid #3b82f6; }
	.nrtok-stf-stat-purple { border-left: 4px solid #8b5cf6; }

	.nrtok-stf-stat-lbl {
		font-size: 11.5px; font-weight: 800; color: #64748b;
		text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;
	}
	.nrtok-stf-stat-val { font-size: 16px; font-weight: 800; color: #0f172a; word-break: break-word; }
	.nrtok-stf-stat-val-flex { display: inline-flex; align-items: center; gap: 8px; }
	.nrtok-stf-val-active { color: #047857; }
	.nrtok-stf-val-off { color: #64748b; }

	.nrtok-stf-dot { border-radius: 50%; display: inline-block; }
	.nrtok-stf-dot-lg { width: 9px; height: 9px; }
	.nrtok-stf-dot-active { background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.18); }
	.nrtok-stf-dot-off { background: #94a3b8; }

	.nrtok-stf-phone-link { color: #0f172a; text-decoration: none; transition: color 0.15s; }
	.nrtok-stf-phone-link:hover { color: #f97316; }
	.nrtok-stf-phone-empty { color: #94a3b8; font-weight: 500; font-size: 14px; }

	/* Permissions Overview */
	.nrtok-stf-perms-section { margin-top: 8px; }
	.nrtok-stf-perms-head { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
	.nrtok-stf-perms-ico { color: #f97316; display: inline-flex; }
	.nrtok-stf-perms-head h3 { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; }
	.nrtok-stf-perm-table-wrap { border: 1.5px solid #e2e8f0; border-radius: 12px; overflow-x: auto; background: #fff; }
	.nrtok-view-perm-table { margin: 0; width: 100%; border-collapse: collapse; }
	.nrtok-view-perm-table thead tr { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
	.nrtok-view-perm-th-left { padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569; text-align: left; }
	.nrtok-view-perm-th-center { padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569; text-align: center; width: 105px; }
	.nrtok-view-perm-table tbody tr { border-bottom: 1px solid #f1f5f9; }
	.nrtok-view-perm-td-name { padding: 11px 16px; font-weight: 700; color: #1e293b; font-size: 13.5px; }
	.nrtok-view-perm-td-center { padding: 11px 16px; text-align: center; }

	.nrtok-stf-perm-badge {
		display: inline-flex; align-items: center; justify-content: center;
		width: 26px; height: 26px; border-radius: 8px;
	}
	.nrtok-perm-yes { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
	.nrtok-perm-no { background: #f8fafc; color: #cbd5e1; border: 1px solid #e2e8f0; }
</style>

<script>
jQuery(document).ready(function($) {
	const confirmDelMsg = <?php echo wp_json_encode( __( 'Are you sure you want to remove "%s" from staff list?', 'nahian-restaurant-ordering-system' ) ); ?>;

	$(document).on('click', '.nrtok-js-confirm-del', function(e) {
		const staffName = $(this).data('name') || <?php echo wp_json_encode( __( 'this staff member', 'nahian-restaurant-ordering-system' ) ); ?>;
		const msg       = confirmDelMsg.replace('%s', staffName);
		if (!confirm(msg)) {
			e.preventDefault();
		}
	});
});
</script>