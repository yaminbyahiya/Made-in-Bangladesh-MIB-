<?php
include_once 'wpc-checker.php';

if ( ! function_exists( 'woosb_update_checker' ) ) {
	add_action( 'init', 'woosb_update_checker', 99 );
	add_action( 'admin_notices', 'woosb_update_notice', 99 );

	function woosb_update_checker() {
		if ( $key = wpc_get_update_key( 5028 ) ) {
			PucFactory::buildUpdateChecker( 'https://api.wpclever.net/update/' . $key . '.json', WOOSB_FILE, plugin_basename( WOOSB_DIR ) );
		}
	}

	function woosb_update_notice() {
		if ( apply_filters( 'wpc_dismiss_notices', false ) ) {
			return;
		}

		if ( get_option( 'wpc_dismiss_notice_woosb_update' ) ) {
			return;
		}

		if ( ! wpc_get_update_key( 5028 ) ) {
			?>
            <div data-dismissible="woosb_update" class="wpc-notice notice notice-warning is-dismissible">
                <p>Please verify <a href="<?php echo admin_url( 'admin.php?page=wpclever-keys' ); ?>">License Key</a> of
                    <strong>WPC Product Bundles</strong> to enjoy unlimited update release and
                    get the latest plugin update directly on the website backend.</p>
            </div>
			<?php
		}
	}
}

if ( ! function_exists( 'wpc_get_update_key' ) ) {
	function wpc_get_update_key( $id = '' ) {
		if ( ! empty( $id ) ) {
			$keys = (array) get_option( 'wpc_update_keys', array() );

			if ( empty( $keys ) ) {
				return false;
			}

			foreach ( array_reverse( $keys ) as $key ) {
				if ( isset( $key['plugins'] ) && is_array( $key['plugins'] ) && ! empty( $key['plugins'] ) ) {
					foreach ( $key['plugins'] as $plugin ) {
						if ( $plugin->id == $id ) {
							return $plugin->key;
						}
					}
				}
			}
		}

		return false;
	}
}

if ( ! class_exists( 'WPCleverPremium' ) ) {
	class WPCleverPremium {
		function __construct() {
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
			add_action( 'wp_ajax_wpc_check_update_key', [ $this, 'check_update_key' ] );
			add_action( 'wp_ajax_wpc_remove_key', [ $this, 'remove_key' ] );
			add_action( 'wp_ajax_wpc_dismiss_notice', [ $this, 'dismiss_notice' ] );
		}

		function admin_enqueue_scripts() {
			wp_enqueue_style( 'wpc-premium', WOOSB_URI . 'assets/css/premium.css' );
			wp_enqueue_script( 'wpc-premium', WOOSB_URI . 'assets/js/premium.js', array( 'jquery', ), null, true );
		}

		function admin_menu() {
			add_submenu_page( 'wpclever', 'WPC License Keys', 'License Keys', 'manage_options', 'wpclever-keys', array(
				$this,
				'update_keys_content'
			) );
		}

		function update_keys_content() {
			?>
            <div class="wpclever_page wpclever_update_keys_page wrap">
                <h1>WPClever | License Keys</h1>
                <div class="card">
                    <h2 class="title">Enter Your License Keys</h2>
                    <p>
                        <strong>Enter your License Key to verify the license you’re using and turn on the update
                            notification.
                            Verified licenses can enjoy unlimited update release and get the latest plugin update
                            directly
                            on our website.</strong>
                    </p>
                    <p>
                        Please check the purchase receipt to find your Receipt ID (old-type invoice) or License Key
                        (new-type invoice) to verify your license(s). You can also access the <a
                                href="https://wpclever.net/my-account/" target="_blank">Membership page</a> to get the
                        license key and enter it below for the verification of each purchase attached to your account.
                    </p>
                    <div class="wpclever_update_keys_form">
                        <input type="text" name="wpc_update_key" id="wpc_update_key" class="regular-text"
                               placeholder="Receipt ID or License Key"/> <input type="button" value="Verify"
                                                                                id="wpc_add_update_key"/>
                    </div>
                </div>
                <div class="card wpclever_plugins">
                    <h2 class="title">Verified Keys</h2>
					<?php
					$keys = (array) get_option( 'wpc_update_keys', array() );

					if ( ! empty( $keys ) ) {
						?>
                        <table class="wpc_update_keys">
                            <thead>
                            <tr>
                                <th>Key</th>
                                <th>Allowed plugins</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
							<?php
							foreach ( array_reverse( $keys ) as $key => $val ) {
								echo '<tr>';
								echo '<td>' . substr( $key, 0, 10 ) . '...' . substr( $key, strlen( $key ) - 4, 4 ) . '</td>';
								echo '<td>';
								echo '<ul>';

								foreach ( $val['plugins'] as $plugin ) {
									echo '<li>' . $plugin->name . '</li>';
								}

								echo '</ul>';
								echo '</td>';
								echo '<td><div class="wpc-premium-validated">Validated on: ' . ( isset( $val['date'] ) ? $val['date'] : '' ) . '</div><div class="wpc-premium-support-expires">Support expires: ' . ( isset( $val['date'] ) ? date( 'Y-m-d H:i:s', strtotime( '+1 year', strtotime( $val['date'] ) ) ) : '' ) . ' ' . ( time() > strtotime( '+1 year', strtotime( $val['date'] ) ) ? '(expired)' : '' ) . '</div></td>';
								echo '<td><a href="#" class="wpc_remove_key" data-key="' . esc_attr( $key ) . '">remove</a></td>';
								echo '</tr>';
							}
							?>
                            </tbody>
                        </table>
					<?php } else {
						echo '<p>Have no keys was verified. Please add your first one!</p>';
					} ?>
                </div>
            </div>
			<?php
		}

		function check_update_key() {
			if ( isset( $_POST['key'] ) && ! empty( $_POST['key'] ) ) {
				$key      = sanitize_key( $_POST['key'] );
				$response = wp_remote_get( 'https://wpclever.net/wp-json/update/v2/key/' . $key, array( 'headers' => array( 'Accept' => 'application/json' ) ) );
				$data     = wp_remote_retrieve_body( $response );

				if ( ! empty( $data ) ) {
					$result = json_decode( $data );

					if ( property_exists( $result, 'id' ) && $result->id && property_exists( $result, 'plugins' ) ) {
						// add keys
						$keys                = (array) get_option( 'wpc_update_keys', array() );
						$secret_key          = substr( $key, 0, 10 ) . substr( $key, strlen( $key ) - 4, 4 );
						$keys[ $secret_key ] = array(
							'id'      => $result->id,
							'plugins' => $result->plugins,
							'date'    => property_exists( $result, 'date' ) ? $result->date : ''
						);

						update_option( 'wpc_update_keys', $keys );
					}
				}
			}

			wp_die();
		}

		function remove_key() {
			if ( isset( $_POST['key'] ) && ! empty( $_POST['key'] ) ) {
				$key  = sanitize_key( $_POST['key'] );
				$keys = (array) get_option( 'wpc_update_keys', array() );
				unset( $keys[ $key ] );

				update_option( 'wpc_update_keys', $keys );
			}

			wp_die();
		}

		function dismiss_notice() {
			if ( isset( $_POST['key'] ) && ! empty( $_POST['key'] ) ) {
				$key = sanitize_key( $_POST['key'] );

				update_option( 'wpc_dismiss_notice_' . $key, time() );
			}

			wp_die();
		}

		public static function get_update_key( $id = '' ) {
			if ( ! empty( $id ) ) {
				$keys = (array) get_option( 'wpc_update_keys', array() );

				if ( empty( $keys ) ) {
					return false;
				}

				foreach ( array_reverse( $keys ) as $key ) {
					if ( isset( $key['plugins'] ) && is_array( $key['plugins'] ) && ! empty( $key['plugins'] ) ) {
						foreach ( $key['plugins'] as $plugin ) {
							if ( $plugin->id == $id ) {
								return $plugin->key;
							}
						}
					}
				}
			}

			return false;
		}
	}

	new WPCleverPremium();
}
