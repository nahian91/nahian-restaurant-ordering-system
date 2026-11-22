<?php
/**
 * Nahian POS Menu Items Header & Sub-Tabs Bar
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

$nrtok_svg_utensils = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>';
$nrtok_svg_plus     = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
$nrtok_svg_edit     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_eye      = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
$nrtok_svg_back     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>';
$nrtok_svg_search   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';

$nrtok_base_items_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=items' );
$nrtok_all_items_url  = add_query_arg( 'subtab', 'all', $nrtok_base_items_url );
$nrtok_add_item_url   = add_query_arg( 'subtab', 'add', $nrtok_base_items_url );
$total_items          = isset( $items ) && is_array( $items ) ? count( $items ) : 0;
?>

<div class="nrtok-header">
	<div>
		<h1><?php esc_html_e( 'Food Items', 'nahian-restaurant-ordering-system' ); ?></h1>
		<p><?php esc_html_e( 'Manage restaurant menu dishes, upload photos, and attach optional add-ons.', 'nahian-restaurant-ordering-system' ); ?></p>
	</div>
</div>

<div class="nrtok-itm-topbar-row">
	<div class="nrtok-itm-subtabs">
		<a href="<?php echo esc_url( $nrtok_all_items_url ); ?>" class="nrtok-itm-subtab <?php echo ( 'all' === $subtab ) ? 'active' : ''; ?>">
			<?php echo $nrtok_svg_utensils; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'All Items', 'nahian-restaurant-ordering-system' ); ?></span>
			<span class="nrtok-itm-count-pill"><?php echo absint( $total_items ); ?></span>
		</a>
		<a href="<?php echo esc_url( $nrtok_add_item_url ); ?>" class="nrtok-itm-subtab <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
			<?php if ( ! empty( $edit_item ) ) : ?>
				<?php echo $nrtok_svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Item', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php else : ?>
				<?php echo $nrtok_svg_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Item', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php endif; ?>
		</a>
		<?php if ( 'view' === $subtab && ! empty( $view_item ) ) : ?>
			<span class="nrtok-itm-subtab active">
				<?php echo $nrtok_svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span>
					<?php
					printf(
						/* translators: %d: item ID */
						esc_html__( 'Viewing #%d', 'nahian-restaurant-ordering-system' ),
						absint( $view_item->id )
					);
					?>
				</span>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( 'all' === $subtab && ! empty( $items ) ) : ?>
		<div class="nrtok-itm-filter-group">
			<select id="nrtok-itm-cat-filter" class="nrtok-input nrtok-itm-cat-select" aria-label="<?php esc_attr_e( 'Filter by Category', 'nahian-restaurant-ordering-system' ); ?>">
				<option value="all"><?php esc_html_e( 'All Categories', 'nahian-restaurant-ordering-system' ); ?></option>
				<?php
				if ( ! empty( $cats ) && is_array( $cats ) ) :
					foreach ( $cats as $c ) :
						?>
					<option value="<?php echo esc_attr( strtolower( $c->name ) ); ?>"><?php echo esc_html( $c->name ); ?></option>
						<?php
					endforeach;
				endif;
				?>
			</select>
			<div class="nrtok-itm-search-box">
				<?php echo $nrtok_svg_search; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="text" id="nrtok-itm-live-search" class="nrtok-input" placeholder="<?php esc_attr_e( 'Search dish name...', 'nahian-restaurant-ordering-system' ); ?>">
			</div>
		</div>
	<?php elseif ( 'view' === $subtab ) : ?>
		<a href="<?php echo esc_url( $nrtok_all_items_url ); ?>" class="nrtok-btn-secondary nrtok-itm-btn-icon">
			<?php echo $nrtok_svg_back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Back to All Items', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	<?php endif; ?>
</div>

<style>
	.nrtok-itm-topbar-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 24px; width: 100%; }
	.nrtok-itm-subtabs { display: inline-flex; background: #e2e8f0; padding: 4px; border-radius: 12px; gap: 4px; }
	.nrtok-itm-subtab {
		padding: 9px 18px; border-radius: 9px; font-size: 13.5px; font-weight: 700; text-decoration: none;
		color: #475569; display: inline-flex; align-items: center; gap: 7px; transition: all 0.18s ease;
	}
	.nrtok-itm-subtab:hover { color: #0f172a; }
	.nrtok-itm-subtab.active { background: #fff; color: #f97316; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08); }
	.nrtok-itm-count-pill { background: #f1f5f9; color: #334155; padding: 1px 8px; border-radius: 10px; font-size: 11px; font-weight: 800; }
	.nrtok-itm-subtab.active .nrtok-itm-count-pill { background: #fff7ed; color: #ea580c; }
	.nrtok-itm-filter-group { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
	.nrtok-itm-cat-select { width: 195px; padding: 9px 36px 9px 14px; background-color: #fff; }
	.nrtok-itm-search-box { position: relative; width: 250px; max-width: 100%; }
	.nrtok-itm-search-box svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); pointer-events: none; }
	.nrtok-itm-search-box input { padding: 9px 14px 9px 38px; background-color: #fff; }
	.nrtok-itm-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
</style>

<script>
jQuery(document).ready(function($) {
	function filterItemsTable() {
		const term = ($('#nrtok-itm-live-search').val() || '').toLowerCase().trim();
		const cat  = ($('#nrtok-itm-cat-filter').val() || 'all').toLowerCase();

		$('.nrtok-itm-row').each(function() {
			const nameTxt = String($(this).data('name') || '');
			const catTxt  = String($(this).data('cat') || '');
			const matchTerm = !term || nameTxt.indexOf(term) !== -1;
			const matchCat  = (cat === 'all') || (catTxt === cat);
			$(this).toggle(matchTerm && matchCat);
		});
	}

	$(document).on('input keyup', '#nrtok-itm-live-search', filterItemsTable);
	$(document).on('change', '#nrtok-itm-cat-filter', filterItemsTable);
});
</script>