<?php
/**
 * Block template loader functions.
 *
 * @package gutenberg
 */

/**
 * Adds necessary filters to use 'wp_template' posts instead of theme template files.
 */
function gutenberg_add_template_loader_filters() {
	if ( ! post_type_exists( 'wp_template' ) ) {
		return;
	}

	/**
	 * Array of all overrideable default template types.
	 *
	 * @see get_query_template
	 *
	 * @var array
	 */
	$template_types = array(
		'index',
		'404',
		'archive',
		'author',
		'category',
		'tag',
		'taxonomy',
		'date',
		// Skip 'embed' for now because it is not a regular template type.
		'home',
		'frontpage',
		'privacypolicy',
		'page',
		'search',
		'single',
		'singular',
		'attachment',
	);
	foreach ( $template_types as $template_type ) {
		add_filter( $template_type . '_template', 'gutenberg_override_query_template', 20, 3 );
	}

	add_filter( 'template_include', 'gutenberg_find_template', 20 );
}
add_action( 'wp_loaded', 'gutenberg_add_template_loader_filters' );

/**
 * Filters into the "{$type}_template" hooks to record the current template hierarchy.
 *
 * The method returns an empty result for every template so that a 'wp_template' post
 * is used instead.
 *
 * @see gutenberg_find_template
 *
 * @param string $template  Path to the template. See locate_template().
 * @param string $type      Sanitized filename without extension.
 * @param array  $templates A list of template candidates, in descending order of priority.
 * @return string Empty string to ensure template file is considered not found.
 */
function gutenberg_override_query_template( $template, $type, array $templates = array() ) {
	global $_wp_current_template_hierarchy;

	if ( ! is_array( $_wp_current_template_hierarchy ) ) {
		$_wp_current_template_hierarchy = $templates;
	} else {
		$_wp_current_template_hierarchy = array_merge( $_wp_current_template_hierarchy, $templates );
	}

	return '';
}

/**
 * Find the correct 'wp_template' post for the current hierarchy and return the path
 * to the canvas file that will render it.
 *
 * @param string $template_file Original template file. Will be overridden.
 * @return string Path to the canvas file to include.
 */
function gutenberg_find_template( $template_file ) {
	global $_wp_current_template_post, $_wp_current_template_hierarchy;

	// Bail if no relevant template hierarchy was determined, or if the template file
	// was overridden another way.
	if ( ! $_wp_current_template_hierarchy || $template_file ) {
		return $template_file;
	}

	$slugs = array_map(
		'gutenberg_strip_php_suffix',
		$_wp_current_template_hierarchy
	);

	// Find most specific 'wp_template' post matching the hierarchy.
	$template_query = new WP_Query(
		array(
			'post_type'      => 'wp_template',
			'post_status'    => 'publish',
			'post_name__in'  => $slugs,
			'orderby'        => 'post_name__in',
			'posts_per_page' => 1,
		)
	);

	if ( $template_query->have_posts() ) {
		$template_posts            = $template_query->get_posts();
		$_wp_current_template_post = array_shift( $template_posts );
	}

	// Add extra hooks for template canvas.
	add_action( 'wp_head', 'gutenberg_viewport_meta_tag', 0 );
	remove_action( 'wp_head', '_wp_render_title_tag', 1 );
	add_action( 'wp_head', 'gutenberg_render_title_tag', 1 );

	// This file will be included instead of the theme's template file.
	return gutenberg_dir_path() . 'lib/template-canvas.php';
}

/**
 * Displays title tag with content, regardless of whether theme has title-tag support.
 *
 * @see _wp_render_title_tag()
 */
function gutenberg_render_title_tag() {
	echo '<title>' . wp_get_document_title() . '</title>' . "\n";
}

/**
 * Renders the markup for the current template.
 */
function gutenberg_render_the_template() {
	global $_wp_current_template_post;
	global $wp_embed;

	if ( ! $_wp_current_template_post || 'wp_template' !== $_wp_current_template_post->post_type ) {
		echo '<h1>' . esc_html__( 'No matching template found', 'gutenberg' ) . '</h1>';
		return;
	}

	$content = $_wp_current_template_post->post_content;

	$content = $wp_embed->run_shortcode( $content );
	$content = $wp_embed->autoembed( $content );
	$content = do_blocks( $content );
	$content = wptexturize( $content );
	$content = wp_make_content_images_responsive( $content );
	$content = str_replace( ']]>', ']]&gt;', $content );

	// Wrap block template in .wp-site-blocks to allow for specific descendant styles
	// (e.g. `.wp-site-blocks > *`).
	echo '<div class="wp-site-blocks">';
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';
}

/**
 * Renders a 'viewport' meta tag.
 *
 * This is hooked into {@see 'wp_head'} to decouple its output from the default template canvas.
 */
function gutenberg_viewport_meta_tag() {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1" />' . "\n";
}

/**
 * Strips .php suffix from template file names.
 *
 * @access private
 *
 * @param string $template_file Template file name.
 * @return string Template file name without extension.
 */
function gutenberg_strip_php_suffix( $template_file ) {
	return preg_replace( '/\.php$/', '', $template_file );
}
