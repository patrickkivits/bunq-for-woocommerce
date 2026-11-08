<?php
/**
 * The payment methods of bunq.me.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Gateway;

use PatrickKivits\BunqForWooCommerce\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * The payment methods bunq.me offers, with the amounts bunq accepts for each.
 */
final class PaymentMethods {

	/**
	 * The methods that can be offered as a payment option of their own (MethodGateway), in checkout order.
	 */
	const SEPARATE = array( 'ideal', 'card', 'bancontact' );

	/**
	 * Where the logos are, relative to the plugin directory.
	 */
	const ICON_PATH = 'assets/images/';

	/**
	 * Every payment method. "id" is what the setting and the checkout form store (keep stable); "bunqme" is the
	 * value bunq.me expects in its paymentMethod query parameter to open that method directly; "icon" is the logo
	 * in assets/images (null for none).
	 *
	 * @return array[] Keys: id, bunqme, description, icon, min, max (null for no maximum).
	 */
	public static function all(): array {
		return array(
			array(
				'id'          => 'card',
				'bunqme'      => 'CARD',
				'description' => __( 'Credit or Debit Card', 'bunq-for-woocommerce' ),
				'icon'        => 'card.svg',
				'min'         => 1,
				'max'         => 500,
			),
			array(
				'id'          => 'ideal',
				'bunqme'      => 'IDEAL',
				'description' => __( 'iDEAL | Wero', 'bunq-for-woocommerce' ),
				'icon'        => 'ideal-wero.svg',
				'min'         => 0.01,
				'max'         => null,
			),
			array(
				'id'          => 'bancontact',
				'bunqme'      => 'BANCONTACT',
				'description' => __( 'Bancontact', 'bunq-for-woocommerce' ),
				'icon'        => 'bancontact.svg',
				'min'         => 5,
				'max'         => 10000,
			),
			array(
				'id'          => 'bunq-transfer',
				'bunqme'      => 'BUNQ_TRANSFER',
				'description' => __( 'From a bunq account', 'bunq-for-woocommerce' ),
				'icon'        => null,
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
	public static function allowed( $enabled_ids, ?float $total = null ): array {
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
			fn( $method ) => $method['min'] <= $total && ( null === $method['max'] || $method['max'] >= $total )
		);
	}

	/**
	 * The method bunq.me should open directly for a method id.
	 *
	 * @param string $id Method id.
	 * @return string|null bunq.me's name for it, null for an unknown method.
	 */
	public static function bunqme_name( string $id ): ?string {
		$names = array_column( self::all(), 'bunqme', 'id' );

		return $names[ $id ] ?? null;
	}

	/**
	 * Whether a method id is known.
	 *
	 * @param string $id Method id.
	 * @return bool
	 */
	public static function exists( $id ): bool {
		return in_array( $id, array_column( self::all(), 'id' ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Kept loose as before.
	}

	/**
	 * Method descriptions by id, for a select.
	 *
	 * @param array[] $methods Methods as returned by all() or allowed().
	 * @return string[]
	 */
	public static function options( array $methods ): array {
		return array_column( $methods, 'description', 'id' );
	}

	/**
	 * A payment method by id.
	 *
	 * @param string $id Method id.
	 * @return array|null See all(); null for an unknown method.
	 */
	public static function get( string $id ): ?array {
		foreach ( self::all() as $method ) {
			if ( $method['id'] === $id ) {
				return $method;
			}
		}

		return null;
	}

	/**
	 * A readable name for the type of a payment bunq received ("type" of a bunq Payment), such as "IDEAL" or
	 * "BUNQ". bunq names card payments after the card scheme, so those are matched loosely.
	 *
	 * @param string $type The payment type as bunq reports it.
	 * @return string The name, or the type itself for a type the plugin does not know; empty when there is none.
	 */
	public static function payment_type_label( string $type ): string {
		$type = strtoupper( trim( (string) $type ) );

		if ( '' === $type || 'UNKNOWN' === $type ) {
			return '';
		}

		$labels = array(
			'BUNQ'       => 'bunq-transfer',
			'IDEAL'      => 'ideal',
			'WERO'       => 'ideal',
			'BANCONTACT' => 'bancontact',
			'MASTERCARD' => 'card',
			'MAESTRO'    => 'card',
			'VISA'       => 'card',
			'AMEX'       => 'card',
			'CARD'       => 'card',
		);

		foreach ( $labels as $needle => $method_id ) {
			if ( str_contains( $type, $needle ) ) {
				$method = self::get( $method_id );

				return $method['description'];
			}
		}

		if ( in_array( $type, array( 'EBA_SCT', 'SWIFT' ), true ) ) {
			return __( 'Bank transfer', 'bunq-for-woocommerce' );
		}

		return $type;
	}

	/**
	 * The logos of payment methods, for the checkout. Methods without a logo are left out.
	 *
	 * @param array[] $methods Methods as returned by all() or allowed().
	 * @return array[] Keys: id, src (URL), alt.
	 */
	public static function icons( array $methods ): array {
		$icons = array();
		$base  = plugin_dir_url( Plugin::instance()->file() ) . self::ICON_PATH;

		foreach ( $methods as $method ) {
			if ( empty( $method['icon'] ) ) {
				continue;
			}

			$icons[] = array(
				'id'  => $method['id'],
				'src' => $base . $method['icon'],
				'alt' => $method['description'],
			);
		}

		return $icons;
	}

	/**
	 * Sort methods in checkout order: those of SEPARATE first, in its order.
	 *
	 * @param array[] $methods Methods as returned by all() or allowed().
	 * @return array[]
	 */
	public static function in_checkout_order( array $methods ): array {
		$position = array_flip( self::SEPARATE );

		usort(
			$methods,
			function ( $a, $b ) use ( $position ) {
				$a = $position[ $a['id'] ] ?? PHP_INT_MAX;
				$b = $position[ $b['id'] ] ?? PHP_INT_MAX;

				return $a === $b ? 0 : ( $a < $b ? -1 : 1 );
			}
		);

		return $methods;
	}
}
