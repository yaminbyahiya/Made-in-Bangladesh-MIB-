<?php
/*
Plugin Name: WPC Product Tabs for WooCommerce (Premium)
Plugin URI: https://wpclever.net/
Description: Product tabs manager for WooCommerce.
Version: 2.0.4
Author: WPClever
Author URI: https://wpclever.net
Text Domain: wpc-product-tabs
Domain Path: /languages/
Requires at least: 4.0
Tested up to: 6.1
WC requires at least: 3.0
WC tested up to: 7.1
*/

defined( 'ABSPATH' ) || exit;

! defined( 'WOOST_VERSION' ) && define( 'WOOST_VERSION', '2.0.4' );
! defined( 'WOOST_FILE' ) && define( 'WOOST_FILE', __FILE__ );
! defined( 'WOOST_URI' ) && define( 'WOOST_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WOOST_DIR' ) && define( 'WOOST_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WOOST_SUPPORT' ) && define( 'WOOST_SUPPORT', 'https://wpclever.net/support?utm_source=support&utm_medium=woost&utm_campaign=wporg' );
! defined( 'WOOST_REVIEWS' ) && define( 'WOOST_REVIEWS', 'https://wordpress.org/support/plugin/wpc-product-tabs/reviews/?filter=5' );
! defined( 'WOOST_CHANGELOG' ) && define( 'WOOST_CHANGELOG', 'https://wordpress.org/plugins/wpc-product-tabs/#developers' );
! defined( 'WOOST_DISCUSSION' ) && define( 'WOOST_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-product-tabs' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WOOST_URI );

include 'includes/wpc-dashboard.php';
include 'includes/wpc-menu.php';
include 'includes/wpc-kit.php';
include 'includes/wpc-premium.php';

if ( ! function_exists( 'woost_init' ) ) {
	add_action( 'plugins_loaded', 'woost_init', 11 );

	function woost_init() {
		// load text-domain
		load_plugin_textdomain( 'wpc-product-tabs', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'woost_notice_wc' );

			return;
		}

		if ( ! class_exists( 'WPCleverWoost' ) && class_exists( 'WC_Product' ) ) {
			class WPCleverWoost {
				protected static $instance = null;

				public static function instance() {
					if ( is_null( self::$instance ) ) {
						self::$instance = new self();
					}

					return self::$instance;
				}

				function __construct() {
					// enqueue
					add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

					// settings page
					add_action( 'admin_init', [ $this, 'register_settings' ] );
					add_action( 'admin_menu', [ $this, 'admin_menu' ] );

					// settings link
					add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
					add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

					// add tab
					add_filter( 'woocommerce_product_tabs', [ $this, 'product_tabs' ] );

					// ajax
					add_action( 'wp_ajax_woost_add_tab', [ $this, 'add_tab' ] );

					// product data
					add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );
					add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
					add_action( 'woocommerce_process_product_meta', [ $this, 'process_product_meta' ] );
				}

				function admin_enqueue_scripts() {
					wp_enqueue_style( 'woost-backend', WOOST_URI . 'assets/css/backend.css', array(), WOOST_VERSION );
					wp_enqueue_script( 'woost-backend', WOOST_URI . 'assets/js/backend.js', array(
						'jquery',
						'jquery-ui-sortable'
					), WOOST_VERSION, true );
				}

				function action_links( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$global = '<a href="' . admin_url( 'admin.php?page=wpclever-woost&tab=global' ) . '">' . esc_html__( 'Global Tabs', 'wpc-product-tabs' ) . '</a>';
						//$links['wpc-premium']       = '<a href="' . admin_url( 'admin.php?page=wpclever-woost&tab=premium' ) . '">' . esc_html__( 'Premium Version', 'wpc-product-tabs' ) . '</a>';
						array_unshift( $links, $global );
					}

					return (array) $links;
				}

				function row_meta( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$row_meta = array(
							'support' => '<a href="' . esc_url( WOOST_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-product-tabs' ) . '</a>',
						);

						return array_merge( $links, $row_meta );
					}

					return (array) $links;
				}

				function register_settings() {
					// settings
					register_setting( 'woost_settings', 'woost_tabs' );
				}

				function admin_menu() {
					add_submenu_page( 'wpclever', esc_html__( 'WPC Product Tabs', 'wpc-product-tabs' ), esc_html__( 'Product Tabs', 'wpc-product-tabs' ), 'manage_options', 'wpclever-woost', array(
						$this,
						'admin_menu_content'
					) );
				}

				function admin_menu_content() {
					$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'global';
					?>
                    <div class="wpclever_settings_page wrap">
                        <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Product Tabs', 'wpc-product-tabs' ) . ' ' . WOOST_VERSION; ?></h1>
                        <div class="wpclever_settings_page_desc about-text">
                            <p>
								<?php printf( esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-product-tabs' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                                <br/>
                                <a href="<?php echo esc_url( WOOST_REVIEWS ); ?>"
                                   target="_blank"><?php esc_html_e( 'Reviews', 'wpc-product-tabs' ); ?></a> | <a
                                        href="<?php echo esc_url( WOOST_CHANGELOG ); ?>"
                                        target="_blank"><?php esc_html_e( 'Changelog', 'wpc-product-tabs' ); ?></a>
                                | <a href="<?php echo esc_url( WOOST_DISCUSSION ); ?>"
                                     target="_blank"><?php esc_html_e( 'Discussion', 'wpc-product-tabs' ); ?></a>
                            </p>
                        </div>
						<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                            <div class="notice notice-success is-dismissible">
                                <p><?php esc_html_e( 'Settings updated.', 'wpc-product-tabs' ); ?></p>
                            </div>
						<?php } ?>
                        <div class="wpclever_settings_page_nav">
                            <h2 class="nav-tab-wrapper">
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-woost&tab=global' ); ?>"
                                   class="<?php echo esc_attr( $active_tab === 'global' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
									<?php esc_html_e( 'Global Tabs', 'wpc-product-tabs' ); ?>
                                </a>
                                <!--
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-woost&tab=premium' ); ?>"
                                   class="<?php echo esc_attr( $active_tab === 'premium' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>" style="color: #c9356e">
									<?php esc_html_e( 'Premium Version', 'wpc-product-tabs' ); ?>
                                </a>
                                -->
                                <a href="<?php echo esc_url( WOOST_SUPPORT ); ?>" class="nav-tab" target="_blank">
									<?php esc_html_e( 'Support', 'wpc-product-tabs' ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-kit' ); ?>" class="nav-tab">
									<?php esc_html_e( 'Essential Kit', 'wpc-product-tabs' ); ?>
                                </a>
                            </h2>
                        </div>
                        <div class="wpclever_settings_page_content">
							<?php if ( 'global' === $active_tab ) {
								wp_enqueue_editor();
								$saved_tabs = get_option( 'woost_tabs' );

								if ( empty( $saved_tabs ) ) {
									$saved_tabs = array(
										array(
											'type'    => 'description',
											'title'   => esc_html__( 'Description', 'wpc-product-tabs' ),
											'content' => 'auto'
										),
										array(
											'type'    => 'additional_information',
											'title'   => esc_html__( 'Additional Information', 'wpc-product-tabs' ),
											'content' => 'auto'
										),
										array(
											'type'    => 'reviews',
											'title'   => esc_html__( 'Reviews (%d)', 'wpc-product-tabs' ),
											'content' => 'auto'
										)
									);
								}
								?>
                                <form method="post" action="options.php">
                                    <table class="form-table">
                                        <tr>
                                            <td colspan="2" class="woost-tabs-wrapper">
                                                <div class="woost-tabs">
													<?php if ( is_array( $saved_tabs ) && ( count( $saved_tabs ) > 0 ) ) {
														foreach ( $saved_tabs as $saved_tab ) {
															self::tab( $saved_tab );
														}
													} ?>
                                                </div>
												<?php self::new_tab(); ?>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
												<?php settings_fields( 'woost_settings' ); ?>
												<?php submit_button(); ?>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } elseif ( $active_tab === 'premium' ) { ?>
                                <div class="wpclever_settings_page_content_text">
                                    <p>
                                        Get the Premium Version just $29! <a
                                                href="https://wpclever.net/downloads/product-tabs?utm_source=pro&utm_medium=woost&utm_campaign=wporg"
                                                target="_blank">https://wpclever.net/downloads/product-tabs</a>
                                    </p>
                                    <p><strong>Extra features for Premium Version:</strong></p>
                                    <ul style="margin-bottom: 0">
                                        <li>- Manage tabs at product basis.</li>
                                        <li>- Get the lifetime update & premium support.</li>
                                    </ul>
                                </div>
							<?php } ?>
                        </div>
                    </div>
					<?php
				}

				function add_tab() {
					$type = isset( $_POST['type'] ) ? $_POST['type'] : 'custom';

					switch ( $type ) {
						case 'description':
							$title = esc_html__( 'Description', 'wpc-product-tabs' );
							break;

						case 'additional_information':
							$title = esc_html__( 'Additional Information', 'wpc-product-tabs' );
							break;

						case 'reviews':
							$title = esc_html__( 'Reviews (%d)', 'wpc-product-tabs' );
							break;

						case 'woosb':
							$title = esc_html__( 'WPC Product Bundles', 'wpc-product-tabs' );
							break;

						case 'woosg':
							$title = esc_html__( 'WPC Grouped Product', 'wpc-product-tabs' );
							break;

						case 'wpcpf':
							$title = esc_html__( 'WPC Product FAQs', 'wpc-product-tabs' );
							break;

						case 'wpcbr':
							$title = esc_html__( 'WPC Brands', 'wpc-product-tabs' );
							break;

						default:
							$title = esc_html__( 'Tab title', 'wpc-product-tabs' );
					}

					self::tab( array(
						'editor'  => isset( $_POST['editor'] ) ? $_POST['editor'] : '',
						'type'    => $type,
						'title'   => $title,
						'content' => ''
					), true );

					die();
				}

				function tab( $tab, $new = false ) {
					if ( isset( $tab['key'] ) && ! empty( $tab['key'] ) ) {
						$key = $tab['key'];
					} else {
						$key = uniqid();
					}

					if ( ! isset( $tab['type'] ) || empty( $tab['type'] ) ) {
						$tab['type'] = 'custom';
					}

					if ( 'custom' === $tab['type'] ) {
						$woost_editor_id = ! empty( $tab['editor'] ) ? $tab['editor'] : uniqid( 'woost-editor-' );
						?>
                        <div class="woost-tab woost-tab-custom <?php echo esc_attr( $new ? 'active' : '' ); ?>">
                            <div class="woost-tab-header">
                                <span class="woost-tab-move"><?php esc_html_e( 'move', 'wpc-product-tabs' ); ?></span>
                                <span class="woost-tab-label"><span
                                            class="woost-tab-title"><?php echo esc_html( $tab['title'] ); ?></span> <span
                                            class="woost-tab-label-type">#custom</span></span>
                                <span class="woost-tab-remove"><?php esc_html_e( 'remove', 'wpc-product-tabs' ); ?></span>
                            </div>
                            <div class="woost-tab-content">
                                <div class="woost-tab-line">
                                    <input type="hidden" value="<?php echo esc_attr( $key ); ?>"
                                           name="woost_tabs[<?php echo esc_attr( $key ); ?>][key]"/>
                                    <input type="hidden" value="<?php echo esc_attr( $tab['type'] ); ?>"
                                           name="woost_tabs[<?php echo esc_attr( $key ); ?>][type]"/>
                                    <input type="text" class="woost-tab-title-input" style="width: 100%"
                                           name="woost_tabs[<?php echo esc_attr( $key ); ?>][title]"
                                           placeholder="<?php esc_attr_e( 'Tab title', 'wpc-product-tabs' ); ?>"
                                           value="<?php echo esc_attr( $tab['title'] ); ?>" required/>
                                </div>
                                <div class="woost-tab-line">
									<?php
									if ( $new ) {
										echo '<textarea id="' . $woost_editor_id . '" name="woost_tabs[' . esc_attr( $key ) . '][content]" rows="10"></textarea>';
									} else {
										$content = html_entity_decode( $tab['content'] );
										$content = stripslashes( $content );

										wp_editor( $content, $woost_editor_id, array(
											'textarea_name' => 'woost_tabs[' . esc_attr( $key ) . '][content]',
											'textarea_rows' => 10
										) );
									}
									?>
                                </div>
                            </div>
                        </div>
					<?php } else { ?>
                        <div class="<?php echo esc_attr( 'woost-tab woost-tab-' . $tab['type'] ); ?> <?php echo esc_attr( $new ? 'active' : '' ); ?>">
                            <div class="woost-tab-header">
                                <span class="woost-tab-move"><?php esc_html_e( 'move', 'wpc-product-tabs' ); ?></span>
                                <span class="woost-tab-label"><span
                                            class="woost-tab-title"><?php echo esc_html( $tab['title'] ); ?></span> <span
                                            class="woost-tab-label-type">#<?php echo esc_attr( $tab['type'] ); ?></span></span>
                                <span class="woost-tab-remove"><?php esc_html_e( 'remove', 'wpc-product-tabs' ); ?></span>
                            </div>
                            <div class="woost-tab-content">
                                <div class="woost-tab-line">
                                    <input type="hidden" value="<?php echo esc_attr( $key ); ?>"
                                           name="woost_tabs[<?php echo esc_attr( $key ); ?>][key]"/>
                                    <input type="hidden" value="<?php echo esc_attr( $tab['type'] ); ?>"
                                           name="woost_tabs[<?php echo esc_attr( $key ); ?>][type]"/>
                                    <input type="text" class="woost-tab-title-input" style="width: 100%"
                                           name="woost_tabs[<?php echo esc_attr( $key ); ?>][title]"
                                           placeholder="<?php echo esc_attr( $tab['type'] ); ?>"
                                           value="<?php echo esc_attr( $tab['title'] ); ?>" required/>
                                    <input type="hidden" value="auto"
                                           name="woost_tabs[<?php echo esc_attr( $key ); ?>][content]"/>
                                </div>
                            </div>
                        </div>
						<?php
					}
				}

				function new_tab() {
					?>
                    <div class="woost-tabs-new">
                        <select class="woost-tab-type">
                            <option value="description"><?php esc_html_e( 'Description', 'wpc-product-tabs' ); ?></option>
                            <option value="additional_information"><?php esc_html_e( 'Additional Information', 'wpc-product-tabs' ); ?></option>
                            <option value="reviews"><?php esc_html_e( 'Reviews (%d)', 'wpc-product-tabs' ); ?></option>
							<?php
							if ( class_exists( 'WPCleverWoosb' ) && ( ( get_option( '_woosb_bundled_position', 'above' ) === 'tab' ) || ( get_option( '_woosb_bundles_position', 'no' ) === 'tab' ) ) ) {
								echo '<option value="woosb">' . esc_html__( 'WPC Product Bundles', 'wpc-product-tabs' ) . '</option>';
							}

							if ( class_exists( 'WPCleverWoosg' ) && ( get_option( '_woosg_position', 'above' ) === 'tab' ) ) {
								echo '<option value="woosg">' . esc_html__( 'WPC Grouped Product', 'wpc-product-tabs' ) . '</option>';
							}

							if ( class_exists( 'WPCleverWpcpf' ) ) {
								echo '<option value="wpcpf">' . esc_html__( 'WPC Product FAQs', 'wpc-product-tabs' ) . '</option>';
							}

							if ( class_exists( 'WPCleverWpcbr' ) && ( get_option( 'wpcbr_single_position', 'after_meta' ) === 'tab' ) ) {
								echo '<option value="wpcbr">' . esc_html__( 'WPC Brands', 'wpc-product-tabs' ) . '</option>';
							}
							?>
                            <option value="custom"><?php esc_html_e( 'Custom', 'wpc-product-tabs' ); ?></option>
                        </select>
                        <input type="button" class="button woost-tab-new"
                               value="<?php esc_attr_e( '+ Add new tab', 'wpc-product-tabs' ); ?>"/>
                    </div>
					<?php
				}

				function product_tabs( $tabs ) {
					global $product, $post;

					if ( $product && ( $product_id = $product->get_id() ) ) {
						$overwrite  = get_post_meta( $product_id, 'woost_overwrite', true );
						$saved_tabs = get_option( 'woost_tabs', [] );

						if ( $overwrite === 'overwrite' || $overwrite === 'on' ) {
							$saved_tabs = get_post_meta( $product_id, 'woost_tabs', true ) ?: [];
						}

						if ( $overwrite === 'prepend' || $overwrite === 'append' ) {
							$single_tabs = get_post_meta( $product_id, 'woost_tabs', true ) ?: [];

							if ( $overwrite === 'prepend' ) {
								$saved_tabs = array_merge( $single_tabs, $saved_tabs );
							}

							if ( $overwrite === 'append' ) {
								$saved_tabs = array_merge( $saved_tabs, $single_tabs );
							}
						}

						if ( is_array( $saved_tabs ) && ! empty( $saved_tabs ) ) {
							$saved_tab_has_description = $saved_tab_has_reviews = $saved_tab_has_additional_information = $saved_tab_has_woosb = $saved_tab_has_woosg = $saved_tab_has_wpcpf = $saved_tab_has_wpcbr = false;
							$priority                  = 0;

							foreach ( $saved_tabs as $key => $saved_tab ) {
								if ( ( ( $saved_tab_type = $saved_tab['type'] ) === 'description' ) || ( $saved_tab_type === 'additional_information' ) || ( $saved_tab_type === 'reviews' ) || ( $saved_tab_type === 'woosb' ) || ( $saved_tab_type === 'woosg' ) || ( $saved_tab_type === 'wpcpf' ) || ( $saved_tab_type === 'wpcbr' ) ) {
									$tabs[ $saved_tab_type ]['title']     = sprintf( $saved_tab['title'], $product->get_review_count() );
									$tabs[ $saved_tab_type ]['priority']  = $priority;
									${'saved_tab_has_' . $saved_tab_type} = true;
								} else {
									$tab_slug          = 'woost-' . $key;
									$tabs[ $tab_slug ] = array(
										'title'    => $saved_tab['title'],
										'priority' => $priority,
										'callback' => array( $this, 'tab_content' )
									);
								}

								$priority ++;
							}

							if ( ! $saved_tab_has_description || ! $post->post_content ) {
								unset( $tabs['description'] );
							}

							if ( ! $saved_tab_has_reviews || ! comments_open() ) {
								unset( $tabs['reviews'] );
							}

							if ( ! $saved_tab_has_additional_information || ( ! $product->has_attributes() && ! apply_filters( 'wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions() ) ) ) {
								unset( $tabs['additional_information'] );
							}

							if ( ! $saved_tab_has_woosb || ! $product->is_type( 'woosb' ) ) {
								unset( $tabs['woosb'] );
							}

							if ( ! $saved_tab_has_woosg || ! $product->is_type( 'woosg' ) ) {
								unset( $tabs['woosg'] );
							}

							if ( ! $saved_tab_has_wpcpf ) {
								unset( $tabs['wpcpf'] );
							}

							if ( ! $saved_tab_has_wpcbr ) {
								unset( $tabs['wpcbr'] );
							}
						}
					}

					return $tabs;
				}

				function tab_content( $name, $tab ) {
					global $product;

					if ( $product && ( $product_id = $product->get_id() ) ) {
						$overwrite  = get_post_meta( $product_id, 'woost_overwrite', true );
						$saved_tabs = get_option( 'woost_tabs', [] );

						if ( $overwrite === 'overwrite' || $overwrite === 'on' ) {
							$saved_tabs = get_post_meta( $product_id, 'woost_tabs', true ) ?: [];
						}

						if ( $overwrite === 'prepend' || $overwrite === 'append' ) {
							$single_tabs = get_post_meta( $product_id, 'woost_tabs', true ) ?: [];

							if ( $overwrite === 'prepend' ) {
								$saved_tabs = array_merge( $single_tabs, $saved_tabs );
							}

							if ( $overwrite === 'append' ) {
								$saved_tabs = array_merge( $saved_tabs, $single_tabs );
							}
						}

						if ( is_array( $saved_tabs ) && ! empty( $saved_tabs ) ) {
							$key = str_replace( 'woost-', '', $name );

							if ( ! isset( $saved_tabs[ $key ] ) ) {
								$key = (int) preg_replace( '/\D/', '', $name );
							}

							if ( isset( $saved_tabs[ $key ] ) && isset( $saved_tabs[ $key ]['content'] ) ) {
								$content = wpautop( stripslashes( html_entity_decode( $saved_tabs[ $key ]['content'] ) ) );
							} else {
								$content = '';
							}

							echo apply_filters( 'woost_tab_content', do_shortcode( $content ), $name, $tab );
						}
					}
				}

				function product_data_tabs( $tabs ) {
					$tabs['woost'] = array(
						'label'  => esc_html__( 'Product Tabs', 'wpc-product-tabs' ),
						'target' => 'woost_settings'
					);

					return $tabs;
				}

				function product_data_panels() {
					global $post;
					$post_id    = $post->ID;
					$saved_tabs = get_post_meta( $post_id, 'woost_tabs', true );
					$overwrite  = get_post_meta( $post_id, 'woost_overwrite', true );
					wp_enqueue_editor();
					?>
                    <div id='woost_settings' class='panel woocommerce_options_panel woost_settings'>
                        <div class="woost-overwrite">
                            <a href="<?php echo admin_url( 'admin.php?page=wpclever-woost&tab=global' ); ?>"
                               target="_blank"><?php esc_html_e( 'Manager Global Tabs', 'wpc-product-tabs' ); ?></a>
                            <span class="woost-overwrite-items">
                                <label class="woost-overwrite-item">
                                    <input name="woost_overwrite" type="radio"
                                           value="default" <?php echo esc_attr( empty( $overwrite ) || $overwrite === 'default' ? 'checked' : '' ); ?>/> <?php esc_html_e( 'Global', 'wpc-product-tabs' ); ?>
                                </label>
                                <label class="woost-overwrite-item">
                                    <input name="woost_overwrite" type="radio"
                                           value="overwrite" <?php echo esc_attr( $overwrite === 'overwrite' || $overwrite === 'on' ? 'checked' : '' ); ?>/> <?php esc_html_e( 'Overwrite', 'wpc-product-tabs' ); ?>
                                </label>
                                <label class="woost-overwrite-item">
                                    <input name="woost_overwrite" type="radio"
                                           value="prepend" <?php echo esc_attr( $overwrite === 'prepend' ? 'checked' : '' ); ?>/> <?php esc_html_e( 'Prepend', 'wpc-product-tabs' ); ?>
                                </label>
                                <label class="woost-overwrite-item">
                                    <input name="woost_overwrite" type="radio"
                                           value="append" <?php echo esc_attr( $overwrite === 'append' ? 'checked' : '' ); ?>/> <?php esc_html_e( 'Append', 'wpc-product-tabs' ); ?>
                                </label>
                            </span>
                        </div>
                        <div class="woost-tabs">
							<?php if ( is_array( $saved_tabs ) && ( count( $saved_tabs ) > 0 ) ) {
								foreach ( $saved_tabs as $saved_tab ) {
									self::tab( $saved_tab );
								}
							} ?>
                        </div>
						<?php self::new_tab(); ?>
                    </div>
					<?php
				}

				function process_product_meta( $post_id ) {
					if ( isset( $_POST['woost_overwrite'] ) ) {
						update_post_meta( $post_id, 'woost_overwrite', sanitize_text_field( $_POST['woost_overwrite'] ) );
					} else {
						delete_post_meta( $post_id, 'woost_overwrite' );
					}

					if ( isset( $_POST['woost_tabs'] ) ) {
						update_post_meta( $post_id, 'woost_tabs', self::sanitize_array( $_POST['woost_tabs'] ) );
					} else {
						delete_post_meta( $post_id, 'woost_tabs' );
					}
				}

				function sanitize_array( $arr ) {
					foreach ( (array) $arr as $k => $v ) {
						if ( is_array( $v ) ) {
							$arr[ $k ] = self::sanitize_array( $v );
						} else {
							$arr[ $k ] = sanitize_post_field( 'post_content', $v, 0, 'db' );
						}
					}

					return $arr;
				}
			}

			return WPCleverWoost::instance();
		}
	}
}

if ( ! function_exists( 'woost_notice_wc' ) ) {
	function woost_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Product Tabs</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
