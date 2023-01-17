<?php
Redux::set_section( Minimog_Redux::OPTION_NAME, array(
	'title'       => esc_html__( 'Popup', 'minimog' ),
	'description' => esc_html__( 'Create coupon or newsletter popup to interact with your customer.', 'minimog' ),
	'id'          => 'panel_popup',
	'icon'        => 'eicon-upload-circle-o',
	'fields'      => array(
		array(
			'id'     => 'section_promo_popup_layout_content',
			'type'   => 'tm_heading',
			'title'  => esc_html__( 'Layout & Content', 'minimog' ),
			'indent' => true,
		),
		array(
			'id'      => 'promo_popup_enable',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Visibility', 'minimog' ),
			'options' => array(
				'0' => esc_html__( 'Hide', 'minimog' ),
				'1' => esc_html__( 'Show', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_enable' ),
		),
		array(
			'id'      => 'promo_popup_style',
			'type'    => 'select',
			'title'   => esc_html__( 'Style', 'minimog' ),
			'options' => [
				'01' => sprintf( esc_html__( 'Style %s', 'minimog' ), '01' ),
				'02' => sprintf( esc_html__( 'Style %s', 'minimog' ), '02' ),
				'03' => sprintf( esc_html__( 'Style %s', 'minimog' ), '03' ),
			],
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_style' ),
		),
		array(
			'id'          => 'promo_popup_type',
			'type'        => 'select',
			'title'       => esc_html__( 'Popup Type', 'minimog' ),
			'description' => esc_html__( 'Choose goal for your promo popup', 'minimog' ),
			'options'     => [
				'subscribe'     => esc_html__( 'Subscribe', 'minimog' ),
				'discount_code' => esc_html__( 'Give a discount code', 'minimog' ),
				'announcement'  => esc_html__( 'Announcement', 'minimog' ),
			],
			'default'     => Minimog_Redux::get_default_setting( 'promo_popup_type' ),
		),
		array(
			'id'          => 'promo_popup_form_id',
			'type'        => 'select',
			'title'       => esc_html__( 'Form ID', 'minimog' ),
			'description' => esc_html__( 'Select a form by WPForms to display', 'minimog' ),
			'options'     => Minimog_Helper::get_wpforms_list( [ 'context' => 'options' ] ),
			'default'     => '',
			'required'    => array(
				[ 'promo_popup_type', '=', 'subscribe' ],
			),
		),
		array(
			'id'       => 'promo_popup_discount_code',
			'type'     => 'text',
			'title'    => esc_html__( 'Discount Code', 'minimog' ),
			'required' => array(
				[ 'promo_popup_type', '=', 'discount_code' ],
			),
		),
		array(
			'id'      => 'promo_popup_heading',
			'type'    => 'textarea',
			'title'   => esc_html__( 'Heading', 'minimog' ),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_heading' ),
		),
		array(
			'id'      => 'promo_popup_description',
			'type'    => 'textarea',
			'title'   => esc_html__( 'Description', 'minimog' ),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_description' ),
		),
		array(
			'id'    => 'promo_popup_content',
			'type'  => 'editor',
			'title' => esc_html__( 'Content', 'minimog' ),
			'desc'  => esc_html__( 'Support shortcode tags', 'minimog' ),
			'args'  => array(
				'textarea_rows'  => 5,
				'default_editor' => 'html',
			),
		),
		array(
			'id'      => 'promo_popup_image',
			'type'    => 'media',
			'title'   => esc_html__( 'Image', 'minimog' ),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_image' ),
		),
		array(
			'id'       => 'promo_popup_button_style',
			'type'     => 'select',
			'title'    => esc_html__( 'Button Style', 'minimog' ),
			'options'  => Minimog_Helper::get_button_style_options(),
			'default'  => Minimog_Redux::get_default_setting( 'promo_popup_button_style' ),
			'required' => array(
				[ 'promo_popup_type', '=', 'announcement' ],
			),
		),
		array(
			'id'       => 'promo_popup_button_text',
			'type'     => 'text',
			'title'    => esc_html__( 'Button Text', 'minimog' ),
			'default'  => Minimog_Redux::get_default_setting( 'promo_popup_button_text' ),
			'required' => array(
				[ 'promo_popup_type', '=', 'announcement' ],
			),
		),
		array(
			'id'       => 'promo_popup_button_url',
			'type'     => 'text',
			'title'    => esc_html__( 'Button Url', 'minimog' ),
			'default'  => Minimog_Redux::get_default_setting( 'promo_popup_button_url' ),
			'required' => array(
				[ 'promo_popup_type', '=', 'announcement' ],
			),
		),
		array(
			'id'       => 'section_promo_popup_conditions',
			'type'     => 'tm_heading',
			'title'    => esc_html__( 'Conditions', 'minimog' ),
			'subtitle' => esc_html__( 'Apply the popup to specify pages.', 'minimog' ),
			'indent'   => true,
		),
		array(
			'id'    => 'promo_popup_show_on_pages',
			'type'  => 'select',
			'data'  => 'pages',
			'multi' => true,
			'title' => esc_html__( 'Select Pages', 'minimog' ),
			'desc'  => esc_html__( 'Select any pages you want to display the popup. Leave blank to display on all pages.', 'minimog' ),
		),
		array(
			'id'       => 'section_promo_popup_triggers',
			'type'     => 'tm_heading',
			'title'    => esc_html__( 'Triggers', 'minimog' ),
			'subtitle' => esc_html__( 'What action the user needs to do for the popup to open.', 'minimog' ),
			'indent'   => true,
		),
		array(
			'id'      => 'promo_popup_trigger_on_load',
			'type'    => 'button_set',
			'title'   => esc_html__( 'On Page Load', 'minimog' ),
			'options' => array(
				'0' => esc_html__( 'No', 'minimog' ),
				'1' => esc_html__( 'Yes', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_trigger_on_load' ),
			'class'   => 'redux-row-field-parent redux-row-field-first-parent',
		),
		array(
			'type'       => 'text',
			'id'         => 'promo_popup_trigger_on_load_delay',
			'title'      => esc_html__( 'Within (sec)', 'minimog' ),
			'attributes' => [
				'type' => 'number',
				'step' => 0.1,
				'min'  => 0,
			],
			'required'   => array(
				[ 'promo_popup_trigger_on_load', '=', '1' ],
			),
			'class'      => 'redux-row-field-child',
		),
		array(
			'id'      => 'promo_popup_trigger_on_scrolling',
			'type'    => 'button_set',
			'title'   => esc_html__( 'On Scroll', 'minimog' ),
			'options' => array(
				'0' => esc_html__( 'No', 'minimog' ),
				'1' => esc_html__( 'Yes', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_trigger_on_scrolling' ),
			'class'   => 'redux-row-field-parent',
		),
		array(
			'id'       => 'promo_popup_trigger_scrolling_direction',
			'type'     => 'button_set',
			'title'    => esc_html__( 'Direction', 'minimog' ),
			'options'  => array(
				'down' => esc_html__( 'Down', 'minimog' ),
				'up'   => esc_html__( 'Up', 'minimog' ),
			),
			'default'  => Minimog_Redux::get_default_setting( 'promo_popup_trigger_scrolling_direction' ),
			'required' => array(
				[ 'promo_popup_trigger_on_scrolling', '=', '1' ],
			),
			'class'    => 'redux-row-field-child',
		),
		array(
			'type'       => 'text',
			'id'         => 'promo_popup_trigger_scrolling_offset',
			'title'      => esc_html__( 'Within (%)', 'minimog' ),
			'attributes' => [
				'type' => 'number',
				'step' => 1,
				'min'  => 0,
				'max'  => 100,
			],
			'default'    => Minimog_Redux::get_default_setting( 'promo_popup_trigger_scrolling_offset' ),
			'required'   => array(
				[ 'promo_popup_trigger_on_scrolling', '=', '1' ],
			),
			'class'      => 'redux-row-field-child',
		),
		array(
			'id'      => 'promo_popup_trigger_on_click',
			'type'    => 'button_set',
			'title'   => esc_html__( 'On Click', 'minimog' ),
			'options' => array(
				'0' => esc_html__( 'No', 'minimog' ),
				'1' => esc_html__( 'Yes', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_trigger_on_click' ),
			'class'   => 'redux-row-field-parent',
		),
		array(
			'type'       => 'text',
			'id'         => 'promo_popup_trigger_click_times',
			'title'      => esc_html__( 'Click Times', 'minimog' ),
			'attributes' => [
				'type' => 'number',
				'step' => 1,
				'min'  => 1,
				'max'  => 100,
			],
			'default'    => Minimog_Redux::get_default_setting( 'promo_popup_trigger_click_times' ),
			'required'   => array(
				[ 'promo_popup_trigger_on_click', '=', '1' ],
			),
			'class'      => 'redux-row-field-child',
		),
		array(
			'id'       => 'section_promo_popup_advanced_rules',
			'type'     => 'tm_heading',
			'title'    => esc_html__( 'Advanced Rules', 'minimog' ),
			'subtitle' => esc_html__( 'Requirements that have to be met for the popup to open.', 'minimog' ),
			'indent'   => true,
		),
		array(
			'id'      => 'promo_popup_rule_by_times',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Show up to X times', 'minimog' ),
			'options' => array(
				'0' => esc_html__( 'No', 'minimog' ),
				'1' => esc_html__( 'Yes', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_rule_by_times' ),
			'class'   => 'redux-row-field-parent redux-row-field-first-parent',
		),
		array(
			'type'       => 'text',
			'id'         => 'promo_popup_rule_times_up_to',
			'title'      => esc_html__( 'Times', 'minimog' ),
			'attributes' => [
				'type' => 'number',
				'step' => 1,
				'min'  => 1,
				'max'  => 100,
			],
			'default'    => Minimog_Redux::get_default_setting( 'promo_popup_rule_times_up_to' ),
			'required'   => array(
				[ 'promo_popup_rule_by_times', '=', '1' ],
			),
			'class'      => 'redux-row-field-child',
		),
		array(
			'id'      => 'promo_popup_rule_show_by_page_views',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Show after X page views', 'minimog' ),
			'options' => array(
				'0' => esc_html__( 'No', 'minimog' ),
				'1' => esc_html__( 'Yes', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_rule_show_by_page_views' ),
			'class'   => 'redux-row-field-parent',
		),
		array(
			'type'       => 'text',
			'id'         => 'promo_popup_rule_page_views_reach',
			'title'      => esc_html__( 'Page Views', 'minimog' ),
			'attributes' => [
				'type' => 'number',
				'step' => 1,
				'min'  => 1,
				'max'  => 100,
			],
			'default'    => Minimog_Redux::get_default_setting( 'promo_popup_rule_page_views_reach' ),
			'required'   => array(
				[ 'promo_popup_rule_show_by_page_views', '=', '1' ],
			),
			'class'      => 'redux-row-field-child',
		),
		array(
			'id'      => 'promo_popup_rule_hide_by_logged_in',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Hide for logged in users', 'minimog' ),
			'options' => array(
				'0' => esc_html__( 'No', 'minimog' ),
				'1' => esc_html__( 'Yes', 'minimog' ),
			),
			'default' => Minimog_Redux::get_default_setting( 'promo_popup_rule_hide_by_logged_in' ),
			'class'   => 'redux-row-field-parent',
		),
	),
) );
