<?php

namespace MABEL_SILITE\Code\Controllers
{

	use MABEL_SILITE\Code\Models\Shoppable_Image_VM;
	use MABEL_SILITE\Code\Models\Tag;
	use MABEL_SILITE\Code\Services\Woocommerce_Service;
	use MABEL_SILITE\Core\Common\Linq\Enumerable;
	use MABEL_SILITE\Core\Common\Managers\Config_Manager;
	use MABEL_SILITE\Core\Common\Managers\Settings_Manager;
	use MABEL_SILITE\Core\Common\Shortcode;

	if(!defined('ABSPATH')){die;}

	class Shortcode_Controller
	{
		private $slug;

		public function __construct()
		{
			$this->slug = Config_Manager::$slug;
			$this->init_shortcode();
		}

		private function init_shortcode()
		{
			new Shortcode(
				'shoppable_image',
				'shoppable-image',
				array($this,'create_shortcode_model')
			);
		}

		public function create_shortcode_model($attributes){
			$model = new Shoppable_Image_VM();

			if(!isset($attributes['id']) || get_post($attributes['id']) == null || get_post($attributes['id'])->post_type !== 'mb_siwc_lite_image') {
				$model->show_error = true;
				return $model;
			}

			$model->button_text = Settings_Manager::get_setting('buttontext');
			$model->size = Settings_Manager::get_setting('tagsize');
			$model->icon = Settings_Manager::get_setting('tagicon');
			$model->image = json_decode(get_post_meta($attributes['id'],'image',true))->image;
			$taglist = json_decode(get_post_meta($attributes['id'],'tags',true));
			foreach($taglist as $tag)
			{
				$t = new Tag(round(doubleval($tag->x),4),round(doubleval($tag->y),4));
				if($tag->id){
					$product = Woocommerce_Service::get_product($tag->id);
					if($product === null)
						continue;
					$t->link = $product->get_permalink();
					$t->thumb = get_the_post_thumbnail_url($product->get_id(),'woocommerce_thumbnail');
                    $t->price = $this->format_price(wc_get_price_to_display($product));
					$t->title = $product->get_title();
				}else{
					$t->price = esc_html($tag->price);
					$t->title = esc_html($tag->name);
					$t->link = esc_url($tag->url);
				}

				array_push($model->tags, $t);
			}

			return $model;
		}

        private function format_price($price) {
            if(empty($price))
                $price = 0;

            return sprintf(
                get_woocommerce_price_format(),
                get_woocommerce_currency_symbol(),
                number_format($price,wc_get_price_decimals(),wc_get_price_decimal_separator(),wc_get_price_thousand_separator())
            );
        }


    }
}