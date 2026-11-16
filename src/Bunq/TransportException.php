<?php
/**
 * No answer from bunq.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Bunq;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * No answer from bunq: a timeout, a DNS or TLS failure, or a server key that does not match the pinned one.
 */
class TransportException extends RuntimeException {}
