# Author Archive Extended

Version:           1.0.0  
Requires at least: 4.0  
Tested up to:      4.9  

Add extra author pages to your WordPress author archive page.

The default WordPress permalink for author archives is `/author/author-name`. This script let's you extend this permalink with extra author pages. For example, `/author/author-name/location`.

## Installation
Include the file `author-archive-extended.php` in your project (plugin/theme) and add the extra pages with the `author_archive_extended_pages` filter.

**Note** Use a [child theme](https://developer.wordpress.org/themes/advanced-topics/child-themes/) or [create a plugin](https://wordpress.org/plugins/pluginception/) if you want to include it with your theme. If you upgrade the theme all your modifications will be lost.

```php
// Include the file in your project
include 'author-archive-extended.php';
```

**Adding pages**

You can add the extra pages with the `author-archive-extended.php` filter.

```php
// Use the filter to add extra author archive pages. 
add_filter( 'author_archive_extended_pages', 'my_add_extended_author_pages');

function my_add_extended_author_pages( $pages ) {
	$pages[] = 'location';
	$pages[] = 'recipes';

	return $pages;
}
```

**Note**: You'll have to re-save your permalink structure in `wp-admin` -> `Settings` -> `Permalinks` before you can visit the pages.

Now you should be able to visit the pages without getting a 404.

You still need to update what posts are displayed there by:
* Using the [pre_get_posts](https://developer.wordpress.org/reference/hooks/pre_get_posts/) action
* Or using a custom query ([WP_Query](https://developer.wordpress.org/reference/classes/wp_query/)) in an author theme template file.

Example using `pre_get_posts`

```php
add_action( 'pre_get_posts', 'my_author_archive_pages_queries' );

function my_author_archive_pages_queries( $query ) {

	/*
	 * Bail early if:
	 *     It's a query in the wp-admin
	 *     It's not the main query for an author archive page
	 */
	if ( is_admin() || ! ( $query->is_main_query() && is_author() ) ) {
		return;
	}

	// Get the current author archive page.
	$author_page = $query->get( 'author_page' );

	// Check what author archive page we're on.
	if ( ! $author_page ) {
		// We're on the normal author archive page: /author/author-name
		// Use your own query here.
	}

	if ( 'location' === $author_page ) {
		// We're on the 'location' author archive page: /author/author-name/location
		// Use your own query here.

		// Example setting the post type.
		$query->set( 'post_type', array( 'location' ) );
	}
}
```

## Theme template files
The normal (author) [template hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/#author-display) is used to display the author archive and 
extended pages. To use specific template files create template files like
`author-page-{page-slug}.php` in your child theme. If the page slug is `location`, WordPress will look for `author-page-location.php`.
