<?php
/**
 * Template Name: Photo Album Search page
 *
 * A custom page template without sidebar.
 *
 * The "Template Name:" bit above allows this to be selectable
 * from a dropdown menu on the edit page screen.
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 *
 * 8.8.08.001
 */
global $wppa_show_statistics;

get_header(); ?>

		<div id="container" class="one-column">
			<div id="content" role="main">

<?php /* wppa_statistics(); */ /* This would show the statistics at the to of the page */?>
<?php $wppa_show_statistics = true; /* This will show the statistics within the wppa-container */?>

<?php
	/* Display the search field */
	global $wppa;
		if ( !isset($wppa['searchstring']) ) $wppa['searchstring'] = '';
		$page = get_option('wppa_search_linkpage', '0');
		if ($page == '0') {
			esc_attr_e('Warning. No page defined for search results!', 'wp-photo-album-plus');
		}
		else {
			if ( function_exists('wppa_dbg_url') ) $pagelink = wppa_dbg_url(get_page_link($page));
			else $pagelink = get_page_link($page);

			echo '
			<form id="wppa_searchform" action="'. esc_attr( $pagelink ) , '" method="post" class="widget_search">
				<div>
					<input type="text" name="wppa-searchstring" id="wppa_s" value="' . esc_attr( $wppa['searchstring'] ) . '" />
					<input id = "wppa_searchsubmit" type="submit" value="' . esc_attr( 'Search', 'wppa_theme' ) . '" />
				</div>
			</form>';
		}


		if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<div class="entry-content">
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'wp-photo-album-plus' ), 'after' => '</div>' ) ); ?>
						<?php edit_post_link( __( 'Edit', 'wp-photo-album-plus' ), '<span class="edit-link">', '</span>' ); ?>
					</div><!-- .entry-content -->
				</div><!-- #post-## -->
				<?php /*do_action( 'addthis_widget' ); */?>
				<?php comments_template( '', true ); ?>

<?php endwhile; ?>

			</div><!-- #content -->
		</div><!-- #container -->
		<script type="text/javascript">
		/* <![CDATA[	*/
		jQuery(document).ready(function(){
			jQuery('#wppa-container-1').css('background-color', 'black');
			jQuery('#wppa-container-1').css('padding', '80px');
			jQuery('#wppa-container-1').css('margin-left', '-80px');
//			jQuery('.wppa-fulldesc').css('color', '#eef7e6');
//			jQuery('.wppa-fulltitle').css('color', '#eef7e6');
//	jQuery('.wppa-nav').css('background-color', '#ccc');
	});
	/* ]]> */
		</script>

<?php get_footer(); ?>
