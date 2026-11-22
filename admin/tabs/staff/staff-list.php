<?php
/**
 * Nahian POS Staff Management Table List View
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

$nrtok_base_staff_url = add_query_arg(
	array(
		'page' => 'nahian-restaurant-ordering-system',
		'tab'  => 'staff',
	),
	admin_url( 'admin.php' )
);
$nrtok_add_staff_url  = add_query_arg( 'subtab', 'add', $nrtok_base_staff_url );
?>

<?php if ( empty( $staff_list ) ) : ?>
	<div class="nrtok-card nrtok-full-card nrtok-stf-empty-card">
		<div class="nrtok-stf-empty-ico">
			<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
		</div>
		<h3 class="nrtok-stf-empty-title"><?php esc_html_e( 'No Staff Members Added Yet', 'nahian-restaurant-ordering-system' ); ?></h3>
		<p class="nrtok-stf-empty-desc"><?php esc_html_e( 'Add your chefs, waiters, and managers to keep track of shift schedules.', 'nahian-restaurant-ordering-system' ); ?></p>
		<a href="<?php echo esc_url( $nrtok_add_staff_url ); ?>" class="nrtok-btn nrtok-stf-btn-icon">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
			<span><?php esc_html_e( 'Add First Staff', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	</div>
<?php else : ?>
	<div class="nrtok-stf-table-wrap">
		<table class="nrtok-table nrtok-full-table nrtok-stf-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Staff Member', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-stf-role"><?php esc_html_e( 'Role', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-stf-phone"><?php esc_html_e( 'Phone', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-stf-status"><?php esc_html_e( 'Shift Status', 'nahian-restaurant-ordering-system' ); ?></th>
					<th class="nrtok-col-stf-act"><?php esc_html_e( 'Actions', 'nahian-restaurant-ordering-system' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $staff_list as $st ) :
					$staff_id   = absint( $st->id );
					$staff_name = isset( $st->name ) ? $st->name : '';
					$staff_role = isset( $st->role ) ? $st->role : '';
					$staff_phn  = isset( $st->phone ) ? $st->phone : '';
					$staff_eml  = isset( $st->email ) ? $st->email : '';
					$staff_stat = isset( $st->status ) ? $st->status : 'Active';

					$initial     = strtoupper( mb_substr( $staff_name, 0, 1 ) );
					$is_active   = ( 'Active' === $staff_stat );
					$search_blob = strtolower( $staff_name . ' ' . $staff_phn . ' ' . $staff_eml );

					$view_url   = add_query_arg( 'view_staff', $staff_id, $nrtok_base_staff_url );
					$edit_url   = add_query_arg( 'edit_staff', $staff_id, $nrtok_base_staff_url );
					$toggle_url = wp_nonce_url(
						add_query_arg(
							array(
								'subtab'       => 'all',
								'toggle_staff' => $staff_id,
							),
							$nrtok_base_staff_url
						),
						'nrtok_toggle_staff_' . $staff_id
					);
					$del_url    = wp_nonce_url(
						add_query_arg(
							array(
								'subtab'    => 'all',
								'del_staff' => $staff_id,
							),
							$nrtok_base_staff_url
						),
						'nrtok_delete_staff_' . $staff_id
					);

					$confirm_msg = sprintf(
						/* translators: %s: staff member name */
						__( 'Are you sure you want to remove "%s" from staff list?', 'nahian-restaurant-ordering-system' ),
						$staff_name
					);
					?>
				<tr class="nrtok-stf-row" data-search="<?php echo esc_attr( $search_blob ); ?>" data-role="<?php echo esc_attr( strtolower( $staff_role ) ); ?>" data-status="<?php echo esc_attr( strtolower( $staff_stat ) ); ?>">
					<td>
						<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-stf-member-link">
							<div class="nrtok-stf-avatar"><?php echo esc_html( $initial ); ?></div>
							<div>
								<strong class="nrtok-stf-member-name"><?php echo esc_html( $staff_name ); ?></strong>
								<?php if ( ! empty( $staff_eml ) ) : ?>
									<div class="nrtok-stf-member-email"><?php echo esc_html( $staff_eml ); ?></div>
								<?php endif; ?>
							</div>
						</a>
					</td>
					<td>
						<span class="nrtok-stf-role-badge"><?php echo esc_html( $staff_role ); ?></span>
					</td>
					<td class="nrtok-stf-phone-cell"><?php echo esc_html( ! empty( $staff_phn ) ? $staff_phn : '—' ); ?></td>
					<td>
						<span class="nrtok-stf-status-pill <?php echo $is_active ? 'nrtok-stf-pill-active' : 'nrtok-stf-pill-off'; ?>">
							<span class="nrtok-stf-dot <?php echo $is_active ? 'nrtok-stf-dot-active' : 'nrtok-stf-dot-off'; ?>"></span>
							<span><?php echo esc_html( $staff_stat ); ?></span>
						</span>
					</td>
					<td>
						<div class="nrtok-stf-act-group">
							<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-stf-act-btn nrtok-act-view" title="<?php esc_attr_e( 'View Profile', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
								<span><?php esc_html_e( 'View', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $toggle_url ); ?>" class="nrtok-stf-act-btn nrtok-act-shift" title="<?php esc_attr_e( 'Switch Shift Status', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
								<span><?php esc_html_e( 'Shift', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-stf-act-btn nrtok-act-edit" title="<?php esc_attr_e( 'Edit Staff', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
								<span><?php esc_html_e( 'Edit', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $del_url ); ?>" class="nrtok-stf-act-btn nrtok-act-del nrtok-js-confirm-del" data-confirm="<?php echo esc_attr( $confirm_msg ); ?>" title="<?php esc_attr_e( 'Delete Staff', 'nahian-restaurant-ordering-system' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
								<span><?php esc_html_e( 'Delete', 'nahian-restaurant-ordering-system' ); ?></span>
							</a>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
				<tr id="nrtok-stf-no-results-row" class="nrtok-stf-hidden-row">
					<td colspan="5" class="nrtok-stf-no-results-cell">
						<?php esc_html_e( 'No matching staff members found.', 'nahian-restaurant-ordering-system' ); ?>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- Pagination Bar -->
		<nav id="nrtok-stf-pagination-bar" class="nrtok-stf-pagination-bar nrtok-stf-hidden-row" aria-label="<?php esc_attr_e( 'Staff Pagination', 'nahian-restaurant-ordering-system' ); ?>">
			<div id="nrtok-stf-page-info" class="nrtok-stf-page-info"></div>
			<div id="nrtok-stf-page-controls" class="nrtok-stf-page-controls"></div>
		</nav>
	</div>
<?php endif; ?>

<style>
	.nrtok-stf-hidden-row { display: none; }
	.nrtok-stf-no-results-cell { text-align: center; padding: 36px 16px; color: #94a3b8; font-size: 14px; font-weight: 500; }

	/* Column Widths */
	.nrtok-col-stf-role { width: 150px; }
	.nrtok-col-stf-phone { width: 160px; }
	.nrtok-col-stf-status { width: 140px; }
	.nrtok-col-stf-act { width: 275px; }

	/* Empty State Card */
	.nrtok-stf-empty-card { text-align: center; padding: 54px 24px; }
	.nrtok-stf-empty-ico {
		width: 64px; height: 64px; border-radius: 18px; background: #fff7ed; color: #f97316;
		border: 1.5px solid #fed7aa; display: inline-flex; align-items: center; justify-content: center;
		margin-bottom: 16px;
	}
	.nrtok-stf-empty-title { margin: 0 0 6px; font-size: 18px; font-weight: 800; color: #0f172a; }
	.nrtok-stf-empty-desc { margin: 0 0 20px; font-size: 14px; color: #64748b; }
	.nrtok-stf-btn-icon { display: inline-flex; align-items: center; gap: 7px; }

	/* Table & Row Styling */
	.nrtok-stf-table-wrap {
		border: 1.5px solid #e2e8f0; border-radius: 14px; overflow-x: auto; background: #fff;
		box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
	}
	.nrtok-stf-table { margin: 0; border: none; border-radius: 0; box-shadow: none; }
	.nrtok-stf-member-link { display: inline-flex; align-items: center; gap: 11px; text-decoration: none; color: #0f172a; }
	.nrtok-stf-member-link:hover .nrtok-stf-member-name { color: #f97316; }
	.nrtok-stf-avatar {
		width: 38px; height: 38px; border-radius: 10px; background: #fff7ed; color: #ea580c;
		border: 1px solid #fed7aa; font-weight: 800; font-size: 14px;
		display: flex; align-items: center; justify-content: center; flex-shrink: 0;
	}
	.nrtok-stf-member-name { font-size: 14px; font-weight: 750; transition: color 0.15s ease; display: block; }
	.nrtok-stf-member-email { font-size: 12px; color: #64748b; font-weight: 500; margin-top: 2px; }

	.nrtok-stf-role-badge {
		display: inline-block; padding: 4px 11px; border-radius: 8px;
		background: #f1f5f9; color: #334155; font-size: 12px; font-weight: 700;
	}
	.nrtok-stf-phone-cell { font-size: 13.5px; color: #475569; font-weight: 500; }

	.nrtok-stf-status-pill {
		display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px;
		border-radius: 20px; font-size: 12px; font-weight: 700;
	}
	.nrtok-stf-pill-active { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
	.nrtok-stf-pill-off { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
	.nrtok-stf-dot { width: 7px; height: 7px; border-radius: 50%; }
	.nrtok-stf-dot-active { background: #10b981; }
	.nrtok-stf-dot-off { background: #94a3b8; }

	/* Action Buttons */
	.nrtok-stf-act-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
	.nrtok-stf-act-btn {
		display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px;
		border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none;
		transition: all 0.15s ease;
	}
	.nrtok-act-view { background: #eff6ff; color: #1d4ed8; }
	.nrtok-act-view:hover { background: #dbeafe; color: #1e40af; }
	.nrtok-act-shift { background: #fff7ed; color: #c2410c; }
	.nrtok-act-shift:hover { background: #ffedd5; color: #9a3412; }
	.nrtok-act-edit { background: #f1f5f9; color: #334155; }
	.nrtok-act-edit:hover { background: #e2e8f0; color: #0f172a; }
	.nrtok-act-del { background: #fef2f2; color: #dc2626; }
	.nrtok-act-del:hover { background: #fee2e2; color: #b91c1c; }

	/* Enhanced Pagination Bar */
	.nrtok-stf-pagination-bar {
		display: flex; align-items: center; justify-content: space-between;
		flex-wrap: wrap; gap: 12px; padding: 14px 18px;
		background: #f8fafc; border-top: 1px solid #e2e8f0;
	}
	.nrtok-stf-page-info {
		display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
		font-size: 13px; font-weight: 600; color: #64748b;
	}
	.nrtok-stf-page-badge {
		background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;
		padding: 2px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 800;
	}
	.nrtok-stf-page-controls { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
	.nrtok-stf-pg-numbers {
		display: inline-flex; align-items: center; gap: 4px;
		background: #ffffff; padding: 3px; border-radius: 11px; border: 1px solid #e2e8f0;
	}
	.nrtok-stf-pg-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 4px;
		min-width: 32px; height: 32px; padding: 0 10px;
		border-radius: 8px; border: 1.5px solid #e2e8f0; background: #ffffff;
		color: #334155; font-size: 12.5px; font-weight: 750; cursor: pointer;
		transition: all 0.15s ease;
	}
	.nrtok-stf-pg-numbers .nrtok-stf-pg-btn { border-color: transparent; background: transparent; padding: 0 7px; }
	.nrtok-stf-pg-btn:hover:not(:disabled):not(.active) { border-color: #cbd5e1; color: #0f172a; background: #f1f5f9; }
	.nrtok-stf-pg-numbers .nrtok-stf-pg-btn:hover:not(.active) { background: #f8fafc; border-color: #e2e8f0; }
	.nrtok-stf-pg-btn.active {
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		border-color: #f97316; color: #ffffff;
		box-shadow: 0 3px 8px rgba(249, 115, 22, 0.3);
	}
	.nrtok-stf-pg-btn:disabled { opacity: 0.42; cursor: not-allowed; }
	.nrtok-stf-pg-ellipsis { padding: 0 5px; color: #94a3b8; font-weight: 800; font-size: 12.5px; user-select: none; }
</style>

<script>
jQuery(document).ready(function($) {
	const perPage     = 10;
	let currentPage   = 1;
	const txtPrev     = <?php echo wp_json_encode( __( 'Prev', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtNext     = <?php echo wp_json_encode( __( 'Next', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtShowing  = <?php echo wp_json_encode( __( 'Showing %1$d–%2$d of %3$d staff members', 'nahian-restaurant-ordering-system' ) ); ?>;
	const txtPageOf   = <?php echo wp_json_encode( __( 'Page %1$d of %2$d', 'nahian-restaurant-ordering-system' ) ); ?>;

	function renderStaffTable() {
		const searchVal   = ($('#nrtok-stf-live-search').val() || '').toLowerCase().trim();
		const roleVal     = ($('#nrtok-stf-role-filter').val() || 'all').toLowerCase();
		const statusVal   = ($('#nrtok-stf-status-filter').val() || 'all').toLowerCase();
		const $allRows    = $('.nrtok-stf-row');
		const matchedRows = [];

		$allRows.each(function() {
			const matchText   = String($(this).data('search') || '').indexOf(searchVal) > -1;
			const matchRole   = (roleVal === 'all') || ($(this).data('role') === roleVal);
			const matchStatus = (statusVal === 'all') || ($(this).data('status') === statusVal);
			if (matchText && matchRole && matchStatus) {
				matchedRows.push(this);
			}
			$(this).hide();
		});

		const totalItems = matchedRows.length;
		if (totalItems === 0) {
			$('#nrtok-stf-no-results-row').show();
			$('#nrtok-stf-pagination-bar').hide();
			return;
		}

		$('#nrtok-stf-no-results-row').hide();

		const totalPages = Math.ceil(totalItems / perPage);
		if (currentPage > totalPages) currentPage = totalPages;
		if (currentPage < 1) currentPage = 1;

		const startIdx = (currentPage - 1) * perPage;
		const endIdx   = Math.min(startIdx + perPage, totalItems);

		for (let i = startIdx; i < endIdx; i++) {
			$(matchedRows[i]).show();
		}

		if (totalItems > perPage) {
			const $infoWrap = $('#nrtok-stf-page-info').empty();
			const summaryStr = txtShowing
				.replace('%1$d', startIdx + 1)
				.replace('%2$d', endIdx)
				.replace('%3$d', totalItems);
			$infoWrap.append($('<span></span>').text(summaryStr));

			const pageBadgeStr = txtPageOf
				.replace('%1$d', currentPage)
				.replace('%2$d', totalPages);
			$infoWrap.append($('<span class="nrtok-stf-page-badge"></span>').text(pageBadgeStr));

			const $controls = $('#nrtok-stf-page-controls').empty();

			const $prevBtn = $('<button type="button" class="nrtok-stf-pg-btn"></button>')
				.attr('data-page', currentPage - 1)
				.prop('disabled', currentPage === 1)
				.append('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>')
				.append($('<span></span>').text(txtPrev));
			$controls.append($prevBtn);

			const $numGroup = $('<div class="nrtok-stf-pg-numbers"></div>');
			const pageRange = [];
			for (let p = 1; p <= totalPages; p++) {
				if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
					pageRange.push(p);
				}
			}

			let prevNum = 0;
			pageRange.forEach(function(p) {
				if (prevNum > 0 && (p - prevNum) > 1) {
					$numGroup.append('<span class="nrtok-stf-pg-ellipsis" aria-hidden="true">&hellip;</span>');
				}
				prevNum = p;

				const $numBtn = $('<button type="button" class="nrtok-stf-pg-btn"></button>')
					.attr('data-page', p)
					.text(p);
				if (p === currentPage) {
					$numBtn.addClass('active').attr('aria-current', 'page');
				}
				$numGroup.append($numBtn);
			});
			$controls.append($numGroup);

			const $nextBtn = $('<button type="button" class="nrtok-stf-pg-btn"></button>')
				.attr('data-page', currentPage + 1)
				.prop('disabled', currentPage === totalPages)
				.append($('<span></span>').text(txtNext))
				.append('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>');
			$controls.append($nextBtn);

			$('#nrtok-stf-pagination-bar').css('display', 'flex');
		} else {
			$('#nrtok-stf-pagination-bar').hide();
		}
	}

	renderStaffTable();

	$('#nrtok-stf-live-search').on('input keyup', function() {
		currentPage = 1;
		renderStaffTable();
	});

	$('#nrtok-stf-role-filter, #nrtok-stf-status-filter').on('change', function() {
		currentPage = 1;
		renderStaffTable();
	});

	$(document).on('click', '.nrtok-stf-pg-btn', function() {
		if ($(this).prop('disabled')) return;
		currentPage = parseInt($(this).data('page'), 10) || 1;
		renderStaffTable();
	});

	$(document).on('click', '.nrtok-js-confirm-del', function(e) {
		const msg = $(this).data('confirm');
		if (msg && !window.confirm(msg)) {
			e.preventDefault();
		}
	});
});
</script>