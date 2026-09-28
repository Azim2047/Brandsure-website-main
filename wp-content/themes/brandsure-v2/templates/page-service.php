<?php
/**
 * The four standalone service pages.
 *
 * Architecture v3 §6's three-part shape, with the parts owned by whoever
 * should own them:
 *
 *   1. Satisfy the search intent — about 70% of the page, and all of it
 *      WordPress editor content. Somebody searching "furniture product
 *      design services india" must feel they landed in the right place, and
 *      the people who can keep that copy current are the ones who answer
 *      those enquiries, not a developer with a deploy.
 *   2. Its own enquiry path — a component, so a staff edit cannot break the
 *      routing into n8n.
 *   3. One quiet Growth Partnership bridge — a component, and it refuses to
 *      render on labour hiring whatever a template asks for.
 *
 * The theme supplies the shell and the conversion furniture. WordPress
 * supplies the words. That division is Override 4 on a page people edit
 * every month.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_slug  = bs_slug();
$bs_route = bs_service_routes()[ $bs_slug ] ?? '';

get_header();

?>
<main id="main" class="bs-main bs-main--service" role="main">
	<?php
	while ( have_posts() ) :
		the_post();

		get_template_part( 'components/section', null, array( 'open' => true, 'width' => 'prose' ) );
		?>

		<h1 class="bs-page__title"><?php the_title(); ?></h1>

		<div class="bs-prose">
			<?php the_content(); ?>
		</div>

		<?php
		get_template_part( 'components/section', null, array( 'close' => true ) );

	endwhile;

	get_template_part( 'components/service-enquiry', null, array( 'route' => $bs_route ) );

	// Refuses itself on labour hiring. See the component.
	get_template_part( 'components/gp-bridge' );
	?>
</main>
<?php

get_footer();
