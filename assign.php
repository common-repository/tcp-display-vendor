<?php
namespace TheCartPress;

defined('ABSPATH') or exit;

/**
 * - assign vendor ID to product in
 *   - product edit page, using meta box
 *   - product list quick edit & bulk edit form
 * - display vendor name in product list
 */
class TCP_display_vendor_assign {

	public function __construct() {
		add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
		add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
		add_action('woocommerce_process_product_meta', [$this, 'woocommerce_process_product_meta']);
		add_action('add_inline_data', [$this, 'add_inline_data'], 10, 2);
		add_action('woocommerce_product_quick_edit_end', [$this, 'product_quick_edit_end']);
		add_action('woocommerce_product_quick_edit_save', [$this, 'product_quick_edit_save']);
		add_action('woocommerce_product_bulk_edit_end', [$this, 'product_bulk_edit_end']);
		add_action('woocommerce_product_bulk_edit_save', [$this, 'product_bulk_edit_save']);
		add_filter('manage_edit-product_columns', [$this, 'add_vendor_column']);
		add_action('manage_posts_custom_column', [$this, 'get_vendor_column'], 10, 2);
	}

	function admin_enqueue_scripts() {
		global $pagenow, $typenow, $tcp_display_vendor;
		if (in_array($pagenow, ['edit.php', 'post.php']) && $typenow == 'product') {
			wp_enqueue_style('tcpdv_select2', $tcp_display_vendor->plugin_url . '/select2/css/select2.min.css', [], TCP_display_vendor::SELECT2_VERSION);
			wp_enqueue_script('tcpdv_select2', $tcp_display_vendor->plugin_url . '/select2/js/select2.full.min.js', ['jquery'],  TCP_display_vendor::SELECT2_VERSION, true);
			wp_enqueue_style('tcpdv_edit', $tcp_display_vendor->plugin_url . '/css/edit.css', [], $tcp_display_vendor->asset_version);
			wp_enqueue_script('tcpdv_edit', $tcp_display_vendor->plugin_url . '/js/edit.js', ['jquery'], $tcp_display_vendor->asset_version, true);
		}
	}

	// add meta box in edit product page
	function add_meta_boxes() {
		global $tcp_display_vendor;
		$vendors = $tcp_display_vendor->get_vendors();
		if (!empty($vendors)) {
			add_meta_box(
				'tcpdv-assign',
				__('Product vendor'),
				[$this, 'meta_box_assign_vendor'],
				'product',
				'side'
			);
		}
	}

	// add meta box content in edit product page
	function meta_box_assign_vendor($post) {
		global $tcp_display_vendor;
		$vid = (int) get_post_meta($post->ID, TCP_display_vendor::VENDOR_ID_META, true);
		$vendors = $tcp_display_vendor->get_vendors();
		?>
		<select name="tcpdv_vid">
			<option value="">-</option>
			<?php foreach ($vendors as $v) { ?>
				<option value="<?php echo esc_attr($v['id']); ?>" <?php selected($vid, $v['id']); ?>><?php echo esc_html($v['name']); ?></option>
			<?php } ?>
		</select>
		<?php
	}

	// save meta box changes
	function woocommerce_process_product_meta($post_id) {
		global $tcp_display_vendor;
		$vendors = $tcp_display_vendor->get_vendors();
		$vid = isset($_POST['tcpdv_vid']) ? (int) $_POST['tcpdv_vid'] : 0;
		if (!$vid || !isset($vendors[$vid])) {
			$vid = 0;
		}
		update_post_meta($post_id, TCP_display_vendor::VENDOR_ID_META, $vid);
	}

	// add data for quick edit
	function add_inline_data($post, $post_type_object) {
		if ($post_type_object->name == 'product') {
			$vid = (int) get_post_meta($post->ID, TCP_display_vendor::VENDOR_ID_META, true);
			if ($vid) {
				echo '<div class="product_vendor">' . esc_html($vid ?: '') . '</div>';
			}
		}
	}

	// add field for quick edit
	function product_quick_edit_end() {
		global $tcp_display_vendor;
		$vendors = $tcp_display_vendor->get_vendors();
		if (!empty($vendors)) { ?>
			<br class="clear">
			<label class="alignleft">
				<span class="title"><?php _e('Vendor'); ?></span>
				<span class="input-text-wrap">
					<select name="tcpdv_vid">
						<option value="">-</option>
						<?php foreach ($vendors as $v) { ?>
							<option value="<?php echo esc_attr($v['id']); ?>"><?php echo esc_html($v['name']); ?></option>
						<?php } ?>
					</select>
				</span>
			</label><?php
		}
	}

	// save quick edit changes
	function product_quick_edit_save($product) {
		global $tcp_display_vendor;
		$vid = isset($_POST['tcpdv_vid']) ? (int) $_POST['tcpdv_vid'] : 0;
		$vendors = $tcp_display_vendor->get_vendors();
		if (!$vid || !isset($vendors[$vid])) {
			$vid = 0;
		}
		update_post_meta($product->get_id(), TCP_display_vendor::VENDOR_ID_META, $vid);
	}

	// add field for bulk edit
	function product_bulk_edit_end() {
		global $tcp_display_vendor;
		$vendors = $tcp_display_vendor->get_vendors();
		if (!empty($vendors)) { ?>
			<label>
				<span class="title"><?php _e('Product vendor'); ?></span>
				<span class="input-text-wrap">
					<select name="tcpdv_vid">
						<option value=""><?php _e('— No change —', 'woocommerce'); ?></option>
						<?php foreach ($vendors as $v) { ?>
							<option value="<?php echo esc_attr($v['id']); ?>"><?php echo esc_html($v['name']); ?></option>
						<?php } ?>
					</select>
				</span>
			</label>
		<?php }
	}

	// save bulk edit changes
	function product_bulk_edit_save($product) {
		global $tcp_display_vendor;
		$vid = isset($_REQUEST['tcpdv_vid']) ? (int) $_REQUEST['tcpdv_vid'] : 0;
		if (!$vid) {
			return;
		}
		$vendors = $tcp_display_vendor->get_vendors();
		if (isset($vendors[$vid])) {
			update_post_meta($product->get_id(), TCP_display_vendor::VENDOR_ID_META, $vid);
		}
	}

	function add_vendor_column($cols) {
		$tmp = [];
		foreach ($cols as $k => $v) {
			$tmp[$k] = $v;
			if ($k == 'date') {
				$tmp['vendor'] = __('Vendor'); // add after 'Date' column
			}
		}
		return $tmp;
	}

	function get_vendor_column($col) {
		global $post, $tcp_display_vendor;
		if ($col == 'vendor') {
			$vid = (int) get_post_meta($post->ID, TCP_display_vendor::VENDOR_ID_META, true);
			$vendors = $tcp_display_vendor->get_vendors();
			if ($vid && isset($vendors[$vid])) {
				echo esc_html($vendors[$vid]['name']);
			} else {
				echo '&mdash;';
			}
		}
	}

}
new TCP_display_vendor_assign();