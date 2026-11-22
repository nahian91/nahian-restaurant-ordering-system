<?php
/**
 * Nahian POS Menu Items Table List & Availability Switcher
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'nrtok_current_user_can_pos' ) ? ! nrtok_current_user_can_pos() : ! current_user_can( 'manage_options' ) ) {
	return;
}

$cur = isset( $cur ) ? $cur : '৳';

$nrtok_base_items_url   = add_query_arg(
	array(
		'page' => 'nahian-restaurant-ordering-system',
		'tab'  => 'items',
	),
	admin_url( 'admin.php' )
);
$nrtok_add_item_url     = add_query_arg( 'subtab', 'add', $nrtok_base_items_url );
$nrtok_avail_ajax_nonce = wp_create_nonce( 'nrtok_toggle_avail_nonce' );
?>

<?php if ( empty( $items ) ) : ?>
	<div class="nrtok-card nrtok-full-card nrtok-itm-empty-card">
		<div class="nrtok-itm-empty-ico">
			<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>
		</div>
		<h3 class="nrtok-itm-empty-title"><?php esc_html_e( 'No Dishes Added Yet', 'nahian-restaurant-ordering-system' ); ?></h3>
		<p class="nrtok-itm-empty-desc"><?php esc_html_e( 'Start building your digital restaurant menu by adding your first delicious food item.', 'nahian-restaurant-ordering-system' ); ?></p>
		<a href="<?php echo esc_url( $nrtok_add_item_url ); ?>" class="nrtok-btn nrtok-itm-btn-icon">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
			<span><?php esc_html_e( 'Add First Dish', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	</div>
<?php else : ?>
	<div class="nrtok-card nrtok-full-card nrtok-itm-table-card">
		<div class="nrtok-table-responsive">
			<table class="nrtok-table nrtok-full-table nrtok-itm-table" id="nrtok-items-table">
				<thead>
					<tr class="nrtok-itm-thead-row">
						<th class="nrtok-col-itm-order nrtok-itm-th-center"><?php esc_html_e( 'Order', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-itm-photo nrtok-itm-th-pad20"><?php esc_html_e( 'Photo', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-itm-th-pad14"><?php esc_html_e( 'Item Name', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-itm-cat nrtok-itm-th-pad14"><?php esc_html_e( 'Category', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-itm-price nrtok-itm-th-pad14"><?php esc_html_e( 'Price', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-itm-avail nrtok-itm-th-center"><?php esc_html_e( 'Availability', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-itm-act nrtok-itm-th-right"><?php esc_html_e( 'Actions', 'nahian-restaurant-ordering-system' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $items as $item ) :
						$item_id    = absint( $item->id );
						$item_order = isset( $item->item_order ) ? absint( $item->item_order ) : 0;
						$is_avail   = isset( $item->is_available ) ? absint( $item->is_available ) : 1;

						$view_url = add_query_arg( 'view_item', $item_id, $nrtok_base_items_url );
						$edit_url = add_query_arg( 'edit_item', $item_id, $nrtok_base_items_url );
						$del_url  = wp_nonce_url(
							add_query_arg(
								array(
									'subtab'   => 'all',
									'del_item' => $item_id,
								),
								$nrtok_base_items_url
							),
							'nrtok_delete_item_' . $item_id
						);

						$confirm_msg = sprintf(
							/* translators: %s: dish item name */
							__( 'Are you sure you want to delete "%s"?', 'nahian-restaurant-ordering-system' ),
							$item->name
						);
						?>
					<tr class="nrtok-itm-row" data-name="<?php echo esc_attr( strtolower( $item->name ) ); ?>" data-cat="<?php echo esc_attr( strtolower( $item->category ) ); ?>">
						<td class="nrtok-itm-td-center">
							<span class="nrtok-itm-order-badge"><?php echo absint( $item_order ); ?></span>
						</td>
						<td class="nrtok-itm-td-pad20">
							<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-itm-photo-link">
								<?php if ( ! empty( $item->image_url ) ) : ?>
									<img src="<?php echo esc_url( $item->image_url ); ?>" alt="<?php echo esc_attr( $item->name ); ?>" class="nrtok-itm-thumb">
								<?php else : ?>
									<div class="nrtok-itm-thumb-fallback">
										<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
									</div>
								<?php endif; ?>
							</a>
						</td>
						<td class="nrtok-itm-td-pad14">
							<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-itm-name-link">
								<?php echo esc_html( $item->name ); ?>
							</a>
						</td>
						<td class="nrtok-itm-td-pad14">
							<span class="nrtok-itm-cat-badge"><?php echo esc_html( $item->category ); ?></span>
						</td>
						<td class="nrtok-itm-td-pad14">
							<strong class="nrtok-itm-price-val"><?php echo esc_html( html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' ) . number_format( (float) $item->price, 2 ) ); ?></strong>
						</td>
						<!-- Availability Switch Column -->
						<td class="nrtok-itm-td-center">
							<label class="nrtok-switch" title="<?php esc_attr_e( 'Toggle Availability', 'nahian-restaurant-ordering-system' ); ?>">
								<input type="checkbox" class="nrtok-toggle-avail" data-id="<?php echo absint( $item_id ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: dish name */ __( 'Toggle availability for %s', 'nahian-restaurant-ordering-system' ), $item->name ) ); ?>" <?php checked( $is_avail, 1 ); ?>>
								<span class="nrtok-slider"></span>
							</label>
						</td>
						<td class="nrtok-itm-td-right">
							<div class="nrtok-itm-act-group">
								<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-itm-act-btn nrtok-act-view" title="<?php esc_attr_e( 'View Details', 'nahian-restaurant-ordering-system' ); ?>">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
									<span><?php esc_html_e( 'View', 'nahian-restaurant-ordering-system' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-itm-act-btn nrtok-act-edit" title="<?php esc_attr_e( 'Edit Dish', 'nahian-restaurant-ordering-system' ); ?>">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
									<span><?php esc_html_e( 'Edit', 'nahian-restaurant-ordering-system' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $del_url ); ?>" class="nrtok-itm-act-btn nrtok-act-del js-nrtok-confirm-del" data-confirm="<?php echo esc_attr( $confirm_msg ); ?>" title="<?php esc_attr_e( 'Delete Dish', 'nahian-restaurant-ordering-system' ); ?>">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
									<span><?php esc_html_e( 'Delete', 'nahian-restaurant-ordering-system' ); ?></span>
								</a>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
					<tr id="nrtok-itm-no-results-row" class="nrtok-hidden">
						<td colspan="7" class="nrtok-itm-no-results-cell">
							<?php esc_html_e( 'No matching dishes found.', 'nahian-restaurant-ordering-system' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Pagination Bar -->
		<nav id="nrtok-itm-pagination-bar" class="nrtok-itm-pagination-bar nrtok-hidden" aria-label="<?php esc_attr_e( 'Menu Items Pagination', 'nahian-restaurant-ordering-system' ); ?>">
			<div id="nrtok-itm-page-info" class="nrtok-itm-page-info"></div>
			<div id="nrtok-itm-page-controls" class="nrtok-itm-page-controls"></div>
		</nav>
	</div>
<?php endif; ?>

<style>
	.nrtok-hidden { display: none; }
	.nrtok-itm-no-results-cell { text-align: center; padding: 36px 16px; color: #94a3b8; font-size: 14px; font-weight: 500; }

	.nrtok-itm-table-card {
		padding: 0; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 16px;
		box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
	}
	.nrtok-table-responsive { width: 100%; overflow-x: auto; }
	.nrtok-itm-table { margin: 0; border: none; border-radius: 0; box-shadow: none; }
	.nrtok-itm-thead-row { background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; }

	.nrtok-itm-th-center { padding: 16px 14px; text-align: center; }
	.nrtok-itm-th-pad20 { padding: 16px 20px; }
	.nrtok-itm-th-pad14 { padding: 16px 14px; }
	.nrtok-itm-th-right { padding: 16px 20px; text-align: right; }

	.nrtok-itm-td-center { padding: 14px; vertical-align: middle; text-align: center; }
	.nrtok-itm-td-pad20 { padding: 14px 20px; vertical-align: middle; }
	.nrtok-itm-td-pad14 { padding: 14px; vertical-align: middle; }
	.nrtok-itm-td-right { padding: 14px 20px; vertical-align: middle; text-align: right; }

	.nrtok-col-itm-order { width: 70px; }
	.nrtok-col-itm-photo { width: 90px; }
	.nrtok-col-itm-cat { width: 170px; }
	.nrtok-col-itm-price { width: 130px; }
	.nrtok-col-itm-avail { width: 120px; }
	.nrtok-col-itm-act { width: 230px; }

	.nrtok-itm-photo-link { display: block; width: fit-content; }
	.nrtok-itm-order-badge {
		background: #f8fafc; color: #475569; border: 1px solid #cbd5e1;
		width: 30px; height: 30px; border-radius: 50%; display: inline-flex;
		align-items: center; justify-content: center; font-size: 12px; font-weight: 800;
		font-family: "Courier New", Courier, monospace;
	}

	.nrtok-itm-thumb {
		width: 52px; height: 52px; object-fit: cover; border-radius: 12px;
		border: 1.5px solid #e2e8f0; display: block; box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
		transition: transform 0.2s ease;
	}
	.nrtok-itm-thumb:hover { transform: scale(1.05); }

	.nrtok-itm-thumb-fallback {
		width: 52px; height: 52px; border-radius: 12px; background: #f1f5f9; color: #94a3b8;
		border: 1.5px solid #e2e8f0; display: flex; align-items: center; justify-content: center;
	}

	.nrtok-itm-name-link { font-size: 15px; font-weight: 800; color: #0f172a; text-decoration: none; transition: color 0.15s; }
	.nrtok-itm-name-link:hover { color: #f97316; }

	.nrtok-itm-cat-badge {
		background: #f1f5f9; color: #334155; padding: 5px 12px; border-radius: 20px;
		font-size: 12px; font-weight: 700; display: inline-block;
	}
	.nrtok-itm-price-val { font-size: 15.5px; color: #0f172a; font-family: "Courier New", Courier, monospace; }

	/* iOS Style Toggle Switch Styles */
	.nrtok-switch {
		position: relative; display: inline-block; width: 38px; height: 22px; vertical-align: middle;
	}
	.nrtok-switch input { opacity: 0; width: 0; height: 0; }
	.nrtok-slider {
		position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
		background-color: #cbd5e1; transition: .2s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 22px;
	}
	.nrtok-slider:before {
		position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
		background-color: white; transition: .2s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 50%;
		box-shadow: 0 2px 4px rgba(0,0,0,0.15);
	}
	.nrtok-switch input:checked + .nrtok-slider { background-color: #10b981; }
	.nrtok-switch input:focus + .nrtok-slider { box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15); }
	.nrtok-switch input:checked + .nrtok-slider:before { transform: translateX(16px); }

	.nrtok-itm-act-group { display: flex; gap: 6px; align-items: center; justify-content: flex-end; }
	.nrtok-itm-act-btn {
		padding: 6px 11px; border-radius: 8px; font-size: 12px; font-weight: 700;
		text-decoration: none; cursor: pointer; border: none; transition: all 0.2s ease;
		display: inline-flex; align-items: center; gap: 5px;
	}
	.nrtok-act-view { background: #eff6ff; color: #1d4ed8; }
	.nrtok-act-view:hover { background: #dbeafe; }
	.nrtok-act-edit { background: #f1f5f9; color: #334155; }
	.nrtok-act-edit:hover { background: #e2e8f0; color: #0f172a; }
	.nrtok-act-del { background: #fef2f2; color: #dc2626; }
	.nrtok-act-del:hover { background: #fee2e2; }

	.nrtok-itm-empty-card { text-align: center; padding: 60px 20px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; }
	.nrtok-itm-empty-ico {
		width: 64px; height: 64px; border-radius: 18px; background: #fff7ed; color: #f97316;
		display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;
		box-shadow: 0 4px 12px rgba(249, 115, 22, 0.1);
	}
	.nrtok-itm-empty-title { margin: 0 0 8px; font-size: 20px; font-weight: 800; color: #0f172a; }
	.nrtok-itm-empty-desc { color: #64748b; margin: 0 0 22px; font-size: 14px; }
	.nrtok-itm-btn-icon { display: inline-flex; align-items: center; gap: 7px; padding: 11px 20px; font-weight: 700; border-radius: 12px; }

	.nrtok-itm-row { transition: background 0.15s ease; }
	.nrtok-itm-row:hover { background-color: #fafaf9; }

	/* Pagination Bar */
	.nrtok-itm-pagination-bar {
		display: flex; align-items: center; justify-content: space-between;
		flex-wrap: wrap; gap: 12px; padding: 14px 20px;
		background: #f8fafc; border-top: 1px solid #e2e8f0;
	}
	.nrtok-itm-page-info {
		display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
		font-size: 13px; font-weight: 600; color: #64748b;
	}
	.nrtok-itm-page-badge {
		background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;
		padding: 2px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 800;
	}
	.nrtok-itm-page-controls { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
	.nrtok-itm-pg-numbers {
		display: inline-flex; align-items: center; gap: 4px;
		background: #ffffff; padding: 3px; border-radius: 11px; border: 1px solid #e2e8f0;
	}
	.nrtok-itm-pg-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 4px;
		min-width: 32px; height: 32px; padding: 0 10px;
		border-radius: 8px; border: 1.5px solid #e2e8f0; background: #ffffff;
		color: #334155; font-size: 12.5px; font-weight: 750; cursor: pointer;
		transition: all 0.15s ease;
	}
	.nrtok-itm-pg-numbers .nrtok-itm-pg-btn { border-color: transparent; background: transparent; padding: 0 7px; }
	.nrtok-itm-pg-btn:hover:not(:disabled):not(.active) { border-color: #cbd5e1; color: #0f172a; background: #f1f5f9; }
	.nrtok-itm-pg-numbers .nrtok-itm-pg-btn:hover:not(.active) { background: #f8fafc; border-color: #e2e8f0; }
	.nrtok-itm-pg-btn.active {
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		border-color: #f97316; color: #ffffff;
		box-shadow: 0 3px 8px rgba(249, 115, 22, 0.3);
	}
	.nrtok-itm-pg-btn:disabled { opacity: 0.42; cursor: not-allowed; }
	.nrtok-itm-pg-ellipsis { padding: 0 5px; color: #94a3b8; font-weight: 800; font-size: 12.5px; user-select: none; }
</style>

<script>
jQuery(document).ready(function($) {
	const availNonce = <?php echo wp_json_encode( $nrtok_avail_ajax_nonce ); ?>;
	const perPage    = 10;
	let currentPage  = 1;
	const txtPrev    = <?php echo wp_json_encode( __( 'Prev', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtNext    = <?php echo wp_json_encode( __( 'Next', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtShowing = <?php echo wp_json_encode( __( 'Showing %1$d–%2$d of %3$d dishes', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtPageOf  = <?php echo wp_json_encode( __( 'Page %1$d of %2$d', 'nahian-restaurant-ordering-system' ) ); ?>;

	function renderItemsTable() {
		const q           = ($('#nrtok-itm-live-search').val() || '').toLowerCase().trim();
		const rawCat      = ($('#nrtok-itm-cat-filter, .nrtok-itm-cat-filter').first().val() || '').toLowerCase().trim();
		const catFilter   = (rawCat === 'all' || rawCat === 'all categories') ? '' : rawCat;
		const $allRows    = $('.nrtok-itm-row');
		const matchedRows = [];

		$allRows.each(function() {
			const nameAttr = String($(this).data('name') || '');
			const catAttr  = String($(this).data('cat') || '');
			const matchQ   = !q || nameAttr.indexOf(q) > -1 || catAttr.indexOf(q) > -1;
			const matchCat = !catFilter || catAttr === catFilter;

			if (matchQ && matchCat) {
				matchedRows.push(this);
			}
			$(this).hide();
		});

		const totalItems = matchedRows.length;
		if (totalItems === 0) {
			$('#nrtok-itm-no-results-row').show();
			$('#nrtok-itm-pagination-bar').hide();
			return;
		}

		$('#nrtok-itm-no-results-row').hide();

		const totalPages = Math.ceil(totalItems / perPage);
		if (currentPage > totalPages) currentPage = totalPages;
		if (currentPage < 1) currentPage = 1;

		const startIdx = (currentPage - 1) * perPage;
		const endIdx   = Math.min(startIdx + perPage, totalItems);

		for (let i = startIdx; i < endIdx; i++) {
			$(matchedRows[i]).show();
		}

		if (totalItems > perPage) {
			const $infoWrap = $('#nrtok-itm-page-info').empty();
			const summaryStr = txtShowing
				.replace('%1$d', startIdx + 1)
				.replace('%2$d', endIdx)
				.replace('%3$d', totalItems);
			$infoWrap.append($('<span></span>').text(summaryStr));

			const pageBadgeStr = txtPageOf
				.replace('%1$d', currentPage)
				.replace('%2$d', totalPages);
			$infoWrap.append($('<span class="nrtok-itm-page-badge"></span>').text(pageBadgeStr));

			const $controls = $('#nrtok-itm-page-controls').empty();

			const $prevBtn = $('<button type="button" class="nrtok-itm-pg-btn"></button>')
				.attr('data-page', currentPage - 1)
				.prop('disabled', currentPage === 1)
				.append('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>')
				.append($('<span></span>').text(txtPrev));
			$controls.append($prevBtn);

			const $numGroup = $('<div class="nrtok-itm-pg-numbers"></div>');
			const pageRange = [];
			for (let p = 1; p <= totalPages; p++) {
				if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
					pageRange.push(p);
				}
			}

			let prevNum = 0;
			pageRange.forEach(function(p) {
				if (prevNum > 0 && (p - prevNum) > 1) {
					$numGroup.append('<span class="nrtok-itm-pg-ellipsis" aria-hidden="true">&hellip;</span>');
				}
				prevNum = p;

				const $numBtn = $('<button type="button" class="nrtok-itm-pg-btn"></button>')
					.attr('data-page', p)
					.text(p);
				if (p === currentPage) {
					$numBtn.addClass('active').attr('aria-current', 'page');
				}
				$numGroup.append($numBtn);
			});
			$controls.append($numGroup);

			const $nextBtn = $('<button type="button" class="nrtok-itm-pg-btn"></button>')
				.attr('data-page', currentPage + 1)
				.prop('disabled', currentPage === totalPages)
				.append($('<span></span>').text(txtNext))
				.append('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>');
			$controls.append($nextBtn);

			$('#nrtok-itm-pagination-bar').css('display', 'flex');
		} else {
			$('#nrtok-itm-pagination-bar').hide();
		}
	}

	renderItemsTable();

	$('#nrtok-itm-live-search').on('input keyup', function() {
		currentPage = 1;
		renderItemsTable();
	});

	$('#nrtok-itm-cat-filter, .nrtok-itm-cat-filter').on('change', function() {
		currentPage = 1;
		renderItemsTable();
	});

	$(document).on('click', '.nrtok-itm-pg-btn', function() {
		if ($(this).prop('disabled')) return;
		currentPage = parseInt($(this).data('page'), 10) || 1;
		renderItemsTable();
	});

	$(document).on('click', '.js-nrtok-confirm-del', function(e) {
		const msg = $(this).data('confirm');
		if (msg && !window.confirm(msg)) {
			e.preventDefault();
		}
	});

	// AJAX Handler for Live Availability Toggle with CSRF Nonce & Rollback
	$(document).on('change', '.nrtok-toggle-avail', function() {
		const $checkbox = $(this);
		const itemId    = $checkbox.data('id');
		const isAvail   = $checkbox.is(':checked') ? 1 : 0;

		$checkbox.prop('disabled', true);
		$.post(ajaxurl, {
			action: 'nrtok_toggle_item_availability',
			_ajax_nonce: availNonce,
			id: itemId,
			available: isAvail
		}).done(function(res) {
			if (!res || !res.success) {
				$checkbox.prop('checked', !isAvail);
			}
		}).fail(function() {
			$checkbox.prop('checked', !isAvail);
		}).always(function() {
			$checkbox.prop('disabled', false);
		});
	});
});
</script>