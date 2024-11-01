<?php
namespace TheCartPress;
/**
 * Plugin Name: TCP Display Vendor
 * Plugin URI:
 * Description: Display vendor name at WooCommerce product page, cart, checkout and order page
 * Version: 1.2.0
 * Stable tag: 1.2.0
 * Requires PHP: 5.6
 * Requires at least: 5.5
 * Tested up to: 6.0
 * Author: TCP Team
 * Author URI: https://www.thecartpress.com
 * WC tested up to: 6.3.1
 */
defined('ABSPATH') or exit;

class TCP_display_vendor {

	const ASSET_VERSION = 37;
	const SELECT2_VERSION = '4.0.13';
	const VENDORS_OPT = 'tcpdv_vendors';
	const VENDOR_ID_META = 'tcpdv_vid';

	static $instance;

	public function __construct() {
		$tcp_f = __DIR__ . '/tcp.php';
		if (file_exists($tcp_f)) {
			require_once $tcp_f;
		}
		tcp_init_plugin($this, __FILE__);
		tcp_register_updater($this->plugin_id, 'https://app.thecartpress.com/api/?op=check_update&view=json&pid=' . $this->plugin_id);
		if (!tcp_is_plugin_available('woocommerce', 'WooCommerce', 'woocommerce/woocommerce.php', $this->plugin_name)) {
			return;
		}
		require_once __DIR__ . '/admin.php';
		require_once __DIR__ . '/assign.php';
		require_once __DIR__ . '/display.php';
	}

	function get_vendors() {
		static $vendors = null;
		if (is_null($vendors)) {
			$vendors = get_option(self::VENDORS_OPT);
			if (!is_array($vendors)) {
				$vendors = [];
			}
			$tmp = [];
			foreach ($vendors as $v) {
				$tmp[$v['id']] = $v;
			}
			$vendors = $tmp;
		}
		return $vendors;
	}

}
new TCP_display_vendor();