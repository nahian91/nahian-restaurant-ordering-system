<?php
/**
 * Nahian POS Single Extra / Add-on Details View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) || empty( $view_extra ) ) {
	return;
}

// Safe fallback for currency if not passed.
if ( ! isset( $s ) || ! is_array( $s ) ) {
	$s = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
}
if ( ! isset( $cur ) ) {
	$cur = $s['currency'] ?? '৳';
}

$nrtok_svg_sparkles = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>';
$nrtok_svg_edit     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_trash    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
$nrtok_svg_utensils = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>';

$ex_id           = absint( $view_extra->id );
$assigned_dishes = isset( $dishes_by_extra[ $ex_id ] ) && is_array( $dishes_by_extra[ $ex_id ] ) ? $dishes_by_extra[ $ex_id ] : array();

$nrtok_base_extras_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=extras' );
$nrtok_base_items_url  = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=items' );

$edit_url      = add_query_arg( 'edit_extra', $ex_id, $nrtok_base_extras_url );
$del_url       = wp_nonce_url(
	add_query_arg(
		array(
			'subtab'    => 'all',
			'del_extra' => $ex_id,
		),
		$nrtok_base_extras_url
	),
	'nrtok_delete_extra_' . $ex_id
);
$all_items_url = add_query_arg( 'subtab', 'all', $nrtok_base_items_url );
$clean_cur     = html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' );
?>

<div class="nrtok-card nrtok-full-card">
	<div class="nrtok-ex-view-hero">
		<div class="nrtok-ex-view-hero-left">
			<?php if ( ! empty( $view_extra->image ) ) : ?>
				<img src="<?php echo esc_url( $view_extra->image ); ?>" alt="<?php echo esc_attr( $view_extra->name ); ?>" class="nrtok-ex-view-hero-img">
			<?php else : ?>
				<div class="nrtok-ex-view-hero-ico"><?php echo $nrtok_svg_sparkles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
			<div>
				<div class="nrtok-ex-view-id">
					<?php
					printf(
						/* translators: %d: add-on ID */
						esc_html__( 'ADD-ON ID #%d', 'nahian-restaurant-ordering-system' ),
						absint( $ex_id )
					);
					?>
				</div>
				<h2 class="nrtok-ex-view-title"><?php echo esc_html( $view_extra->name ); ?></h2>
			</div>
		</div>

		<div class="nrtok-ex-view-btns">
			<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-btn nrtok-ex-btn-icon">
				<?php echo $nrtok_svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Extra', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
			<a href="<?php echo esc_url( $del_url ); ?>" class="nrtok-ex-act-btn nrtok-act-del nrtok-ex-del-lg js-nrtok-confirm-del" data-name="<?php echo esc_attr( $view_extra->name ); ?>">
				<?php echo $nrtok_svg_trash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Delete', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
		</div>
	</div>

	<!-- Summary Stats -->
	<div class="nrtok-ex-view-stats">
		<div class="nrtok-ex-stat-box nrtok-ex-stat-orange">
			<div class="nrtok-ex-stat-lbl"><?php esc_html_e( 'Add-on Price', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-ex-stat-val nrtok-ex-val-orange">+<?php echo esc_html( $clean_cur . number_format( (float) $view_extra->price, 2 ) ); ?></div>
		</div>
		<div class="nrtok-ex-stat-box nrtok-ex-stat-green">
			<div class="nrtok-ex-stat-lbl"><?php esc_html_e( 'Attached Menu Dishes', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-ex-stat-val">
				<?php
				printf(
					/* translators: %d: count of attached dishes */
					esc_html__( '%d Dish(es)', 'nahian-restaurant-ordering-system' ),
					absint( count( $assigned_dishes ) )
				);
				?>
			</div>
		</div>
		<div class="nrtok-ex-stat-box nrtok-ex-stat-blue">
			<div class="nrtok-ex-stat-lbl"><?php esc_html_e( 'Availability Status', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-ex-stat-val nrtok-ex-val-live"><?php esc_html_e( 'Active on Customer Tablet', 'nahian-restaurant-ordering-system' ); ?></div>
		</div>
	</div>

	<!-- Assigned Dishes Table -->
	<h3 class="nrtok-ex-dishes-heading">
		<span class="nrtok-ex-title-ico"><?php echo $nrtok_svg_utensils; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span>
			<?php
			printf(
				/* translators: %s: extra add-on name */
				esc_html__( 'Dishes Offering "%s"', 'nahian-restaurant-ordering-system' ),
				esc_html( $view_extra->name )
			);
			?>
		</span>
	</h3>

	<?php if ( empty( $assigned_dishes ) ) : ?>
		<div class="nrtok-ex-dishes-empty">
			<p class="nrtok-ex-dishes-empty-txt"><?php esc_html_e( 'This add-on is not attached to any specific dish yet.', 'nahian-restaurant-ordering-system' ); ?></p>
			<a href="<?php echo esc_url( $all_items_url ); ?>" class="nrtok-btn-secondary"><?php esc_html_e( 'Go to Food Items to Attach', 'nahian-restaurant-ordering-system' ); ?></a>
		</div>
	<?php else : ?>
		<div class="nrtok-ex-dishes-table-wrap">
			<table class="nrtok-table nrtok-full-table nrtok-ex-view-dishes-table">
				<thead>
					<tr>
						<th class="nrtok-col-ed-photo"><?php esc_html_e( 'Photo', 'nahian-restaurant-ordering-system' ); ?></th>
						<th><?php esc_html_e( 'Dish Name', 'nahian-restaurant-ordering-system' ); ?></th>
						<th><?php esc_html_e( 'Category', 'nahian-restaurant-ordering-system' ); ?></th>
						<th><?php esc_html_e( 'Base Price', 'nahian-restaurant-ordering-system' ); ?></th>
						<th><?php esc_html_e( 'Price With This Extra', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-ed-action"><?php esc_html_e( 'Action', 'nahian-restaurant-ordering-system' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $assigned_dishes as $dish ) :
						$dish_id       = absint( $dish->id );
						$combined      = (float) $dish->price + (float) $view_extra->price;
						$edit_dish_url = add_query_arg( 'edit_item', $dish_id, $nrtok_base_items_url );
						?>
					<tr>
						<td>
							<?php if ( ! empty( $dish->image_url ) ) : ?>
								<img src="<?php echo esc_url( $dish->image_url ); ?>" alt="<?php echo esc_attr( $dish->name ); ?>" class="nrtok-ex-dish-thumb">
							<?php else : ?>
								<div class="nrtok-ex-dish-thumb-fallback"><?php echo $nrtok_svg_utensils; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<?php endif; ?>
						</td>
						<td><strong><?php echo esc_html( $dish->name ); ?></strong></td>
						<td><span class="nrtok-ex-dish-cat"><?php echo esc_html( $dish->category ); ?></span></td>
						<td><?php echo esc_html( $clean_cur . number_format( (float) $dish->price, 2 ) ); ?></td>
						<td><strong class="nrtok-ex-val-green"><?php echo esc_html( $clean_cur . number_format( $combined, 2 ) ); ?></strong></td>
						<td>
							<a href="<?php echo esc_url( $edit_dish_url ); ?>" class="nrtok-ex-act-btn nrtok-act-edit">
								<?php echo $nrtok_svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Edit Dish', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<style>
	.nrtok-ex-view-hero { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px; padding-bottom: 20px; border-bottom: 1.5px solid #f1f5f9; margin-bottom: 22px; }
	.nrtok-ex-view-hero-left { display: flex; align-items: center; gap: 14px; }
	.nrtok-ex-view-hero-ico { width: 54px; height: 54px; border-radius: 14px; background: #fff7ed; color: #f97316; border: 1px solid #fed7aa; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
	.nrtok-ex-view-hero-img { width: 54px; height: 54px; border-radius: 14px; object-fit: cover; border: 1.5px solid #e2e8f0; display: block; flex-shrink: 0; }
	.nrtok-ex-view-id { font-size: 12px; font-weight: 700; color: #64748b; }
	.nrtok-ex-view-title { margin: 2px 0 0; font-size: 24px; font-weight: 800; color: #0f172a; }
	.nrtok-ex-view-btns { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
	.nrtok-ex-del-lg { padding: 10px 16px; font-size: 13px; border-radius: 10px; }

	.nrtok-ex-view-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 26px; width: 100%; }
	.nrtok-ex-stat-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; }
	.nrtok-ex-stat-orange { border-left: 4px solid #f97316; }
	.nrtok-ex-stat-green { border-left: 4px solid #10b981; }
	.nrtok-ex-stat-blue { border-left: 4px solid #3b82f6; }
	.nrtok-ex-stat-lbl { font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.4px; }
	.nrtok-ex-stat-val { font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 6px; }
	.nrtok-ex-val-orange { color: #f97316; font-family: 'Courier New', Courier, monospace; }
	.nrtok-ex-val-live { color: #059669; font-size: 18px; }

	.nrtok-ex-dishes-heading { margin: 0 0 14px; font-size: 16.5px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
	.nrtok-ex-title-ico { color: #f97316; display: inline-flex; }
	.nrtok-ex-dishes-empty { background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 14px; padding: 34px 20px; text-align: center; color: #64748b; }
	.nrtok-ex-dishes-empty-txt { margin: 0 0 12px; font-size: 14px; }

	.nrtok-ex-dishes-table-wrap { border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; background: #fff; }
	.nrtok-ex-view-dishes-table { box-shadow: none; border: none; border-radius: 0; }
	.nrtok-col-ed-photo { width: 70px; }
	.nrtok-col-ed-action { width: 130px; }
	.nrtok-ex-dish-thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0; display: block; }
	.nrtok-ex-dish-thumb-fallback { width: 44px; height: 44px; border-radius: 10px; background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; }
	.nrtok-ex-dish-cat { background: #f1f5f9; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
	.nrtok-ex-val-green { color: #059669; font-family: 'Courier New', Courier, monospace; }
</style>

<script>
jQuery(document).ready(function($) {
	$(document).on('click', '.js-nrtok-confirm-del', function(e) {
		const exName = $(this).data('name') || <?php echo wp_json_encode( __( 'this extra', 'nahian-restaurant-ordering-system' ) ); ?>;
		const msg    = <?php echo wp_json_encode( __( 'Delete extra "%s"?', 'nahian-restaurant-ordering-system' ) ); ?>.replace('%s', exName);
		if (!confirm(msg)) {
			e.preventDefault();
		}
	});
});
</script>