<?php
/**
 * Namespaced WooCommerce classes the plugin integrates with: feature compatibility declarations and the block
 * checkout payment method registry.
 */

namespace Automattic\WooCommerce\Utilities {

    class FeaturesUtil
    {
        /** @var array[] Every declare_compatibility() call as array(feature, plugin file, compatible). */
        public static $declared = array();

        public static function declare_compatibility($feature_id, $plugin_file, $positive_compatibility = true)
        {
            self::$declared[] = array($feature_id, $plugin_file, $positive_compatibility);

            return true;
        }
    }
}

namespace Automattic\WooCommerce\Blocks\Payments\Integrations {

    abstract class AbstractPaymentMethodType
    {
        protected $name = '';
        protected $settings = array();

        public function get_name() { return $this->name; }

        abstract public function initialize();

        abstract public function is_active();

        public function get_payment_method_script_handles() { return array(); }

        public function get_payment_method_data() { return array(); }
    }
}

namespace Automattic\WooCommerce\Blocks\Payments {

    use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

    class PaymentMethodRegistry
    {
        /** @var AbstractPaymentMethodType[] keyed by payment method name */
        public $registered = array();

        public function register(AbstractPaymentMethodType $payment_method_type)
        {
            $this->registered[$payment_method_type->get_name()] = $payment_method_type;

            return true;
        }
    }
}
