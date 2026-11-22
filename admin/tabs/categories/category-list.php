<?php
/**
 * Nahian POS Menu Categories Table List View
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

$nrtok_base_cats_url = add_query_arg(
	array(
		'page' => 'nahian-restaurant-ordering-system',
		'tab'  => 'categories',
	),
	admin_url( 'admin.php' )
);
$nrtok_add_cat_url = add_query_arg( 'subtab', 'add', $nrtok_base_cats_url );
?>

<?php if ( empty( $cats ) ) : ?>
	<div class="nrtok-card nrtok-full-card nrtok-cat-empty-card">
		<div class="nrtok-cat-empty-ico">
			<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
		</div>
		<h3 class="nrtok-cat-empty-title"><?php esc_html_e( 'No Categories Created Yet', 'nahian-restaurant-ordering-system' ); ?></h3>
		<p class="nrtok-cat-empty-desc"><?php esc_html_e( 'Create categories like Starters, Main Course, or Drinks to organize your dishes.', 'nahian-restaurant-ordering-system' ); ?></p>
		<a href="<?php echo esc_url( $nrtok_add_cat_url ); ?>" class="nrtok-btn nrtok-cat-btn-icon">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
			<span><?php esc_html_e( 'Add First Category', 'nahian-restaurant-ordering-system' ); ?></span>
		</a>
	</div>
<?php else : ?>
	<div class="nrtok-card nrtok-full-card nrtok-cat-table-card">
		<div class="nrtok-table-responsive">
			<table class="nrtok-table nrtok-full-table nrtok-cat-table">
				<thead>
					<tr class="nrtok-cat-thead-row">
						<th class="nrtok-col-cat-order nrtok-cat-th-order"><?php esc_html_e( 'Order', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-cat-th-name"><?php esc_html_e( 'Category Name', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-cat-dishes nrtok-cat-th-dishes"><?php esc_html_e( 'Assigned Dishes', 'nahian-restaurant-ordering-system' ); ?></th>
						<th class="nrtok-col-cat-actions nrtok-cat-th-actions"><?php esc_html_e( 'Actions', 'nahian-restaurant-ordering-system' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $cats as $c ) :
						$cat_id     = absint( $c->id );
						$cat_dishes = isset( $items_by_cat[ $c->name ] ) && is_array( $items_by_cat[ $c->name ] ) ? $items_by_cat[ $c->name ] : array();
						$dish_count = count( $cat_dishes );
						$cat_order  = isset( $c->category_order ) ? absint( $c->category_order ) : 0;

						$view_url = add_query_arg( 'view_cat', $cat_id, $nrtok_base_cats_url );
						$edit_url = add_query_arg( 'edit_cat', $cat_id, $nrtok_base_cats_url );
						$del_url  = wp_nonce_url(
							add_query_arg(
								array(
									'subtab'  => 'all',
									'del_cat' => $cat_id,
								),
								$nrtok_base_cats_url
							),
							'nrtok_delete_cat_' . $cat_id
						);

						$confirm_msg = sprintf(
							/* translators: %s: category name */
							__( 'Are you sure you want to delete "%s"?', 'nahian-restaurant-ordering-system' ),
							$c->name
						);
						?>
					<tr class="nrtok-cat-row" data-name="<?php echo esc_attr( strtolower( $c->name ) ); ?>">
						<td class="nrtok-cat-td-order">
							<span class="nrtok-cat-order-badge"><?php echo absint( $cat_order ); ?></span>
						</td>
						<td class="nrtok-cat-td-name">
							<div class="nrtok-cat-name-flex">
								<?php if ( ! empty( $c->image ) ) : ?>
									<img src="<?php echo esc_url( $c->image ); ?>" alt="<?php echo esc_attr( $c->name ); ?>" class="nrtok-cat-thumb">
								<?php else : ?>
									<span class="nrtok-cat-thumb-placeholder">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
									</span>
								<?php endif; ?>
								<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-cat-name-link">
									<?php echo esc_html( $c->name ); ?>
								</a>
							</div>
						</td>
						<td class="nrtok-cat-td-dishes">
							<span class="nrtok-cat-dish-badge <?php echo ( $dish_count > 0 ) ? 'nrtok-cat-badge-active' : 'nrtok-cat-badge-empty'; ?>">
								<?php
								printf(
									/* translators: %d: count of assigned dishes */
									esc_html__( '%d dish(es)', 'nahian-restaurant-ordering-system' ),
									absint( $dish_count )
								);
								?>
							</span>
						</td>
						<td class="nrtok-cat-td-actions">
							<div class="nrtok-cat-act-group">
								<a href="<?php echo esc_url( $view_url ); ?>" class="nrtok-cat-act-btn nrtok-act-view" title="<?php esc_attr_e( 'View Details', 'nahian-restaurant-ordering-system' ); ?>">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
									<span><?php esc_html_e( 'View', 'nahian-restaurant-ordering-system' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="nrtok-cat-act-btn nrtok-act-edit" title="<?php esc_attr_e( 'Edit Category', 'nahian-restaurant-ordering-system' ); ?>">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
									<span><?php esc_html_e( 'Edit', 'nahian-restaurant-ordering-system' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $del_url ); ?>" class="nrtok-cat-act-btn nrtok-act-del js-nrtok-confirm-del" data-confirm="<?php echo esc_attr( $confirm_msg ); ?>" title="<?php esc_attr_e( 'Delete Category', 'nahian-restaurant-ordering-system' ); ?>">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
									<span><?php esc_html_e( 'Delete', 'nahian-restaurant-ordering-system' ); ?></span>
								</a>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
<?php endif; ?>

<style>
	.nrtok-col-cat-order { width: 80px; }
	.nrtok-col-cat-dishes { width: 180px; }
	.nrtok-col-cat-actions { width: 250px; }

	.nrtok-cat-table-card {
		padding: 0; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 16px;
		box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
	}
	.nrtok-table-responsive { width: 100%; overflow-x: auto; }
	.nrtok-cat-table { margin: 0; border: none; border-radius: 0; box-shadow: none; }
	.nrtok-cat-thead-row { background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; }
	.nrtok-cat-th-order { padding: 16px 14px; text-align: center; }
	.nrtok-cat-th-name { padding: 16px 20px; }
	.nrtok-cat-th-dishes { padding: 16px 14px; }
	.nrtok-cat-th-actions { padding: 16px 20px; text-align: right; }

	.nrtok-cat-td-order { padding: 14px; vertical-align: middle; text-align: center; }
	.nrtok-cat-td-name { padding: 14px 20px; vertical-align: middle; }
	.nrtok-cat-td-dishes { padding: 14px; vertical-align: middle; }
	.nrtok-cat-td-actions { padding: 14px 20px; vertical-align: middle; text-align: right; }

	.nrtok-cat-name-flex { display: flex; align-items: center; gap: 10px; }
	.nrtok-cat-thumb {
		width: 34px; height: 34px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;
	}
	.nrtok-cat-thumb-placeholder {
		width: 34px; height: 34px; border-radius: 8px; background: #fff7ed; color: #f97316;
		display: inline-flex; align-items: center; justify-content: center; border: 1px solid #ffedd5; flex-shrink: 0;
	}

	.nrtok-cat-order-badge {
		background: #f8fafc; color: #475569; border: 1px solid #cbd5e1;
		width: 30px; height: 30px; border-radius: 50%; display: inline-flex;
		align-items: center; justify-content: center; font-size: 12px; font-weight: 800;
		font-family: "Courier New", Courier, monospace;
	}

	.nrtok-cat-name-link { font-size: 15px; font-weight: 800; color: #0f172a; text-decoration: none; transition: color 0.15s; }
	.nrtok-cat-name-link:hover { color: #f97316; }

	.nrtok-cat-dish-badge {
		padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block;
	}
	.nrtok-cat-badge-active { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
	.nrtok-cat-badge-empty { background: #f1f5f9; color: #64748b; }

	.nrtok-cat-act-group { display: flex; gap: 6px; align-items: center; justify-content: flex-end; }
	.nrtok-cat-act-btn {
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

	.nrtok-cat-empty-card { text-align: center; padding: 60px 20px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; }
	.nrtok-cat-empty-ico {
		width: 64px; height: 64px; border-radius: 18px; background: #fff7ed; color: #f97316;
		display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;
		box-shadow: 0 4px 12px rgba(249, 115, 22, 0.1);
	}
	.nrtok-cat-empty-title { margin: 0 0 8px; font-size: 20px; font-weight: 800; color: #0f172a; }
	.nrtok-cat-empty-desc { color: #64748b; margin: 0 0 22px; font-size: 14px; }
	.nrtok-cat-btn-icon { display: inline-flex; align-items: center; gap: 7px; padding: 11px 20px; font-weight: 700; border-radius: 12px; }

	.nrtok-cat-row { transition: background 0.15s ease; }
	.nrtok-cat-row:hover { background-color: #fafaf9; }
</style>

<script>
jQuery(document).ready(function($) {
	$(document).on('click', '.js-nrtok-confirm-del', function(e) {
		const msg = $(this).data('confirm');
		if (msg && !window.confirm(msg)) {
			e.preventDefault();
		}
	});
});
</script>