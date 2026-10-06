<?php
$email = cdb_mod( 'email' );
?>
<footer class="site-footer"><div class="wrap">
	<div class="footer-grid">
		<div>
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo cdb_icon( 'cloche' ); // phpcs:ignore ?><span class="brand-name"><?php bloginfo( 'name' ); ?></span></a>
			<p>L'inventaire des cloches des églises et chapelles de Bretagne, relevées clocher par clocher : fondeurs, notes, poids, inscriptions et enregistrements.</p>
		</div>
		<div>
			<h4>Départements</h4>
			<ul>
				<?php foreach ( cdb_departements( true ) as $t ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( cdb_dept_label( $t ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div>
			<h4>Ressources</h4>
			<ul>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'pied',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'depth'          => 1,
					'fallback_cb'    => 'cdb_menu_pied_fallback',
				) );
				?>
			</ul>
		</div>
		<div>
			<h4>Suivre &amp; contact</h4>
			<ul>
				<?php if ( cdb_mod( 'youtube' ) ) : ?><li><a href="<?php echo esc_url( cdb_mod( 'youtube' ) ); ?>" target="_blank" rel="noopener">Chaîne YouTube</a></li><?php endif; ?>
				<?php if ( cdb_mod( 'facebook' ) ) : ?><li><a href="<?php echo esc_url( cdb_mod( 'facebook' ) ); ?>" target="_blank" rel="noopener">Facebook</a></li><?php endif; ?>
				<?php if ( $email ) : ?><li><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></li><?php endif; ?>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'edifice' ) ); ?>">Tous les édifices</a></li>
				<?php if ( cdb_url_actualites() ) : ?><li><a href="<?php echo esc_url( cdb_url_actualites() ); ?>">Actualités</a></li><?php endif; ?>
			</ul>
		</div>
	</div>
	<div class="footer-bottom">
		<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( CDB_AUTEUR ); ?> · <?php bloginfo( 'name' ); ?></span>
		<span>Reprise libre en citant : <?php echo cdb_credit(); // phpcs:ignore ?>. <?php $mentions = get_page_by_path( 'mentions-legales' ); if ( $mentions ) : ?><a href="<?php echo esc_url( get_permalink( $mentions ) ); ?>">Mentions légales</a><?php endif; ?></span>
	</div>
</div></footer>
<?php wp_footer(); ?>
</body>
</html>
