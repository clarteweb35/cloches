<?php
/**
 * Une actualité (article du blog).
 */

get_header();

while ( have_posts() ) :
	the_post();
	cdb_page_head( get_the_date(), get_the_title() );
	?>
	<main id="main" class="wrap section">
		<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large', array( 'class' => 'fiche-photo actu-photo' ) ); } ?>
		<div class="entry"><?php the_content(); ?></div>
		<?php if ( cdb_url_actualites() ) : ?>
			<p class="retour"><a href="<?php echo esc_url( cdb_url_actualites() ); ?>">← Toutes les actualités</a></p>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer();
