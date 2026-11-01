<?php
/**
 * The payment methods of bunq.me.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * The payment methods bunq.me offers, with the amounts bunq accepts for each.
 */
final class PaymentMethods {

	/**
	 * Every payment method. "id" is what the setting and the checkout form store (keep stable); "bunqme" is the
	 * value bunq.me expects in its paymentMethod query parameter to open that method directly.
	 *
	 * @return array[] Keys: id, bunqme, description, min, max (null for no maximum).
	 */
	public static function all() {
		return array(
			array(
				'id'          => 'card',
				'bunqme'      => 'CARD',
				'description' => __( 'Credit or Debit Card', 'bunq-for-woocommerce' ),
				'min'         => 1,
				'max'         => 500,
			),
			array(
				'id'          => 'ideal',
				'bunqme'      => 'IDEAL',
				'description' => __( 'iDEAL | Wero', 'bunq-for-woocommerce' ),
				'min'         => 0.01,
				'max'         => null,
			),
			array(
				'id'          => 'bancontact',
				'bunqme'      => 'BANCONTACT',
				'description' => __( 'Bancontact', 'bunq-for-woocommerce' ),
				'min'         => 5,
				'max'         => 10000,
			),
			array(
				'id'          => 'bunq-transfer',
				'bunqme'      => 'BUNQ_TRANSFER',
				'description' => __( 'From a bunq account', 'bunq-for-woocommerce' ),
				'min'         => 0.01,
				'max'         => null,
			),
		);
	}

	/**
	 * The payment methods the customer may pick: the enabled ones whose bunq limits fit the total.
	 *
	 * @param mixed      $enabled_ids The enabled method ids; anything but a non-empty array enables every method.
	 * @param float|null $total       Cart or order total; null allows every enabled method.
	 * @return array[] As all(), with their original keys.
	 */
	public static function allowed( $enabled_ids, $total = null ) {
		$methods = self::all();

		if ( is_array( $enabled_ids ) && ! empty( $enabled_ids ) ) {
			$methods = array_filter(
				$methods,
				function ( $method ) use ( $enabled_ids ) {
					return in_array( $method['id'], $enabled_ids ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Saved settings are strings.
				}
			);
		}

		if ( null === $total ) {
			return $methods;
		}

		return array_filter(
			$methods,
			function ( $method ) use ( $total ) {
				return $method['min'] <= $total && ( null === $method['max'] || $method['max'] >= $total );
			}
		);
	}

	/**
	 * The method bunq.me should open directly for a method id.
	 *
	 * @param string $id Method id.
	 * @return string|null bunq.me's name for it, null for an unknown method.
	 */
	public static function bunqme_name( $id ) {
		$names = array_column( self::all(), 'bunqme', 'id' );

		return isset( $names[ $id ] ) ? $names[ $id ] : null;
	}

	/**
	 * Whether a method id is known.
	 *
	 * @param string $id Method id.
	 * @return bool
	 */
	public static function exists( $id ) {
		return in_array( $id, array_column( self::all(), 'id' ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Kept loose as before.
	}

	/**
	 * Method descriptions by id, for a select.
	 *
	 * @param array[] $methods Methods as returned by all() or allowed().
	 * @return string[]
	 */
	public static function options( array $methods ) {
		return array_column( $methods, 'description', 'id' );
	}
}
