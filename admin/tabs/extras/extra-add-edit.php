<?php
/**
 * Nahian POS Add / Edit Extra Add-on Form View
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

$cur = isset( $cur ) ? $cur : '৳';

$nrtok_svg_sparkles = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>';
$nrtok_svg_edit     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
$nrtok_svg_tag      = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>';
$nrtok_svg_check    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$nrtok_svg_image    = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';

$nrtok_extra_image_url = ( ! empty( $edit_extra ) && ! empty( $edit_extra->image ) ) ? $edit_extra->image : '';
$nrtok_all_extras_url  = admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=extras&subtab=all' );
?>

<div class="nrtok-card nrtok-full-card">
	<div class="nrtok-form-header">
		<h3>
			<span class="nrtok-ex-title-ico"><?php echo ! empty( $edit_extra ) ? $nrtok_svg_edit : $nrtok_svg_sparkles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php if ( ! empty( $edit_extra ) ) : ?>
				<span>
					<?php
					printf(
						/* translators: %s: extra add-on name */
						esc_html__( 'Edit Extra: %s', 'nahian-restaurant-ordering-system' ),
						esc_html( $edit_extra->name )
					);
					?>
				</span>
			<?php else : ?>
				<span><?php esc_html_e( 'Add New Extra / Add-on', 'nahian-restaurant-ordering-system' ); ?></span>
			<?php endif; ?>
		</h3>
		<p>
			<?php
			printf(
				/* translators: %s: Items menu label */
				esc_html__( 'After saving, you can attach this add-on to any dish from the %s menu.', 'nahian-restaurant-ordering-system' ),
				'<strong>' . esc_html__( 'Items', 'nahian-restaurant-ordering-system' ) . '</strong>'
			);
			?>
		</p>
	</div>

	<form method="post" action="<?php echo esc_url( $nrtok_all_extras_url ); ?>">
		<?php wp_nonce_field( 'nrtok_save_extra_action' ); ?>
		<input type="hidden" name="edit_extra_id" value="<?php echo ! empty( $edit_extra ) ? absint( $edit_extra->id ) : ''; ?>">

		<div class="nrtok-ex-form-grid">
			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Extra Name', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
					<small><?php esc_html_e( '(e.g. Extra Cheese)', 'nahian-restaurant-ordering-system' ); ?></small>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo $nrtok_svg_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="text" name="extra_name" class="nrtok-input has-prefix" placeholder="<?php esc_attr_e( 'Garlic Sauce', 'nahian-restaurant-ordering-system' ); ?>" value="<?php echo ! empty( $edit_extra ) ? esc_attr( $edit_extra->name ) : ''; ?>" required>
				</div>
			</div>

			<div class="nrtok-form-group">
				<label class="nrtok-label">
					<span><?php esc_html_e( 'Add-on Price', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
				</label>
				<div class="nrtok-input-wrap">
					<span class="nrtok-input-prefix"><?php echo esc_html( html_entity_decode( $cur, ENT_QUOTES, 'UTF-8' ) ); ?></span>
					<input type="number" step="0.01" min="0" name="extra_price" class="nrtok-input has-prefix" placeholder="0.00" value="<?php echo ! empty( $edit_extra ) ? esc_attr( $edit_extra->price ) : ''; ?>" required>
				</div>
			</div>
		</div>

		<!-- Extra Image Upload Field -->
		<div class="nrtok-form-group" style="margin-bottom: 24px;">
			<label class="nrtok-label">
				<span><?php esc_html_e( 'Add-on Image / Icon', 'nahian-restaurant-ordering-system' ); ?></span>
				<small><?php esc_html_e( '(Optional)', 'nahian-restaurant-ordering-system' ); ?></small>
			</label>
			<div class="nrtok-ex-media-row">
				<input type="hidden" name="extra_image" id="nrtok_extra_image_input" value="<?php echo esc_url( $nrtok_extra_image_url ); ?>">

				<div id="nrtok_extra_img_preview" class="nrtok-ex-media-preview-box <?php echo ! empty( $nrtok_extra_image_url ) ? 'has-img' : ''; ?>">
					<?php if ( ! empty( $nrtok_extra_image_url ) ) : ?>
						<img src="<?php echo esc_url( $nrtok_extra_image_url ); ?>" alt="<?php esc_attr_e( 'Add-on preview', 'nahian-restaurant-ordering-system' ); ?>">
					<?php else : ?>
						<span class="nrtok-ex-media-placeholder"><?php echo $nrtok_svg_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php endif; ?>
				</div>

				<div class="nrtok-ex-media-actions">
					<button type="button" id="nrtok_upload_extra_img_btn" class="nrtok-btn-secondary nrtok-ex-btn-icon" style="font-weight: 700;">
						<?php echo $nrtok_svg_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Choose Image', 'nahian-restaurant-ordering-system' ); ?></span>
					</button>
					<button type="button" id="nrtok_remove_extra_img_btn" class="nrtok-btn-secondary nrtok-ex-media-remove-btn" style="<?php echo empty( $nrtok_extra_image_url ) ? 'display:none;' : ''; ?>">
						<?php esc_html_e( 'Remove', 'nahian-restaurant-ordering-system' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="nrtok-ex-form-footer">
			<button type="submit" name="nrtok_save_extra" class="nrtok-btn nrtok-ex-btn-icon">
				<?php echo $nrtok_svg_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo ! empty( $edit_extra ) ? esc_html__( 'Update Extra', 'nahian-restaurant-ordering-system' ) : esc_html__( 'Save Extra', 'nahian-restaurant-ordering-system' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $nrtok_all_extras_url ); ?>" class="nrtok-btn-secondary"><?php esc_html_e( 'Cancel', 'nahian-restaurant-ordering-system' ); ?></a>
		</div>
	</form>
</div>

<style>
	.nrtok-ex-title-ico { color: #f97316; display: inline-flex; }
	.nrtok-ex-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; width: 100%; }
	@media (max-width: 640px) { .nrtok-ex-form-grid { grid-template-columns: 1fr; gap: 0; } }

	.nrtok-form-group { margin-bottom: 20px; width: 100%; }
	.nrtok-label {
		display: flex; align-items: center; justify-content: space-between;
		font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; letter-spacing: 0.2px;
	}
	.nrtok-label small { font-weight: 500; color: #64748b; font-size: 12px; }

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

	.nrtok-required-star {
		color: #ef4444;
		font-weight: 800;
		margin-left: 3px;
	}
	.nrtok-ex-media-row {
		display: flex;
		gap: 14px;
		align-items: center;
		flex-wrap: wrap;
		padding: 12px 14px;
		background: #f8fafc;
		border: 1.5px solid #e2e8f0;
		border-radius: 12px;
		transition: border-color 0.2s ease;
	}
	.nrtok-ex-media-row:hover {
		border-color: #cbd5e1;
	}
	.nrtok-ex-media-preview-box {
		width: 68px;
		height: 68px;
		border: 2px dashed #cbd5e1;
		border-radius: 14px;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #ffffff;
		overflow: hidden;
		transition: all 0.2s ease;
	}
	.nrtok-ex-media-preview-box.has-img {
		border-style: solid;
		border-color: #e2e8f0;
		box-shadow: 0 3px 10px rgba(15, 23, 42, 0.06);
	}
	.nrtok-ex-media-preview-box img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}
	.nrtok-ex-media-placeholder {
		color: #94a3b8;
		display: inline-flex;
	}
	.nrtok-ex-media-actions {
		display: flex;
		gap: 8px;
		align-items: center;
	}
	.nrtok-ex-media-remove-btn {
		color: #dc2626 !important;
		border-color: #fee2e2 !important;
		background: #fef2f2 !important;
	}
	.nrtok-ex-media-remove-btn:hover {
		background: #fee2e2 !important;
	}
	.nrtok-ex-form-footer {
		margin-top: 24px;
		padding-top: 18px;
		border-top: 1px solid #f1f5f9;
		display: flex;
		align-items: center;
		gap: 12px;
	}
	.nrtok-ex-btn-icon {
		display: inline-flex;
		align-items: center;
		gap: 7px;
	}
</style>

<script>
jQuery(document).ready(function($) {
	let mediaUploader;
	const placeholderIcon = <?php echo wp_json_encode( $nrtok_svg_image ); ?>;

	$('#nrtok_upload_extra_img_btn').on('click', function(e) {
		e.preventDefault();
		if (typeof wp === 'undefined' || !wp.media) {
			return;
		}

		if (mediaUploader) {
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media.frames.file_frame = wp.media({
			title: <?php echo wp_json_encode( __( 'Choose Add-on Image', 'nahian-restaurant-ordering-system' ) ); ?>,
			button: { text: <?php echo wp_json_encode( __( 'Use Image', 'nahian-restaurant-ordering-system' ) ); ?> },
			library: { type: 'image' },
			multiple: false
		});

		mediaUploader.on('select', function() {
			const attachment = mediaUploader.state().get('selection').first().toJSON();
			const imgUrl = attachment.url || '';
			$('#nrtok_extra_image_input').val(imgUrl);

			const $imgEl = $('<img>', {
				src: imgUrl,
				alt: <?php echo wp_json_encode( __( 'Add-on preview', 'nahian-restaurant-ordering-system' ) ); ?>
			});
			$('#nrtok_extra_img_preview').addClass('has-img').empty().append($imgEl);
			$('#nrtok_remove_extra_img_btn').show();
		});

		mediaUploader.open();
	});

	$('#nrtok_remove_extra_img_btn').on('click', function() {
		$('#nrtok_extra_image_input').val('');
		$('#nrtok_extra_img_preview')
			.removeClass('has-img')
			.html('<span class="nrtok-ex-media-placeholder">' + placeholderIcon + '</span>');
		$(this).hide();
	});
});
</script>