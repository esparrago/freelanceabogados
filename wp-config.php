<?php
/** 
 * Configuración básica de WordPress.
 *
 * Este archivo contiene las siguientes configuraciones: ajustes de MySQL, prefijo de tablas,
 * claves secretas, idioma de WordPress y ABSPATH. Para obtener más información,
 * visita la página del Codex{@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} . Los ajustes de MySQL te los proporcionará tu proveedor de alojamiento web.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** Ajustes de MySQL. Solicita estos datos a tu proveedor de alojamiento web. ** //
/** El nombre de tu base de datos de WordPress */
define('DB_NAME', 'wordpress');

/** Tu nombre de usuario de MySQL */
define('DB_USER', 'root');

/** Tu contraseña de MySQL */
define('DB_PASSWORD', 'root');

/** Host de MySQL (es muy probable que no necesites cambiarlo) */
define('DB_HOST', 'localhost');

/** Codificación de caracteres para la base de datos. */
define('DB_CHARSET', 'utf8mb4');

/** Cotejamiento de la base de datos. No lo modifiques si tienes dudas. */
define('DB_COLLATE', '');

/**#@+
 * Claves únicas de autentificación.
 *
 * Define cada clave secreta con una frase aleatoria distinta.
 * Puedes generarlas usando el {@link https://api.wordpress.org/secret-key/1.1/salt/ servicio de claves secretas de WordPress}
 * Puedes cambiar las claves en cualquier momento para invalidar todas las cookies existentes. Esto forzará a todos los usuarios a volver a hacer login.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'Y8_,-mG;ko{)c v^g?u *um-Cq0S|N?F tPZ2kv3+o=-K+F!crUJ7Gy.(O#@`ngD');
define('SECURE_AUTH_KEY', '6jr$iJ:QvEV/;SL//dS+it[(=x+kvNTZ3rB;QE-8N4{LGyZr@+JtXYc19C87|L,u');
define('LOGGED_IN_KEY', 'ixN+(B9u1-xv&?7KMmfuHD{~Qba3ub<ya!J7=dCQ;7}L7 Xdm@rd4}-,Xv{p2t`.');
define('NONCE_KEY', 'RS O&KP@hd8-;z$)m*nBFB1jN7vu!dZ:$Z^wGO-6)+9x-ggih;z9{+HnmiHnK,!$');
define('AUTH_SALT', 'oN~fZBO!<a4$x,DCv$k07qL$.{CjZ90oA8|;=z@c<,-;bXik?&dnQLh%:Ea4wyEI');
define('SECURE_AUTH_SALT', 'tzB|k@$FJU2UDsu;AWFCg^FiAZFkKbkn$wQ;T|&E,,Hs{wli0-Bf_w6}L3[1nnzr');
define('LOGGED_IN_SALT', '+FZ(A7@#g$3Yrr$ Hc)TY5QM@&JQ=QhOW<<|k?N`7G)PLif,O@oeKqEK<j?c[TLy');
define('NONCE_SALT', 'oa`I3@~ )h?6<J,{Kr-J.I-;3Ncj]5xOY1 4[;7:P<+Gj;%Ojy8@G@+:xbXc!W4&');

/**#@-*/

/**
 * Prefijo de la base de datos de WordPress.
 *
 * Cambia el prefijo si deseas instalar multiples blogs en una sola base de datos.
 * Emplea solo números, letras y guión bajo.
 */
$table_prefix  = 'wp_';


/**
 * Para desarrolladores: modo debug de WordPress.
 *
 * Cambia esto a true para activar la muestra de avisos durante el desarrollo.
 * Se recomienda encarecidamente a los desarrolladores de temas y plugins que usen WP_DEBUG
 * en sus entornos de desarrollo.
 */
define('WP_DEBUG', false);

/* ¡Eso es todo, deja de editar! Feliz blogging */

/** WordPress absolute path to the Wordpress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

