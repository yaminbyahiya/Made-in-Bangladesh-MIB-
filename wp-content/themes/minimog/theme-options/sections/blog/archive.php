<?php
$sidebar_positions   = Minimog_Helper::get_list_sidebar_positions();
$registered_sidebars = Minimog_Redux::instance()->get_registered_widgets_options();

Redux::set_section( Minimog_Redux::OPTION_NAME, array(
	'title'      => esc_html__( 'Blog Archive', 'minimog' ),
	'id'         => 'blog_archive',
	'subsection' => true,
	'fields'     => array(
		array(
			'id'     => 'section_start_blog_archive_header',
			'type'   => 'tm_heading',
			'title'  => esc_html__( 'Header Settings', 'minimog' ),
			'indent' => true,
		),
		array(
			'id'          => 'blog_archive_header_type',
			'type'        => 'select',
			'title'       => esc_html__( 'Header Style', 'minimog' ),
			'description' => esc_html__( 'Select default header style that displays on archive product page.', 'minimog' ),
			'placeholder' => esc_html__( 'Use Global Setting', 'minimog' ),
			'options'     => Minimog_Header::instance()->get_list( true ),
		),
		array(
			'id'          => 'blog_archive_header_overlay',
			'type'        => 'select',
			'title'       => esc_html__( 'Header Overlay', 'minimog' ),
			'placeholder' => esc_html__( 'Use Global Setting', 'minimog' ),
			'options'     => Minimog_Header::instance()->get_overlay_list(),
		),
		array(
			'id'          => 'blog_archive_header_skin',
			'type'        => 'select',
			'title'       => esc_html__( 'Header Skin', 'minimog' ),
			'placeholder' => esc_html__( 'Use Global Setting', 'minimog' ),
			'options'     => Minimog_Header::instance()->get_skin_list(),
		),
		array(
			'id'     => 'section_start_blog_archive_title_bar',
			'type'   => 'tm_heading',
			'title'  => esc_html__( 'Title Bar Settings', 'minimog' ),
			'indent' => true,
		),
		array(
			'id'          => 'blog_archive_title_bar_layout',
			'type'        => 'select',
			'title'       => esc_html__( 'Title Bar Style', 'minimog' ),
			'description' => esc_html__( 'Select default Title Bar that displays on all archive product (included cart, checkout, my-account...) pages.', 'minimog' ),
			'placeholder' => esc_html__( 'Use Global Setting', 'minimog' ),
			'options'     => Minimog_Title_Bar::instance()->get_list( true ),
		),
		array(
			'id'     => 'section_start_blog_archive_sidebar',
			'type'   => 'tm_heading',
			'title'  => esc_html__( 'Sidebar Settings', 'minimog' ),
			'indent' => true,
		),
		array(
			'id'      => 'blog_archive_page_sidebar_1',
			'type'    => 'select',
			'title'   => esc_html__( 'Sidebar 1', 'minimog' ),
			'options' => $registered_sidebars,
			'default' => Minimog_Redux::get_default_setting( 'blog_archive_page_sidebar_1' ),
		),
		array(
			'id'      => 'blog_archive_page_sidebar_2',
			'type'    => 'select',
			'title'   => esc_html__( 'Sidebar 2', 'minimog' ),
			'options' => $registered_sidebars,
			'default' => Minimog_Redux::get_default_setting( 'blog_archive_page_sidebar_2' ),
		),
		array(
			'id'      => 'blog_archive_page_sidebar_position',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Sidebar Position', 'minimog' ),
			'options' => $sidebar_positions,
			'default' => Minimog_Redux::get_default_setting( 'blog_archive_page_sidebar_position' ),
		),
		array(
			'id'             => 'blog_archive_single_sidebar_width',
			'type'           => 'dimensions',
			'units'          => array( '%' ),
			'units_extended' => 'false',
			'title'          => esc_html__( 'Single Sidebar Width', 'minimog' ),
			'description'    => esc_html__( 'Controls the width of the sidebar when only one sidebar is present. Leave blank to use global setting.', 'minimog' ),
			'height'         => false,
			'default'        => Minimog_Redux::get_default_setting( 'blog_archive_single_sidebar_width' ),
		),
		array(
			'id'             => 'blog_archive_single_sidebar_offset',
			'type'           => 'dimensions',
			'units'          => array( 'px' ),
			'units_extended' => 'false',
			'title'          => esc_html__( 'Single Sidebar Offset', 'minimog' ),
			'description'    => esc_html__( 'Controls the offset of the sidebar when only one sidebar is present. Leave blank to use global setting.', 'minimog' ),
			'height'         => false,
			'default'        => Minimog_Redux::get_default_setting( 'blog_archive_single_sidebar_offset' ),
		),
		array(
			'id'      => 'blog_archive_page_sidebar_style',
			'type'    => 'select',
			'title'   => esc_html__( 'Sidebar Style', 'minimog' ),
			'options' => Minimog_Sidebar::instance()->get_supported_style_options(),
			'default' => Minimog_Redux::get_default_setting( 'blog_archive_page_sidebar_style' ),
		),
		array(
			'id'     => 'section_start_blog_archive_layout',
			'type'   => 'tm_heading',
			'title'  => esc_html__( 'Layout Settings', 'minimog' ),
			'indent' => true,
		),
		array(
			'id'      => 'blog_archive_site_layout',
			'type'    => 'select',
			'title'   => esc_html__( 'Site Layout', 'minimog' ),
			'options' => Minimog_Site_Layout::instance()->get_container_wide_list(),
			'default' => Minimog_Redux::get_default_setting( 'blog_archive_site_layout' ),
		),
		array(
			'id'      => 'blog_archive_pagination_type',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Pagination Type', 'minimog' ),
			'options' => array(
				''          => esc_html__( 'Numbered list', 'minimog' ),
				'load-more' => esc_html__( 'Load more button', 'minimog' ),
				'infinite'  => esc_html__( 'Infinite scrolling', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'blog_archive_pagination_type' ),
		),
		array(
			'id'          => 'blog_archive_style',
			'type'        => 'select',
			'title'       => esc_html__( 'Blog Style', 'minimog' ),
			'description' => esc_html__( 'Select blog style that display for archive pages.', 'minimog' ),
			'options'     => [
				'grid' => esc_attr__( 'Grid', 'minimog' ),
				'list' => esc_attr__( 'List', 'minimog' ),
			],
			'default'     => Minimog_Redux::get_default_setting( 'blog_archive_style' ),
		),
		array(
			'id'       => 'blog_archive_masonry',
			'type'     => 'switch',
			'title'    => esc_html__( 'Masonry', 'minimog' ),
			'default'  => Minimog_Redux::get_default_setting( 'blog_archive_masonry' ),
			'required' => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
		array(
			'id'       => 'blog_archive_grid_image_size',
			'type'     => 'select',
			'title'    => esc_html__( 'Image Size', 'minimog' ),
			'options'  => [
				'740x480' => '740x480',
				'840x544' => '840x544',
				'custom'  => esc_html__( 'Custom', 'minimog' ),
			],
			'default'  => Minimog_Redux::get_default_setting( 'blog_archive_grid_image_size' ),
			'required' => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
		array(
			'id'            => 'blog_archive_grid_image_size_width',
			'title'         => esc_html__( 'Image Size Width', 'minimog' ),
			'type'          => 'slider',
			'min'           => 1,
			'max'           => 960,
			'step'          => 1,
			'display_value' => 'text',
			'required'      => array(
				[ 'blog_archive_style', '=', 'grid' ],
				[ 'blog_archive_grid_image_size', '=', 'custom' ],
			),
		),
		array(
			'id'            => 'blog_archive_grid_image_size_height',
			'title'         => esc_html__( 'Image Size Height', 'minimog' ),
			'type'          => 'slider',
			'min'           => 1,
			'max'           => 960,
			'step'          => 1,
			'display_value' => 'text',
			'required'      => array(
				[ 'blog_archive_style', '=', 'grid' ],
				[ 'blog_archive_grid_image_size', '=', 'custom' ],
			),
		),
		array(
			'id'          => 'blog_archive_grid_caption_style',
			'type'        => 'select',
			'title'       => esc_html__( 'Blog Caption Style', 'minimog' ),
			'description' => esc_html__( 'Select blog grid caption style that display for archive pages.', 'minimog' ),
			'options'     => [
				'01' => '01',
				'02' => '02',
				'03' => '03',
				'04' => '04',
			],
			'default'     => Minimog_Redux::get_default_setting( 'blog_archive_grid_caption_style' ),
			'required'    => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
		array(
			'id'       => 'blog_archive_grid_caption_alignment',
			'type'     => 'button_set',
			'title'    => esc_html__( 'Blog Caption Alignment', 'minimog' ),
			'options'  => array(
				'left'   => esc_html__( 'Left', 'minimog' ),
				'center' => esc_html__( 'Center', 'minimog' ),
				'right'  => esc_html__( 'Right', 'minimog' ),
			),
			'default'  => Minimog_Redux::get_default_setting( 'blog_archive_grid_caption_alignment' ),
			'required' => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
		array(
			'id'            => 'blog_archive_posts_per_page',
			'title'         => esc_html__( 'Number posts', 'minimog' ),
			'description'   => esc_html__( 'Controls the number of posts per page', 'minimog' ),
			'type'          => 'spinner',
			'default'       => Minimog_Redux::get_default_setting( 'blog_archive_posts_per_page' ),
			'min'           => 1,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
		),
		array(
			'id'            => 'blog_archive_lg_columns',
			'title'         => esc_html__( 'Grid Columns', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'blog_archive_lg_columns' ),
			'min'           => 1,
			'max'           => 6,
			'step'          => 1,
			'display_value' => 'text',
			'required'      => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
		array(
			'id'            => 'blog_archive_lg_gutter',
			'title'         => esc_html__( 'Grid Gutter', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'blog_archive_lg_gutter' ),
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
			'required'      => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
		array(
			'id'            => 'blog_archive_md_columns',
			'title'         => esc_html__( 'Grid Columns (Tablet)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'blog_archive_md_columns' ),
			'min'           => 1,
			'max'           => 6,
			'step'          => 1,
			'display_value' => 'text',
			'required'      => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
		array(
			'id'            => 'blog_archive_md_gutter',
			'title'         => esc_html__( 'Grid Gutter (Tablet)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'blog_archive_md_gutter' ),
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
			'required'      => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
		array(
			'id'            => 'blog_archive_sm_columns',
			'title'         => esc_html__( 'Grid Columns (Mobile)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'blog_archive_sm_columns' ),
			'min'           => 1,
			'max'           => 6,
			'step'          => 1,
			'display_value' => 'text',
			'required'      => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
		array(
			'id'            => 'blog_archive_sm_gutter',
			'title'         => esc_html__( 'Grid Gutter (Mobile)', 'minimog' ),
			'type'          => 'slider',
			'default'       => Minimog_Redux::get_default_setting( 'blog_archive_sm_gutter' ),
			'min'           => 0,
			'max'           => 100,
			'step'          => 1,
			'display_value' => 'text',
			'required'      => array(
				[ 'blog_archive_style', '=', 'grid' ],
			),
		),
	),
) );
