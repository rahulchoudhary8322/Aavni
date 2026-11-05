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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'aavni_db' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'h@?8,F_D6~ ZbqcGs%[6>ou@Ks*2P$k/5,hkJeMSD/P&yhW:im9==OVl4UZt{,?0' );
define( 'SECURE_AUTH_KEY',   'E`)@B9[r@o`Tw:s4L~=TN0(fhf5mb1I)d*(RU1e0 RzAnkTa85}!sG.hTj1fR1G8' );
define( 'LOGGED_IN_KEY',     'hvYiK|??VYh:fVDp=xF7`z#kt}8,lX@cSr;Ep-8[1/__)$<d~qB4$+oVEof#4Mzj' );
define( 'NONCE_KEY',         '!OlW^}+BV/1qp La:-]wDs~mBQ1<Yr[B^sMr^TZKp;K4GP%OkyAUcb3D1+2x[eo}' );
define( 'AUTH_SALT',         'v&;W> 4E|IY[uu4u%r6d5Y+d.hN;J#JkaoxoT4vw)&J26SO5(5b)G6f3PD>kWRfK' );
define( 'SECURE_AUTH_SALT',  'LaEDAR.m&K8&^mRcZM6Xi0.pXB<}vwY!o^~cMP,l`,7{Vl0oCeA%#eW$b1S(T*)9' );
define( 'LOGGED_IN_SALT',    'D}5`:88/PB(e+%czq^L(~Q.ee]x~~.zAsog|X=Ncp#uN<`9@L&d76dfP>kR(=>,q' );
define( 'NONCE_SALT',        'WJCJk>:QJ?)Se|h[p3f>l z`gI;h0pP)_.=o1e~3k9]7,&7aH;EN@3[o#/NS26 ]' );
define( 'WP_CACHE_KEY_SALT', 'tt^n*1_Hz_FZ8UpX>$5UdG(wy;Q @}vV04_To!ORa<&>:X&[L._4+1n{00GutQ|-' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
