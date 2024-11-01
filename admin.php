<?php
namespace TheCartPress;

defined('ABSPATH') or exit;

class TCP_display_vendor_admin {

	public function __construct() {
		$tcp_display_vendor = TCP_display_vendor::$instance;
		tcp_add_menu(
			$tcp_display_vendor->plugin_id,
			__('TCP Display Vendor'), // $page_title
			__('Display Vendor'), // $menu_title
			'tcpdv_admin', // $menu_slug
			[$this, 'create_admin_page'] // $function
		);
		add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'], 11);
		add_action('admin_post_tcpdv_save_vendor', [$this, 'save_vendor']);
		add_action('admin_post_tcpdv_delete_vendor', [$this, 'delete_vendor']);
		add_action('pre_get_posts', [$this, 'pre_get_posts']);
	}

	function admin_enqueue_scripts() {
		global $tcp_display_vendor;
		if (isset($_GET['page']) && $_GET['page'] == 'tcpdv_admin') {
			wp_enqueue_script('tcpdv_admin', $tcp_display_vendor->plugin_url . '/js/admin.js', ['jquery'], $tcp_display_vendor->asset_version, true);
			wp_localize_script('tcpdv_admin', 'tcpdv_lang', [
				'confirm_delete' => __('Confirm delete this vendor?'),
			]);
			wp_enqueue_style('tcpdv_admin', $tcp_display_vendor->plugin_url . '/css/admin.css', [], $tcp_display_vendor->asset_version);
		}
	}

	function create_admin_page() { ?>
		<div class="wrap">
			<h1><?php _e('TCP Display Vendor'); ?></h1>
			<?php
			if (isset($_GET['vid'])) {
				$this->create_vendor_form();
			} else {
				$this->vendor_list();
			} ?>
		</div>
		<?php
	}

	function vendor_list() {
		global $tcp_display_vendor, $wpdb;
		$url = admin_url('admin.php?page=tcpdv_admin');
		$vendors = $tcp_display_vendor->get_vendors();
		$results = $wpdb->get_results($wpdb->prepare("
			SELECT pm.meta_value, COUNT(*) AS total
			FROM {$wpdb->posts} AS p
			LEFT JOIN {$wpdb->postmeta} AS pm
			ON pm.post_id = p.ID
			WHERE pm.meta_key = %s
			AND p.post_status != 'trash'
			GROUP BY pm.meta_value
		", TCP_display_vendor::VENDOR_ID_META));
		$assigned = wp_list_pluck($results, 'total', 'meta_value');
		?>
		<p><a class="button" href="<?php echo esc_url(add_query_arg('vid', '', $url)); ?>"><?php _e('Add vendor'); ?></a></p>
		<table class="wp-list-table widefat striped">
			<tr>
				<th width="2%"><?php _e('ID'); ?></th>
				<th><?php _e('Name'); ?></th>
				<th width="10%"><?php _e('Count'); ?></th>
			</tr>
			<?php if (empty($vendors)) { ?>
				<tr><td colspan="3"><?php _e('No record'); ?></td></tr>
			<?php } else { ?>
				<?php foreach ($vendors as $v) { ?>
					<tr>
						<td><?php echo esc_html($v['id']); ?></td>
						<td>
							<a href="<?php echo esc_url(add_query_arg('vid', $v['id'], $url)); ?>"><?php echo esc_html($v['name']); ?></a>
						</td>
						<td>
							<?php if (isset($assigned[$v['id']])) { ?>
								<a href="<?php echo esc_url(admin_url('edit.php?post_type=product&vid='. $v['id'])); ?>" target="_blank"><?php echo esc_html($assigned[$v['id']]); ?></a>
							<?php } else { ?>
								0
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
			<?php } ?>
		</table>
		<?php
	}

	function create_vendor_form() {
		global $tcp_display_vendor;
		$vid = (int) $_GET['vid'];
		$vendor = null;
		if ($vid) {
			$vendors = $tcp_display_vendor->get_vendors();
			if (isset($vendors[$vid])) {
				$vendor = $vendors[$vid];
			}
		} ?>
		<h2><?php _e($vendor ? 'Edit vendor' : 'Add vendor'); ?></h2>
		<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
			<input type="hidden" name="action" value="tcpdv_save_vendor">
			<?php wp_nonce_field('tcpdv_save_vendor'); ?>
			<?php if ($vendor) { ?>
				<input type="hidden" name="vid" value="<?php echo esc_attr($vendor['id']); ?>">
			<?php } ?>
			<table class="form-table">
				<?php if ($vendor) { ?>
					<tr>
						<th><?php _e('ID'); ?></th>
						<td>
							<?php echo esc_html($vendor['id']); ?>
							<span class="del_span">&middot; <a class="link_delete" href="#"><?php _e('Delete'); ?></a></span>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<th><?php _e('Name'); ?></th>
					<td>
						<input class="regular-text" type="text" name="name" value="<?php echo $vendor ? esc_attr($vendor['name']) : ''; ?>" minlength="2">
					</td>
				</tr>
			</table>
			<p>
				<button class="button button-primary" type="submit"><?php _e('Save'); ?></button>
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tcpdv_admin')); ?>"><?php _e('Cancel'); ?></a>
			</p>
		</form>
		<?php if ($vendor) { ?>
			<form id="delete_vendor_form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
				<input type="hidden" name="action" value="tcpdv_delete_vendor">
				<input type="hidden" name="vid" value="<?php echo esc_attr($vendor['id']); ?>">
				<?php wp_nonce_field('tcpdv_delete_vendor'); ?>
			</form><?php
		}
	}

	function save_vendor() {
		global $tcp_display_vendor;
		check_admin_referer('tcpdv_save_vendor');
		$redirect_url = admin_url('admin.php?page=tcpdv_admin');
		$vid = isset($_POST['vid']) ? (int) $_POST['vid'] : 0;
		$name = isset($_POST['name']) ? tcp_sanitize_text_field($_POST['name'], 2, 255) : '';
		$vendors = $tcp_display_vendor->get_vendors();
		$notice = [
			'status' => 'success',
			'message' => __('Vendor info saved'),
		];
		if (empty($name)) {
			$notice = [
				'status' => 'error',
				'message' => __('Vendor name is required'),
			];
		}
		if ($notice['status'] != 'error') {
			if ($vid && !isset($vendors[$vid])) {
				$notice = [
					'status' => 'error',
					'message' => __('Vendor ID not found'),
				];
			}
		}
		if ($notice['status'] == 'error') {
			$redirect_url = add_query_arg('vid', $vid ?: '', $redirect_url);
		} else {
			if (!$vid) {
				if (empty($vendors)) {
					$vid = 1;
				} else {
					$vid = max(wp_list_pluck($vendors, 'id')) + 1;
				}
			}
			$slug = sanitize_title($name);
			do {
				$c = 1;
				$ok = true;
				foreach ($vendors as $v) {
					if (isset($v['slug']) && $v['slug'] == $slug) {
						$ok = false;
						break;
					}
				}
				if ($ok) {
					break;
				} else {
					$slug = sanitize_title($name) . '-' . (++$c);
				}
			} while (true);
			$vendors[$vid] = [
				'id' => $vid,
				'name' => $name,
				'slug' => $slug,
			];
			update_option(TCP_display_vendor::VENDORS_OPT, $vendors, false);
		}
		tcp_notice($notice, $redirect_url);
	}

	function delete_vendor() {
		global $tcp_display_vendor, $wpdb;
		check_admin_referer('tcpdv_delete_vendor');
		$vendors = $tcp_display_vendor->get_vendors();
		$vid = isset($_POST['vid']) ? (int) $_POST['vid'] : 0;
		$notice = [
			'status' => 'warning',
			'message' => __('Vendor deleted'),
		];
		if (!$vid || !isset($vendors[$vid])) {
			$notice = [
				'status' => 'error',
				'message' => __('Vendor ID not found'),
			];
		}
		if ($notice['status'] != 'error') {
			unset($vendors[$vid]);
			update_option(TCP_display_vendor::VENDORS_OPT, $vendors, false);
			$wpdb->delete($wpdb->postmeta, [
				'meta_key' => TCP_display_vendor::VENDOR_ID_META,
				'meta_value' => $vid,
			], [
				'%s',
				'%d',
			]);
		}
		tcp_notice($notice, admin_url('admin.php?page=tcpdv_admin'));
	}

	// filter product list table by vendor ID
	function pre_get_posts(&$query) {
		global $pagenow;
		$post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
		$vid = isset($_GET['vid']) ? (int) $_GET['vid'] : 0;
		if ($pagenow == 'edit.php' && is_admin() && $query->is_main_query() && $post_type == 'product' && $vid) {
			$query->set('meta_key', TCP_display_vendor::VENDOR_ID_META);
			$query->set('meta_value', $vid);
		}
	}

}

if (is_admin()) {
	new TCP_display_vendor_admin();
}