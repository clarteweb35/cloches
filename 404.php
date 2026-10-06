<?php
get_header();
cdb_page_head( 'Erreur 404', 'Cette page n\'existe pas' );
?>
<main id="main" class="wrap section">
	<p>La page demandée a peut-être été déplacée. Retrouvez tous les clochers dans l'inventaire.</p>
	<p><a class="btn btn-primary" href="<?php echo esc_url( get_post_type_archive_link( 'edifice' ) ); ?>">Voir les édifices</a></p>
</main>
<?php
get_footer();
