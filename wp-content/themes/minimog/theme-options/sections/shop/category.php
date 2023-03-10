<?php
Redux::set_section( Minimog_Redux::OPTION_NAME, array(
	'title'      => esc_html__( 'Category Page', 'minimog' ),
	'id'         => 'shop_category',
	'subsection' => true,
	'fields'     => array(
		array(
			'id'     => 'section_start_product_category_title_bar',
			'type'   => 'tm_heading',
			'title'  => esc_html__( 'Title Bar Settings', 'minimog' ),
			'indent' => true,
		),
		array(
			'id'          => 'product_category_title_bar_layout',
			'type'        => 'select',
			'title'       => esc_html__( 'Title Bar Style', 'minimog' ),
			'placeholder' => esc_html__( 'Use Global Setting', 'minimog' ),
			'options'     => Minimog_Title_Bar::instance()->get_list( true ),
		),
		array(
			'id'          => 'shop_category_title_bar_show_description',
			'type'        => 'button_set',
			'title'       => esc_html__( 'Show Category Description', 'minimog' ),
			'description' => 'Note: This option only works with Title Bar style Fill 01',
			'options'     => array(
				'0' => esc_html__( 'No', 'minimog' ),
				'1' => esc_html__( 'Yes', 'minimog' ),
			),
			'default'     => Minimog_Redux::get_default_setting( 'shop_category_title_bar_show_description' ),
		),
		array(
			'id'       => 'shop_category_general_settings',
			'type'     => 'tm_heading',
			'title'    => 'Loop General Settings',
			'collapse' => 'show',
		),
		array(
			'id'      => 'shop_category_hover_effect',
			'type'    => 'select',
			'title'   => esc_html__( 'Hover Effect', 'minimog' ),
			'options' => array(
				''                    => esc_html__( 'None', 'minimog' ),
				'zoom-in'             => esc_html__( 'Zoom In', 'minimog' ),
				'zoom-out'            => esc_html__( 'Zoom Out', 'minimog' ),
				'scaling-up'          => esc_html__( 'Scale Up', 'minimog' ),
				'scaling-up-style-02' => esc_html__( 'Scale Up Bigger', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'shop_category_hover_effect' ),
		),
		array(
			'id'      => 'shop_category_show_count',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Show Count', 'minimog' ),
			'options' => array(
				'0' => esc_html__( 'No', 'minimog' ),
				'1' => esc_html__( 'Yes', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'shop_category_show_count' ),
		),
		array(
			'id'      => 'shop_category_show_min_price',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Show Min Price', 'minimog' ),
			'options' => array(
				'0' => esc_html__( 'No', 'minimog' ),
				'1' => esc_html__( 'Yes', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'shop_category_show_min_price' ),
		),
		array(
			'id'       => 'shop_category_for_catalog',
			'type'     => 'tm_heading',
			'title'    => 'Shop Catalog Page',
			'subtitle' => 'Controls look and feel of product categories display on shop catalog page.',
		),
		array(
			'id'          => 'shop_sub_categories_position',
			'type'        => 'select',
			'title'       => esc_html__( 'Product Categories Position', 'minimog' ),
			'description' => 'Note: Inside Title Bar position only works with Title Bar style Fill 01',
			'options'     => [
				'above_sidebar'    => esc_html__( 'Above Sidebar', 'minimog' ),
				'beside_sidebar'   => esc_html__( 'Beside Sidebar', 'minimog' ),
				'inside_title_bar' => esc_html__( 'Inside Title Bar', 'minimog' ),
			],
			'default'     => Minimog_Redux::get_default_setting( 'shop_sub_categories_position' ),
		),
		array(
			'id'          => 'shop_sub_categories_style',
			'type'        => 'select',
			'title'       => esc_html__( 'Product Categories Style', 'minimog' ),
			'options'     => Minimog_Woo::instance()->get_shop_categories_style_options(),
			'default'     => Minimog_Redux::get_default_setting( 'shop_sub_categories_style' ),
		),
		array(
			'id'      => 'shop_sub_categories_layout',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Layout', 'minimog' ),
			'options' => array(
				'slider' => esc_html__( 'Carousel', 'minimog' ),
				'grid'   => esc_html__( 'Grid', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'shop_sub_categories_layout' ),
		),
		array(
			'id'            => 'shop_sub_categories_lg_columns',
			'title'         => esc_html__( 'Columns', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'shop_sub_categories_lg_columns' ),
			'min'           => 1,
			'max'           => 6,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'shop_sub_categories_lg_gutter',
			'title'         => esc_html__( 'Gutter', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'shop_sub_categories_lg_gutter' ),
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'shop_sub_categories_md_columns',
			'title'         => esc_html__( 'Columns (Tablet)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'shop_sub_categories_md_columns' ),
			'min'           => 1,
			'max'           => 6,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'shop_sub_categories_md_gutter',
			'title'         => esc_html__( 'Gutter (Tablet)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'shop_sub_categories_md_gutter' ),
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'shop_sub_categories_sm_columns',
			'title'         => esc_html__( 'Columns (Mobile)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'shop_sub_categories_sm_columns' ),
			'min'           => 1,
			'max'           => 6,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'shop_sub_categories_sm_gutter',
			'title'         => esc_html__( 'Gutter (Mobile)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'shop_sub_categories_sm_gutter' ),
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'       => 'shop_category_for_category',
			'type'     => 'tm_heading',
			'title'    => 'Category Page',
			'subtitle' => 'Controls look and feel of product categories display on category page.',
		),
		array(
			'id'          => 'product_category_sub_categories_position',
			'type'        => 'select',
			'title'       => esc_html__( 'Product Categories Position', 'minimog' ),
			'description' => 'Note: Inside Title Bar position only works with Title Bar style Fill 01',
			'options'     => [
				'above_sidebar'    => esc_html__( 'Above Sidebar', 'minimog' ),
				'beside_sidebar'   => esc_html__( 'Beside Sidebar', 'minimog' ),
				'inside_title_bar' => esc_html__( 'Inside Title Bar', 'minimog' ),
			],
			'default'     => Minimog_Redux::get_default_setting( 'product_category_sub_categories_position' ),
		),
		array(
			'id'          => 'product_category_sub_categories_style',
			'type'        => 'select',
			'title'       => esc_html__( 'Product Sub Categories Style', 'minimog' ),
			'options'     => Minimog_Woo::instance()->get_shop_categories_style_options(),
			'default'     => Minimog_Redux::get_default_setting( 'product_category_sub_categories_style' ),
		),
		array(
			'id'      => 'product_category_sub_categories_layout',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Layout', 'minimog' ),
			'options' => array(
				'slider' => esc_html__( 'Carousel', 'minimog' ),
				'grid'   => esc_html__( 'Grid', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'product_category_sub_categories_layout' ),
		),
		array(
			'id'            => 'product_category_sub_categories_lg_columns',
			'title'         => esc_html__( 'Columns', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'product_category_sub_categories_lg_columns' ),
			'min'           => 1,
			'max'           => 6,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'product_category_sub_categories_lg_gutter',
			'title'         => esc_html__( 'Gutter', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'product_category_sub_categories_lg_gutter' ),
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'product_category_sub_categories_md_columns',
			'title'         => esc_html__( 'Columns (Tablet)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'product_category_sub_categories_md_columns' ),
			'min'           => 1,
			'max'           => 6,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'product_category_sub_categories_md_gutter',
			'title'         => esc_html__( 'Gutter (Tablet)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'product_category_sub_categories_md_gutter' ),
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'product_category_sub_categories_sm_columns',
			'title'         => esc_html__( 'Columns (Mobile)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'product_category_sub_categories_sm_columns' ),
			'min'           => 1,
			'max'           => 6,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'product_category_sub_categories_sm_gutter',
			'title'         => esc_html__( 'Gutter (Mobile)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'product_category_sub_categories_sm_gutter' ),
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
		),
	),
) );
