<?php
/**
 * Nahian POS Add / Edit Food Item Form View
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

$cur = isset( $cur ) ? $cur : ( $s['currency'] ?? '৳' );

$nrtok_svg_utensils = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>';
$nrtok_svg_folder   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>';
$nrtok_svg_edit     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_upload   = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>';
$nrtok_svg_link     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
$nrtok_svg_sparkles = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>';
$nrtok_svg_tag      = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>';
$nrtok_svg_plus     = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
$nrtok_svg_check    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
$nrtok_svg_close    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
$nrtok_svg_order    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>';
$nrtok_svg_file     = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
$nrtok_svg_ratio    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M6 12h12"/></svg>';

$nrtok_all_items_url     = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=items&subtab=all' );
$nrtok_quick_extra_nonce = wp_create_nonce( 'nrtok_quick_add_extra_nonce' );
$nrtok_placeholder_img   = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="%2394a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
$nrtok_preview_img       = ( ! empty( $edit_item ) && ! empty( $edit_item->image_url ) ) ? $edit_item->image_url : $nrtok_placeholder_img;
?>

<div class="nrtok-card nrtok-full-card">
	<div class="nrtok-form-header">
		<h3>
			<span class="nrtok-itm-title-ico"><?php echo ! empty( $edit_item ) ? $nrtok_svg_edit : $nrtok_svg_utensils; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php if ( ! empty( $edit_item ) ) : ?>
				<span>
					<?php
					printf(
						/* translators: %s: dish name */
						esc_html__( 'Edit Dish: %s', 'nahian-restaurant-ordering-system' ),
						esc_html( $edit_item->name )
					);
					?>
				</span>
			<?php else : ?>
				<span><?php esc_html_e( 'Add New Food Item', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php endif; ?>
		</h3>
		<p><?php esc_html_e( 'Enter dish details below. Changes reflect immediately on all customer tablets.', 'nahian-restaurant-ordering-system' ); ?></p>
	</div>

	<form method="post" action="<?php echo esc_url( $nrtok_all_items_url ); ?>">
		<?php wp_nonce_field( 'nrtok_save_item_action' ); ?>
		<input type="hidden" name="edit_id" value="<?php echo ! empty( $edit_item ) ? absint( $edit_item->id ) : ''; ?>">

		<div class="nrtok-itm-form-grid-top">
			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Dish Name', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $nrtok_svg_utensils; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="text" name="item_name" class="nrtok-input has-prefix" placeholder="<?php esc_attr_e( 'e.g. Turkish Adana Kebab', 'nahian-restaurant-ordering-system' ); ?>" value="<?php echo ! empty( $edit_item ) ? esc_attr( $edit_item->name ) : ''; ?>" required>
				</div>
			</div>

			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Category', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $nrtok_svg_folder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<select name="item_cat" class="nrtok-input has-prefix" required aria-label="<?php esc_attr_e( 'Category', 'nahian-restaurant-ordering-system' ); ?>">
						<option value=""><?php esc_html_e( '-- Select Category --', 'nahian-restaurant-ordering-system' ); ?></option>
						<?php
						if ( ! empty( $cats ) && is_array( $cats ) ) :
							foreach ( $cats as $c ) :
								?>
							<option value="<?php echo esc_attr( $c->name ); ?>" <?php selected( ! empty( $edit_item ) ? $edit_item->category : '', $c->name ); ?>><?php echo esc_html( $c->name ); ?></option>
								<?php
							endforeach;
						endif;
						?>
					</select>
				</div>
			</div>
		</div>

		<div class="nrtok-itm-form-grid-bot">
			<div class="nrtok-form-group" style="margin: 0;">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Price', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo esc_html( html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' ) ); ?></span>
					<input type="number" step="0.01" min="0" name="item_price" class="nrtok-input has-prefix" placeholder="0.00" value="<?php echo ! empty( $edit_item ) ? esc_attr( $edit_item->price ) : ''; ?>" required>
				</div>
			</div>

			<div class="nrtok-form-group" style="margin: 0;">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Portion / Ratio', 'nahian-restaurant-ordering-system' ); ?></span>
					<small><?php esc_html_e( '(Optional)', 'nahian-restaurant-ordering-system' ); ?></small>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $nrtok_svg_ratio; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="text" name="item_ratio" class="nrtok-input has-prefix" placeholder="<?php esc_attr_e( 'e.g. 1:1, 1:2, 1:4', 'nahian-restaurant-ordering-system' ); ?>" value="<?php echo ( ! empty( $edit_item ) && isset( $edit_item->ratio ) ) ? esc_attr( $edit_item->ratio ) : ''; ?>">
				</div>
			</div>

			<div class="nrtok-form-group" style="margin: 0;">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Display Order / Number', 'nahian-restaurant-ordering-system' ); ?></span>
					<small><?php esc_html_e( '(Lower first)', 'nahian-restaurant-ordering-system' ); ?></small>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $nrtok_svg_order; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="number" name="item_order" class="nrtok-input has-prefix" placeholder="0" value="<?php echo ( ! empty( $edit_item ) && isset( $edit_item->item_order ) ) ? absint( $edit_item->item_order ) : 0; ?>">
				</div>
			</div>
		</div>

		<!-- Short Description / Ingredients Field -->
		<div class="nrtok-form-group" style="margin-top: 20px;">
			<label class="nrtok-label">
				<span><?php esc_html_e( 'Short Description / Ingredients', 'nahian-restaurant-ordering-system' ); ?></span>
				<small><?php esc_html_e( '(Optional)', 'nahian-restaurant-ordering-system' ); ?></small>
			</label>
			<div class="nrtok-input-wrap nrtok-textarea-wrap">
				<span class="nrtok-input-prefix nrtok-textarea-prefix"><?php echo $nrtok_svg_file; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<textarea name="item_description" class="nrtok-input has-prefix" rows="3" placeholder="<?php esc_attr_e( 'e.g. Minced lamb meat, red bell peppers, special spices...', 'nahian-restaurant-ordering-system' ); ?>"><?php echo ( ! empty( $edit_item ) && isset( $edit_item->description ) ) ? esc_textarea( $edit_item->description ) : ''; ?></textarea>
			</div>
		</div>

		<!-- Dish Photo Upload / URL Field -->
		<div class="nrtok-form-group">
			<label class="nrtok-label">
				<span><?php esc_html_e( 'Dish Photo', 'nahian-restaurant-ordering-system' ); ?></span>
				<small><?php esc_html_e( '(Upload or Paste URL)', 'nahian-restaurant-ordering-system' ); ?></small>
			</label>
			<div class="nrtok-itm-photo-row">
				<img id="nrtok-img-preview" class="nrtok-itm-photo-preview" src="<?php echo esc_url( $nrtok_preview_img ); ?>" alt="<?php esc_attr_e( 'Preview', 'nahian-restaurant-ordering-system' ); ?>">
				<div class="nrtok-input-wrap nrtok-itm-photo-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $nrtok_svg_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="url" name="item_img" id="nrtok-item-img" class="nrtok-input has-prefix" value="<?php echo ! empty( $edit_item ) ? esc_attr( $edit_item->image_url ) : ''; ?>" placeholder="https://...">
				</div>
				<button type="button" id="nrtok-upload-btn" class="nrtok-btn-secondary nrtok-itm-btn-icon nrtok-itm-upload-btn">
					<?php echo $nrtok_svg_upload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Upload', 'nahian-restaurant-ordering-system' ); ?></span>
				</button>
			</div>
		</div>

		<!-- Extras & Add-ons Section -->
		<div class="nrtok-form-group">
			<div class="nrtok-label nrtok-label-mb">
				<span><?php esc_html_e( 'Attach Available Extras', 'nahian-restaurant-ordering-system' ); ?> <small><?php esc_html_e( '(Optional add-ons)', 'nahian-restaurant-ordering-system' ); ?></small></span>
				<div class="nrtok-itm-ex-controls">
					<?php if ( ! empty( $extras ) ) : ?>
						<button type="button" id="nrtok-select-all-ex" class="nrtok-btn-secondary nrtok-itm-sel-all-btn"><?php esc_html_e( 'Select All', 'nahian-restaurant-ordering-system' ); ?></button>
					<?php endif; ?>
					<button type="button" id="nrtok-toggle-quick-ex" class="nrtok-itm-btn-icon nrtok-itm-quick-ex-trigger">
						<?php echo $nrtok_svg_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Quick New Extra', 'nahian-restaurant-ordering-system' ); ?></span>
					</button>
				</div>
			</div>

			<!-- Inline Quick-Create Extra Drawer -->
			<div class="nrtok-quick-extra-box" id="nrtok-quick-ex-box" style="display: none;">
				<div class="nrtok-quick-extra-title"><?php echo $nrtok_svg_sparkles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php esc_html_e( 'Create & Auto-Attach New Add-on:', 'nahian-restaurant-ordering-system' ); ?></span></div>
				<div class="nrtok-quick-extra-row">
					<div class="nrtok-input-wrap" style="flex: 1.5; min-width: 180px;">
						<span class="nrtok-input-prefix"><?php echo $nrtok_svg_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<input type="text" id="nrtok-q-ex-name" class="nrtok-input has-prefix" placeholder="<?php esc_attr_e( 'Extra name (e.g. Extra Cheese)', 'nahian-restaurant-ordering-system' ); ?>">
					</div>
					<div class="nrtok-input-wrap" style="flex: 1; min-width: 130px;">
						<span class="nrtok-input-prefix"><?php echo esc_html( html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' ) ); ?></span>
						<input type="number" step="0.01" min="0" id="nrtok-q-ex-price" class="nrtok-input has-prefix" placeholder="<?php esc_attr_e( 'Price', 'nahian-restaurant-ordering-system' ); ?>">
					</div>
					<button type="button" id="nrtok-q-ex-save-btn" class="nrtok-btn nrtok-itm-btn-icon" style="height: 44px;">
						<?php echo $nrtok_svg_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Save & Attach', 'nahian-restaurant-ordering-system' ); ?></span>
					</button>
					<button type="button" id="nrtok-q-ex-cancel-btn" class="nrtok-btn-secondary" style="height: 44px;" title="<?php esc_attr_e( 'Close', 'nahian-restaurant-ordering-system' ); ?>"><?php echo $nrtok_svg_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>
			</div>

			<!-- Modern Switch Grid for Extras -->
			<div class="nrtok-switch-grid" id="nrtok-extras-grid">
				<?php if ( empty( $extras ) ) : ?>
					<span id="nrtok-no-extras-msg" style="color:#64748b; font-size:13px;">
						<?php esc_html_e( 'No extras created yet. Click "Quick New Extra" above!', 'nahian-restaurant-ordering-system' ); ?>
					</span>
				<?php else : ?>
					<?php
					foreach ( $extras as $ex ) :
						$ex_id      = absint( $ex->id );
						$is_checked = in_array( $ex_id, array_map( 'absint', $edit_extras ), true );
						?>
					<label class="nrtok-switch-pill <?php echo $is_checked ? 'checked' : ''; ?>">
						<div class="nrtok-switch-info">
							<span class="nrtok-switch-name"><?php echo esc_html( $ex->name ); ?></span>
							<span class="nrtok-switch-price">+<?php echo esc_html( html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' ) ) . esc_html( number_format( (float) $ex->price, 2 ) ); ?></span>
						</div>
						<div class="nrtok-switch-control">
							<input type="checkbox" name="item_extras[]" value="<?php echo absint( $ex_id ); ?>" <?php checked( $is_checked ); ?>>
							<span class="nrtok-slider"></span>
						</div>
					</label>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<div class="nrtok-itm-form-footer">
			<button type="submit" name="nrtok_save_item" class="nrtok-btn nrtok-itm-btn-icon">
				<?php echo $nrtok_svg_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo ! empty( $edit_item ) ? esc_html__( 'Update Food Item', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Save Food Item', 'nahian-restaurant-ordering-system' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $nrtok_all_items_url ); ?>" class="nrtok-btn-secondary"><?php esc_html_e( 'Cancel', 'nahian-restaurant-ordering-system' ); ?></a>
		</div>
	</form>
</div>

<style>
	.nrtok-itm-title-ico { color: #f97316; display: inline-flex; }
	.nrtok-required-star { color: #ef4444; font-weight: 800; margin-left: 3px; }

	/* Form Grid Layouts */
	.nrtok-itm-form-grid-top { display: grid; grid-template-columns: 1.4fr 1fr; gap: 18px; width: 100%; }
	.nrtok-itm-form-grid-bot { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; width: 100%; }
	@media (max-width: 820px) {
		.nrtok-itm-form-grid-top { grid-template-columns: 1fr; gap: 0; }
		.nrtok-itm-form-grid-bot { grid-template-columns: 1fr; gap: 18px; }
	}

	.nrtok-form-group { margin-bottom: 20px; width: 100%; }
	.nrtok-label {
		display: flex; align-items: center; justify-content: space-between;
		font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; letter-spacing: 0.2px;
		gap: 8px; flex-wrap: wrap;
	}
	.nrtok-label small { font-weight: 500; color: #64748b; font-size: 12px; }
	.nrtok-label-mb { margin-bottom: 12px; }

	/* Modern Elevated Input Containers */
	.nrtok-input-wrap {
		position: relative; display: flex; align-items: center; width: 100%;
		background: #ffffff; border-radius: 12px;
		border: 1.5px solid #e2e8f0;
		box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
		transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
	}
	.nrtok-input-wrap:hover {
		border-color: #cbd5e1;
		box-shadow: 0 3px 6px rgba(15, 23, 42, 0.04);
	}
	.nrtok-input-wrap:focus-within {
		border-color: #f97316;
		background-color: #fff;
		box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12), 0 2px 4px rgba(0, 0, 0, 0.04);
	}

	.nrtok-input-prefix {
		position: absolute; left: 14px; font-weight: 700; color: #94a3b8; font-size: 14px;
		pointer-events: none; display: flex; align-items: center; justify-content: center;
		transition: color 0.2s ease;
	}
	.nrtok-input-wrap:focus-within .nrtok-input-prefix {
		color: #f97316;
	}

	.nrtok-input {
		width: 100% !important; max-width: 100% !important;
		padding: 12px 16px; border: none !important; border-radius: 12px;
		font-size: 14px; font-weight: 500; color: #0f172a; background: transparent !important;
		box-shadow: none !important; outline: none !important; box-sizing: border-box;
		min-height: 44px;
	}
	.nrtok-input.has-prefix { padding-left: 44px; }
	.nrtok-input::placeholder { color: #94a3b8; opacity: 1; font-weight: 400; }

	/* Full-width Select Override for WP Admin */
	select.nrtok-input {
		display: block; width: 100% !important; max-width: 100% !important;
		height: auto !important; line-height: 1.5 !important;
		appearance: none; -webkit-appearance: none; padding-right: 40px; cursor: pointer;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
		background-repeat: no-repeat !important; background-position: right 14px center !important; background-size: 16px !important;
	}

	/* Textarea Alignment */
	.nrtok-textarea-wrap { align-items: flex-start; }
	.nrtok-textarea-prefix { top: 14px; }
	textarea.nrtok-input { resize: vertical; line-height: 1.55; padding-top: 12px; padding-bottom: 12px; }

	/* Photo Upload Row */
	.nrtok-itm-photo-row {
		display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
		padding: 12px 14px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px;
	}
	.nrtok-itm-photo-preview {
		width: 56px; height: 56px; border-radius: 10px; object-fit: cover;
		border: 1px solid #e2e8f0; background: #fff; flex-shrink: 0;
	}
	.nrtok-itm-photo-input-wrap { flex: 1; min-width: 220px; }
	.nrtok-itm-upload-btn { height: 44px; flex-shrink: 0; }

	/* Quick Extra Drawer */
	.nrtok-itm-ex-controls { display: flex; align-items: center; gap: 8px; }
	.nrtok-itm-sel-all-btn { padding: 5px 12px; font-size: 12px; border-radius: 8px; }
	.nrtok-itm-quick-ex-trigger {
		background: #fff7ed; color: #ea580c; border: 1.5px solid #fed7aa;
		padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;
		transition: all 0.2s ease;
	}
	.nrtok-itm-quick-ex-trigger:hover { background: #ffedd5; border-color: #fdba74; }

	.nrtok-quick-extra-box {
		background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 14px;
		padding: 16px; margin-bottom: 16px;
	}
	.nrtok-quick-extra-title {
		display: flex; align-items: center; gap: 6px; font-size: 12.5px;
		font-weight: 800; color: #c2410c; margin-bottom: 12px;
	}
	.nrtok-quick-extra-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

	/* Modern Switch Grid Design */
	.nrtok-switch-grid {
		display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px;
		background: #f8fafc; padding: 16px; border-radius: 14px; border: 1.5px dashed #cbd5e1;
	}
	.nrtok-switch-pill {
		display: flex; justify-content: space-between; align-items: center;
		background: #fff; border: 1.5px solid #e2e8f0; padding: 12px 16px;
		border-radius: 12px; cursor: pointer; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		user-select: none; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.02);
	}
	.nrtok-switch-pill:hover { border-color: #cbd5e1; background: #fafaf9; transform: translateY(-1px); }
	.nrtok-switch-pill.checked {
		border-color: #f97316; background: #fff7ed;
		box-shadow: 0 4px 12px rgba(249, 115, 22, 0.08);
	}
	.nrtok-switch-info { display: flex; flex-direction: column; gap: 2px; }
	.nrtok-switch-name { font-size: 13.5px; font-weight: 700; color: #0f172a; }
	.nrtok-switch-pill.checked .nrtok-switch-name { color: #c2410c; }
	.nrtok-switch-price { font-size: 12px; font-weight: 700; color: #f97316; font-family: 'Courier New', Courier, monospace; }

	/* Switch Toggle Button Styles */
	.nrtok-switch-control { position: relative; display: inline-block; width: 42px; height: 22px; flex-shrink: 0; }
	.nrtok-switch-control input { opacity: 0; width: 0; height: 0; }
	.nrtok-slider {
		position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
		background-color: #cbd5e1; transition: .25s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 22px;
	}
	.nrtok-slider:before {
		position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
		background-color: white; transition: .25s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 50%;
		box-shadow: 0 2px 4px rgba(0,0,0,0.15);
	}
	.nrtok-switch-control input:checked + .nrtok-slider { background-color: #f97316; }
	.nrtok-switch-control input:focus + .nrtok-slider { box-shadow: 0 0 1px #f97316; }
	.nrtok-switch-control input:checked + .nrtok-slider:before { transform: translateX(20px); }

	.nrtok-itm-form-footer {
		margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9;
		display: flex; align-items: center; gap: 12px;
	}
	.nrtok-itm-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
</style>

<script>
jQuery(document).ready(function($) {
	const cur          = <?php echo wp_json_encode( html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' ) ); ?>;
	const quickExNonce = <?php echo wp_json_encode( $nrtok_quick_extra_nonce ); ?>;
	const placeholder  = <?php echo wp_json_encode( $nrtok_placeholder_img ); ?>;

	function escHtml(str) {
		return String(str === null || str === undefined ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	// Toggle Switch Active Class on Change
	$(document).on('change', '.nrtok-switch-control input[type="checkbox"]', function() {
		if ($(this).is(':checked')) {
			$(this).closest('.nrtok-switch-pill').addClass('checked');
		} else {
			$(this).closest('.nrtok-switch-pill').removeClass('checked');
		}
	});

	$('#nrtok-upload-btn').on('click', function(e) {
		e.preventDefault();
		if (typeof wp === 'undefined' || !wp.media) {
			return;
		}

		const frame = wp.media({
			title: <?php echo wp_json_encode( __( 'Select Food Photo', 'nahian-restaurant-ordering-system' ) ); ?>,
			button: { text: <?php echo wp_json_encode( __( 'Use Photo', 'nahian-restaurant-ordering-system' ) ); ?> },
			library: { type: 'image' },
			multiple: false
		});
		frame.on('select', function() {
			const attachment = frame.state().get('selection').first().toJSON();
			$('#nrtok-item-img').val(attachment.url);
			$('#nrtok-img-preview').attr('src', attachment.url);
		});
		frame.open();
	});

	$('#nrtok-item-img').on('input', function() {
		const url = $(this).val().trim();
		$('#nrtok-img-preview').attr('src', url ? url : placeholder);
	});

	$('#nrtok-toggle-quick-ex').on('click', function() {
		$('#nrtok-quick-ex-box').slideToggle(180);
		$('#nrtok-q-ex-name').focus();
	});
	$('#nrtok-q-ex-cancel-btn').on('click', function() {
		$('#nrtok-quick-ex-box').slideUp(150);
	});

	$('#nrtok-q-ex-save-btn').on('click', function() {
		const name  = $('#nrtok-q-ex-name').val().trim();
		const price = $('#nrtok-q-ex-price').val();
		if (!name) {
			alert(<?php echo wp_json_encode( __( 'Please enter extra name!', 'nahian-restaurant-ordering-system' ) ); ?>);
			return;
		}
		const $btn = $(this);
		$btn.prop('disabled', true);

		$.post(ajaxurl, {
			action: 'nrtok_quick_add_extra',
			_ajax_nonce: quickExNonce,
			name: name,
			price: price || 0
		}, function(res) {
			if (res && res.success && res.data) {
				$('#nrtok-no-extras-msg').remove();
				const exId    = parseInt(res.data.id, 10);
				const exName  = escHtml(res.data.name);
				const exPrice = escHtml(res.data.price);

				const switchHtml = `
					<label class="nrtok-switch-pill checked">
						<div class="nrtok-switch-info">
							<span class="nrtok-switch-name">${exName}</span>
							<span class="nrtok-switch-price">+${escHtml(cur)}${exPrice}</span>
						</div>
						<div class="nrtok-switch-control">
							<input type="checkbox" name="item_extras[]" value="${exId}" checked>
							<span class="nrtok-slider"></span>
						</div>
					</label>`;
				$('#nrtok-extras-grid').append(switchHtml);
				$('#nrtok-q-ex-name, #nrtok-q-ex-price').val('');
				$('#nrtok-quick-ex-box').slideUp(150);
			}
			$btn.prop('disabled', false);
		});
	});

	let allSelected = false;
	$('#nrtok-select-all-ex').on('click', function() {
		allSelected = !allSelected;
		$('#nrtok-extras-grid input[type="checkbox"]').prop('checked', allSelected).trigger('change');
		$(this).text(allSelected
			? <?php echo wp_json_encode( __( 'Clear All', 'nahian-restaurant-ordering-system' ) ); ?>
			: <?php echo wp_json_encode( __( 'Select All', 'nahian-restaurant-ordering-system' ) ); ?>
		);
	});
});
</script>