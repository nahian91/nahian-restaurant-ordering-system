<?php
/**
 * Nahian POS Single Menu Category Details View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) || empty( $view_cat ) ) {
	return;
}

// Safe fallback for currency if not passed.
if ( ! isset( $s ) || ! is_array( $s ) ) {
	$s = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
}
$nrtok_cur = isset( $cur ) ? $cur : ( $s['currency'] ?? '$' );

$nrtok_svg_folder   = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>';
$nrtok_svg_edit     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_trash    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
$nrtok_svg_utensils = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>';
$nrtok_svg_plus     = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';

$nrtok_cat_id          = absint( $view_cat->id );
$nrtok_assigned_dishes = isset( $items_by_cat[ $view_cat->name ] ) && is_array( $items_by_cat[ $view_cat->name ] ) ? $items_by_cat[ $view_cat->name ] : array();

$nrtok_avg_price = 0.0;
if ( ! empty( $nrtok_assigned_dishes ) ) {
	$nrtok_sum       = array_sum(
		array_map(
			static function ( $d ) {
				return (float) ( $d->price ?? 0 );
			},
			$nrtok_assigned_dishes
		)
	);
	$nrtok_avg_price = $nrtok_sum / count( $nrtok_assigned_dishes );
}

$nrtok_base_cats_url  = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=categories' );
$nrtok_base_items_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=items' );

$nrtok_edit_url     = add_query_arg( 'edit_cat', $nrtok_cat_id, $nrtok_base_cats_url );
$nrtok_del_url      = wp_nonce_url(
	add_query_arg(
		array(
			'subtab'  => 'all',
			'del_cat' => $nrtok_cat_id,
		),
		$nrtok_base_cats_url
	),
	'nrtok_delete_cat_' . $nrtok_cat_id
);
$nrtok_add_item_url = add_query_arg( 'subtab', 'add', $nrtok_base_items_url );
$nrtok_clean_cur    = html_entity_decode( $nrtok_cur, ENT_QUOTES, 'UTF-8' );
?>

<div class="nrtok-card nrtok-full-card">
	<div class="nrtok-cat-view-hero">
		<div class="nrtok-cat-view-hero-left">
			<?php if ( ! empty( $view_cat->image ) ) : ?>
				<img src="<?php echo esc_url( $view_cat->image ); ?>" alt="<?php echo esc_attr( $view_cat->name ); ?>" class="nrtok-cat-view-hero-img">
			<?php else : ?>
				<div class="nrtok-cat-view-hero-ico"><?php echo $nrtok_svg_folder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
			<div>
				<div class="nrtok-cat-view-id">
					<?php
					printf(
						/* translators: %d: category ID */
						esc_html__( 'CATEGORY ID #%d', 'nahian-restaurant-ordering-system' ),
						absint( $nrtok_cat_id )
					);
					?>
				</div>
				<h2 class="nrtok-cat-view-title"><?php echo esc_html( $view_cat->name ); ?></h2>
			</div>
		</div>

		<div class="nrtok-cat-view-btns">
			<a href="<?php echo esc_url( $nrtok_edit_url ); ?>" class="nrtok-btn nrtok-cat-btn-icon">
				<?php echo $nrtok_svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Category', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
			<a href="<?php echo esc_url( $nrtok_del_url ); ?>" class="nrtok-cat-act-btn nrtok-act-del nrtok-cat-del-lg js-nrtok-confirm-del" data-name="<?php echo esc_attr( $view_cat->name ); ?>">
				<?php echo $nrtok_svg_trash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Delete', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
		</div>
	</div>

	<!-- Category Stats -->
	<div class="nrtok-cat-view-stats">
		<div class="nrtok-cat-stat-box nrtok-cat-stat-orange">
			<div class="nrtok-cat-stat-lbl"><?php esc_html_e( 'Total Assigned Dishes', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-cat-stat-val">
				<?php
				printf(
					/* translators: %d: count of assigned dishes */
					esc_html__( '%d Dish(es)', 'nahian-restaurant-ordering-system' ),
					absint( count( $nrtok_assigned_dishes ) )
				);
				?>
			</div>
		</div>
		<div class="nrtok-cat-stat-box nrtok-cat-stat-green">
			<div class="nrtok-cat-stat-lbl"><?php esc_html_e( 'Average Dish Price', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-cat-stat-val nrtok-cat-val-green"><?php echo esc_html( $nrtok_clean_cur . number_format( $nrtok_avg_price, 2 ) ); ?></div>
		</div>
		<div class="nrtok-cat-stat-box nrtok-cat-stat-blue">
			<div class="nrtok-cat-stat-lbl"><?php esc_html_e( 'Tablet Menu Visibility', 'nahian-restaurant-ordering-system' ); ?></div>
			<div class="nrtok-cat-stat-val nrtok-cat-val-vis">
				<?php echo ! empty( $nrtok_assigned_dishes ) ? esc_html__( 'Visible on Tablet', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Hidden (No Dishes)', 'nahian-restaurant-ordering-system' ); ?>
			</div>
		</div>
	</div>

	<!-- Dishes in this Category -->
	<div class="nrtok-cat-dishes-head">
		<h3 class="nrtok-cat-dishes-title">
			<span class="nrtok-cat-title-ico"><?php echo $nrtok_svg_utensils; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span>
				<?php
				printf(
					/* translators: %s: category name */
					esc_html__( 'Menu Items in "%s"', 'nahian-restaurant-ordering-system' ),
					esc_html( $view_cat->name )
				);
				?>
			</span>
		</h3>
		<a href="<?php echo esc_url( $nrtok_add_item_url ); ?>" class="nrtok-btn-secondary nrtok-cat-btn-icon nrtok-cat-add-dish-sm">
			<?php echo $nrtok_svg_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Add New Dish', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	</div>

	<?php if ( empty( $nrtok_assigned_dishes ) ) : ?>
		<div class="nrtok-cat-dishes-empty">
			<p class="nrtok-cat-dishes-empty-txt"><?php esc_html_e( 'No food items have been assigned to this category yet.', 'nahian-restaurant-ordering-system' ); ?></p>
			<a href="<?php echo esc_url( $nrtok_add_item_url ); ?>" class="nrtok-btn nrtok-cat-btn-icon">
				<?php echo $nrtok_svg_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add First Dish to Category', 'nahian-restaurant-ordering-system' ); ?></span>
			</a>
		</div>
	<?php else : ?>
		<div class="nrtok-cat-dishes-table-wrap">
			<table class="nrtok-table nrtok-full-table nrtok-cat-view-dishes-table">
				<thead>
					<tr>
						<th class="nrtok-col-vd-photo"><?php esc_html_e( 'Photo', 'nahian-restaurant-ordering-system' ); ?></th>
						<th><?php esc_html_e( 'Dish Name', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-vd-addons"><?php esc_html_e( 'Add-ons', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-vd-price"><?php esc_html_e( 'Price', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-vd-action"><?php esc_html_e( 'Action', 'nahian-restaurant-ordering-system' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $nrtok_assigned_dishes as $nrtok_dish ) :
						$nrtok_dish_id = absint( $nrtok_dish->id );
						$nrtok_ex_arr  = ! empty( $nrtok_dish->extras ) ? json_decode( $nrtok_dish->extras, true ) : array();
						if ( ! is_array( $nrtok_ex_arr ) ) {
							$nrtok_ex_arr = array();
						}
						$nrtok_edit_item_url = add_query_arg( 'edit_item', $nrtok_dish_id, $nrtok_base_items_url );
						?>
					<tr>
						<td>
							<?php if ( ! empty( $nrtok_dish->image_url ) ) : ?>
								<img src="<?php echo esc_url( $nrtok_dish->image_url ); ?>" alt="<?php echo esc_attr( $nrtok_dish->name ); ?>" class="nrtok-cat-dish-thumb">
							<?php else : ?>
								<div class="nrtok-cat-dish-thumb-fallback"><?php echo $nrtok_svg_utensils; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<?php endif; ?>
						</td>
						<td><strong class="nrtok-cat-dish-name"><?php echo esc_html( $nrtok_dish->name ); ?></strong></td>
						<td>
							<?php if ( ! empty( $nrtok_ex_arr ) ) : ?>
								<span class="nrtok-cat-dish-addons">
									<?php
									printf(
										/* translators: %d: count of extras */
										esc_html__( '%d extra(s)', 'nahian-restaurant-ordering-system' ),
										absint( count( $nrtok_ex_arr ) )
									);
									?>
								</span>
							<?php else : ?>
								<span class="nrtok-cat-dish-no-addons"><?php esc_html_e( 'None', 'nahian-restaurant-ordering-system' ); ?></span>
							<?php endif; ?>
						</td>
						<td><strong class="nrtok-cat-dish-price"><?php echo esc_html( $nrtok_clean_cur . number_format( (float) $nrtok_dish->price, 2 ) ); ?></strong></td>
						<td>
							<a href="<?php echo esc_url( $nrtok_edit_item_url ); ?>" class="nrtok-cat-act-btn nrtok-act-edit">
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
	.nrtok-cat-view-hero { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px; padding-bottom: 20px; border-bottom: 1.5px solid #f1f5f9; margin-bottom: 22px; }
	.nrtok-cat-view-hero-left { display: flex; align-items: center; gap: 14px; }
	.nrtok-cat-view-hero-ico { width: 54px; height: 54px; border-radius: 14px; background: #fff7ed; color: #f97316; border: 1px solid #fed7aa; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
	.nrtok-cat-view-hero-img { width: 54px; height: 54px; border-radius: 14px; object-fit: cover; border: 1.5px solid #e2e8f0; display: block; flex-shrink: 0; }
	.nrtok-cat-view-id { font-size: 12px; font-weight: 700; color: #64748b; }
	.nrtok-cat-view-title { margin: 2px 0 0; font-size: 24px; font-weight: 800; color: #0f172a; }
	.nrtok-cat-view-btns { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
	.nrtok-cat-del-lg { padding: 10px 16px; font-size: 13px; border-radius: 10px; }

	.nrtok-cat-view-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 26px; width: 100%; }
	.nrtok-cat-stat-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; }
	.nrtok-cat-stat-orange { border-left: 4px solid #f97316; }
	.nrtok-cat-stat-green { border-left: 4px solid #10b981; }
	.nrtok-cat-stat-blue { border-left: 4px solid #3b82f6; }
	.nrtok-cat-stat-lbl { font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.4px; }
	.nrtok-cat-stat-val { font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 6px; }
	.nrtok-cat-val-green { color: #059669; font-family: 'Courier New', Courier, monospace; }
	.nrtok-cat-val-vis { font-size: 16px; color: #1d4ed8; }

	.nrtok-cat-dishes-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px; }
	.nrtok-cat-dishes-title { margin: 0; font-size: 16.5px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
	.nrtok-cat-title-ico { color: #f97316; display: inline-flex; }
	.nrtok-cat-add-dish-sm { padding: 7px 14px; font-size: 12.5px; border-radius: 9px; }
	.nrtok-cat-dishes-empty { background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 14px; padding: 36px 20px; text-align: center; color: #64748b; }
	.nrtok-cat-dishes-empty-txt { margin: 0 0 12px; font-size: 14px; }

	.nrtok-cat-dishes-table-wrap { border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; background: #fff; }
	.nrtok-cat-view-dishes-table { box-shadow: none; border: none; border-radius: 0; }
	.nrtok-col-vd-photo { width: 75px; }
	.nrtok-col-vd-addons { width: 160px; }
	.nrtok-col-vd-price { width: 150px; }
	.nrtok-col-vd-action { width: 140px; }

	.nrtok-cat-dish-thumb { width: 46px; height: 46px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0; display: block; }
	.nrtok-cat-dish-thumb-fallback { width: 46px; height: 46px; border-radius: 10px; background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; }
	.nrtok-cat-dish-name { font-size: 14.5px; color: #0f172a; }
	.nrtok-cat-dish-addons { color: #f97316; font-weight: 700; font-size: 12.5px; }
	.nrtok-cat-dish-no-addons { color: #94a3b8; font-size: 12.5px; }
	.nrtok-cat-dish-price { font-size: 14.5px; color: #0f172a; font-family: 'Courier New', Courier, monospace; }
</style>

<script>
jQuery(document).ready(function($) {
	$(document).on('click', '.js-nrtok-confirm-del', function(e) {
		const catName = $(this).data('name') || <?php echo wp_json_encode( __( 'this category', 'nahian-restaurant-ordering-system' ) ); ?>;
		const msg     = <?php
		/* translators: %s: category name */
		echo wp_json_encode( __( 'Delete category "%s"?', 'nahian-restaurant-ordering-system' ) );
		?>.replace('%s', catName);
		if (!confirm(msg)) {
			e.preventDefault();
		}
	});
});
</script>