<?php
/**
 * Nahian POS Single Food Item Specification View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) || empty( $view_item ) ) {
	return;
}

// Safe fallback for currency if not passed.
if ( ! isset( $s ) || ! is_array( $s ) ) {
	$s = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
}
if ( ! isset( $cur ) ) {
	$cur = $s['currency'] ?? '৳';
}

$nrtok_svg_edit     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_trash    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
$nrtok_svg_sparkles = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>';
$nrtok_svg_tag      = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>';
$nrtok_svg_check    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$nrtok_svg_alert    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
$nrtok_svg_fallback = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';

$item_id  = absint( $view_item->id );
$v_ex_ids = ! empty( $view_item->extras ) ? json_decode( $view_item->extras, true ) : array();
if ( ! is_array( $v_ex_ids ) ) {
	$v_ex_ids = array();
}

$v_extras = array();
foreach ( $v_ex_ids as $eid ) {
	$eid_int = absint( $eid );
	if ( isset( $extras_map[ $eid_int ] ) ) {
		$v_extras[] = $extras_map[ $eid_int ];
	}
}

$is_avail             = isset( $view_item->is_available ) ? absint( $view_item->is_available ) : 1;
$nrtok_base_items_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=items' );
$edit_url             = add_query_arg( 'edit_item', $item_id, $nrtok_base_items_url );
$del_url              = wp_nonce_url(
	add_query_arg(
		array(
			'subtab'   => 'all',
			'del_item' => $item_id,
		),
		$nrtok_base_items_url
	),
	'nrtok_delete_item_' . $item_id
);
$clean_cur            = html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' );
?>

<div class="nrtok-card nrtok-full-card nrtok-itm-detail-wrapper">
	<div class="nrtok-itm-view-grid">
		<!-- Left Column: Dish Image & Quick Card -->
		<div class="nrtok-itm-view-left-col">
			<div class="nrtok-itm-photo-container">
				<?php if ( ! empty( $view_item->image_url ) ) : ?>
					<img src="<?php echo esc_url( $view_item->image_url ); ?>" alt="<?php echo esc_attr( $view_item->name ); ?>" class="nrtok-itm-view-photo">
				<?php else : ?>
					<div class="nrtok-itm-view-photo-fallback"><?php echo $nrtok_svg_fallback; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>
				<div class="nrtok-itm-photo-badge <?php echo $is_avail ? '' : 'is-out'; ?>">
					<?php echo $is_avail ? $nrtok_svg_check : $nrtok_svg_alert; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo $is_avail ? esc_html__( 'Active on Menu', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Out of Stock', 'nahian-restaurant-ordering-system' ); ?></span>
				</div>
			</div>

			<div class="nrtok-itm-quick-info-box">
				<div class="nrtok-itm-q-row">
					<span><?php esc_html_e( 'Database ID:', 'nahian-restaurant-ordering-system' ); ?></span>
					<strong>#<?php echo absint( $item_id ); ?></strong>
				</div>
				<div class="nrtok-itm-q-row">
					<span><?php esc_html_e( 'Category:', 'nahian-restaurant-ordering-system' ); ?></span>
					<strong style="color:#f97316;"><?php echo esc_html( $view_item->category ); ?></strong>
				</div>
				<?php if ( ! empty( $view_item->ratio ) ) : ?>
					<div class="nrtok-itm-q-row">
						<span><?php esc_html_e( 'Portion / Ratio:', 'nahian-restaurant-ordering-system' ); ?></span>
						<strong><?php echo esc_html( $view_item->ratio ); ?></strong>
					</div>
				<?php endif; ?>
				<div class="nrtok-itm-q-row">
					<span><?php esc_html_e( 'Total Add-ons:', 'nahian-restaurant-ordering-system' ); ?></span>
					<strong>
						<?php
						printf(
							/* translators: %d: number of attached add-ons */
							esc_html__( '%d Attached', 'nahian-restaurant-ordering-system' ),
							absint( count( $v_extras ) )
						);
						?>
					</strong>
				</div>
			</div>
		</div>

		<!-- Right Column: Details, Stats & Add-ons Breakdown -->
		<div class="nrtok-itm-view-right-col">
			<div class="nrtok-itm-view-head">
				<div>
					<div class="nrtok-itm-view-meta">
						<span class="nrtok-itm-view-id"><?php esc_html_e( 'DISH SPECIFICATION RECORD', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>
					<h2 class="nrtok-itm-view-title"><?php echo esc_html( $view_item->name ); ?></h2>
					<?php if ( ! empty( $view_item->description ) ) : ?>
						<p class="nrtok-itm-view-desc"><?php echo esc_html( $view_item->description ); ?></p>
					<?php endif; ?>
				</div>

				<div class="nrtok-itm-view-btns">
					<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-btn nrtok-itm-btn-icon">
						<?php echo $nrtok_svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Edit Dish', 'nahian-restaurant-ordering-system' ); ?></span>
					</a>
					<a href="<?php echo esc_url( $del_url ); ?>" class="nrtok-itm-act-btn nrtok-act-del nrtok-itm-del-lg js-nrtok-confirm-del" data-name="<?php echo esc_attr( $view_item->name ); ?>">
						<?php echo $nrtok_svg_trash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Delete', 'nahian-restaurant-ordering-system' ); ?></span>
					</a>
				</div>
			</div>

			<!-- Stats Boxes -->
			<div class="nrtok-itm-view-stats">
				<div class="nrtok-itm-stat-box nrtok-itm-stat-orange">
					<div class="nrtok-itm-stat-lbl"><?php esc_html_e( 'Base Menu Price', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-itm-stat-val nrtok-itm-val-orange"><?php echo esc_html( $clean_cur . number_format( (float) $view_item->price, 2 ) ); ?></div>
				</div>
				<div class="nrtok-itm-stat-box nrtok-itm-stat-green">
					<div class="nrtok-itm-stat-lbl"><?php esc_html_e( 'Optional Add-ons', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-itm-stat-val">
						<?php
						printf(
							/* translators: %d: count of configured extras */
							esc_html__( '%d Configured', 'nahian-restaurant-ordering-system' ),
							absint( count( $v_extras ) )
						);
						?>
					</div>
				</div>
				<div class="nrtok-itm-stat-box nrtok-itm-stat-blue">
					<div class="nrtok-itm-stat-lbl"><?php esc_html_e( 'Tablet Availability', 'nahian-restaurant-ordering-system' ); ?></div>
					<div class="nrtok-itm-stat-val nrtok-itm-val-live">
						<?php echo $is_avail ? esc_html__( 'In Stock (Live)', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Unavailable', 'nahian-restaurant-ordering-system' ); ?>
					</div>
				</div>
			</div>

			<!-- Detailed Extras Section -->
			<h3 class="nrtok-itm-view-ex-heading">
				<span class="nrtok-itm-sparkles-ico"><?php echo $nrtok_svg_sparkles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span><?php esc_html_e( 'Attached Extras & Customization Add-ons', 'nahian-restaurant-ordering-system' ); ?></span>
			</h3>

			<?php if ( empty( $v_extras ) ) : ?>
				<div class="nrtok-itm-view-ex-empty">
					<p style="margin:0 0 12px; color:#64748b;"><?php esc_html_e( 'No optional extras or add-ons are currently attached to this dish.', 'nahian-restaurant-ordering-system' ); ?></p>
					<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-btn-secondary" style="font-size:12px; padding:7px 14px;"><?php esc_html_e( 'Edit Dish to Attach Add-ons', 'nahian-restaurant-ordering-system' ); ?></a>
				</div>
			<?php else : ?>
				<div style="width: 100%; overflow-x: auto;">
					<table class="nrtok-table nrtok-full-table nrtok-itm-view-ex-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Add-on / Extra Name', 'nahian-restaurant-ordering-system' ); ?></th>
								<th width="140"><?php esc_html_e( 'Extra Charge', 'nahian-restaurant-ordering-system' ); ?></th>
								<th width="190" style="text-align:right;"><?php esc_html_e( 'Total with Add-on', 'nahian-restaurant-ordering-system' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $v_extras as $vx ) :
								$combined_total = (float) $view_item->price + (float) $vx->price;
								?>
							<tr>
								<td>
									<div style="display:flex; align-items:center; gap:6px;">
										<span style="color:#f97316; display:inline-flex;"><?php echo $nrtok_svg_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<strong><?php echo esc_html( $vx->name ); ?></strong>
									</div>
								</td>
								<td><strong class="nrtok-itm-val-orange">+<?php echo esc_html( $clean_cur . number_format( (float) $vx->price, 2 ) ); ?></strong></td>
								<td style="text-align:right;"><strong class="nrtok-itm-val-green"><?php echo esc_html( $clean_cur . number_format( $combined_total, 2 ) ); ?></strong></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<style>
	.nrtok-itm-detail-wrapper { padding: 4px; }
	.nrtok-itm-view-grid {
		display: grid; grid-template-columns: 320px 1fr; gap: 28px; align-items: start;
	}
	@media (max-width: 920px) { .nrtok-itm-view-grid { grid-template-columns: 1fr; } }

	.nrtok-itm-view-left-col { display: flex; flex-direction: column; gap: 16px; }
	.nrtok-itm-photo-container { position: relative; width: 100%; border-radius: 18px; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0; background: #f8fafc; }
	.nrtok-itm-view-photo { width: 100%; height: 260px; object-fit: cover; display: block; }
	.nrtok-itm-view-photo-fallback { width: 100%; height: 260px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; }
	.nrtok-itm-photo-badge {
		position: absolute; bottom: 12px; left: 12px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px);
		color: #10b981; padding: 5px 12px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
		display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
	}
	.nrtok-itm-photo-badge.is-out { color: #f87171; }

	.nrtok-itm-quick-info-box {
		background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px 20px;
		display: flex; flex-direction: column; gap: 10px;
	}
	.nrtok-itm-q-row { display: flex; justify-content: space-between; font-size: 13.5px; color: #64748b; }
	.nrtok-itm-q-row strong { color: #0f172a; }

	.nrtok-itm-view-head {
		display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;
		gap: 16px; padding-bottom: 20px; border-bottom: 1.5px solid #f1f5f9; margin-bottom: 22px;
	}
	.nrtok-itm-view-meta { margin-bottom: 4px; }
	.nrtok-itm-view-id { font-size: 11.5px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; }
	.nrtok-itm-view-title { margin: 0; font-size: 26px; font-weight: 900; color: #0f172a; letter-spacing: -0.3px; }
	.nrtok-itm-view-desc { margin: 8px 0 0; font-size: 13.5px; color: #64748b; line-height: 1.55; max-width: 620px; }
	.nrtok-itm-view-btns { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
	.nrtok-itm-del-lg { padding: 10px 16px; border-radius: 11px; font-size: 13px; }

	.nrtok-itm-view-stats {
		display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
		gap: 16px; margin-bottom: 24px;
	}
	.nrtok-itm-stat-box {
		background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px;
		box-shadow: 0 2px 4px rgba(15, 23, 42, 0.02);
	}
	.nrtok-itm-stat-orange { border-left: 4px solid #f97316; }
	.nrtok-itm-stat-green { border-left: 4px solid #10b981; }
	.nrtok-itm-stat-blue { border-left: 4px solid #3b82f6; }
	.nrtok-itm-stat-lbl { font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
	.nrtok-itm-stat-val { font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 6px; }
	.nrtok-itm-val-orange { color: #f97316; font-family: 'Courier New', Courier, monospace; }
	.nrtok-itm-val-live { color: #059669; font-size: 16px; }

	.nrtok-itm-view-ex-heading {
		margin: 0 0 14px; font-size: 16px; font-weight: 800; color: #0f172a;
		display: flex; align-items: center; gap: 8px;
	}
	.nrtok-itm-sparkles-ico { color: #f97316; display: inline-flex; }
	.nrtok-itm-view-ex-empty {
		background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 14px;
		padding: 26px; text-align: center; color: #64748b; font-size: 13.5px;
	}
	.nrtok-itm-view-ex-table { box-shadow: none; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
	.nrtok-itm-val-green { color: #059669; font-family: 'Courier New', Courier, monospace; }
</style>

<script>
jQuery(document).ready(function($) {
	$(document).on('click', '.js-nrtok-confirm-del', function(e) {
		const itemName = $(this).data('name') || <?php echo wp_json_encode( __( 'this item', 'nahian-restaurant-ordering-system' ) ); ?>;
		const msg      = <?php echo wp_json_encode( __( 'Are you sure you want to delete "%s"?', 'nahian-restaurant-ordering-system' ) ); ?>.replace('%s', itemName);
		if (!confirm(msg)) {
			e.preventDefault();
		}
	});
});
</script>