<?php
	/** @var \MABEL_SILITE\Code\Models\Shoppable_Image_VM $model */
?>

<div
	class="mabel-siwc-img-wrapper"
	data-sw-text="<?php echo $model->button_text ?>"
	data-sw-tags='<?php echo \MABEL_SILITE\Code\Services\Woocommerce_Service::thing_to_html_attribute_string($model->tags); ?>'
	data-sw-icon="<?php echo $model->icon; ?>"
    data-sw-size="<?php echo $model->size; ?>"
>
	<img src="<?php echo $model->image;?>" />
</div>