<?php
/**
 * Page « Actualités » : la liste des articles du blog.
 */

get_header();

cdb_page_head( 'Le blog', 'Actualités', 'Les nouvelles du site et de la chaîne : nouveaux clochers, visites et découvertes.' );
?>
<main id="main" class="wrap section">
	<?php if ( have_posts() ) : ?>
		<div class="cards cards-3">
			<?php while ( have_posts() ) { the_post(); cdb_carte_actualite( get_post() ); } ?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '← Plus récentes', 'next_text' => 'Plus anciennes →' ) ); ?>
	<?php else : ?>
		<p class="empty">Aucune actualité pour le moment.</p>
	<?php endif; ?>
</main>
<?php
get_footer();
