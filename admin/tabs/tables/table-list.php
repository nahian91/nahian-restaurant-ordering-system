<?php
/**
 * Nahian POS Restaurant Tables Table List View
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

// Safe currency fallback.
if ( ! isset( $cur ) ) {
	$nrtok_settings = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
	$cur            = isset( $nrtok_settings['currency'] ) ? $nrtok_settings['currency'] : '৳';
}

$nrtok_clean_cur       = html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' );
$nrtok_base_tables_url = add_query_arg(
	array(
		'page' => 'nahian-restaurant-ordering-system',
		'tab'  => 'tables',
	),
	admin_url( 'admin.php' )
);
$nrtok_add_table_url   = add_query_arg( 'subtab', 'add', $nrtok_base_tables_url );
$nrtok_orders_url      = add_query_arg(
	array(
		'page' => 'nahian-restaurant-ordering-system',
		'tab'  => 'orders',
	),
	admin_url( 'admin.php' )
);
?>

<style>
	.nrtok-hidden { display: none; }
	.nrtok-tbl-no-results-cell { text-align: center; padding: 36px 16px; color: #94a3b8; font-size: 14px; font-weight: 500; }

	.nrtok-tbl-empty-card { text-align: center; padding: 60px 20px; }
	.nrtok-tbl-empty-ico {
		width: 56px; height: 56px; border-radius: 16px; background: #fff7ed; color: #f97316;
		display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;
	}
	.nrtok-tbl-empty-title { margin: 0 0 8px; font-size: 19px; color: #0f172a; }
	.nrtok-tbl-empty-desc { color: #64748b; margin: 0 0 20px; font-size: 14px; }

	.nrtok-tbl-table-wrap {
		width: 100%; overflow-x: auto; background: #fff; border-radius: 16px;
		border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
	}
	.nrtok-tbl-table { margin: 0; border: none; border-radius: 0; box-shadow: none; }
	.nrtok-tbl-thead-row { background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; }

	.nrtok-tbl-th-20 { padding: 16px 20px; }
	.nrtok-tbl-th-14 { padding: 16px 14px; }
	.nrtok-tbl-th-right { padding: 16px 20px; text-align: right; }

	.nrtok-tbl-td-20 { padding: 14px 20px; vertical-align: middle; }
	.nrtok-tbl-td-14 { padding: 14px; vertical-align: middle; }
	.nrtok-tbl-td-right { padding: 14px 20px; vertical-align: middle; text-align: right; }

	.nrtok-col-cap { width: 150px; }
	.nrtok-col-login { width: 230px; }
	.nrtok-col-status { width: 170px; }
	.nrtok-col-actions { width: 240px; }

	.nrtok-tbl-row { transition: background 0.15s ease; }
	.nrtok-tbl-row:hover { background-color: #fafaf9; }

	.nrtok-tbl-name-link { text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
	.nrtok-tbl-name-link:hover .nrtok-tbl-name-txt { color: #f97316; }
	.nrtok-tbl-name-txt { font-size: 15px; color: #0f172a; transition: color 0.15s ease; }

	.nrtok-tbl-cap-txt { font-weight: 700; color: #475569; }
	.nrtok-tbl-cred-box { font-size: 12px; color: #334155; }
	.nrtok-tbl-pass-code { background: #f1f5f9; padding: 1px 5px; border-radius: 4px; color: #ea580c; font-weight: 700; }

	.nrtok-tbl-st-badge {
		padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 700;
		display: inline-flex; align-items: center; gap: 5px;
	}
	.nrtok-tbl-st-busy { background: #fef3c7; color: #d97706; }
	.nrtok-tbl-st-free { background: #d1fae5; color: #059669; }
	.nrtok-tbl-st-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
	.nrtok-tbl-dot-busy { background: #f59e0b; }
	.nrtok-tbl-dot-free { background: #10b981; }
	.nrtok-tbl-ord-link { font-size: 11px; color: #ea580c; font-weight: 700; text-decoration: none; }
	.nrtok-tbl-ord-link:hover { text-decoration: underline; }

	.nrtok-tbl-act-group { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
	.nrtok-tbl-act-btn {
		padding: 6px 11px; border-radius: 8px; font-size: 12px; font-weight: 700;
		text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
		transition: all 0.15s ease; cursor: pointer;
	}
	.nrtok-act-view { background: #eff6ff; color: #1d4ed8; }
	.nrtok-act-view:hover { background: #dbeafe; color: #1e40af; }
	.nrtok-act-edit { background: #f1f5f9; color: #334155; }
	.nrtok-act-edit:hover { background: #e2e8f0; color: #0f172a; }
	.nrtok-act-del { background: #fef2f2; color: #dc2626; }
	.nrtok-act-del:hover { background: #fee2e2; color: #b91c1c; }
	.nrtok-tbl-btn-ico { display: inline-flex; align-items: center; gap: 7px; }

	/* Enhanced Pagination Bar */
	.nrtok-tbl-pagination-bar {
		display: flex; align-items: center; justify-content: space-between;
		flex-wrap: wrap; gap: 12px; padding: 14px 20px;
		background: #f8fafc; border-top: 1px solid #e2e8f0;
	}
	.nrtok-tbl-page-info {
		display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
		font-size: 13px; font-weight: 600; color: #64748b;
	}
	.nrtok-tbl-page-badge {
		background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;
		padding: 2px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 800;
	}
	.nrtok-tbl-page-controls { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
	.nrtok-tbl-pg-numbers {
		display: inline-flex; align-items: center; gap: 4px;
		background: #ffffff; padding: 3px; border-radius: 11px; border: 1px solid #e2e8f0;
	}
	.nrtok-tbl-pg-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 4px;
		min-width: 32px; height: 32px; padding: 0 10px;
		border-radius: 8px; border: 1.5px solid #e2e8f0; background: #ffffff;
		color: #334155; font-size: 12.5px; font-weight: 750; cursor: pointer;
		transition: all 0.15s ease;
	}
	.nrtok-tbl-pg-numbers .nrtok-tbl-pg-btn { border-color: transparent; background: transparent; padding: 0 7px; }
	.nrtok-tbl-pg-btn:hover:not(:disabled):not(.active) { border-color: #cbd5e1; color: #0f172a; background: #f1f5f9; }
	.nrtok-tbl-pg-numbers .nrtok-tbl-pg-btn:hover:not(.active) { background: #f8fafc; border-color: #e2e8f0; }
	.nrtok-tbl-pg-btn.active {
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		border-color: #f97316; color: #ffffff;
		box-shadow: 0 3px 8px rgba(249, 115, 22, 0.3);
	}
	.nrtok-tbl-pg-btn:disabled { opacity: 0.42; cursor: not-allowed; }
	.nrtok-tbl-pg-ellipsis { padding: 0 5px; color: #94a3b8; font-weight: 800; font-size: 12.5px; user-select: none; }
</style>

<?php if ( empty( $tables ) ) : ?>
	<div class="nrtok-card nrtok-full-card nrtok-tbl-empty-card">
		<div class="nrtok-tbl-empty-ico">
			<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
		</div>
		<h3 class="nrtok-tbl-empty-title"><?php esc_html_e( 'No Tables Configured Yet', 'nahian-restaurant-ordering-system' ); ?></h3>
		<p class="nrtok-tbl-empty-desc"><?php esc_html_e( 'Add your first restaurant table with login credentials and QR codes.', 'nahian-restaurant-ordering-system' ); ?></p>
		<a href="<?php echo esc_url( $nrtok_add_table_url ); ?>" class="nrtok-btn nrtok-tbl-btn-ico">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
			<span><?php esc_html_e( 'Add First Table', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	</div>
<?php else : ?>
	<div class="nrtok-tbl-table-wrap">
		<table class="nrtok-table nrtok-full-table nrtok-tbl-table">
			<thead>
				<tr class="nrtok-tbl-thead-row">
					<th class="nrtok-tbl-th-20"><?php esc_html_e( 'Table Name', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-cap nrtok-tbl-th-14"><?php esc_html_e( 'Capacity', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-login nrtok-tbl-th-14"><?php esc_html_e( 'Tablet Login / Pass', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-status nrtok-tbl-th-14"><?php esc_html_e( 'Status', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-actions nrtok-tbl-th-right"><?php esc_html_e( 'Actions', 'nahian-restaurant-ordering-system' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $tables as $tbl ) :
					$tbl_id      = absint( $tbl->id );
					$tbl_name    = isset( $tbl->table_name ) ? $tbl->table_name : '';
					$active_info = ( isset( $active_rows ) && isset( $active_rows[ $tbl_name ] ) ) ? $active_rows[ $tbl_name ] : null;
					$is_busy     = ! empty( $active_info );
					$search_blob = strtolower( 'table ' . $tbl_name . ' ' . ( isset( $tbl->table_login ) ? $tbl->table_login : '' ) );

					$view_url = add_query_arg( 'view_table', $tbl_id, $nrtok_base_tables_url );
					$edit_url = add_query_arg( 'edit_table', $tbl_id, $nrtok_base_tables_url );
					$del_url  = wp_nonce_url(
						add_query_arg(
							array(
								'subtab'    => 'all',
								'del_table' => $tbl_id,
							),
							$nrtok_base_tables_url
						),
						'nrtok_delete_table_' . $tbl_id
					);

					$confirm_msg = sprintf(
						/* translators: %s: table name/number */
						__( 'Are you sure you want to delete Table "%s"?', 'nahian-restaurant-ordering-system' ),
						$tbl_name
					);
					?>
				<tr class="nrtok-tbl-row" data-search="<?php echo esc_attr( $search_blob ); ?>" data-status="<?php echo $is_busy ? 'busy' : 'free'; ?>">
					<td class="nrtok-tbl-td-20">
						<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-tbl-name-link">
							<strong class="nrtok-tbl-name-txt">
								<?php
								printf(
									/* translators: %s: table name/number */
									esc_html__( 'Table %s', 'nahian-restaurant-ordering-system' ),
									esc_html( $tbl_name )
								);
								?>
							</strong>
						</a>
					</td>
					<td class="nrtok-tbl-td-14">
						<span class="nrtok-tbl-cap-txt">
							<?php
							printf(
								/* translators: %d: number of seats */
								esc_html__( '%d Persons', 'nahian-restaurant-ordering-system' ),
								absint( $tbl->seats )
							);
							?>
						</span>
					</td>
					<td class="nrtok-tbl-td-14">
						<div class="nrtok-tbl-cred-box">
							<div><?php esc_html_e( 'Login:', 'nahian-restaurant-ordering-system' ); ?> <strong><?php echo esc_html( ! empty( $tbl->table_login ) ? $tbl->table_login : '—' ); ?></strong></div>
							<div><?php esc_html_e( 'Pass:', 'nahian-restaurant-ordering-system' ); ?> <code class="nrtok-tbl-pass-code"><?php echo esc_html( ! empty( $tbl->table_password ) ? $tbl->table_password : '—' ); ?></code></div>
						</div>
					</td>
					<td class="nrtok-tbl-td-14">
						<span class="nrtok-tbl-st-badge <?php echo $is_busy ? 'nrtok-tbl-st-busy' : 'nrtok-tbl-st-free'; ?>">
							<span class="nrtok-tbl-st-dot <?php echo $is_busy ? 'nrtok-tbl-dot-busy' : 'nrtok-tbl-dot-free'; ?>"></span>
							<span><?php echo $is_busy ? esc_html__( 'Occupied', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Available', 'nahian-restaurant-ordering-system' ); ?></span>
						</span>
						<?php if ( $is_busy ) : ?>
							<br><a href="<?php echo esc_url( $nrtok_orders_url ); ?>" class="nrtok-tbl-ord-link">
								<?php
								printf(
									/* translators: 1: order count, 2: running total amount */
									esc_html__( '%1$d Order (%2$s)', 'nahian-restaurant-ordering-system' ),
									absint( $active_info->ord_cnt ),
									esc_html( $nrtok_clean_cur . number_format( (float) $active_info->running_total, 2 ) )
								);
								?>
							</a>
						<?php endif; ?>
					</td>
					<td class="nrtok-tbl-td-right">
						<div class="nrtok-tbl-act-group">
							<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-tbl-act-btn nrtok-act-view" title="<?php esc_attr_e( 'View Table', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
								<span><?php esc_html_e( 'View', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-tbl-act-btn nrtok-act-edit" title="<?php esc_attr_e( 'Edit Table', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
								<span><?php esc_html_e( 'Edit', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $del_url ); ?>" class="nrtok-tbl-act-btn nrtok-act-del js-nrtok-confirm-del" data-confirm="<?php echo esc_attr( $confirm_msg ); ?>" title="<?php esc_attr_e( 'Delete Table', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
								<span><?php esc_html_e( 'Del', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
				<tr id="nrtok-tbl-no-results-row" class="nrtok-hidden">
					<td colspan="5" class="nrtok-tbl-no-results-cell">
						<?php esc_html_e( 'No matching tables found.', 'nahian-restaurant-ordering-system' ); ?>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- Pagination Bar -->
		<nav id="nrtok-tbl-pagination-bar" class="nrtok-tbl-pagination-bar nrtok-hidden" aria-label="<?php esc_attr_e( 'Tables Pagination', 'nahian-restaurant-ordering-system' ); ?>">
			<div id="nrtok-tbl-page-info" class="nrtok-tbl-page-info"></div>
			<div id="nrtok-tbl-page-controls" class="nrtok-tbl-page-controls"></div>
		</nav>
	</div>
<?php endif; ?>

<script>
jQuery(document).ready(function($) {
	const perPage           = 10;
	let currentPage         = 1;
	let currentStatusFilter = 'all';
	const txtPrev           = <?php echo wp_json_encode( __( 'Prev', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtNext           = <?php echo wp_json_encode( __( 'Next', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtShowing        = <?php echo wp_json_encode( __( 'Showing %1$d–%2$d of %3$d tables', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtPageOf         = <?php echo wp_json_encode( __( 'Page %1$d of %2$d', 'nahian-restaurant-ordering-system' ) ); ?>;

	function renderTablesList() {
		const q           = ($('#nrtok-tbl-live-search').val() || '').toLowerCase().trim();
		const $allRows    = $('.nrtok-tbl-row');
		const matchedRows = [];

		$allRows.each(function() {
			const matchText   = String($(this).data('search') || '').indexOf(q) > -1;
			const matchStatus = (currentStatusFilter === 'all') || ($(this).data('status') === currentStatusFilter);
			if (matchText && matchStatus) {
				matchedRows.push(this);
			}
			$(this).hide();
		});

		const totalItems = matchedRows.length;
		if (totalItems === 0) {
			$('#nrtok-tbl-no-results-row').show();
			$('#nrtok-tbl-pagination-bar').hide();
			return;
		}

		$('#nrtok-tbl-no-results-row').hide();

		const totalPages = Math.ceil(totalItems / perPage);
		if (currentPage > totalPages) currentPage = totalPages;
		if (currentPage < 1) currentPage = 1;

		const startIdx = (currentPage - 1) * perPage;
		const endIdx   = Math.min(startIdx + perPage, totalItems);

		for (let i = startIdx; i < endIdx; i++) {
			$(matchedRows[i]).show();
		}

		if (totalItems > perPage) {
			const $infoWrap = $('#nrtok-tbl-page-info').empty();
			const summaryStr = txtShowing
				.replace('%1$d', startIdx + 1)
				.replace('%2$d', endIdx)
				.replace('%3$d', totalItems);
			$infoWrap.append($('<span></span>').text(summaryStr));

			const pageBadgeStr = txtPageOf
				.replace('%1$d', currentPage)
				.replace('%2$d', totalPages);
			$infoWrap.append($('<span class="nrtok-tbl-page-badge"></span>').text(pageBadgeStr));

			const $controls = $('#nrtok-tbl-page-controls').empty();

			const $prevBtn = $('<button type="button" class="nrtok-tbl-pg-btn"></button>')
				.attr('data-page', currentPage - 1)
				.prop('disabled', currentPage === 1)
				.append('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>')
				.append($('<span></span>').text(txtPrev));
			$controls.append($prevBtn);

			const $numGroup = $('<div class="nrtok-tbl-pg-numbers"></div>');
			const pageRange = [];
			for (let p = 1; p <= totalPages; p++) {
				if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
					pageRange.push(p);
				}
			}

			let prevNum = 0;
			pageRange.forEach(function(p) {
				if (prevNum > 0 && (p - prevNum) > 1) {
					$numGroup.append('<span class="nrtok-tbl-pg-ellipsis" aria-hidden="true">&hellip;</span>');
				}
				prevNum = p;

				const $numBtn = $('<button type="button" class="nrtok-tbl-pg-btn"></button>')
					.attr('data-page', p)
					.text(p);
				if (p === currentPage) {
					$numBtn.addClass('active').attr('aria-current', 'page');
				}
				$numGroup.append($numBtn);
			});
			$controls.append($numGroup);

			const $nextBtn = $('<button type="button" class="nrtok-tbl-pg-btn"></button>')
				.attr('data-page', currentPage + 1)
				.prop('disabled', currentPage === totalPages)
				.append($('<span></span>').text(txtNext))
				.append('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>');
			$controls.append($nextBtn);

			$('#nrtok-tbl-pagination-bar').css('display', 'flex');
		} else {
			$('#nrtok-tbl-pagination-bar').hide();
		}
	}

	renderTablesList();

	$('#nrtok-tbl-live-search').on('input keyup', function() {
		currentPage = 1;
		renderTablesList();
	});

	$('.nrtok-tbl-st-pill').on('click', function() {
		$('.nrtok-tbl-st-pill').removeClass('active');
		$(this).addClass('active');
		currentStatusFilter = $(this).data('st') || 'all';
		currentPage = 1;
		renderTablesList();
	});

	$(document).on('click', '.js-nrtok-confirm-del', function(e) {
		const msg = $(this).data('confirm');
		if (msg && !window.confirm(msg)) {
			e.preventDefault();
		}
	});
});
</script>