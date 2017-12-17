<?php
/**
 * Author Archive Extended
 *
 * Add extra author pages to your WordPress author archive page.
 *
 * The default WordPress permalink for the author archive is /author/author-name
 * This script let's you extend this permalink with extra author pages.
 * For example, /author/author-name/recipes.
 *
 * Author: keesiemeijer
 * Licence: GPL v2+
 * GitHub: https://github.com/keesiemeijer/author-archive-extended
 * 
 * ***** Installation *****
 *
 * Include this file in your project (plugin/theme) and add the extra pages with
 * the 'author_archive_extended_pages' filter (see get_author_pages() function below).
 * Re-save your permalink structure in wp-admin -> Settings -> Permalinks.
 * 
 * Now you should be able to visit the pages without getting a 404.
 *
 * You still have to update what posts are displayed on these pages by:
 *     Using the pre_get_posts action
 *     Or using a custom query (WP_Query) in the author template file.
 *
 * ***** Theme template files *****
 *
 * The normal (author) template hierarchy is used to display the author archive and 
 * extended pages. To use specific template files create template files like
 * 'author-page-{$page-slug}.php' in your child theme.
 */

namespace keesiemeijer\Author_Pages_Extended;

add_filter( 'template_include', __NAMESPACE__ . '\\template_include' );
add_filter( 'query_vars', __NAMESPACE__ . '\\query_vars' );
add_action( 'generate_rewrite_rules', __NAMESPACE__ . '\\generate_rewrite_rules' );

/**
 * Get the extended author archive pages to create rewrite rules for.
 *
 * Use the filter 'author_pages_extended_pages' to add extended pages.
 *
 * @return array Array with extended author page slugs. Default empty array.
 */
function get_author_pages() {
	/**
	 * The extended author archive pages to create rewrite rules for.
	 *
	 * @param array $pages Array with page slugs. Default empty array.
	 */
	$pages = apply_filters( 'author_archive_extended_pages', array() );
	$pages = is_array( $pages ) ? $pages : array();

	return array_filter( array_unique( array_map( 'trim', $pages ) ) );
}

/**
 * Get the slug of the queried extended author page.
 *
 * @return string Extended author page slug or empty string.
 */
function get_queried_author_page() {
	if ( ! is_author() ) {
		return '';
	}

	$queried_page = get_query_var( 'author_page' );
	if ( is_array( $queried_page ) ) {
		$queried_page = reset( $queried_page );
	}

	$queried_page = trim( $queried_page );
	$author_pages = get_author_pages();

	if ( ! in_array( $queried_page, $author_pages ) ) {
		return '';
	}

	return $queried_page;
}

/**
 * Adds the 'author_page' query var for use in queries.
 *
 * @param array $query_vars Array with public query vars.
 * @return array Array with public query vars.
 */
function query_vars( $query_vars ) {
	// Add a new query var to the public query vars.
	$query_vars[] = 'author_page';
	return $query_vars;
}

/**
 * Includes an extended author page theme template file if found.
 *
 * @param string $template The path of the template to include.
 * @return string The path of the template to include.
 */
function template_include( $template ) {

	$author_page = get_queried_author_page();
	if ( ! $author_page ) {
		return $template;
	}

	$author_template = get_query_template( "author-page-{ $author_page }" );

	if ( $author_template ) {
		$template = $author_template;
	}

	return $template;
}

/**
 * Updates the rewrite rules with extended author page rewrite rules.
 *
 * @param WP_Rewrite $wp_rewrite Current WP_Rewrite instance (passed by reference).
 */
function generate_rewrite_rules( $wp_rewrite ) {
	// Get the new author archive pages rewrite rules.
	$rules = get_rewrite_rules();

	if ( is_array( $wp_rewrite->rules ) && ! empty( $rules ) ) {
		// Add the author page rewrite rules.
		$wp_rewrite->rules = $rules + $wp_rewrite->rules;
	}
}

/**
 * Gets the rewrite rules needed for all the extended author archive pages.
 *
 * @return array Array with extended author archive pages rewrite rules.
 */
function get_rewrite_rules() {
	global $wp_rewrite;

	$author_permastruct = $wp_rewrite->get_author_permastruct();
	if ( ! $author_permastruct ) {
		return array();
	}

	$author_pages  = get_author_pages();
	$rewrite_rules = array();

	foreach ( $author_pages as $author_page ) {
		// Get the author page permastruct.
		$permastruct = "{$author_permastruct}/{$author_page}";

		// Generate the rewrite rules for this author page.
		$rules = $wp_rewrite->generate_rewrite_rules( $permastruct );

		// Get the rewrite rules for the author page only.
		$rules = get_author_page_rewrite_rules( $rules, $author_page );

		// Add the rewrite rules.
		$rewrite_rules = $rules + $rewrite_rules;
	}

	return $rewrite_rules;
}

/**
 * Gets the rewrite rules for a specific extended author archive page.
 *
 * @param array  $rules       Array with rewrite rules.
 * @param string $author_page query var for an extended author archive page.
 * @return array Array with author archive page rewrite rules
 */
function get_author_page_rewrite_rules( $rules, $author_page ) {
	global $wp_rewrite;

	// New author archive page rewrite rules.
	$author_page_rules = array();

	// Default match for an author archive query.
	$author_match = $wp_rewrite->index . '?author_name=' . $wp_rewrite->preg_index( 1 );

	foreach ( $rules as $rule => $match ) {
		// Check if the query var is in the rule
		if ( false === strpos( $rule, $author_page ) ) {
			continue;
		}

		// Check if the author match is in the match
		if ( false === strpos( $match, $author_match ) ) {
			continue;
		}

		// Add `author_page` query var to the match.
		$new_match = $author_match . "&author_page={$author_page}";

		// Update the match for the rewrite rule.
		$author_page_rules[ $rule ] = str_replace( $author_match, $new_match, $match );
	}

	return $author_page_rules;
}
