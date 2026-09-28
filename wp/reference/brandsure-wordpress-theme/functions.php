<?php
require_once get_template_directory() . '/inc/render-static.php';

function brandsure_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
}
add_action('after_setup_theme', 'brandsure_theme_setup');

/**
 * Keep every public route connected even on hosts that do not refresh rewrite
 * rules immediately after a theme switch.
 */
function brandsure_static_route_fallback() {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $path = isset($_SERVER['REQUEST_URI']) ? wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '/';
    $route = trim((string) $path, '/');

    $routes = array(
        ''                                           => 'index.html',
        'about'                                      => 'about.html',
        'services'                                   => 'services.html',
        'how-it-works'                               => 'how-it-works.html',
        'pricing'                                    => 'pricing.html',
        'furniture-growth-marketing-india'           => 'furniture-growth-marketing-india.html',
        'furniture-business-consulting-india'        => 'furniture-business-consulting-india.html',
        'furniture-product-design-services-india'    => 'furniture-product-design-services-india.html',
        'furniture-labour-hiring-india'              => 'furniture-labour-hiring-india.html',
    );

    if (isset($routes[$route])) {
        brandsure_render_static_page($routes[$route]);
    }
}
add_action('template_redirect', 'brandsure_static_route_fallback', 0);

function brandsure_add_rewrite_rules() {
    $routes = array(
        'about',
        'services',
        'how-it-works',
        'pricing',
        'furniture-growth-marketing-india',
        'furniture-business-consulting-india',
        'furniture-product-design-services-india',
        'furniture-labour-hiring-india',
    );

    foreach ($routes as $route) {
        add_rewrite_rule('^' . preg_quote($route, '/') . '/?$', 'index.php?pagename=' . $route, 'top');
    }
}
add_action('init', 'brandsure_add_rewrite_rules');

function brandsure_flush_rewrite_rules() {
    brandsure_add_rewrite_rules();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'brandsure_flush_rewrite_rules');
