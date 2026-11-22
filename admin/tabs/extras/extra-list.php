<?php
/**
 * Nahian POS Extras & Add-ons Table List View
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

$nrtok_cur = isset( $cur ) ? $cur : '$';

$nrtok_base_extras_url = add_query_arg(
	array(
		'page' => 'nahian-restaurant-ordering-system',
		'tab'  => 'extras',
	),
	admin_url( 'admin.php' )
);
$nrtok_add_extra_url = add_query_arg( 'subtab', 'add', $nrtok_base_extras_url );
?>

<?php if ( empty( $extras ) ) : ?>
	<div class="nrtok-card nrtok-full-card nrtok-ex-empty-card">
		<div class="nrtok-ex-empty-ico">
			<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>
		</div>
		<h3 class="nrtok-ex-empty-title"><?php esc_html_e( 'No Extras Created Yet', 'nahian-restaurant-ordering-system' ); ?></h3>
		<p class="nrtok-ex-empty-desc"><?php esc_html_e( 'Create add-ons like Extra Cheese, Spicy Sauce, or Fries to boost order value.', 'nahian-restaurant-ordering-system' ); ?></p>
		<a href="<?php echo esc_url( $nrtok_add_extra_url ); ?>" class="nrtok-btn nrtok-ex-btn-icon">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
			<span><?php esc_html_e( 'Add First Extra', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	</div>
<?php else : ?>
	<div class="nrtok-ex-table-wrap">
		<table class="nrtok-table nrtok-full-table nrtok-ex-table">
			<thead>
				<tr>
					<th class="nrtok-col-ex-id"><?php esc_html_e( 'ID', 'nahian-restaurant-ordering-system' ); ?></th>
					<th><?php esc_html_e( 'Extra Name', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-ex-price"><?php esc_html_e( 'Price', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-ex-used"><?php esc_html_e( 'Used In Dishes', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-ex-act"><?php esc_html_e( 'Actions', 'nahian-restaurant-ordering-system' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $extras as $nrtok_ex ) :
					$nrtok_ex_id       = absint( $nrtok_ex->id );
					$nrtok_used_dishes = isset( $dishes_by_extra[ $nrtok_ex_id ] ) && is_array( $dishes_by_extra[ $nrtok_ex_id ] ) ? $dishes_by_extra[ $nrtok_ex_id ] : array();
					$nrtok_used_count  = count( $nrtok_used_dishes );

					$nrtok_view_url = add_query_arg( 'view_extra', $nrtok_ex_id, $nrtok_base_extras_url );
					$nrtok_edit_url = add_query_arg( 'edit_extra', $nrtok_ex_id, $nrtok_base_extras_url );
					$nrtok_del_url  = wp_nonce_url(
						add_query_arg(
							array(
								'subtab'    => 'all',
								'del_extra' => $nrtok_ex_id,
							),
							$nrtok_base_extras_url
						),
						'nrtok_delete_extra_' . $nrtok_ex_id
					);

					$nrtok_confirm_msg = sprintf(
						/* translators: %s: extra item name */
						__( 'Delete extra "%s"?', 'nahian-restaurant-ordering-system' ),
						$nrtok_ex->name
					);
					?>
				<tr class="nrtok-ex-row" data-name="<?php echo esc_attr( strtolower( $nrtok_ex->name ) ); ?>">
					<td><strong>#<?php echo absint( $nrtok_ex_id ); ?></strong></td>
					<td>
						<div class="nrtok-ex-name-cell">
							<?php if ( ! empty( $nrtok_ex->image ) ) : ?>
								<img src="<?php echo esc_url( $nrtok_ex->image ); ?>" alt="<?php echo esc_attr( $nrtok_ex->name ); ?>" class="nrtok-ex-thumb-img">
							<?php else : ?>
								<span class="nrtok-ex-thumb-placeholder">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>
								</span>
							<?php endif; ?>
							<a href="<?php echo esc_url( $nrtok_view_url ); ?>" class="nrtok-ex-name-link">
								<?php echo esc_html( $nrtok_ex->name ); ?>
							</a>
						</div>
					</td>
					<td><strong class="nrtok-ex-price-val">+<?php echo esc_html( html_entity_decode( $nrtok_cur, ENT_QUOTES, 'UTF-8' ) . number_format( (float) $nrtok_ex->price, 2 ) ); ?></strong></td>
					<td>
						<span class="nrtok-ex-used-badge <?php echo ( $nrtok_used_count > 0 ) ? 'nrtok-ex-badge-active' : 'nrtok-ex-badge-empty'; ?>">
							<?php
							printf(
								/* translators: %d: count of dishes using this extra */
								esc_html__( '%d dish(es)', 'nahian-restaurant-ordering-system' ),
								absint( $nrtok_used_count )
							);
							?>
						</span>
					</td>
					<td>
						<div class="nrtok-ex-act-group">
							<a href="<?php echo esc_url( $nrtok_view_url ); ?>" class="nrtok-ex-act-btn nrtok-act-view" title="<?php esc_attr_e( 'View Details', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
								<span><?php esc_html_e( 'View', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $nrtok_edit_url ); ?>" class="nrtok-ex-act-btn nrtok-act-edit" title="<?php esc_attr_e( 'Edit Extra', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
								<span><?php esc_html_e( 'Edit', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $nrtok_del_url ); ?>" class="nrtok-ex-act-btn nrtok-act-del js-nrtok-confirm-del" data-confirm="<?php echo esc_attr( $nrtok_confirm_msg ); ?>" title="<?php esc_attr_e( 'Delete Extra', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
								<span><?php esc_html_e( 'Delete', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
				<tr id="nrtok-ex-no-results-row" class="nrtok-hidden">
					<td colspan="5" class="nrtok-ex-no-results-cell">
						<?php esc_html_e( 'No matching extras found.', 'nahian-restaurant-ordering-system' ); ?>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- Pagination Bar -->
		<nav id="nrtok-ex-pagination-bar" class="nrtok-ex-pagination-bar nrtok-hidden" aria-label="<?php esc_attr_e( 'Extras Pagination', 'nahian-restaurant-ordering-system' ); ?>">
			<div id="nrtok-ex-page-info" class="nrtok-ex-page-info"></div>
			<div id="nrtok-ex-page-controls" class="nrtok-ex-page-controls"></div>
		</nav>
	</div>
<?php endif; ?>

<style>
	.nrtok-hidden { display: none; }
	.nrtok-ex-no-results-cell { text-align: center; padding: 36px 16px; color: #94a3b8; font-size: 14px; font-weight: 500; }

	/* Empty State Card */
	.nrtok-ex-empty-card { text-align: center; padding: 54px 24px; }
	.nrtok-ex-empty-ico {
		width: 64px; height: 64px; border-radius: 18px; background: #fff7ed; color: #f97316;
		border: 1.5px solid #fed7aa; display: inline-flex; align-items: center; justify-content: center;
		margin-bottom: 16px;
	}
	.nrtok-ex-empty-title { margin: 0 0 6px; font-size: 18px; font-weight: 800; color: #0f172a; }
	.nrtok-ex-empty-desc { margin: 0 0 20px; font-size: 14px; color: #64748b; }
	.nrtok-ex-btn-icon { display: inline-flex; align-items: center; gap: 7px; }

	/* Table & Row Styling */
	.nrtok-ex-table-wrap {
		border: 1.5px solid #e2e8f0; border-radius: 14px; overflow-x: auto; background: #fff;
		box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
	}
	.nrtok-ex-table { margin: 0; border: none; border-radius: 0; box-shadow: none; }
	.nrtok-col-ex-id { width: 80px; }
	.nrtok-col-ex-price { width: 130px; }
	.nrtok-col-ex-used { width: 160px; }
	.nrtok-col-ex-act { width: 220px; }

	.nrtok-ex-name-cell { display: flex; align-items: center; gap: 11px; }
	.nrtok-ex-thumb-img {
		width: 36px; height: 36px; border-radius: 9px; object-fit: cover;
		border: 1px solid #e2e8f0; flex-shrink: 0;
	}
	.nrtok-ex-thumb-placeholder {
		width: 36px; height: 36px; border-radius: 9px; background: #fff7ed; color: #f97316;
		display: inline-flex; align-items: center; justify-content: center;
		border: 1px solid #fed7aa; flex-shrink: 0;
	}
	.nrtok-ex-name-link {
		font-size: 14px; font-weight: 700; color: #0f172a; text-decoration: none;
		transition: color 0.15s ease;
	}
	.nrtok-ex-name-link:hover { color: #f97316; }

	.nrtok-ex-price-val {
		color: #f97316; font-family: "Courier New", Courier, monospace; font-size: 14px;
	}

	.nrtok-ex-used-badge {
		display: inline-block; padding: 4px 11px; border-radius: 20px;
		font-size: 12px; font-weight: 700;
	}
	.nrtok-ex-badge-active { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
	.nrtok-ex-badge-empty { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

	/* Action Buttons */
	.nrtok-ex-act-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
	.nrtok-ex-act-btn {
		display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px;
		border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none;
		transition: all 0.15s ease;
	}
	.nrtok-act-view { background: #eff6ff; color: #1d4ed8; }
	.nrtok-act-view:hover { background: #dbeafe; color: #1e40af; }
	.nrtok-act-edit { background: #f1f5f9; color: #334155; }
	.nrtok-act-edit:hover { background: #e2e8f0; color: #0f172a; }
	.nrtok-act-del { background: #fef2f2; color: #dc2626; }
	.nrtok-act-del:hover { background: #fee2e2; color: #b91c1c; }

	/* Enhanced Pagination Bar */
	.nrtok-ex-pagination-bar {
		display: flex; align-items: center; justify-content: space-between;
		flex-wrap: wrap; gap: 12px; padding: 14px 18px;
		background: #f8fafc; border-top: 1px solid #e2e8f0;
	}
	.nrtok-ex-page-info {
		display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
		font-size: 13px; font-weight: 600; color: #64748b;
	}
	.nrtok-ex-page-info strong { color: #0f172a; font-weight: 800; }
	.nrtok-ex-page-badge {
		background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;
		padding: 2px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 800;
	}
	.nrtok-ex-page-controls {
		display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
	}
	.nrtok-ex-pg-numbers {
		display: inline-flex; align-items: center; gap: 4px;
		background: #ffffff; padding: 3px; border-radius: 11px; border: 1px solid #e2e8f0;
	}
	.nrtok-ex-pg-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 4px;
		min-width: 32px; height: 32px; padding: 0 10px;
		border-radius: 8px; border: 1.5px solid #e2e8f0; background: #ffffff;
		color: #334155; font-size: 12.5px; font-weight: 750; cursor: pointer;
		transition: all 0.15s ease;
	}
	.nrtok-ex-pg-numbers .nrtok-ex-pg-btn {
		border-color: transparent; background: transparent; padding: 0 7px;
	}
	.nrtok-ex-pg-btn:hover:not(:disabled):not(.active) {
		border-color: #cbd5e1; color: #0f172a; background: #f1f5f9;
	}
	.nrtok-ex-pg-numbers .nrtok-ex-pg-btn:hover:not(.active) {
		background: #f8fafc; border-color: #e2e8f0;
	}
	.nrtok-ex-pg-btn.active {
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		border-color: #f97316; color: #ffffff;
		box-shadow: 0 3px 8px rgba(249, 115, 22, 0.3);
	}
	.nrtok-ex-pg-btn:disabled {
		opacity: 0.42; cursor: not-allowed;
	}
	.nrtok-ex-pg-ellipsis {
		padding: 0 5px; color: #94a3b8; font-weight: 800; font-size: 12.5px; user-select: none;
	}
</style>

<script>
jQuery(document).ready(function($) {
	const perPage     = 10;
	let currentPage   = 1;
	const txtPrev     = <?php echo wp_json_encode( __( 'Prev', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtNext     = <?php echo wp_json_encode( __( 'Next', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtShowing  = <?php
	/* translators: %1$s: start item index, %2$s: end item index, %3$s: total item count */
	echo wp_json_encode( __( 'Showing %1$d–%2$d of %3$d extras', 'nahian-restaurant-ordering-system' ) );
	?>;
	const txtPageOf   = <?php
	/* translators: %1$s: current page number, %2$s: total page count */
	echo wp_json_encode( __( 'Page %1$d of %2$d', 'nahian-restaurant-ordering-system' ) );
	?>;

	function renderExtrasTable() {
		const q           = ($('#nrtok-ex-live-search').val() || '').toLowerCase().trim();
		const $allRows    = $('.nrtok-ex-row');
		const matchedRows = [];

		$allRows.each(function() {
			const nameAttr = String($(this).data('name') || '');
			if (nameAttr.indexOf(q) > -1) {
				matchedRows.push(this);
			}
			$(this).hide();
		});

		const totalItems = matchedRows.length;
		if (totalItems === 0) {
			$('#nrtok-ex-no-results-row').show();
			$('#nrtok-ex-pagination-bar').hide();
			return;
		}

		$('#nrtok-ex-no-results-row').hide();

		const totalPages = Math.ceil(totalItems / perPage);
		if (currentPage > totalPages) currentPage = totalPages;
		if (currentPage < 1) currentPage = 1;

		const startIdx = (currentPage - 1) * perPage;
		const endIdx   = Math.min(startIdx + perPage, totalItems);

		for (let i = startIdx; i < endIdx; i++) {
			$(matchedRows[i]).show();
		}

		if (totalItems > perPage) {
			const $infoWrap = $('#nrtok-ex-page-info').empty();
			const summaryStr = txtShowing
				.replace('%1$d', startIdx + 1)
				.replace('%2$d', endIdx)
				.replace('%3$d', totalItems);
			$infoWrap.append($('<span></span>').text(summaryStr));

			const pageBadgeStr = txtPageOf
				.replace('%1$d', currentPage)
				.replace('%2$d', totalPages);
			$infoWrap.append($('<span class="nrtok-ex-page-badge"></span>').text(pageBadgeStr));

			const $controls = $('#nrtok-ex-page-controls').empty();

			// Prev button
			const $prevBtn = $('<button type="button" class="nrtok-ex-pg-btn"></button>')
				.attr('data-page', currentPage - 1)
				.prop('disabled', currentPage === 1)
				.append('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>')
				.append($('<span></span>').text(txtPrev));
			$controls.append($prevBtn);

			// Smart page range with ellipses
			const $numGroup = $('<div class="nrtok-ex-pg-numbers"></div>');
			const pageRange = [];
			for (let p = 1; p <= totalPages; p++) {
				if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
					pageRange.push(p);
				}
			}

			let prevNum = 0;
			pageRange.forEach(function(p) {
				if (prevNum > 0 && (p - prevNum) > 1) {
					$numGroup.append('<span class="nrtok-ex-pg-ellipsis" aria-hidden="true">&hellip;</span>');
				}
				prevNum = p;

				const $numBtn = $('<button type="button" class="nrtok-ex-pg-btn"></button>')
					.attr('data-page', p)
					.text(p);
				if (p === currentPage) {
					$numBtn.addClass('active').attr('aria-current', 'page');
				}
				$numGroup.append($numBtn);
			});
			$controls.append($numGroup);

			// Next button
			const $nextBtn = $('<button type="button" class="nrtok-ex-pg-btn"></button>')
				.attr('data-page', currentPage + 1)
				.prop('disabled', currentPage === totalPages)
				.append($('<span></span>').text(txtNext))
				.append('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>');
			$controls.append($nextBtn);

			$('#nrtok-ex-pagination-bar').css('display', 'flex');
		} else {
			$('#nrtok-ex-pagination-bar').hide();
		}
	}

	renderExtrasTable();

	$('#nrtok-ex-live-search').on('input keyup', function() {
		currentPage = 1;
		renderExtrasTable();
	});

	$(document).on('click', '.nrtok-ex-pg-btn', function() {
		if ($(this).prop('disabled')) return;
		currentPage = parseInt($(this).data('page'), 10) || 1;
		renderExtrasTable();
	});

	$(document).on('click', '.js-nrtok-confirm-del', function(e) {
		const msg = $(this).data('confirm');
		if (msg && !window.confirm(msg)) {
			e.preventDefault();
		}
	});
});
</script>