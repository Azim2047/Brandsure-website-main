<?php
/**
 * Video block — poster, tap to load, retention events.
 *
 * Three rules this component enforces so that no template has to remember
 * them:
 *
 *   1. A video whose data/videos.json entry says public:false is never
 *      rendered, whatever a page file asks for. Videos 2, 4 and 5 are gated;
 *      Video 4 in particular is the entire commercial reveal, and the way
 *      that leaks is somebody adding a section to a page file. It cannot
 *      leak through here.
 *   2. A public video with no id yet renders the gate card instead of an
 *      empty player, so the page is complete and the conversion path still
 *      works before the video is recorded.
 *   3. Nothing from YouTube loads until the visitor taps. A third-party
 *      iframe on load would cost more than the rest of the page put
 *      together on a 4G phone, and most visitors never press play.
 *
 * @param array $args {
 *     @type string $video    Key in data/videos.json, e.g. 'video1'.
 *     @type string $headline Section headline.
 *     @type string $body     Supporting line.
 *     @type string $tone     Section tone.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_key    = (string) ( $args['video'] ?? '' );
$bs_videos = bs_data( 'videos' );
$bs_video  = (array) ( $bs_videos[ $bs_key ] ?? array() );

// Rule 1. A gated video is not a rendering decision, it is a refusal.
if ( ! $bs_key || empty( $bs_video['public'] ) ) {
	if ( $bs_key ) {
		bs_data_fail( sprintf( 'Refused to render gated or unknown video "%s" on a public template', $bs_key ) );
	}
	return;
}

$bs_id     = (string) ( $bs_video['youtube_id'] ?? '' );
$bs_poster = (string) ( $bs_video['poster'] ?? '' );
$bs_slug   = preg_replace( '/[^a-z0-9]/', '', strtolower( $bs_key ) );

get_template_part(
	'components/section',
	null,
	array(
		'open'  => true,
		'tone'  => (string) ( $args['tone'] ?? 'tint' ),
		'width' => 'prose',
		'class' => 'bs-video',
		'id'    => 'watch',
	)
);

/*
 * Rule 2. When there is no video, the section's own headline and body go
 * with it. They describe the video — "Fourteen minutes on what happens
 * between the ad and the order" — and printing that above a form is a
 * promise the page does not keep. The stand-in card brings its own wording
 * and its own ask instead, and it differs from the page-foot CTA below it
 * because the same request twice in identical words reads as a mistake
 * rather than as emphasis.
 */
if ( '' === $bs_id ) {
	get_template_part(
		'components/gate-card',
		null,
		array(
			'headline' => (string) ( $args['fallback_headline'] ?? __( 'Tell us where your business is today.', 'brandsure-v2' ) ),
			'label'    => (string) ( $args['fallback_label'] ?? __( 'Send the four numbers', 'brandsure-v2' ) ),
		)
	);
	get_template_part( 'components/section', null, array( 'close' => true ) );
	return;
}

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-video__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

if ( ! empty( $args['body'] ) ) {
	printf( '<p class="bs-video__body">%s</p>', esc_html( (string) $args['body'] ) );
}

$bs_duration = (int) ( $bs_video['duration_minutes'] ?? 0 );

?>
<div class="bs-video__player">
	<button
		class="bs-video__poster"
		type="button"
		data-bs-video="<?php echo esc_attr( $bs_id ); ?>"
		data-bs-video-key="<?php echo esc_attr( $bs_slug ); ?>"
		<?php if ( '' !== $bs_poster ) : ?>
			style="background-image:url('<?php echo esc_url( $bs_poster ); ?>')"
		<?php endif; ?>
		>
		<span class="bs-video__play">
			<?php get_template_part( 'components/icon', null, array( 'name' => 'play' ) ); ?>
		</span>
		<span class="bs-video__label">
			<?php
			echo esc_html(
				$bs_duration
					/* translators: %d: run time in minutes. */
					? sprintf( __( 'Watch — %d minutes', 'brandsure-v2' ), $bs_duration )
					: __( 'Watch', 'brandsure-v2' )
			);
			?>
		</span>
	</button>
</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
