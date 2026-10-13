<?php
/**
 * The parts of the gateway settings page that are not plain fields.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Gateway;

use PatrickKivits\BunqForWooCommerce\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Draws the connection status, the redirect URL and the connect button of the settings page as rows of the
 * WooCommerce settings table, and the outcome of the last authorization as admin notices.
 */
final class SettingsPage {

	const SCRIPT_PATH = 'assets/js/admin-settings.js';
	const STYLE_PATH  = 'assets/css/admin-settings.css';
	const HANDLE      = 'bunq-for-woocommerce-admin-settings';

	/**
	 * The bunq Help article about setting up the plugin.
	 */
	const HELP_URL = 'https://help.bunq.com/nl-nl/articles/how-can-i-accept-woocommerce-payments-with-my-bunq-account';

	/**
	 * Load the script and styles of the settings page.
	 */
	public static function enqueue_assets() {
		$plugin_file = Plugin::instance()->file();
		$base_url    = plugin_dir_url( $plugin_file );

		wp_enqueue_style( self::HANDLE, $base_url . self::STYLE_PATH, array(), Plugin::VERSION );
		wp_enqueue_script( self::HANDLE, $base_url . self::SCRIPT_PATH, array( 'jquery', 'wp-a11y' ), Plugin::VERSION, true );
		wp_localize_script(
			self::HANDLE,
			'bunqForWooCommerceSettings',
			array(
				'copiedAnnouncement' => __( 'Redirect URL copied to the clipboard.', 'bunq-for-woocommerce' ),
				'saveToSwitchMode'   => __( 'Save the settings to choose from the bank accounts of the other mode.', 'bunq-for-woocommerce' ),
				'confirmDisconnect'  => __( 'Disconnect the shop from bunq? Customers can no longer pay with bunq until you connect again.', 'bunq-for-woocommerce' ),
			)
		);
	}

	/**
	 * The outcome of the last authorization, above the settings form.
	 *
	 * @param array $notices Message by "error" or "success".
	 * @return string
	 */
	public static function notices_html( $notices ) {
		ob_start();

		if ( ! empty( $notices['error'] ) ) {
			?>
			<div class="notice notice-error inline">
				<p><strong><?php esc_html_e( 'bunq could not be connected:', 'bunq-for-woocommerce' ); ?></strong> <?php echo esc_html( $notices['error'] ); ?></p>
				<p>
					<?php
					printf(
						/* translators: %s: link to the WooCommerce log page */
						esc_html__( 'Details are logged in %s (source: bunq).', 'bunq-for-woocommerce' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs' ) ) . '">' . esc_html__( 'WooCommerce > Status > Logs', 'bunq-for-woocommerce' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( ! empty( $notices['success'] ) ) {
			?>
			<div class="notice notice-success inline"><p><?php echo esc_html( $notices['success'] ); ?></p></div>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * The heading of the bunq account section.
	 *
	 * @param string $heading_html The heading as WooCommerce draws it.
	 * @return string
	 */
	public static function account_heading_html( $heading_html ) {
		// The first submit button of the form is the one Enter presses: keep that a plain save rather than one of the
		// connect buttons below.
		return '<button type="submit" name="save" value="save" class="bunq-default-submit" tabindex="-1" aria-hidden="true"></button>' . $heading_html;
	}

	/**
	 * The connection status of each mode, of which the settings page shows the one the test mode checkbox selects,
	 * and the redirect URL to register in the bunq app.
	 *
	 * @param array[] $connections Gateway::get_connection() by mode.
	 * @param string  $saved_mode  The mode of the saved settings.
	 * @return string
	 */
	public static function connection_html( $connections, $saved_mode ) {
		ob_start();

		foreach ( $connections as $mode => $connection ) {
			self::status_row( $mode, $connection, $saved_mode !== $mode );
		}
		?>
		<tr valign="top" class="bunq-redirect-row">
			<th scope="row" class="titledesc">
				<label for="bunq-redirect-url"><?php esc_html_e( 'Redirect URL', 'bunq-for-woocommerce' ); ?></label>
			</th>
			<td class="forminp">
				<span class="bunq-copy-field">
					<input type="text" id="bunq-redirect-url" class="regular-text code bunq-redirect-url" value="<?php echo esc_attr( Gateway::settings_url() ); ?>" readonly />
					<button type="button" class="button bunq-copy" data-bunq-copy="bunq-redirect-url"><?php esc_html_e( 'Copy', 'bunq-for-woocommerce' ); ?></button>
				</span>
				<span class="success hidden" aria-hidden="true"><?php esc_html_e( 'Copied!', 'bunq-for-woocommerce' ); ?></span>
				<p class="description">
					<?php
					printf(
						/* translators: 1: menu path in the bunq app, 2: action in the bunq app, 3: action in the bunq app */
						esc_html__( 'In the bunq app, go to %1$s, tap %2$s and enter this URL. Then tap %3$s and copy the Client ID and Client Secret into the fields below.', 'bunq-for-woocommerce' ),
						'<strong>' . esc_html__( 'Developers › OAuth', 'bunq-for-woocommerce' ) . '</strong>',
						'<strong>' . esc_html__( 'Add Redirect URL', 'bunq-for-woocommerce' ) . '</strong>',
						'<strong>' . esc_html__( 'Show Client Details', 'bunq-for-woocommerce' ) . '</strong>'
					);
					?>
				</p>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * The row with the button that saves the settings and continues to bunq.
	 *
	 * @return string
	 */
	public static function connect_html() {
		ob_start();
		?>
		<tr valign="top" class="bunq-connect-row">
			<th scope="row" class="titledesc"></th>
			<td class="forminp">
				<button type="submit" name="save" value="<?php echo esc_attr( Gateway::CONNECT_ACTION ); ?>" class="button bunq-connect-button"><?php esc_html_e( 'Connect to bunq', 'bunq-for-woocommerce' ); ?></button>
				<p class="description"><?php esc_html_e( 'Saves your settings and opens bunq, which shows a QR code. Scan it with the bunq app, choose the bank accounts the shop may use and approve. bunq then sends you back here.', 'bunq-for-woocommerce' ); ?></p>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * The connection status row of one mode.
	 *
	 * @param string $mode       "live" or "sandbox".
	 * @param array  $connection Gateway::get_connection().
	 * @param bool   $hidden     Whether the row starts hidden (the settings page shows the active mode's).
	 */
	private static function status_row( $mode, $connection, $hidden ) {
		$sandbox = 'sandbox' === $mode;
		?>
		<tr valign="top" class="bunq-connection-row" data-bunq-mode="<?php echo esc_attr( $mode ); ?>" data-bunq-connected="<?php echo $connection['connected'] ? '1' : '0'; ?>"<?php echo $hidden ? ' hidden' : ''; ?>>
			<th scope="row" class="titledesc"><?php esc_html_e( 'Connection', 'bunq-for-woocommerce' ); ?></th>
			<td class="forminp">
				<?php if ( $connection['connected'] ) : ?>
					<p class="bunq-connection-status">
						<?php if ( $connection['failed'] ) : ?>
							<span class="dashicons dashicons-warning bunq-status-warning" aria-hidden="true"></span>
						<?php else : ?>
							<span class="dashicons dashicons-yes-alt bunq-status-connected" aria-hidden="true"></span>
						<?php endif; ?>
						<?php
						if ( $connection['account_name'] ) {
							if ( $sandbox ) {
								/* translators: %s: name of the bunq user that granted access */
								$format = esc_html__( 'Connected to the bunq sandbox as %s.', 'bunq-for-woocommerce' );
							} else {
								/* translators: %s: name of the bunq user that granted access */
								$format = esc_html__( 'Connected to bunq as %s.', 'bunq-for-woocommerce' );
							}

							// No second period after names such as "Example Shop B.V.".
							if ( '.' === substr( $connection['account_name'], -1 ) ) {
								$format = rtrim( $format, '.' );
							}

							printf( $format, '<strong>' . esc_html( $connection['account_name'] ) . '</strong>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
						} else {
							echo $sandbox ? esc_html__( 'Connected to the bunq sandbox.', 'bunq-for-woocommerce' ) : esc_html__( 'Connected to bunq.', 'bunq-for-woocommerce' );
						}
						?>
					</p>
					<?php if ( $connection['failed'] ) : ?>
						<p class="description"><?php esc_html_e( 'bunq recently rejected the connection, so bunq is not offered at checkout. Connect again to restore it.', 'bunq-for-woocommerce' ); ?></p>
					<?php endif; ?>
					<p class="bunq-connection-actions">
						<button type="submit" name="save" value="<?php echo esc_attr( Gateway::CONNECT_ACTION ); ?>" class="button bunq-connect-button"><?php esc_html_e( 'Connect again', 'bunq-for-woocommerce' ); ?></button>
						<button type="button" class="button-link bunq-edit-client"><?php esc_html_e( 'Change OAuth client', 'bunq-for-woocommerce' ); ?></button>
						<a href="<?php echo esc_url( $connection['disconnect_url'] ); ?>" class="button-link button-link-delete bunq-disconnect"><?php esc_html_e( 'Disconnect', 'bunq-for-woocommerce' ); ?></a>
					</p>
				<?php else : ?>
					<p class="bunq-connection-status"><?php echo $sandbox ? esc_html__( 'Not connected to the bunq sandbox.', 'bunq-for-woocommerce' ) : esc_html__( 'Not connected to bunq.', 'bunq-for-woocommerce' ); ?></p>
					<p class="description">
						<?php
						echo $connection['client_saved']
							? esc_html__( 'Click "Connect to bunq" below and scan the QR code with the bunq app.', 'bunq-for-woocommerce' )
							: esc_html__( 'Register the redirect URL in the bunq app and enter the Client ID and Client Secret of your OAuth client below.', 'bunq-for-woocommerce' );
						?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}
