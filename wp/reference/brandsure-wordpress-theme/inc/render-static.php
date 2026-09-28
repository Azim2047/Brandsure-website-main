<?php
/**
 * Render one of the self-contained BrandSure page documents.
 *
 * @param string $filename File name inside the theme's static directory.
 */
function brandsure_render_static_page($filename) {
    $allowed = array(
        'index.html',
        'about.html',
        'services.html',
        'how-it-works.html',
        'pricing.html',
        'furniture-growth-marketing-india.html',
        'furniture-business-consulting-india.html',
        'furniture-product-design-services-india.html',
        'furniture-labour-hiring-india.html',
    );

    if (!in_array($filename, $allowed, true)) {
        return;
    }

    $path = get_template_directory() . '/static/' . $filename;
    if (!is_readable($path)) {
        status_header(404);
        return;
    }

    status_header(200);
    header('Content-Type: text/html; charset=UTF-8');
    readfile($path);
    exit;
}
