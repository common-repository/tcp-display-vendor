<?php
namespace TheCartPress;

defined('ABSPATH') or exit;

/**
 * display vendor name in
 * - product page
 * - cart page
 * - checkout page (incl. pay for order page)
 * - order details page
 */
class TCP_display_vendor_display {

	public function __construct() {
		add_action('wp_enqueue_scripts', [$this, 'wp_enqueue_scripts']);
		add_action('woocommerce_single_product_summary', [$this, 'single_product_summary'], 6);
		add_action('woocommerce_after_cart_item_name', [$this, 'after_cart_item_name']);
		add_filter('woocommerce_cart_item_name', [$this, 'cart_item_name'], 10, 2);
		add_filter('woocommerce_order_item_name', [$this, 'order_item_name'], 10, 2);
	}

	function wp_enqueue_scripts() {
		global $tcp_display_vendor;
		wp_enqueue_style('tcpdv_display', $tcp_display_vendor->plugin_url . '/css/display.css', [], $tcp_display_vendor->asset_version);
	}

	// product page
	// see hook priority levels - woocommerce/templates/content-single-product.php
	function single_product_summary() {
		global $product, $tcp_display_vendor;
		$vid = (int) get_post_meta($product->get_id(), TCP_display_vendor::VENDOR_ID_META, true);
		$vendors = $tcp_display_vendor->get_vendors();
		if ($vid && isset($vendors[$vid])) { ?>
			<p class="product_vendor"><?php printf('by %s', esc_html($vendors[$vid]['name'])); ?></p><?php
		}
	}

	// cart page
	function after_cart_item_name($cart_item) {
		global $tcp_display_vendor;
		$vid = (int) get_post_meta($cart_item['product_id'], TCP_display_vendor::VENDOR_ID_META, true);
		$vendors = $tcp_display_vendor->get_vendors();
		if ($vid && isset($vendors[$vid])) { ?>
			<p class="product_vendor"><?php printf('Sold by %s', esc_html($vendors[$vid]['name'])); ?></p><?php
		}
	}

	// checkout page
	function cart_item_name($item_name, $cart_item) {
		if (is_checkout() || is_checkout_pay_page()) {
			ob_start();
			$this->after_cart_item_name($cart_item);
			$item_name .= ob_get_contents();
			ob_end_clean();
		}
		return $item_name;
	}

	// view order page
	function order_item_name($item_name, $item) {
		$product = $item->get_product();
		$product_id = $product->get_id();
		if ($product->is_type('variation')) {
			$product_id = $product->get_parent_id();
		}
		if ($product) {
			ob_start();
			$this->after_cart_item_name([
				'product_id' => $product_id,
			]);
			$item_name .= ob_get_contents();
			ob_end_clean();
		}
		return $item_name;
	}

}
new TCP_display_vendor_display();