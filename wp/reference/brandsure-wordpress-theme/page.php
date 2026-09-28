<?php
$brandsure_pages = array(
    'about'                                   => 'about.html',
    'services'                                => 'services.html',
    'how-it-works'                            => 'how-it-works.html',
    'pricing'                                 => 'pricing.html',
    'furniture-growth-marketing-india'        => 'furniture-growth-marketing-india.html',
    'furniture-business-consulting-india'     => 'furniture-business-consulting-india.html',
    'furniture-product-design-services-india' => 'furniture-product-design-services-india.html',
    'furniture-labour-hiring-india'           => 'furniture-labour-hiring-india.html',
);

$brandsure_slug = get_post_field('post_name', get_queried_object_id());
if (isset($brandsure_pages[$brandsure_slug])) {
    brandsure_render_static_page($brandsure_pages[$brandsure_slug]);
}

status_header(404);
echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Page not found</title></head><body><main><h1>Page not found</h1><p><a href="/">Return to BrandSure</a></p></main></body></html>';
