<?php
/**
 * Nahian POS Counter Terminal (Tablet-First Touchscreen Layout with Auto-Save Print)
 *
 * @package NRTOK_POS
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Tablet-Optimized Counter POS Terminal tab with Quantity Steppers & Auto-Save Print.
 *
 * @param array<string, mixed> $settings Plugin settings.
 * @return void
 */
function nrtok_view_counter_pos( $settings ) {
	if ( ! nrtok_current_user_can_pos() ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;
	$cur         = isset( $settings['currency'] ) ? html_entity_decode( $settings['currency'], ENT_QUOTES, 'UTF-8' ) : '$';
	$cur_pos     = isset( $settings['currency_pos'] ) ? $settings['currency_pos'] : 'left';
	$decimals    = isset( $settings['decimals'] ) ? (int) $settings['decimals'] : 2;
	$tax_percent = isset( $settings['tax_percent'] ) ? (float) $settings['tax_percent'] : 0.0;
	$paper_width = isset( $settings['paper_width'] ) ? $settings['paper_width'] : '80mm';
	$auto_print  = isset( $settings['auto_print'] ) ? (int) $settings['auto_print'] : 1;
	$rest_name   = ! empty( $settings['restaurant_name'] ) ? $settings['restaurant_name'] : ( get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'NAHIAN POS' );
	$rest_phone  = ! empty( $settings['phone'] ) ? $settings['phone'] : '';
	$footer_txt  = ! empty( $settings['receipt_footer'] ) ? $settings['receipt_footer'] : __( 'Thank you for dining with us!', 'nahian-restaurant-ordering-system' );
	$ord_prefix  = ! empty( $settings['order_prefix'] ) ? $settings['order_prefix'] : 'ORD-';

	$fmt_money = static function ( $amt ) use ( $cur, $cur_pos, $decimals ) {
		$num = number_format( (float) $amt, $decimals );
		return 'right' === $cur_pos ? $num . $cur : $cur . $num;
	};

	$t_menu   = $wpdb->prefix . 'nrtok_menu';
	$t_cats   = $wpdb->prefix . 'nrtok_categories';
	$t_extras = $wpdb->prefix . 'nrtok_extras';
	$t_tables = $wpdb->prefix . 'nrtok_tables';

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$items  = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_menu ) ) === $t_menu )
		? ( $wpdb->get_results( $wpdb->prepare( 'SELECT id, name, category, price, image_url, extras FROM %i WHERE is_available = 1 ORDER BY item_order ASC, id DESC', $t_menu ) ) ?: array() )
		: array();
	$cats   = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_cats ) ) === $t_cats )
		? ( $wpdb->get_results( $wpdb->prepare( 'SELECT id, name FROM %i ORDER BY category_order ASC, id ASC', $t_cats ) ) ?: array() )
		: array();
	$tables = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_tables ) ) === $t_tables )
		? ( $wpdb->get_results( $wpdb->prepare( 'SELECT id, table_name FROM %i ORDER BY id ASC', $t_tables ) ) ?: array() )
		: array();

	$extras_by_id   = array();
	$extras_by_name = array();
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_extras ) ) === $t_extras ) {
		$extra_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, name, price FROM %i ORDER BY id ASC', $t_extras ) ) ?: array();
		foreach ( $extra_rows as $er ) {
			$ex_entry = array(
				'id'    => absint( $er->id ),
				'name'  => $er->name,
				'price' => (float) $er->price,
			);
			$extras_by_id[ absint( $er->id ) ]         = $ex_entry;
			$extras_by_name[ strtolower( $er->name ) ] = $ex_entry;
		}
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$enabled_methods = isset( $settings['payment_methods'] ) && is_array( $settings['payment_methods'] ) && ! empty( $settings['payment_methods'] )
		? $settings['payment_methods']
		: array( 'cash', 'card', 'wallet' );

	$method_labels = array(
		'cash'   => __( 'Cash', 'nahian-restaurant-ordering-system' ),
		'card'   => __( 'Card', 'nahian-restaurant-ordering-system' ),
		'wallet' => __( 'Digital Wallet', 'nahian-restaurant-ordering-system' ),
	);

	$first_pay_label = isset( $method_labels[ $enabled_methods[0] ] ) ? $method_labels[ $enabled_methods[0] ] : ucfirst( $enabled_methods[0] );
	$nonce           = wp_create_nonce( 'nrtok_admin_nonce' );
	?>

	<div id="nrtok-tab-toast" class="nrtok-tab-toast" aria-live="polite"></div>

	<div class="nrtok-tab-pos">
		<!-- LEFT PANE: TOUCH MENU -->
		<section class="nrtok-tab-menu-pane" aria-label="<?php esc_attr_e( 'Menu Items', 'nahian-restaurant-ordering-system' ); ?>">
			<div class="nrtok-tab-menu-head">
				<div class="nrtok-tab-search-box">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					<input type="search" id="nrtok-tab-q" placeholder="<?php esc_attr_e( 'Tap to search food...', 'nahian-restaurant-ordering-system' ); ?>" autocomplete="off">
					<button type="button" id="nrtok-tab-q-clear" class="nrtok-tab-q-clear nrtok-hidden" aria-label="<?php esc_attr_e( 'Clear', 'nahian-restaurant-ordering-system' ); ?>">&times;</button>
				</div>

				<div class="nrtok-tab-cats" role="tablist">
					<button type="button" class="nrtok-tab-cat active" data-cat="all"><?php esc_html_e( 'All Items', 'nahian-restaurant-ordering-system' ); ?></button>
					<?php foreach ( $cats as $c ) : ?>
						<button type="button" class="nrtok-tab-cat" data-cat="<?php echo esc_attr( strtolower( $c->name ) ); ?>"><?php echo esc_html( $c->name ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="nrtok-tab-grid" id="nrtok-tab-grid">
				<?php
				foreach ( $items as $it ) :
					$it_extras  = array();
					$raw_extras = array();

					if ( ! empty( $it->extras ) ) {
						$decoded = json_decode( $it->extras, true );
						if ( is_array( $decoded ) ) {
							$raw_extras = $decoded;
						} elseif ( is_string( $it->extras ) ) {
							$raw_extras = array_map( 'trim', explode( ',', $it->extras ) );
						}
					}

					foreach ( $raw_extras as $ex_ref ) {
						if ( is_numeric( $ex_ref ) && isset( $extras_by_id[ absint( $ex_ref ) ] ) ) {
							$it_extras[] = $extras_by_id[ absint( $ex_ref ) ];
						} elseif ( is_string( $ex_ref ) && '' !== $ex_ref ) {
							$ex_key = strtolower( trim( $ex_ref ) );
							if ( isset( $extras_by_name[ $ex_key ] ) ) {
								$it_extras[] = $extras_by_name[ $ex_key ];
							}
						}
					}
					?>
					<button type="button" class="nrtok-tab-card"
						data-id="<?php echo absint( $it->id ); ?>"
						data-name="<?php echo esc_attr( $it->name ); ?>"
						data-cat="<?php echo esc_attr( strtolower( $it->category ) ); ?>"
						data-price="<?php echo esc_attr( (float) $it->price ); ?>"
						data-extras="<?php echo esc_attr( wp_json_encode( $it_extras ) ); ?>">

						<span class="nrtok-tab-badge nrtok-hidden">0</span>

						<?php if ( ! empty( $it_extras ) ) : ?>
							<span class="nrtok-tab-addon-tag">+<?php echo absint( count( $it_extras ) ); ?></span>
						<?php endif; ?>

						<?php if ( ! empty( $it->image_url ) ) : ?>
							<img src="<?php echo esc_url( $it->image_url ); ?>" alt="<?php echo esc_attr( $it->name ); ?>" class="nrtok-tab-img" loading="lazy">
						<?php else : ?>
							<div class="nrtok-tab-img-placeholder">
								<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>
							</div>
						<?php endif; ?>

						<div class="nrtok-tab-card-title"><?php echo esc_html( $it->name ); ?></div>
						<div class="nrtok-tab-card-price"><?php echo esc_html( $fmt_money( $it->price ) ); ?></div>
					</button>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- RIGHT PANE: TOUCH ORDER TICKET -->
		<aside class="nrtok-tab-ticket-pane" id="nrtok-tab-ticket-pane" aria-label="<?php esc_attr_e( 'Current Order', 'nahian-restaurant-ordering-system' ); ?>">
			<div class="nrtok-tab-ticket-head">
				<div class="nrtok-tab-modes">
					<button type="button" class="nrtok-tab-mode active" data-mode="dine_in"><?php esc_html_e( 'Dine-In', 'nahian-restaurant-ordering-system' ); ?></button>
					<button type="button" class="nrtok-tab-mode" data-mode="takeaway"><?php esc_html_e( 'Takeaway', 'nahian-restaurant-ordering-system' ); ?></button>
				</div>

				<select id="nrtok-tab-table" class="nrtok-tab-table-select" aria-label="<?php esc_attr_e( 'Table', 'nahian-restaurant-ordering-system' ); ?>">
					<?php foreach ( $tables as $t ) : ?>
						<option value="<?php echo esc_attr( $t->table_name ); ?>"><?php esc_html_e( 'Table', 'nahian-restaurant-ordering-system' ); ?> <?php echo esc_html( $t->table_name ); ?></option>
					<?php endforeach; ?>
				</select>

				<span id="nrtok-tab-qty-summary" class="nrtok-tab-qty-summary" title="<?php esc_attr_e( 'Total Quantity', 'nahian-restaurant-ordering-system' ); ?>">0</span>

				<button type="button" id="nrtok-tab-close-drawer" class="nrtok-tab-close-drawer" aria-label="<?php esc_attr_e( 'Back to Menu', 'nahian-restaurant-ordering-system' ); ?>">&times;</button>
			</div>

			<div id="nrtok-tab-cart" class="nrtok-tab-cart">
				<div class="nrtok-tab-empty">
					<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
					<p><?php esc_html_e( 'Tap dishes on the left to add', 'nahian-restaurant-ordering-system' ); ?></p>
				</div>
			</div>

			<div class="nrtok-tab-ticket-foot">
				<div class="nrtok-tab-quick-tools">
					<button type="button" id="nrtok-tab-opt-btn" class="nrtok-tab-tool-link">+ <?php esc_html_e( 'Discount / Note', 'nahian-restaurant-ordering-system' ); ?></button>
					<button type="button" id="nrtok-tab-clear-btn" class="nrtok-tab-tool-clear"><?php esc_html_e( 'Clear All', 'nahian-restaurant-ordering-system' ); ?></button>
				</div>

				<div id="nrtok-tab-opt-box" class="nrtok-tab-opt-box nrtok-hidden">
					<input type="number" id="nrtok-tab-disc" min="0" step="0.01" placeholder="<?php echo esc_attr( sprintf( /* translators: %s: currency */ __( 'Discount (%s)', 'nahian-restaurant-ordering-system' ), $cur ) ); ?>">
					<input type="text" id="nrtok-tab-note" placeholder="<?php esc_attr_e( 'Kitchen note...', 'nahian-restaurant-ordering-system' ); ?>">
				</div>

				<div class="nrtok-tab-pay-grid" style="grid-template-columns: repeat(<?php echo absint( count( $enabled_methods ) ); ?>, 1fr);">
					<?php
					foreach ( $enabled_methods as $idx => $m_key ) :
						$m_label = isset( $method_labels[ $m_key ] ) ? $method_labels[ $m_key ] : ucfirst( $m_key );
						?>
						<button type="button" class="nrtok-tab-pay <?php echo 0 === $idx ? 'active' : ''; ?>" data-pay="<?php echo esc_attr( $m_label ); ?>">
							<?php echo esc_html( $m_label ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="nrtok-tab-cta-row">
					<button type="button" id="nrtok-tab-kot-btn" class="nrtok-tab-btn-kot js-nahian-restaurant-ordering-system-submit" title="<?php esc_attr_e( 'Save & Print Kitchen Ticket', 'nahian-restaurant-ordering-system' ); ?>">
						<?php esc_html_e( 'KOT', 'nahian-restaurant-ordering-system' ); ?>
					</button>
					<button type="button" id="nrtok-tab-print-btn" class="nrtok-tab-btn-print js-nahian-restaurant-ordering-system-submit" title="<?php esc_attr_e( 'Auto-Save Order & Print Customer Bill', 'nahian-restaurant-ordering-system' ); ?>">
						<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
						<span><?php esc_html_e( 'Print', 'nahian-restaurant-ordering-system' ); ?></span>
					</button>
					<button type="button" id="nrtok-tab-charge-btn" class="nrtok-tab-btn-charge js-nahian-restaurant-ordering-system-submit" title="<?php esc_attr_e( 'Complete Payment & Print Paid Receipt', 'nahian-restaurant-ordering-system' ); ?>">
						<span><?php esc_html_e( 'Charge', 'nahian-restaurant-ordering-system' ); ?></span>
						<strong id="nrtok-tab-total"><?php echo esc_html( $fmt_money( 0 ) ); ?></strong>
					</button>
				</div>
			</div>
		</aside>
	</div>

	<div id="nrtok-tab-mobile-bar" class="nrtok-tab-mobile-bar">
		<button type="button" id="nrtok-tab-open-drawer" class="nrtok-tab-mobile-btn">
			<span class="nrtok-tab-mob-left">
				<span id="nrtok-tab-mob-count" class="nrtok-tab-mob-pill">0</span>
				<span><?php esc_html_e( 'View Order Ticket', 'nahian-restaurant-ordering-system' ); ?></span>
			</span>
			<strong id="nrtok-tab-mob-total"><?php echo esc_html( $fmt_money( 0 ) ); ?></strong>
		</button>
	</div>

	<div id="nrtok-tab-modal" class="nrtok-tab-modal nrtok-hidden" role="dialog" aria-modal="true">
		<div class="nrtok-tab-modal-sheet">
			<div class="nrtok-tab-modal-head">
				<strong id="nrtok-tab-modal-title"></strong>
				<button type="button" id="nrtok-tab-modal-close" aria-label="<?php esc_attr_e( 'Close', 'nahian-restaurant-ordering-system' ); ?>">&times;</button>
			</div>
			<div id="nrtok-tab-modal-list" class="nrtok-tab-modal-list"></div>

			<div class="nrtok-tab-modal-qty-bar">
				<span><?php esc_html_e( 'Quantity', 'nahian-restaurant-ordering-system' ); ?></span>
				<div class="nrtok-tab-stepper">
					<button type="button" id="nrtok-tab-modal-dec" class="nrtok-tab-step" aria-label="<?php esc_attr_e( 'Decrease quantity', 'nahian-restaurant-ordering-system' ); ?>">&minus;</button>
					<input type="number" id="nrtok-tab-modal-qty" class="nrtok-tab-qty-input" value="1" min="1" max="999" aria-label="<?php esc_attr_e( 'Quantity', 'nahian-restaurant-ordering-system' ); ?>">
					<button type="button" id="nrtok-tab-modal-inc" class="nrtok-tab-step" aria-label="<?php esc_attr_e( 'Increase quantity', 'nahian-restaurant-ordering-system' ); ?>">+</button>
				</div>
			</div>

			<button type="button" id="nrtok-tab-modal-confirm" class="nrtok-tab-btn-charge nrtok-tab-w-full">
				<span><?php esc_html_e( 'Add to Order', 'nahian-restaurant-ordering-system' ); ?></span>
				<strong id="nrtok-tab-modal-total"></strong>
			</button>
		</div>
	</div>

	<style>
		.nrtok-hidden { display: none !important; }
		.nrtok-tab-w-full { width: 100%; }

		.nrtok-tab-pos button, .nrtok-tab-pos select, .nrtok-tab-pos input,
		.nrtok-tab-mobile-bar button, .nrtok-tab-modal button, .nrtok-tab-modal input {
			touch-action: manipulation;
			-webkit-tap-highlight-color: transparent;
		}

		.nrtok-tab-toast {
			position: fixed; top: 18px; left: 50%; transform: translateX(-50%); z-index: 100030;
			background: #0f172a; color: #fff; padding: 14px 24px; border-radius: 999px;
			font-weight: 800; font-size: 14.5px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.3);
			border: 2px solid #10b981; display: none; white-space: nowrap;
		}

		.nrtok-tab-pos {
			display: grid;
			grid-template-columns: 1.55fr 1fr;
			gap: 18px;
			height: calc(100vh - 68px);
			height: calc(100dvh - 68px);
			min-height: 540px;
			box-sizing: border-box;
		}

		.nrtok-tab-menu-pane {
			background: #fff; border-radius: 22px; border: 1px solid #e2e8f0;
			display: flex; flex-direction: column; overflow: hidden;
			box-shadow: 0 4px 18px rgba(15, 23, 42, 0.03);
		}
		.nrtok-tab-menu-head { padding: 16px 18px 12px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }

		.nrtok-tab-search-box { position: relative; display: flex; align-items: center; margin-bottom: 12px; }
		.nrtok-tab-search-box svg { position: absolute; left: 16px; pointer-events: none; }
		.nrtok-tab-search-box input {
			width: 100%; height: 50px; padding: 0 44px 0 48px; border-radius: 14px;
			border: 1.5px solid #cbd5e1; background: #f8fafc; font-size: 15.5px; font-weight: 600; color: #0f172a;
		}
		.nrtok-tab-search-box input:focus { outline: none; background: #fff; border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12); }
		.nrtok-tab-q-clear {
			position: absolute; right: 10px; width: 32px; height: 32px; border-radius: 50%; border: none;
			background: #e2e8f0; color: #334155; font-size: 18px; font-weight: 800; cursor: pointer;
		}

		.nrtok-tab-cats {
			display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;
			-webkit-overflow-scrolling: touch; scrollbar-width: none;
		}
		.nrtok-tab-cats::-webkit-scrollbar { display: none; }
		.nrtok-tab-cat {
			min-height: 44px; padding: 0 18px; border-radius: 12px; border: 1.5px solid #e2e8f0;
			background: #f8fafc; color: #334155; font-weight: 800; font-size: 13.5px;
			cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: transform 0.1s ease;
		}
		.nrtok-tab-cat:active { transform: scale(0.96); }
		.nrtok-tab-cat.active { background: #0f172a; color: #fff; border-color: #0f172a; }

		.nrtok-tab-grid {
			flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch;
			padding: 16px 18px 24px; display: grid;
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
			gap: 14px; align-content: start;
		}
		.nrtok-tab-card {
			position: relative; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 18px;
			padding: 14px 10px; text-align: center; cursor: pointer;
			display: flex; flex-direction: column; align-items: center; justify-content: space-between;
			min-height: 142px; transition: transform 0.1s ease, border-color 0.15s ease, background 0.15s ease;
		}
		.nrtok-tab-card:active { transform: scale(0.95); }
		.nrtok-tab-card.selected { border-color: #f97316; background: #fff7ed; }

		.nrtok-tab-badge {
			position: absolute; top: -8px; right: -8px; min-width: 28px; height: 28px; padding: 0 7px;
			border-radius: 999px; background: #f97316; color: #fff; font-size: 13.5px; font-weight: 900;
			display: inline-flex; align-items: center; justify-content: center; border: 2.5px solid #fff;
			box-shadow: 0 4px 10px rgba(249, 115, 22, 0.4);
		}
		.nrtok-tab-addon-tag {
			position: absolute; top: 8px; left: 8px; padding: 2px 7px;
			border-radius: 8px; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;
			font-size: 10.5px; font-weight: 800; line-height: 1.3;
		}
		.nrtok-tab-img { width: 62px; height: 62px; border-radius: 14px; object-fit: cover; margin-bottom: 10px; }
		.nrtok-tab-img-placeholder {
			width: 62px; height: 62px; border-radius: 14px; background: #fff; color: #94a3b8;
			display: flex; align-items: center; justify-content: center; margin-bottom: 10px; border: 1px solid #e2e8f0;
		}
		.nrtok-tab-card-title { font-size: 14px; font-weight: 800; color: #0f172a; line-height: 1.25; margin-bottom: 6px; }
		.nrtok-tab-card-price { font-size: 15.5px; font-weight: 900; color: #f97316; }

		.nrtok-tab-ticket-pane {
			background: #fff; border-radius: 22px; border: 1px solid #e2e8f0;
			display: flex; flex-direction: column; overflow: hidden;
			box-shadow: 0 8px 26px rgba(15, 23, 42, 0.05);
		}
		.nrtok-tab-ticket-head {
			padding: 14px 16px; border-bottom: 1px solid #f1f5f9;
			display: flex; align-items: center; gap: 8px; flex-shrink: 0;
		}
		.nrtok-tab-modes {
			display: grid; grid-template-columns: 1fr 1fr; background: #f1f5f9;
			padding: 4px; border-radius: 13px; flex: 1;
		}
		.nrtok-tab-mode {
			min-height: 40px; border: none; background: transparent; border-radius: 10px;
			font-weight: 800; font-size: 13.5px; color: #64748b; cursor: pointer;
		}
		.nrtok-tab-mode.active { background: #fff; color: #0f172a; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08); }
		.nrtok-tab-table-select {
			height: 48px; padding: 0 12px; border-radius: 13px; border: 1.5px solid #cbd5e1;
			font-weight: 800; font-size: 14px; color: #0f172a; background: #fff;
		}
		.nrtok-tab-qty-summary {
			min-width: 34px; height: 34px; padding: 0 8px; border-radius: 999px;
			background: #fff7ed; color: #ea580c; border: 1.5px solid #fed7aa;
			font-size: 13px; font-weight: 900; display: inline-flex; align-items: center; justify-content: center;
			flex-shrink: 0;
		}
		.nrtok-tab-close-drawer {
			display: none; width: 44px; height: 44px; border-radius: 12px; border: none;
			background: #f1f5f9; color: #0f172a; font-size: 22px; font-weight: 800; cursor: pointer;
		}

		.nrtok-tab-cart {
			flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch;
			padding: 12px 16px; background: #f8fafc;
		}
		.nrtok-tab-empty { text-align: center; color: #94a3b8; font-weight: 700; font-size: 14px; padding: 70px 12px; }
		.nrtok-tab-empty p { margin: 10px 0 0; }

		.nrtok-tab-row {
			display: flex; justify-content: space-between; align-items: center; gap: 10px;
			background: #fff; padding: 12px 14px; border-radius: 14px; margin-bottom: 10px;
			border: 1px solid #e2e8f0;
		}
		.nrtok-tab-row-info { flex: 1; min-width: 0; }
		.nrtok-tab-row-name { font-weight: 800; font-size: 14px; color: #0f172a; line-height: 1.3; }
		.nrtok-tab-row-price { font-size: 12.5px; color: #64748b; font-weight: 700; margin-top: 3px; }

		.nrtok-tab-stepper { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
		.nrtok-tab-step {
			width: 40px; height: 40px; border-radius: 11px; border: 1.5px solid #cbd5e1;
			background: #f8fafc; font-size: 19px; font-weight: 900; cursor: pointer; color: #0f172a;
			display: inline-flex; align-items: center; justify-content: center;
		}
		.nrtok-tab-step:active { transform: scale(0.92); border-color: #f97316; color: #f97316; }
		.nrtok-tab-qty-input {
			width: 46px; height: 40px; padding: 0 4px; border-radius: 10px;
			border: 1.5px solid #e2e8f0; background: #fff; text-align: center;
			font-size: 15px; font-weight: 900; color: #0f172a;
			-moz-appearance: textfield;
		}
		.nrtok-tab-qty-input::-webkit-outer-spin-button,
		.nrtok-tab-qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
		.nrtok-tab-qty-input:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.14); }

		.nrtok-tab-ticket-foot {
			padding: 12px 16px 16px; background: #fff; border-top: 1px solid #e2e8f0; flex-shrink: 0;
		}
		.nrtok-tab-quick-tools { display: flex; justify-content: space-between; margin-bottom: 8px; }
		.nrtok-tab-tool-link, .nrtok-tab-tool-clear {
			min-height: 34px; background: none; border: none; font-size: 13px; font-weight: 800; cursor: pointer; padding: 0 4px;
		}
		.nrtok-tab-tool-link { color: #f97316; }
		.nrtok-tab-tool-clear { color: #94a3b8; }

		.nrtok-tab-opt-box { display: grid; grid-template-columns: 1fr 1.4fr; gap: 8px; margin-bottom: 10px; }
		.nrtok-tab-opt-box input {
			height: 44px; padding: 0 12px; border-radius: 11px; border: 1.5px solid #cbd5e1; font-size: 14px; font-weight: 600;
		}

		.nrtok-tab-pay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; margin-bottom: 12px; }
		.nrtok-tab-pay {
			min-height: 42px; border-radius: 11px; border: 1.5px solid #e2e8f0; background: #f8fafc;
			font-weight: 800; font-size: 13px; color: #475569; cursor: pointer;
		}
		.nrtok-tab-pay.active { border-color: #0f172a; background: #0f172a; color: #fff; }

		.nrtok-tab-cta-row { display: grid; grid-template-columns: 0.8fr 0.95fr 1.85fr; gap: 8px; }
		.nrtok-tab-btn-kot {
			height: 58px; border-radius: 15px; border: none; background: #0f172a; color: #fff;
			font-size: 14.5px; font-weight: 900; cursor: pointer; letter-spacing: 0.3px;
		}
		.nrtok-tab-btn-print {
			height: 58px; border-radius: 15px; border: 1.5px solid #cbd5e1; background: #f8fafc; color: #0f172a;
			font-size: 14px; font-weight: 850; cursor: pointer; padding: 0 10px;
			display: inline-flex; align-items: center; justify-content: center; gap: 6px;
		}
		.nrtok-tab-btn-print:hover { background: #f1f5f9; border-color: #94a3b8; }
		.nrtok-tab-btn-kot:active, .nrtok-tab-btn-print:active, .nrtok-tab-btn-charge:active { transform: scale(0.97); }
		.nrtok-tab-btn-charge {
			height: 58px; border-radius: 15px; border: none;
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #fff;
			font-size: 15.5px; font-weight: 900; cursor: pointer; padding: 0 16px;
			display: inline-flex; align-items: center; justify-content: space-between; gap: 6px;
			box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
		}

		.nrtok-tab-mobile-bar { display: none; }
		@media (max-width: 820px) {
			.nrtok-tab-pos { display: block; height: auto; min-height: 0; padding-bottom: 78px; }
			.nrtok-tab-grid { max-height: none; overflow-y: visible; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
			.nrtok-tab-ticket-pane {
				display: none; position: fixed; inset: 0; z-index: 100020; border-radius: 0;
			}
			.nrtok-tab-ticket-pane.open { display: flex; }
			.nrtok-tab-close-drawer { display: inline-flex; align-items: center; justify-content: center; }
			.nrtok-tab-mobile-bar {
				display: block; position: fixed; bottom: 16px; left: 16px; right: 16px; z-index: 99990;
			}
			.nrtok-tab-mobile-btn {
				width: 100%; height: 58px; border-radius: 16px; border: none;
				background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #fff;
				padding: 0 20px; display: flex; align-items: center; justify-content: space-between;
				font-size: 16px; font-weight: 900; box-shadow: 0 12px 28px rgba(249, 115, 22, 0.45); cursor: pointer;
			}
			.nrtok-tab-mob-left { display: inline-flex; align-items: center; gap: 10px; }
			.nrtok-tab-mob-pill {
				background: rgba(0, 0, 0, 0.25); padding: 3px 10px; border-radius: 999px; font-size: 13px;
			}
		}

		.nrtok-tab-modal {
			position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 100025;
			display: flex; align-items: center; justify-content: center; padding: 16px;
		}
		.nrtok-tab-modal-sheet {
			background: #fff; width: 100%; max-width: 400px; border-radius: 22px; padding: 22px;
			box-shadow: 0 24px 48px rgba(15, 23, 42, 0.3);
		}
		.nrtok-tab-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-size: 17px; }
		.nrtok-tab-modal-head button { border: none; background: #f1f5f9; width: 38px; height: 38px; border-radius: 10px; font-size: 22px; cursor: pointer; }
		.nrtok-tab-modal-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; max-height: 44vh; overflow-y: auto; }
		.nrtok-tab-addon-row {
			display: flex; justify-content: space-between; align-items: center; min-height: 48px; padding: 10px 14px;
			border-radius: 13px; border: 1.5px solid #e2e8f0; font-weight: 800; font-size: 14px; cursor: pointer;
		}
		.nrtok-tab-addon-row input { accent-color: #f97316; width: 20px; height: 20px; margin-right: 10px; }

		.nrtok-tab-modal-qty-bar {
			display: flex; justify-content: space-between; align-items: center;
			background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px;
			padding: 10px 14px; margin-bottom: 16px; font-weight: 800; font-size: 14.5px; color: #0f172a;
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		const cur       = <?php echo wp_json_encode( $cur ); ?>;
		const curPos    = <?php echo wp_json_encode( $cur_pos ); ?>;
		const decimals  = <?php echo (int) $decimals; ?>;
		const taxPct    = <?php echo wp_json_encode( $tax_percent ); ?>;
		const nonce     = <?php echo wp_json_encode( $nonce ); ?>;
		const restName  = <?php echo wp_json_encode( $rest_name ); ?>;
		const restPhone = <?php echo wp_json_encode( $rest_phone ); ?>;
		const footerTxt = <?php echo wp_json_encode( $footer_txt ); ?>;
		const ordPrefix = <?php echo wp_json_encode( $ord_prefix ); ?>;
		const paperW    = <?php echo wp_json_encode( $paper_width ); ?>;
		const autoPrint = <?php echo (int) $auto_print; ?>;

		let cart      = [];
		let activeCat = 'all';
		let orderMode = 'dine_in';
		let payMethod = <?php echo wp_json_encode( $first_pay_label ); ?>;
		let modalDish = null;

		function fmtMoney(val) {
			const num = (parseFloat(val) || 0).toFixed(decimals);
			return curPos === 'right' ? (num + cur) : (cur + num);
		}

		function toast(msg) {
			$('#nrtok-tab-toast').text(msg).fadeIn(150);
			setTimeout(function() { $('#nrtok-tab-toast').fadeOut(250); }, 2500);
		}

		function escHtml(s) {
			return String(s === null || s === undefined ? '' : s)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		}

		function parseCardExtras($card) {
			let raw = $card.attr('data-extras');
			if (!raw) return [];
			try {
				const parsed = JSON.parse(raw);
				return Array.isArray(parsed) ? parsed : [];
			} catch (e) {
				return [];
			}
		}

		function filterTiles() {
			const q = ($('#nrtok-tab-q').val() || '').toLowerCase().trim();
			$('#nrtok-tab-q-clear').toggleClass('nrtok-hidden', q.length === 0);
			$('.nrtok-tab-card').each(function() {
				const n = String($(this).data('name') || '').toLowerCase();
				const c = String($(this).data('cat') || '').toLowerCase();
				$(this).toggle((!q || n.indexOf(q) > -1) && (activeCat === 'all' || c === activeCat));
			});
		}

		$('#nrtok-tab-q').on('input search', filterTiles);
		$('#nrtok-tab-q-clear').on('click', function() { $('#nrtok-tab-q').val(''); filterTiles(); });

		$('.nrtok-tab-cat').on('click', function() {
			$('.nrtok-tab-cat').removeClass('active');
			$(this).addClass('active');
			activeCat = $(this).data('cat');
			filterTiles();
		});

		$('.nrtok-tab-mode').on('click', function() {
			$('.nrtok-tab-mode').removeClass('active');
			$(this).addClass('active');
			orderMode = $(this).data('mode');
			$('#nrtok-tab-table').toggle(orderMode === 'dine_in');
		});

		$('.nrtok-tab-pay').on('click', function() {
			$('.nrtok-tab-pay').removeClass('active');
			$(this).addClass('active');
			payMethod = $(this).data('pay');
		});

		$('#nrtok-tab-opt-btn').on('click', function() { $('#nrtok-tab-opt-box').toggleClass('nrtok-hidden'); });
		$('#nrtok-tab-open-drawer').on('click', function() { $('#nrtok-tab-ticket-pane').addClass('open'); });
		$('#nrtok-tab-close-drawer').on('click', function() { $('#nrtok-tab-ticket-pane').removeClass('open'); });

		function render() {
			const $box = $('#nrtok-tab-cart').empty();
			const counts = {};
			let sub = 0;
			let totalItems = 0;

			if (cart.length === 0) {
				$box.append(
					'<div class="nrtok-tab-empty">' +
						'<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>' +
						'<p><?php echo esc_js( __( 'Tap dishes on the left to add', 'nahian-restaurant-ordering-system' ) ); ?></p>' +
					'</div>'
				);
			} else {
				cart.forEach(function(it, idx) {
					counts[it.id] = (counts[it.id] || 0) + it.qty;
					totalItems += it.qty;
					const line = it.unitPrice * it.qty;
					sub += line;

					const $row = $('<div class="nrtok-tab-row"></div>');
					const label = it.extras.length ? it.name + ' (+' + it.extras.join(', ') + ')' : it.name;
					$row.append(
						$('<div class="nrtok-tab-row-info"></div>')
							.append($('<div class="nrtok-tab-row-name"></div>').text(label))
							.append($('<div class="nrtok-tab-row-price"></div>').text(fmtMoney(it.unitPrice) + ' × ' + it.qty + ' = ' + fmtMoney(line)))
					);

					const $st = $('<div class="nrtok-tab-stepper"></div>')
						.append($('<button type="button" class="nrtok-tab-step" data-a="dec">−</button>').attr('data-i', idx))
						.append($('<input type="number" class="nrtok-tab-qty-input" min="0" max="999">').attr('data-i', idx).val(it.qty))
						.append($('<button type="button" class="nrtok-tab-step" data-a="inc">+</button>').attr('data-i', idx));
					$row.append($st);
					$box.append($row);
				});
			}

			$('.nrtok-tab-card').each(function() {
				const id = parseInt($(this).data('id'), 10);
				const q  = counts[id] || 0;
				$(this).toggleClass('selected', q > 0);
				$(this).find('.nrtok-tab-badge').text(q).toggleClass('nrtok-hidden', q === 0);
			});

			const disc     = Math.min(sub, Math.max(0, parseFloat($('#nrtok-tab-disc').val()) || 0));
			const tax      = (Math.max(0, sub - disc) * taxPct) / 100;
			const total    = Math.max(0, sub - disc) + tax;
			const totalStr = fmtMoney(total);

			$('#nrtok-tab-total, #nrtok-tab-mob-total').text(totalStr);
			$('#nrtok-tab-mob-count, #nrtok-tab-qty-summary').text(totalItems);
		}

		function addItem(dish, extras, extraSum, addQty) {
			const qtyToAdd = Math.max(1, parseInt(addQty, 10) || 1);
			const unit     = dish.price + extraSum;
			const key      = dish.id + '|' + extras.slice().sort().join(',');
			const hit      = cart.find(x => x.key === key);
			if (hit) hit.qty += qtyToAdd;
			else cart.push({ key: key, id: dish.id, name: dish.name, unitPrice: unit, qty: qtyToAdd, extras: extras });
			render();
		}

		function updateModalTotal() {
			if (!modalDish) return;
			let extraSum = 0;
			$('.nrtok-tab-cb:checked').each(function() { extraSum += parseFloat($(this).data('p')) || 0; });
			const mQty = Math.max(1, parseInt($('#nrtok-tab-modal-qty').val(), 10) || 1);
			const tot  = (modalDish.price + extraSum) * mQty;
			$('#nrtok-tab-modal-total').text(fmtMoney(tot));
		}

		$(document).on('click', '.nrtok-tab-card', function(e) {
			e.preventDefault();
			const $card = $(this);
			const dish = {
				id: parseInt($card.attr('data-id'), 10),
				name: $card.attr('data-name') || '',
				price: parseFloat($card.attr('data-price')) || 0
			};
			const extras = parseCardExtras($card);
			if (extras.length > 0) {
				modalDish = dish;
				$('#nrtok-tab-modal-title').text(dish.name);
				$('#nrtok-tab-modal-qty').val(1);
				const $list = $('#nrtok-tab-modal-list').empty();
				extras.forEach(function(ex) {
					const $lbl = $('<label class="nrtok-tab-addon-row"></label>');
					$lbl.append($('<span></span>').append($('<input type="checkbox" class="nrtok-tab-cb">').val(ex.name).attr('data-p', ex.price), document.createTextNode(ex.name)));
					$lbl.append($('<strong></strong>').text('+' + fmtMoney(ex.price)));
					$list.append($lbl);
				});
				updateModalTotal();
				$('#nrtok-tab-modal').removeClass('nrtok-hidden');
			} else {
				addItem(dish, [], 0, 1);
			}
		});

		$(document).on('change', '.nrtok-tab-cb', updateModalTotal);
		$('#nrtok-tab-modal-qty').on('input change', updateModalTotal);
		$('#nrtok-tab-modal-dec').on('click', function() {
			const curQ = Math.max(1, (parseInt($('#nrtok-tab-modal-qty').val(), 10) || 1) - 1);
			$('#nrtok-tab-modal-qty').val(curQ);
			updateModalTotal();
		});
		$('#nrtok-tab-modal-inc').on('click', function() {
			const curQ = Math.min(999, (parseInt($('#nrtok-tab-modal-qty').val(), 10) || 1) + 1);
			$('#nrtok-tab-modal-qty').val(curQ);
			updateModalTotal();
		});

		$('#nrtok-tab-modal-close').on('click', function() { $('#nrtok-tab-modal').addClass('nrtok-hidden'); });
		$('#nrtok-tab-modal').on('click', function(e) { if (e.target === this) $(this).addClass('nrtok-hidden'); });
		$('#nrtok-tab-modal-confirm').on('click', function() {
			if (!modalDish) return;
			const sel  = [];
			let sum    = 0;
			const mQty = Math.max(1, parseInt($('#nrtok-tab-modal-qty').val(), 10) || 1);
			$('.nrtok-tab-cb:checked').each(function() { sel.push($(this).val()); sum += parseFloat($(this).data('p')) || 0; });
			addItem(modalDish, sel, sum, mQty);
			$('#nrtok-tab-modal').addClass('nrtok-hidden');
		});

		$(document).on('click', '.nrtok-tab-step[data-i]', function() {
			const i = parseInt($(this).data('i'), 10);
			if ($(this).data('a') === 'inc') cart[i].qty += 1;
			else {
				cart[i].qty -= 1;
				if (cart[i].qty <= 0) cart.splice(i, 1);
			}
			render();
		});

		$(document).on('change', '.nrtok-tab-qty-input', function() {
			const i   = parseInt($(this).data('i'), 10);
			const val = parseInt($(this).val(), 10);
			if (isNaN(val) || val <= 0) {
				cart.splice(i, 1);
			} else {
				cart[i].qty = Math.min(999, val);
			}
			render();
		});

		$('#nrtok-tab-disc').on('input', render);
		$('#nrtok-tab-clear-btn').on('click', function() { cart = []; $('#nrtok-tab-disc, #nrtok-tab-note').val(''); render(); });

		function printSlip(orderId, slipType, snap) {
			const isCustomerBill = (slipType === 'bill' || slipType === 'paid');
			let rows = '';

			snap.items.forEach(function(i) {
				const extraStr = (i.extras && i.extras.length)
					? '<br><span style="font-size:11px;font-weight:normal;">  + ' + i.extras.map(escHtml).join(', ') + '</span>'
					: '';
				const priceCol = isCustomerBill ? escHtml(fmtMoney(i.unitPrice * i.qty)) : '';
				rows += '<tr><td style="padding:3px 0;">' + i.qty + 'x ' + escHtml(i.name) + extraStr + '</td><td style="text-align:right;vertical-align:top;">' + priceCol + '</td></tr>';
			});

			const badgeTitle = (slipType === 'kot')
				? '*** KITCHEN TICKET (KOT) ***'
				: (slipType === 'paid' ? '*** TAX INVOICE (PAID) ***' : '*** CUSTOMER BILL (UNPAID) ***');

			let footerHtml = '';
			if (isCustomerBill) {
				footerHtml += '<hr style="border-top:1px dashed #000;">';
				footerHtml += '<div style="display:flex;justify-content:space-between;font-size:12px;"><span>Subtotal:</span><span>' + escHtml(fmtMoney(snap.sub)) + '</span></div>';
				if (snap.disc > 0) {
					footerHtml += '<div style="display:flex;justify-content:space-between;font-size:12px;"><span>Discount:</span><span>-' + escHtml(fmtMoney(snap.disc)) + '</span></div>';
				}
				if (snap.tax > 0) {
					footerHtml += '<div style="display:flex;justify-content:space-between;font-size:12px;"><span>VAT/Tax:</span><span>' + escHtml(fmtMoney(snap.tax)) + '</span></div>';
				}
				footerHtml += '<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:15px;border-top:1px solid #000;padding-top:4px;margin-top:4px;"><span>TOTAL:</span><span>' + escHtml(fmtMoney(snap.total)) + '</span></div>';
				if (slipType === 'paid') {
					footerHtml += '<div style="display:flex;justify-content:space-between;font-size:11.5px;margin-top:3px;"><span>Paid Via:</span><strong>' + escHtml(snap.payMethod) + '</strong></div>';
				}
				footerHtml += '<hr style="border-top:1px dashed #000;"><p style="text-align:center;font-size:11px;margin:4px 0;">' + escHtml(footerTxt) + '</p>';
			} else if (snap.note) {
				footerHtml += '<hr style="border-top:1px dashed #000;"><p style="margin:4px 0;"><strong>NOTE:</strong> ' + escHtml(snap.note) + '</p>';
			}

			const slip =
				'<div style="text-align:center;">' +
					'<h3 style="margin:0;font-size:17px;">' + escHtml(restName) + '</h3>' +
					(restPhone ? '<div style="font-size:10.5px;">Ph: ' + escHtml(restPhone) + '</div>' : '') +
					'<div style="font-size:11px;font-weight:bold;margin:3px 0;">' + badgeTitle + '</div>' +
					'<div style="font-size:14px;font-weight:900;border:1.5px solid #000;padding:3px;margin:4px 0;">' + escHtml(snap.tableLabel) + '</div>' +
					'<p style="margin:2px 0;font-size:11px;">' + escHtml(ordPrefix) + parseInt(orderId, 10) + ' | ' + new Date().toLocaleTimeString() + '</p>' +
				'</div>' +
				'<hr style="border-top:1px dashed #000;">' +
				'<table style="width:100%;font-size:13px;font-weight:bold;">' + rows + '</table>' +
				footerHtml;

			if ($('#nrtok-print-area').length === 0) $('body').append('<div id="nrtok-print-area"></div>');
			$('#nrtok-print-area').toggleClass('is-58mm', paperW === '58mm').html(slip);
			window.print();
		}

		function submitOrder(paid, slipType) {
			if (cart.length === 0) {
				toast('<?php echo esc_js( __( 'Tap dishes first!', 'nahian-restaurant-ordering-system' ) ); ?>');
				return;
			}

			const tbl  = $('#nrtok-tab-table').val();
			const note = $('#nrtok-tab-note').val();
			let sub = 0;
			cart.forEach(function(i) { sub += i.unitPrice * i.qty; });
			const disc  = Math.min(sub, Math.max(0, parseFloat($('#nrtok-tab-disc').val()) || 0));
			const tax   = (Math.max(0, sub - disc) * taxPct) / 100;
			const total = Math.max(0, sub - disc) + tax;

			const snap = {
				items: cart.slice(),
				sub: sub,
				disc: disc,
				tax: tax,
				total: total,
				payMethod: payMethod,
				note: note,
				tableLabel: orderMode === 'dine_in' ? ('TABLE ' + tbl) : 'TAKEAWAY'
			};

			$('.js-nahian-restaurant-ordering-system-submit').prop('disabled', true);

			$.post(ajaxurl, {
				action: 'nrtok_create_counter_order',
				nonce: nonce,
				order_type: orderMode,
				table_no: tbl,
				discount_amount: disc.toFixed(2),
				payment_method: payMethod,
				note: note,
				mark_paid: paid ? 1 : 0,
				items: JSON.stringify(cart)
			}, function(res) {
				$('.js-nahian-restaurant-ordering-system-submit').prop('disabled', false);
				if (res && res.success) {
					toast(res.data.message);
					if (autoPrint === 1 || slipType === 'bill') {
						printSlip(res.data.order_id, slipType, snap);
					}
					cart = [];
					$('#nrtok-tab-disc, #nrtok-tab-note').val('');
					$('#nrtok-tab-ticket-pane').removeClass('open');
					render();
				} else {
					toast((res && res.data && res.data.message) || '<?php echo esc_js( __( 'Could not save order.', 'nahian-restaurant-ordering-system' ) ); ?>');
				}
			}).fail(function() {
				$('.js-nahian-restaurant-ordering-system-submit').prop('disabled', false);
				toast('<?php echo esc_js( __( 'Network error saving order.', 'nahian-restaurant-ordering-system' ) ); ?>');
			});
		}

		$('#nrtok-tab-kot-btn').on('click', function() { submitOrder(false, 'kot'); });
		$('#nrtok-tab-print-btn').on('click', function() { submitOrder(false, 'bill'); });
		$('#nrtok-tab-charge-btn').on('click', function() { submitOrder(true, 'paid'); });
	});
	</script>
	<?php
}