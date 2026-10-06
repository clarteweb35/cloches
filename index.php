<?php
/**
 * Gabarit de secours (recherche, archives diverses).
 */

get_header();

$titre = is_search() ? 'Recherche : ' . get_search_query() : wp_strip_all_tags( get_the_archive_title() );
cdb_page_head( '', $titre ?: get_bloginfo( 'name' ) );
?>
<main id="main" class="wrap section">
	<?php if ( have_posts() ) : ?>
		<ul class="list">
			<?php while ( have_posts() ) : the_post(); ?>
				<li><a href="<?php the_permalink(); ?>"><span>
					<span class="commune"><?php echo esc_html( 'edifice' === get_post_type() ? cdb_commune( get_the_ID() ) : get_the_title() ); ?></span>
					<?php if ( 'edifice' === get_post_type() ) : ?><span class="edifice"><?php the_title(); ?></span><?php endif; ?>
				</span></a></li>
			<?php endwhile; ?>
		</ul>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p class="empty">Aucun résultat.</p>
	<?php endif; ?>
</main>
<?php
get_footer();
