<div class="upgrade-warning" style="display: none;">
	<p>
		<?php _e('To add more images, <a href="https://studiowombat.com/plugin/woocommerce-shoppable-images/?utm_source=sifree&utm_medium=plugin&utm_campaign=upsell" target="_blank">please upgrade to the pro version</a>', $slug); ?>
	</p>
</div>
<div class="step-tracker-wrapper" id="add-image">
	<ul class="step-tracker steps-3">
		<li class="step current">
			<span></span><h2><?php _e('Select image', $slug) ?></h2>
		</li>
		<li class="step">
			<span></span><h2><?php _e('Add tags', $slug) ?></h2>
		</li>
		<li class="step">
			<span></span><h2><?php _e('Save', $slug) ?></h2>
		</li>
	</ul>
	<div class="step-tracker-content">
		<div data-step="1" class="t-c p-t-5">
			<button class="mabel-btn btn-select-image"><?php _e('Select an image', $slug); ?></button>
		</div>
		<div data-step="2" class="t-c p-t-5" style="display: none;">
			<div class="selected-img-holder">
				<img src="" class="u-max-850-width" />
			</div>
			<div class="p-t-2">
				<button class="mabel-btn-prev-step mabel-btn mabel-secondary">Back</button>
				<button class="btn-save-image mabel-btn">Save</button>
			</div>
		</div>
		<div data-step="3" class="t-c p-t-5" style="display: none;">
			<span>
				All done! You can use the following shortcode to display the image on the frontend:
				<code><span class="active-shortcode"></span></code>
			</span>
			<div class="p-t-2">
				<button class="btn-start-over mabel-btn">Add another image</button>
			</div>
		</div>
	</div>
</div>

<script id="popup-content-template" type="text/x-jsrender">
	<div class="mb-siwc-popup-header">
		<span>Add product info</span>
		<a class="btn-delete-tag" href="#"><i class="dashicons dashicons-trash"></i></a>
	</div>
	<div class="mb-siwc-popup-content">
		<?php if($data['woocommerce_active'] === true){ ?>
			{{if !product && !customProduct}}
				<div class="popup-options">
					<label>Which product is this? <span class="loader-indicator"></span></label>
					<input type="text" class="skip-save mb-siwc-autocomplete" placeholder="Search product by name"/>
				</div>
				<div class="p-t-2">
					Or <a href="#" class="btn-change-to-custom primary">add a custom product</a>
				</div>
			{{/if}}
		<?php } ?>
		<?php if($data['woocommerce_active'] === true){ ?>
			{{if customProduct && !product}}
		<?php }else{?>
			{{if !product}}
		<?php } ?>
			<div class="popup-options">
				<div>
					<label>Product name</label>
					<input type="text" placeholder="My awesome product" name="p-name" />
				</div>
				<div>
					<label>Product price + currency</label>
					<input type="text" placeholder="$19" name="p-price" />
				</div>
				<div>
					<label>Link</label>
					<input type="text" name="p-link" placeholder="https://" />
				</div>
				<div class="p-t-2">
					<a href="#" class="btn-save-custom primary">Save this product</a>
					<?php if($data['woocommerce_active'] === true) { ?>
						or <a href="#" class="btn-change-to-auto primary">choose Woocommerce product</a>
					<?php } ?>
				</div>
			</div>
		{{/if}}
		{{if product}}
			<div class="popup-chosen-option">
				<div class="p-t-2">
					<div><label>This product will be shown:</label></div>
					<span class="product-label">{{:product.name}} ({{:product.price}})</span>
				</div>
				<div class="p-t-2">
					<a href="#" class="btn-change-product primary">Change product</a>
				</div>
			</div>
		{{/if}}
	</div>
</script>