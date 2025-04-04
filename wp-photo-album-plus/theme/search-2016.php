<?php
/**
 * The template for displaying search results pages
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 *
 * ver 8.7.03.006
 */

 /* Rename this file to search.php and replace search.php from theme twentysixteen by this file */

get_header(); ?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php /* if ( have_posts() ) : */ ?>
		<?php $have_photos = function_exists('wppa_have_photos') && wppa_have_photos(); ?>
		<?php $have_posts = have_posts(); ?>
		<?php if ( $have_posts || $have_photos ) {
			$s = '<span>' . get_search_query() . '</span>';
			$title = esc_html__( 'Search Results for:', 'wp-photo-album-plus' ) . ' ' . esc_html( $s );
			?>
			<header class="page-header">
				<h1 class="page-title">
					<?php echo esc_html( wppa_qt( $title ) ); ?>
				</h1>
			</header><!-- .page-header -->

			<?php
			// Start the loop.
			if ( $have_posts ) while ( have_posts() ) : the_post();


				/**
				 * Run the loop for the search to output the results.
				 * If you want to overload this in a child theme then include a file
				 * called content-search.php and that will be used instead.
				 */
				get_template_part( 'template-parts/content', 'search' );


			// End the loop.
			endwhile;

			if ( $have_photos ) wppa_the_photos();

			// Previous/next page navigation.
			the_posts_pagination( array(
				'prev_text'          => __( 'Previous page', 'wp-photo-album-plus' ),
				'next_text'          => __( 'Next page', 'wp-photo-album-plus' ),
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'wp-photo-album-plus' ) . ' </span>',
			) );

		// If no content, include the "No posts found" template.
		}
		else {
			get_template_part( 'template-parts/content', 'none' );
		}
		?>

		</main><!-- .site-main -->
	</section><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
