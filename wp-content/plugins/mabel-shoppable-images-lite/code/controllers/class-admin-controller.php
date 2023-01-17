<?php

namespace MABEL_SILITE\Code\Controllers
{

	use MABEL_SILITE\Code\Services\Woocommerce_Service;
	use MABEL_SILITE\Core\Common\Admin;
	use MABEL_SILITE\Core\Common\Linq\Enumerable;
	use MABEL_SILITE\Core\Common\Managers\Config_Manager;
	use MABEL_SILITE\Core\Common\Managers\Options_Manager;
	use MABEL_SILITE\Core\Common\Managers\Settings_Manager;
	use MABEL_SILITE\Core\Models\ColorPicker_Option;
	use MABEL_SILITE\Core\Models\Custom_Option;
	use MABEL_SILITE\Core\Models\Text_Option;

	if(!defined('ABSPATH')){die;}

	class Admin_Controller extends Admin
	{
		private $slug;
		public function __construct()
		{
			parent::__construct(new Options_Manager());
			$this->slug = Config_Manager::$slug;

			$this->add_mediamanager_scripts = true;

			$this->add_script_dependencies('wp-color-picker');
			$this->add_style('wp-color-picker',null);

			$this->add_ajax_function('mb-siwc-get-images', $this,'get_images',false,true);
			$this->add_ajax_function('mb-siwc-get-image', $this,'get_image',false,true);
			$this->add_ajax_function('mb-siwc-add-image', $this,'add_image',false,true);
			$this->add_ajax_function('mb-siwc-update-image', $this,'update_image',false,true);
			$this->add_ajax_function('mb-siwc-delete-image', $this, 'delete_image', false, true);

			$this->add_ajax_function('mb-siwc-get-product-by-id', $this, 'get_wc_product_by_id', false, true);
			$this->add_ajax_function('mb-siwc-get-products-by-ids', $this, 'get_wc_products_by_ids', false, true);
			$this->add_ajax_function('mb-siwc-get-products', $this, 'get_wc_product_by_name', false, true);

			$this->add_script_variable('tagsize',Settings_Manager::get_setting('tagsize'));
			$this->add_script_variable('iconsize',Settings_Manager::get_setting('iconsize'));
			$this->add_script_variable('tagicon',Settings_Manager::get_setting('tagicon'));
		}

		public function render_main_sidebar() {
			include Config_Manager::$dir . 'admin/views/sidebar-main.php';
		}

		public function get_wc_products_by_ids() {
			if(empty($_GET['ids'])){
				echo json_encode(array());
				wp_die();
			}

			$ps = get_posts(array( 'posts_per_page' => -1, 'post_type' => 'product', 'post__in' => explode(',',$_GET['ids']) ));

			$products = [];

			foreach ($ps as $p){
				$product = wc_get_product($p->ID);

				$products[] = [
					'name'  => $product->get_title(),
					'url'   => $product->get_permalink(),
					'price' => Woocommerce_Service::format_price(wc_get_price_to_display($product)),
					'id' => $product->get_id()
				];

			}

			echo json_encode($products);
			wp_die();
		}

		public function get_wc_product_by_id()
		{
			echo json_encode($this->get_wc_product($_GET['id']));
			wp_die();
		}

		public function get_wc_product_by_name()
		{
			global $wpdb;
			$product_ids = $wpdb->get_results( $wpdb->prepare( "
				SELECT ID as id 
				FROM {$wpdb->prefix}posts i
				WHERE post_type = 'product' AND post_title LIKE %s
				ORDER BY post_title ASC 
				LIMIT 5",'%' . $_GET['q'] . '%'
			));

			$products = array();

			foreach(Enumerable::from($product_ids)->select( function($x){return $x->id;})->toArray() as $pid) {
				$product = $this->get_wc_product($pid);
				if(empty($product)) continue;
				array_push($products,$product);
			}

			echo json_encode($products);
			wp_die();
		}

		private function get_wc_product($pid)
		{
			$is_new = version_compare( WC()->version, '3.0.0','>=') === true;

			$product = wc_get_product($pid);

			return array(
				'name'  => $product->get_title(),
				'url'   => $product->get_permalink(),
				'price' => get_woocommerce_currency_symbol() . ($is_new ? wc_get_price_to_display($product) :  $product->get_display_price()),
				'id' => $product->get_id()
			);
		}

		public function delete_image()
		{
			wp_delete_post( $_REQUEST['imageId'], true );
			wp_die();
		}

		public function get_image()
		{
			if(!isset($_GET['id'])) wp_die();

			$post = get_post(sanitize_text_field($_GET['id']));
			if($post == null) wp_die();

			wp_send_json(array(
				'id' => $post->ID,
				'image'  => json_decode(get_post_meta($post->ID,'image',true))->image,
				'tags' => json_decode(get_post_meta($post->ID,'tags',true))
			));
		}

		public function get_images()
		{
			$page = isset($_GET['page']) ? $_GET['page'] : 1;

			$post_ids = new \WP_Query(array(
				'post_type' => 'mb_siwc_lite_image',
				'fields' => 'ids',
				'posts_per_page' => 12,
				'paged' => $page
			));

			$images = array();

			foreach ($post_ids->posts as $id){
				$thumb = json_decode(get_post_meta($id,'image',true))->thumb;
				$obj = (object) array(
					'id' => $id,
					'image'  => $thumb,
					'tags' => json_decode(get_post_meta($id,'tags',true))
				);
				array_push($images,$obj);
			}
			wp_reset_postdata();

			wp_send_json( array(
				'images' => $images,
				'maxPages' => $post_ids->max_num_pages,
				'currentPage' => $page
			));
		}

		public function update_image()
		{
			if(isset($_POST['id'])) {
				update_post_meta( intval( $_POST['id'] ), 'tags', sanitize_text_field( $_POST['tags'] ) );
			}
			wp_die();
		}

		public function add_image()
		{
			$id = wp_insert_post(array(
				'post_type' => 'mb_siwc_lite_image',
				'post_status' => 'publish'
			),true);

			if(!is_wp_error( $id ) && $id > 0){

				add_post_meta($id,'image', json_encode(array(
					'image' => sanitize_text_field($_POST['image']),
					'thumb' => sanitize_text_field($_POST['thumb'])
				)));
				add_post_meta($id,'tags', sanitize_text_field($_POST['tags']));
			}
			wp_die($id);
		}

		public function init_admin_page()
		{
			add_action(Config_Manager::$slug . '-render-sidebar', array($this,'render_main_sidebar'));

			$this->options_manager->add_section('design', __('Design','mabel-shoppable-images-lite'), 'admin-customizer', true);
			$this->options_manager->add_section('addimage', __('Add image','mabel-shoppable-images-lite'), 'format-image');
			$this->options_manager->add_section('images', __('Images','mabel-shoppable-images-lite'), 'images-alt2');

			$this->options_manager->add_option('design',
				new ColorPicker_Option(
					'tagbgcolor',
					Settings_Manager::get_setting('tagbgcolor'),
					__('Tag background color','mabel-shoppable-images-lite')
				)
			);

			$this->options_manager->add_option('design',
				new ColorPicker_Option(
					'tagfgcolor',
					Settings_Manager::get_setting('tagfgcolor'),
					__('Icon color','mabel-shoppable-images-lite','mabel-shoppable-images-lite')
				)
			);

			$this->options_manager->add_option('addimage',
				new Custom_Option(null,'add_image',array(
					'woocommerce_active' => class_exists( 'WooCommerce' )
				))
			);

			$this->options_manager->add_option('design',
				new Text_Option(
					'buttontext',
					__('Button text', 'mabel-shoppable-images-lite'),
					Settings_Manager::get_setting('buttontext'),
					null,
					__('What text should appear on the button linking to the product page?','mabel-shoppable-images-lite')
				)
			);

			$this->options_manager->add_option('images',
				new Custom_Option(null,'all_images')
			);

		}

	}
}