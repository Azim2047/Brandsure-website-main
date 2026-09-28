<?php
/**
 * Front page.
 *
 * WordPress requires this file at the theme root to serve the site's front
 * page, but the implementation belongs with the other page templates. This
 * delegates so there is exactly one Home template, whether the page is
 * reached as the front page or at /home/.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

require BRANDSURE_DIR . '/templates/page-home.php';
