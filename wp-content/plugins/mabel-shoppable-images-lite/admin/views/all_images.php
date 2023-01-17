<div class="all-images-wrapper">
	<span><?php _e("You didn't create any shoppable images yet.", $slug); ?></span>
</div>
<script id="image-template" type="text/x-jsrender">
	{{for images}}
		<div data-id="{{:id}}" class="image-tile">
			<div class="tile-header" style="background-image: url({{:image}})">
				<span class="tag-id">{{:id}}</span>
			</div>
			<div class="tile-footer">
				<ul>
					<li>
						<a href="#" title="edit" class="btn-edit-image"><i class="dashicons dashicons-edit"></i></a>
					</li>
					<li>
						<a href="#" data-tooltip-direction="bottom" data-tooltip='[shoppable_image id="{{:id}}"]' title="shortcode" class="tooltip btn-image-shortcode"><i class="dashicons dashicons-editor-code"></i></a>
					</li>
					<li>
						<a href="#" title="delete" class="btn-delete-image"><i class="dashicons dashicons-trash"></i></a>
					</li>
				</ul>
			</div>
		</div>
	{{/for}}
	{{if maxPages > 1}}
		<div class="mabel-pagination t-c">
			{{if currentPage > 1 }}
				<a href="#" class="prev"><i class="dashicons dashicons-arrow-left-alt2"></i></a>
			{{/if}}
			<span class="mabel-pagination-info">{{:currentPage}}/{{:maxPages}}</span>
			{{if currentPage < maxPages}}
				<a href="#" class="next"><i class="dashicons dashicons-arrow-right-alt2"></i></a>
			{{/if}}
		</div>
	{{/if}}
</script>