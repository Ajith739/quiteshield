<?php
/**
 * BENIGN FIXTURE — a normal site plugin.
 *
 * Common plugin behaviors: shortcodes, script enqueues, WP_Query loops,
 * option storage, sanitization. The engine must NOT flag this file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'gwqsh_fixture_notes', 'gwqsh_fixture_notes_render' );
/**
 * Notes render.
 *
 * @param array $atts Atts.
 * @return string
 */
function gwqsh_fixture_notes_render( $atts ) {
	$atts = shortcode_atts(
		array(
			'limit' => 5,
		),
		$atts,
		'gwqsh_fixture_notes'
	);
	$limit = max( 1, (int) $atts['limit'] );
	$query = new WP_Query(
		array(
			'post_type'      => 'post',
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
		)
	);
	$out = '<ul class="gwqsh-fixture-notes">';
	while ( $query->have_posts() ) {
		$query->the_post();
		$out .= '<li>' . esc_html( get_the_title() ) . '</li>';
	}
	wp_reset_postdata();
	$out .= '</ul>';
	return $out;
}

add_action( 'wp_enqueue_scripts', 'gwqsh_fixture_enqueue' );
/**
 * Enqueue.
 */
function gwqsh_fixture_enqueue() {
	wp_enqueue_style( 'gwqsh-fixture', plugins_url( 'assets/fixture.css', __FILE__ ), array(), '1.0.0' );
}

register_activation_hook( __FILE__, 'gwqsh_fixture_activate' );
/**
 * Activate.
 */
function gwqsh_fixture_activate() {
	$settings = get_option( 'gwqsh_fixture_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	$settings['activated'] = true;
	update_option( 'gwqsh_fixture_settings', $settings, false );
}
