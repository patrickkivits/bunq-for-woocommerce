<?php
/**
 * The health of the connection with bunq.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Gateway;

use GuzzleHttp\Exception\GuzzleException;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiException;
use PatrickKivits\BunqForWooCommerce\Logger;
use PatrickKivits\BunqForWooCommerce\Orders\PaymentMonitor;
use PatrickKivits\BunqForWooCommerce\Plugin;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Checks every hour whether the connection with bunq still works (the API context, and the OAuth grant it was
 * created from), so the merchant hears about a revoked access, a changed IP address or a closed bank account before
 * checkouts fail. A broken connection shows an admin notice, and the outcome of the last check is in WooCommerce >
 * Status.
 *
 * The outcome is stored per mode in an option: {"live": {"status", "checked_at", "error", "failures",
 * "failing_since"}, "sandbox": {...}}. Failures of payments and refunds that made the plugin recreate the API
 * context in vain are recorded there as well.
 */
final class ConnectionHealth {

	/**
	 * The option with the outcome of the last check per mode.
	 */
	const OPTION = 'bunq_for_woocommerce_connection_health';

	/**
	 * Action Scheduler hook of the recurring check.
	 */
	const CHECK_ACTION = 'bunq_for_woocommerce_connection_check';

	/**
	 * The admin-post action of the "Check again" link.
	 */
	const CHECK_NOW_ACTION = 'bunq_check_connection';

	/**
	 * Prefix of the transient that holds the outcome of "Check again" for the user who clicked it.
	 */
	const CHECKED_TRANSIENT = 'bunq_for_woocommerce_connection_checked_';

	/**
	 * Seconds between the checks.
	 */
	const INTERVAL = 3600;

	/**
	 * Checks in a row in which bunq could not be reached before the merchant is warned: bunq itself or the network
	 * can be down for a moment, and that fixes itself.
	 */
	const UNREACHABLE_THRESHOLD = 3;

	/**
	 * Status: bunq accepted the connection.
	 */
	const OK = 'ok';

	/**
	 * Status: bunq refused the connection (revoked access, another IP address, a closed bank account), so the
	 * merchant has to act.
	 */
	const BROKEN = 'broken';

	/**
	 * Status: bunq could not be reached or had an error of its own.
	 */
	const UNREACHABLE = 'unreachable';

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Hands out the gateway WooCommerce registered.
	 */
	public function __construct( private readonly Plugin $plugin ) {}

	/**
	 * Hook into WordPress, WooCommerce and Action Scheduler.
	 */
	public function register_hooks() {
		add_action( self::CHECK_ACTION, array( $this, 'check' ) );
		add_action( 'admin_init', array( $this, 'schedule' ) );
		add_action( 'admin_notices', array( $this, 'show_notice' ) );
		add_action( 'admin_post_' . self::CHECK_NOW_ACTION, array( $this, 'handle_check_now' ) );
		add_action( 'woocommerce_system_status_report', array( $this, 'show_system_status' ) );
	}

	/**
	 * Queue the recurring check, unless it is queued already (admin_init). Action Scheduler ships with WooCommerce.
	 */
	public function schedule() {
		if ( wp_doing_ajax() || ! function_exists( 'as_next_scheduled_action' ) || ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		if ( false !== as_next_scheduled_action( self::CHECK_ACTION, array(), PaymentMonitor::ACTION_GROUP ) ) {
			return;
		}

		as_schedule_recurring_action( time() + self::INTERVAL, self::INTERVAL, self::CHECK_ACTION, array(), PaymentMonitor::ACTION_GROUP );
	}

	/**
	 * Remove the recurring check (plugin deactivation and uninstall).
	 */
	public static function unschedule(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::CHECK_ACTION );
		}
	}

	/**
	 * Check the connection of the active mode with bunq and store the outcome. Nothing is checked while the gateway is
	 * disabled or not connected.
	 *
	 * @return array|null The outcome (see the class description), or null when nothing was checked.
	 */
	public function check() {
		$gateway = $this->plugin->gateway();

		if ( ! $gateway || 'yes' !== $gateway->enabled || ! $gateway->get_setting( 'api_context' ) ) {
			return null;
		}

		$mode = Gateway::mode( $gateway->testmode );

		// The check is where a remembered failure is tried again; payments wait for it to expire.
		delete_transient( Gateway::CONTEXT_FAILED_TRANSIENT );

		$before = self::status( $mode );

		try {
			$gateway->check_connection();
			self::record( $mode );
		} catch ( Throwable $exception ) {
			Logger::log( 'The bunq connection check failed: ' . Logger::format_error( $exception ) );

			// Unless the gateway recorded the failure already, when recreating the API context failed as well.
			if ( self::status( $mode ) === $before ) {
				self::record( $mode, $exception );
			}
		}

		return self::status( $mode );
	}

	/**
	 * Run the check for the "Check again" link and send the merchant back where they came from (admin-post). Ends
	 * the request.
	 */
	public function handle_check_now() {
		wp_safe_redirect( $this->check_now() );
		exit;
	}

	/**
	 * Run the check for the "Check again" link and remember its outcome for the notice on the next page.
	 *
	 * @return string Where to send the merchant.
	 */
	public function check_now() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'pay-with-bunq-for-woocommerce' ), 403 );
		}

		check_admin_referer( self::CHECK_NOW_ACTION );

		$status = $this->check();
		set_transient( self::CHECKED_TRANSIENT . get_current_user_id(), $status ? $status['status'] : 'skipped', MINUTE_IN_SECONDS );

		$referer = wp_get_referer();

		return $referer ? $referer : Gateway::settings_url();
	}

	/**
	 * Store the outcome of a check, or of a failed attempt to recreate the API context.
	 *
	 * @param string         $mode      "live" or "sandbox".
	 * @param Throwable|null $exception Why the connection failed; null when it works.
	 */
	public static function record( string $mode, ?Throwable $exception = null ): void {
		$statuses = self::statuses();
		$previous = $statuses[ $mode ] ?? null;
		$failing  = $previous && self::OK !== $previous['status'];
		$now      = time();

		if ( null === $exception ) {
			if ( $failing ) {
				Logger::log( 'The connection with bunq works again.', 'info' );
			}

			$statuses[ $mode ] = array(
				'status'        => self::OK,
				'checked_at'    => $now,
				'error'         => '',
				'failures'      => 0,
				'failing_since' => 0,
			);
		} else {
			$statuses[ $mode ] = array(
				'status'        => self::classify( $exception ),
				'checked_at'    => $now,
				'error'         => substr( Logger::format_error( $exception ), 0, 500 ),
				'failures'      => ( $failing ? (int) $previous['failures'] : 0 ) + 1,
				'failing_since' => $failing ? (int) $previous['failing_since'] : $now,
			);
		}

		update_option( self::OPTION, $statuses, true );
	}

	/**
	 * Record that the connection works, when a failure is recorded for the mode: a payment or refund just went
	 * through. Writes nothing otherwise, so a checkout does not cost an option update.
	 *
	 * @param string $mode "live" or "sandbox".
	 */
	public static function record_success( string $mode ): void {
		$status = self::status( $mode );

		if ( $status && self::OK !== $status['status'] ) {
			self::record( $mode );
		}
	}

	/**
	 * Forget the outcome for a mode whose connection was removed.
	 *
	 * @param string $mode "live" or "sandbox".
	 */
	public static function forget( string $mode ): void {
		$statuses = self::statuses();

		if ( isset( $statuses[ $mode ] ) ) {
			unset( $statuses[ $mode ] );
			update_option( self::OPTION, $statuses, true );
		}
	}

	/**
	 * The outcome of the last check of a mode.
	 *
	 * @param string $mode "live" or "sandbox".
	 * @return array|null Null when the mode was not checked yet.
	 */
	public static function status( string $mode ): ?array {
		$statuses = self::statuses();

		return $statuses[ $mode ] ?? null;
	}

	/**
	 * The failure of the active mode's connection that the merchant should hear about: bunq refused the connection,
	 * or could not be reached in several checks in a row.
	 *
	 * @param Gateway $gateway The bunq gateway.
	 * @return array|null The outcome of the last check (see the class description), or null when there is none.
	 */
	public static function problem( Gateway $gateway ): ?array {
		if ( 'yes' !== $gateway->enabled || ! $gateway->get_setting( 'api_context' ) ) {
			return null;
		}

		$status = self::status( Gateway::mode( $gateway->testmode ) );

		if ( ! $status || self::OK === $status['status'] ) {
			return null;
		}

		if ( self::UNREACHABLE === $status['status'] && $status['failures'] < self::UNREACHABLE_THRESHOLD ) {
			return null;
		}

		return $status;
	}

	/**
	 * Warn the merchant on every admin page while the connection is broken, and show the outcome of "Check again"
	 * (admin_notices). The bunq settings page shows the connection state itself.
	 */
	public function show_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$checked_transient = self::CHECKED_TRANSIENT . get_current_user_id();
		$checked           = get_transient( $checked_transient );

		if ( $checked ) {
			delete_transient( $checked_transient );
		}

		if ( self::OK === $checked ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'The connection with bunq works.', 'pay-with-bunq-for-woocommerce' ) . '</p></div>';
		}

		// Most admin pages: nothing failed, so the gateways need not be loaded.
		$failing = false;
		foreach ( self::statuses() as $status ) {
			$failing = $failing || self::OK !== $status['status'];
		}

		if ( ! $failing || self::is_settings_page() ) {
			return;
		}

		$gateway = $this->plugin->gateway();
		$problem = $gateway ? self::problem( $gateway ) : null;

		if ( $problem ) {
			echo self::notice_html( $problem, $gateway->testmode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while drawn.
		}
	}

	/**
	 * The notice about a broken connection.
	 *
	 * @param array $problem  See problem().
	 * @param bool  $testmode Whether the sandbox is used.
	 * @return string
	 */
	public static function notice_html( array $problem, bool $testmode ): string {
		if ( self::UNREACHABLE === $problem['status'] ) {
			$message = sprintf(
				$testmode
					/* translators: %s: how long, for example "3 hours" */
					? __( 'The bunq sandbox could not be reached for %s. Payments with bunq fail while it cannot be reached.', 'pay-with-bunq-for-woocommerce' )
					/* translators: %s: how long, for example "3 hours" */
					: __( 'bunq could not be reached for %s. Payments with bunq fail while bunq cannot be reached.', 'pay-with-bunq-for-woocommerce' ),
				human_time_diff( $problem['failing_since'] )
			);
		} else {
			$message = $testmode
				? __( 'The connection with the bunq sandbox stopped working, so payments with bunq fail. Connect again on the bunq settings page. This happens when the access was removed in the bunq app or the shop now connects from another IP address.', 'pay-with-bunq-for-woocommerce' )
				: __( 'The connection with bunq stopped working, so payments with bunq fail. Connect again on the bunq settings page. This happens when the access was removed in the bunq app or the shop now connects from another IP address.', 'pay-with-bunq-for-woocommerce' );
		}

		$check_url = wp_nonce_url( add_query_arg( 'action', self::CHECK_NOW_ACTION, admin_url( 'admin-post.php' ) ), self::CHECK_NOW_ACTION );

		ob_start();
		?>
		<div class="notice notice-error bunq-connection-notice">
			<p><strong><?php esc_html_e( 'Pay with bunq for WooCommerce', 'pay-with-bunq-for-woocommerce' ); ?>:</strong> <?php echo esc_html( $message ); ?></p>
			<?php if ( '' !== $problem['error'] ) : ?>
				<p>
					<?php
					/* translators: %s: error message from bunq */
					printf( esc_html__( 'bunq\'s answer: %s', 'pay-with-bunq-for-woocommerce' ), '<code>' . esc_html( $problem['error'] ) . '</code>' );
					?>
				</p>
			<?php endif; ?>
			<p>
				<a href="<?php echo esc_url( Gateway::settings_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Go to the bunq settings', 'pay-with-bunq-for-woocommerce' ); ?></a>
				<a href="<?php echo esc_url( $check_url ); ?>" class="button"><?php esc_html_e( 'Check again', 'pay-with-bunq-for-woocommerce' ); ?></a>
			</p>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * The bunq section of WooCommerce > Status, also in the system report merchants copy for support
	 * (woocommerce_system_status_report).
	 */
	public function show_system_status() {
		$gateway = $this->plugin->gateway();

		echo self::system_status_html( $gateway, self::next_check() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while drawn.
	}

	/**
	 * The bunq section of WooCommerce > Status.
	 *
	 * @param Gateway|null $gateway    The bunq gateway.
	 * @param int|null     $next_check When the next check runs.
	 * @return string
	 */
	public static function system_status_html( ?Gateway $gateway, ?int $next_check ): string {
		$rows = array(
			array( __( 'Version', 'pay-with-bunq-for-woocommerce' ), 'Version', esc_html( Plugin::VERSION ) ),
		);

		if ( $gateway ) {
			$mode       = Gateway::mode( $gateway->testmode );
			$connection = $gateway->get_connection( $mode );
			$status     = $connection['connected'] ? self::status( $mode ) : null;

			$rows[] = array( __( 'Enabled', 'pay-with-bunq-for-woocommerce' ), 'Enabled', 'yes' === $gateway->enabled ? self::mark( true, __( 'Yes', 'pay-with-bunq-for-woocommerce' ) ) : esc_html__( 'No', 'pay-with-bunq-for-woocommerce' ) );
			$rows[] = array( __( 'Mode', 'pay-with-bunq-for-woocommerce' ), 'Mode', $gateway->testmode ? esc_html__( 'Sandbox', 'pay-with-bunq-for-woocommerce' ) : esc_html__( 'Live', 'pay-with-bunq-for-woocommerce' ) );

			if ( ! $connection['connected'] ) {
				$connected = self::mark( false, __( 'Not connected', 'pay-with-bunq-for-woocommerce' ) );
			} elseif ( $connection['account_name'] ) {
				/* translators: %s: name of the bunq user that granted access */
				$connected = self::mark( true, sprintf( __( 'Connected as %s', 'pay-with-bunq-for-woocommerce' ), $connection['account_name'] ) );
			} else {
				$connected = self::mark( true, __( 'Connected', 'pay-with-bunq-for-woocommerce' ) );
			}
			$rows[] = array( __( 'Connection', 'pay-with-bunq-for-woocommerce' ), 'Connection', $connected );

			if ( $connection['connected'] ) {
				if ( ! $status ) {
					$check = esc_html__( 'Not checked yet', 'pay-with-bunq-for-woocommerce' );
				} elseif ( self::OK === $status['status'] ) {
					$check = self::mark( true, __( 'Works', 'pay-with-bunq-for-woocommerce' ) );
				} else {
					$label = self::BROKEN === $status['status'] ? __( 'Broken', 'pay-with-bunq-for-woocommerce' ) : __( 'bunq could not be reached', 'pay-with-bunq-for-woocommerce' );
					$check = self::mark( false, $label . ( '' !== $status['error'] ? ': ' . $status['error'] : '' ) );
				}

				$check_url = wp_nonce_url( add_query_arg( 'action', self::CHECK_NOW_ACTION, admin_url( 'admin-post.php' ) ), self::CHECK_NOW_ACTION );
				$check    .= ' <a href="' . esc_url( $check_url ) . '">' . esc_html__( 'Check again', 'pay-with-bunq-for-woocommerce' ) . '</a>';

				$rows[] = array( __( 'Connection check', 'pay-with-bunq-for-woocommerce' ), 'Connection check', $check );

				if ( $status && self::OK !== $status['status'] ) {
					$rows[] = array( __( 'Failing since', 'pay-with-bunq-for-woocommerce' ), 'Failing since', esc_html( self::format_time( $status['failing_since'] ) ) );
				}

				$rows[] = array( __( 'Last check', 'pay-with-bunq-for-woocommerce' ), 'Last check', $status ? esc_html( self::format_time( $status['checked_at'] ) ) : '&ndash;' );
				$rows[] = array( __( 'Next check', 'pay-with-bunq-for-woocommerce' ), 'Next check', $next_check ? esc_html( self::format_time( $next_check ) ) : esc_html__( 'Not scheduled', 'pay-with-bunq-for-woocommerce' ) );
			}

			$rows[] = array( __( 'Callback URL', 'pay-with-bunq-for-woocommerce' ), 'Callback URL', '<code>' . esc_html( CallbackHandler::url() ) . '</code>' );
		}

		ob_start();
		?>
		<table class="wc_status_table widefat" cellspacing="0" id="status-bunq">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Pay with bunq for WooCommerce"><h2><?php esc_html_e( 'Pay with bunq for WooCommerce', 'pay-with-bunq-for-woocommerce' ); ?></h2></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td data-export-label="<?php echo esc_attr( $row[1] ); ?>"><?php echo esc_html( $row[0] ); ?>:</td>
						<td class="help">&nbsp;</td>
						<td><?php echo $row[2]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}

	/**
	 * When the next check runs.
	 *
	 * @return int|null Null when none is queued.
	 */
	private static function next_check(): ?int {
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return null;
		}

		$next = as_next_scheduled_action( self::CHECK_ACTION, array(), PaymentMonitor::ACTION_GROUP );

		// True while it runs.
		return is_int( $next ) ? $next : null;
	}

	/**
	 * Whether the failure is bunq refusing the connection, or bunq not answering.
	 *
	 * @param Throwable $exception Why the connection failed.
	 * @return string self::BROKEN or self::UNREACHABLE.
	 */
	private static function classify( Throwable $exception ): string {
		// No answer at all: a timeout, DNS or TLS failure.
		if ( $exception instanceof GuzzleException ) {
			return self::UNREACHABLE;
		}

		// An error of bunq itself, or too many requests.
		if ( $exception instanceof ApiException && ! $exception->is_context_rejected() && ( $exception->getCode() >= 500 || 429 === $exception->getCode() ) ) {
			return self::UNREACHABLE;
		}

		return self::BROKEN;
	}

	/**
	 * The stored outcomes by mode.
	 *
	 * @return array[]
	 */
	private static function statuses(): array {
		$statuses = get_option( self::OPTION, array() );

		return is_array( $statuses ) ? array_filter( $statuses, 'is_array' ) : array();
	}

	/**
	 * Whether this is the bunq settings page, which shows the connection state itself.
	 *
	 * @return bool
	 */
	private static function is_settings_page(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Only decides where a notice is shown.
		return isset( $_GET['page'], $_GET['section'] ) && 'wc-settings' === $_GET['page'] && Gateway::ID === $_GET['section'];
		// phpcs:enable
	}

	/**
	 * A value with the check mark or warning sign of WooCommerce > Status.
	 *
	 * @param bool   $ok   Whether it is good news.
	 * @param string $text The value.
	 * @return string
	 */
	private static function mark( bool $ok, string $text ): string {
		return $ok
			? '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . esc_html( $text ) . '</mark>'
			: '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html( $text ) . '</mark>';
	}

	/**
	 * A time for the system report, which merchants paste into support requests: absolute and in UTC.
	 *
	 * @param int $timestamp Unix time.
	 * @return string
	 */
	private static function format_time( int $timestamp ): string {
		return gmdate( 'Y-m-d H:i', (int) $timestamp ) . ' UTC';
	}
}
