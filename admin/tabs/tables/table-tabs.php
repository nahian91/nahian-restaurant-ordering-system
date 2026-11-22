<?php
/**
 * Nahian POS Restaurant Tables Header & Sub-Tabs Bar
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
$nrtok_svg_grid   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>';
$nrtok_svg_plus   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
$nrtok_svg_edit   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_eye    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
$nrtok_svg_back   = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>';
$nrtok_svg_search = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';

$nrtok_base_tables_url = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=tables' );
$nrtok_all_tables_url  = add_query_arg( 'subtab', 'all', $nrtok_base_tables_url );
$nrtok_add_table_url   = add_query_arg( 'subtab', 'add', $nrtok_base_tables_url );

$total_seats    = isset( $total_seats ) ? absint( $total_seats ) : 0;
$total_tables   = isset( $tables ) && is_array( $tables ) ? count( $tables ) : 0;
$occupied_count = isset( $occupied_count ) ? absint( $occupied_count ) : 0;
$free_count     = isset( $free_count ) ? absint( $free_count ) : 0;
?>

<!-- Page Header -->
<div class="nrtok-header">
	<div>
		<h1><?php esc_html_e( 'Restaurant Tables', 'nahian-restaurant-ordering-system' ); ?></h1>
		<p>
			<?php
			printf(
				/* translators: 1: total seats, 2: total tables, 3: occupied count, 4: available count */
				esc_html__( 'Total Capacity: %1$s Seats across %2$s Tables (%3$s Occupied, %4$s Available)', 'nahian-restaurant-ordering-system' ),
				'<strong>' . absint( $total_seats ) . '</strong>',
				'<strong>' . absint( $total_tables ) . '</strong>',
				absint( $occupied_count ),
				absint( $free_count )
			);
			?>
		</p>
	</div>
</div>

<!-- Sub-Tabs & Filter Toolbar -->
<div class="nrtok-tbl-topbar-row">
	<div class="nrtok-tbl-topbar-left">
		<div class="nrtok-tbl-subtabs">
			<a href="<?php echo esc_url( $nrtok_all_tables_url ); ?>" class="nrtok-tbl-subtab <?php echo ( 'all' === $subtab ) ? 'active' : ''; ?>">
				<?php echo $nrtok_svg_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'All Tables', 'nahian-restaurant-ordering-system' ); ?></span>
				<span class="nrtok-tbl-count-pill"><?php echo absint( $total_tables ); ?></span>
			</a>
			<a href="<?php echo esc_url( $nrtok_add_table_url ); ?>" class="nrtok-tbl-subtab <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
				<?php if ( ! empty( $edit_tbl ) ) : ?>
					<?php echo $nrtok_svg_edit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Edit Table', 'nahian-restaurant-ordering-system' ); ?></span>
				<?php else : ?>
					<?php echo $nrtok_svg_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Add Table', 'nahian-restaurant-ordering-system' ); ?></span>
				<?php endif; ?>
			</a>
			<?php if ( 'view' === $subtab && ! empty( $view_tbl ) ) : ?>
				<span class="nrtok-tbl-subtab active">
					<?php echo $nrtok_svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span>
						<?php
						printf(
							/* translators: %s: table name */
							esc_html__( 'Table %s', 'nahian-restaurant-ordering-system' ),
							esc_html( $view_tbl->table_name )
						);
						?>
					</span>
				</span>
			<?php endif; ?>
		</div>

		<?php if ( 'all' === $subtab && ! empty( $tables ) ) : ?>
			<div class="nrtok-tbl-status-filters">
				<button type="button" class="nrtok-tbl-st-pill active" data-st="all">
					<?php
					printf(
						/* translators: %d: total table count */
						esc_html__( 'All (%d)', 'nahian-restaurant-ordering-system' ),
						absint( $total_tables )
					);
					?>
				</button>
				<button type="button" class="nrtok-tbl-st-pill" data-st="free">
					<span class="nrtok-dot-free"></span>
					<?php
					printf(
						/* translators: %d: available table count */
						esc_html__( 'Available (%d)', 'nahian-restaurant-ordering-system' ),
						absint( $free_count )
					);
					?>
				</button>
				<button type="button" class="nrtok-tbl-st-pill" data-st="busy">
					<span class="nrtok-dot-busy"></span>
					<?php
					printf(
						/* translators: %d: occupied table count */
						esc_html__( 'Occupied (%d)', 'nahian-restaurant-ordering-system' ),
						absint( $occupied_count )
					);
					?>
				</button>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( 'all' === $subtab && ! empty( $tables ) ) : ?>
		<div class="nrtok-tbl-search-box">
			<?php echo $nrtok_svg_search; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<input type="text" id="nrtok-tbl-live-search" class="nrtok-input" placeholder="<?php esc_attr_e( 'Search table or login...', 'nahian-restaurant-ordering-system' ); ?>">
		</div>
	<?php elseif ( 'view' === $subtab ) : ?>
		<a href="<?php echo esc_url( $nrtok_all_tables_url ); ?>" class="nrtok-btn-secondary nrtok-tbl-btn-icon">
			<?php echo $nrtok_svg_back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Back to All Tables', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	<?php endif; ?>
</div>

<style>
	.nrtok-tbl-topbar-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 24px; width: 100%; }
	.nrtok-tbl-topbar-left { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
	.nrtok-tbl-subtabs { display: inline-flex; background: #e2e8f0; padding: 4px; border-radius: 12px; gap: 4px; }
	.nrtok-tbl-subtab {
		padding: 9px 18px; border-radius: 9px; font-size: 13.5px; font-weight: 700; text-decoration: none;
		color: #475569; transition: all 0.18s ease; display: inline-flex; align-items: center; gap: 7px;
	}
	.nrtok-tbl-subtab:hover { color: #0f172a; }
	.nrtok-tbl-subtab.active { background: #fff; color: #f97316; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08); }
	.nrtok-tbl-count-pill { background: #f1f5f9; color: #334155; padding: 1px 8px; border-radius: 10px; font-size: 11px; font-weight: 800; }
	.nrtok-tbl-subtab.active .nrtok-tbl-count-pill { background: #fff7ed; color: #ea580c; }

	.nrtok-tbl-status-filters { display: flex; gap: 8px; flex-wrap: wrap; }
	.nrtok-tbl-st-pill {
		padding: 7px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700;
		border: 1.5px solid #e2e8f0; background: #fff; color: #475569; cursor: pointer;
		transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px;
	}
	.nrtok-tbl-st-pill.active, .nrtok-tbl-st-pill:hover { background: #0f172a; color: #fff; border-color: #0f172a; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.1); }
	.nrtok-dot-free { width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block; }
	.nrtok-dot-busy { width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block; }

	.nrtok-tbl-search-box { position: relative; width: 260px; max-width: 100%; }
	.nrtok-tbl-search-box svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); pointer-events: none; }
	.nrtok-tbl-search-box input { padding: 9px 14px 9px 38px; background-color: #fff; }
	.nrtok-tbl-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
</style>

<script>
jQuery(document).ready(function($) {
	let currentStatus = 'all';

	function filterTableCards() {
		const term = ($('#nrtok-tbl-live-search').val() || '').toLowerCase().trim();

		$('.nrtok-tbl-card-item').each(function() {
			const nameAttr   = String($(this).data('name') || '');
			const statusAttr = String($(this).data('status') || 'free');
			const matchTerm  = !term || nameAttr.indexOf(term) !== -1;
			const matchStatus = (currentStatus === 'all') || (statusAttr === currentStatus);

			$(this).toggle(matchTerm && matchStatus);
		});
	}

	$(document).on('input keyup', '#nrtok-tbl-live-search', filterTableCards);

	$(document).on('click', '.nrtok-tbl-st-pill', function() {
		$('.nrtok-tbl-st-pill').removeClass('active');
		$(this).addClass('active');
		currentStatus = $(this).data('st') || 'all';
		filterTableCards();
	});
});
</script>