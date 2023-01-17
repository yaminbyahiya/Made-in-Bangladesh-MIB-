<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'mibspiri_wp581' );

/** Database username */
define( 'DB_USER', 'mibspiri_wp581' );

/** Database password */
define( 'DB_PASSWORD', '1vp(Bi@S86' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'umhlpb6i1kwl71fg3ecqn6aa6bzajg1dnnph0ral9omn31j1aaywwyh37xva57ho' );
define( 'SECURE_AUTH_KEY',  'yo9vfomhyvhnplow5i3281fmi3nstbw7v4ejfk3wmjdhb8phyoeabddrl8rxudgt' );
define( 'LOGGED_IN_KEY',    '3jkjam5pspsfxtclpemqg1pytorb988idu79qgu8ncdxgfulebkhiefvpypor9j1' );
define( 'NONCE_KEY',        'f3rl7alvc7xfufbjbobebhmdmoj7x5leuunwzlrboellctscnpzk16lpsngnwgga' );
define( 'AUTH_SALT',        'qqwfq5tyo4pcu5ftn2bbqxcuczgegrud2ahvp60akjxjdegjnvucrvtmwhtce3lt' );
define( 'SECURE_AUTH_SALT', 'ejbuoprjc7mwq69uvibt8yixm5tmcocq2gajdq95k1ebje3liguojrs2bnnoutqf' );
define( 'LOGGED_IN_SALT',   'hez1hjkus4bjebchispisqnxvesccaykwb2ohw8g3o0yyvwvop2hhu7yllsi5jet' );
define( 'NONCE_SALT',       'y8lcxbjiqhmhjtylwofqshiqvwyrv3fnsqihmfzfukmtqh3zgur03eoyhyxxu25k' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wphq_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );
set_time_limit(300);

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
