<?php
/**
 * The template for displaying Search Results pages.
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 *
 * ver 8.7.03.006
 */

 /* Rename this file to search.php and replace search.php from theme twentyten by this file */

get_header(); ?>

		<div id="container">
			<div id="content" role="main">

<?php
	$have_photos = function_exists('wppa_have_photos') && wppa_have_photos('450');
	$have_posts  = have_posts();
	if ( $have_posts || $have_photos ) {
		$s = '<span>' . get_search_query() . '</span>';
		$title = esc_html__( 'Search Results for:', 'wp-photo-album-plus' ) . ' ' . esc_html( $s );
		?>
		<h1 class="page-title">
			<?php echo esc_html( wppa_qt( $title ) ); ?>
		</h1>
				<?php
				/* Run the loop for the search to output the results.
				 * If you want to overload this in a child theme then include a file
				 * called loop-search.php and that will be used instead.
				 */
				if ( $have_posts )  get_template_part( 'loop', 'search' );
				if ( $have_photos ) wppa_the_photos();
	}
	else { ?>
				<div id="post-0" class="post no-results not-found">
					<h2 class="entry-title"><?php esc_html_e( 'Nothing Found', 'wp-photo-album-plus' ); ?></h2>
					<div class="entry-content">
						<p><?php esc_html_e( 'Sorry, but nothing matched your search criteria. Please try again with some different keywords.', 'wp-photo-album-plus' ); ?></p>
						<?php get_search_form(); ?>
					</div><!-- .entry-content -->
				</div><!-- #post-0 -->
	<?php } ?>
			</div><!-- #content -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
