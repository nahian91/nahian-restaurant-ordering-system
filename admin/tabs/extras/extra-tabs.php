<?php
/**
 * Nahian POS Extras & Add-ons Header & Sub-Tabs Bar
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

$nrtok_svg_sparkles = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>';
$nrtok_svg_plus     = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
$nrtok_svg_edit     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_eye      = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
$nrtok_svg_back     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>';
$nrtok_svg_search   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';

$nrtok_base_extras_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=extras' );
$nrtok_all_extras_url  = add_query_arg( 'subtab', 'all', $nrtok_base_extras_url );
$nrtok_add_extra_url   = add_query_arg( 'subtab', 'add', $nrtok_base_extras_url );
$total_extras          = isset( $extras ) && is_array( $extras ) ? count( $extras ) : 0;
?>

<div class="nrtok-header">
	<div>
		<h1><?php esc_html_e( 'Extras & Add-ons', 'nahian-restaurant-ordering-system' ); ?></h1>
		<p><?php esc_html_e( 'Create optional paid toppings, sauces, cheese, or sides that customers can add to dishes.', 'nahian-restaurant-ordering-system' ); ?></p>
	</div>
</div>

<div class="nrtok-ex-topbar-row">
	<div class="nrtok-ex-subtabs">
		<a href="<?php echo esc_url( $nrtok_all_extras_url ); ?>" class="nrtok-ex-subtab <?php echo ( 'all' === $subtab ) ? 'active' : ''; ?>">
			<?php echo $nrtok_svg_sparkles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'All Extras', 'nahian-restaurant-ordering-system' ); ?></span>
			<span class="nrtok-ex-count-pill"><?php echo absint( $total_extras ); ?></span>
		</a>
		<a href="<?php echo esc_url( $nrtok_add_extra_url ); ?>" class="nrtok-ex-subtab <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
			<?php if ( ! empty( $edit_extra ) ) : ?>
				<?php echo $nrtok_svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Edit Extra', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php else : ?>
				<?php echo $nrtok_svg_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Add Extra', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php endif; ?>
		</a>
		<?php if ( 'view' === $subtab && ! empty( $view_extra ) ) : ?>
			<span class="nrtok-ex-subtab active">
				<?php echo $nrtok_svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span>
					<?php
					printf(
						/* translators: %d: extra ID */
						esc_html__( 'Viewing #%d', 'nahian-restaurant-ordering-system' ),
						absint( $view_extra->id )
					);
					?>
				</span>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( 'all' === $subtab && ! empty( $extras ) ) : ?>
		<div class="nrtok-ex-search-box">
			<?php echo $nrtok_svg_search; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<input type="text" id="nrtok-ex-live-search" class="nrtok-input" placeholder="<?php esc_attr_e( 'Search extra add-on...', 'nahian-restaurant-ordering-system' ); ?>">
		</div>
	<?php elseif ( 'view' === $subtab ) : ?>
		<a href="<?php echo esc_url( $nrtok_all_extras_url ); ?>" class="nrtok-btn-secondary nrtok-ex-btn-icon">
			<?php echo $nrtok_svg_back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Back to All Extras', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	<?php endif; ?>
</div>

<style>
	.nrtok-ex-topbar-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 24px; width: 100%; }
	.nrtok-ex-subtabs { display: inline-flex; background: #e2e8f0; padding: 4px; border-radius: 12px; gap: 4px; }
	.nrtok-ex-subtab {
		padding: 9px 18px; border-radius: 9px; font-size: 13.5px; font-weight: 700; text-decoration: none;
		color: #475569; display: inline-flex; align-items: center; gap: 7px; transition: all 0.18s ease;
	}
	.nrtok-ex-subtab:hover { color: #0f172a; }
	.nrtok-ex-subtab.active { background: #fff; color: #f97316; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08); }
	.nrtok-ex-count-pill { background: #f1f5f9; color: #334155; padding: 1px 8px; border-radius: 10px; font-size: 11px; font-weight: 800; }
	.nrtok-ex-subtab.active .nrtok-ex-count-pill { background: #fff7ed; color: #ea580c; }
	.nrtok-ex-search-box { position: relative; width: 300px; max-width: 100%; }
	.nrtok-ex-search-box svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); pointer-events: none; }
	.nrtok-ex-search-box input { padding: 9px 14px 9px 38px; background-color: #fff; }
	.nrtok-ex-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
</style>

<script>
jQuery(document).ready(function($) {
	$(document).on('input keyup', '#nrtok-ex-live-search', function() {
		const term = ($(this).val() || '').toLowerCase().trim();
		$('.nrtok-ex-row').each(function() {
			const match = !term || $(this).text().toLowerCase().indexOf(term) !== -1;
			$(this).toggle(match);
		});
	});
});
</script>