<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'houseace' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost:/tmp/mysql.sock' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'Ahu:NZljlKS-v$W~1hFZDfXv3T3BqKF:qVA;9uo=Mry9&2tmxT#,P~G!.C3}lHx4' );
define( 'SECURE_AUTH_KEY',  '#7&v-Alsf7ke)P(`*>y -1#^Tp8T{g!ZP(jmV,sDM(an{WxrS{ ^AGG8O/knjRl<' );
define( 'LOGGED_IN_KEY',    'MM?)yFSnq5A%[xKNTacO;mWPbJ6z/NN0pEYLME&%?lR9my[|O+(OtCrDzQ${&Z8P' );
define( 'NONCE_KEY',        '3DdWoDw5bfa] #PcSU%X~wIP=~n1?A9:| W8w..Zy;q{?-8(d+{Kk[3pTJ&W.8^t' );
define( 'AUTH_SALT',        'Rt%+A}B<;TDZH6L`<T.-qUU5E,%CDYmBWCR  l5#EOfNHHG78-RXcD)s@07cSgs$' );
define( 'SECURE_AUTH_SALT', 'dCOvnm#EtW?RNVUv2g%Y2yaAQysV|/&)x,26jt6]+#^}+cH]Nivc/BK/C%2,kIx+' );
define( 'LOGGED_IN_SALT',   '2YCIY`:1ijb(BI%uxTtsFEo6,,^@JcV.%3?Bp!TPlv$uk&),e-S%Z%A;[Y7yuqi/' );
define( 'NONCE_SALT',       '5L&[0g/d7l73fP^j{1 8fy,L8G[+9Y&FI$pA.>V>Wbv92(x(}gt=6:qop=(~Zz@<' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
