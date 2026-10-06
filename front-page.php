<?php
get_header();

$stats   = cdb_stats();
$hero    = cdb_mod( 'hero_image' );
$video   = cdb_youtube_id( cdb_mod( 'video_accueil' ) );
$depts   = cdb_departements( true );
$recents = get_posts( array( 'post_type' => 'edifice', 'posts_per_page' => 5, 'post_status' => 'publish' ) );
$actus   = get_posts( array( 'posts_per_page' => 3 ) );
$prochains = array_filter( array_map( 'trim', explode( "\n", (string) cdb_mod( 'a_paraitre' ) ) ) );
?>
<main id="main">
	<section class="hero">
		<?php if ( $hero ) : ?><img src="<?php echo esc_url( $hero ); ?>" alt="" fetchpriority="high"><?php endif; ?>
		<div class="wrap">
			<span class="eyebrow"><?php echo esc_html( cdb_mod( 'hero_surtitre' ) ); ?></span>
			<h1><?php echo esc_html( cdb_mod( 'hero_titre' ) ); ?></h1>
			<p class="lead"><?php echo esc_html( cdb_mod( 'hero_texte' ) ); ?></p>
			<div class="hero-actions">
				<a class="btn btn-primary" href="<?php echo esc_url( get_post_type_archive_link( 'edifice' ) ); ?>">Explorer les édifices</a>
				<?php $carte = get_page_by_path( 'carte' ); if ( $carte ) : ?>
					<a class="btn btn-ghost" href="<?php echo esc_url( get_permalink( $carte ) ); ?>">Voir la carte</a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="stats"><div class="wrap">
		<div class="stat"><b><?php echo esc_html( number_format_i18n( $stats['cloches'] ) ); ?></b><span>cloches répertoriées</span></div>
		<div class="stat"><b><?php echo esc_html( number_format_i18n( $stats['edifices'] ) ); ?></b><span>édifices visités</span></div>
		<div class="stat"><b><?php echo count( $depts ); ?></b><span>départements</span></div>
		<div class="stat"><b>2015</b><span>première montée en clocher</span></div>
	</div></section>

	<?php if ( $recents ) : ?>
	<section class="section"><div class="wrap">
		<div class="section-head">
			<div><span class="eyebrow">Dernières publications</span><h2>Récemment ajoutés</h2></div>
			<a class="btn btn-line" href="<?php echo esc_url( get_post_type_archive_link( 'edifice' ) ); ?>">Tous les édifices</a>
		</div>
		<div class="cards cards-une">
			<?php foreach ( $recents as $p ) { cdb_carte_edifice( $p ); } ?>
		</div>
	</div></section>
	<?php endif; ?>

	<?php if ( $depts ) : ?>
	<section class="section alt"><div class="wrap">
		<div class="section-head">
			<div><span class="eyebrow">Parcourir</span><h2>Par département</h2></div>
			<p>Les départements de la Bretagne historique, et quelques escapades campanaires au-delà.</p>
		</div>
		<div class="depts">
			<?php foreach ( $depts as $t ) : ?>
				<a class="dept" href="<?php echo esc_url( get_term_link( $t ) ); ?>">
					<div class="num"><?php echo esc_html( cdb_numero( $t ) ); ?></div>
					<div class="name"><?php echo esc_html( $t->name ); ?></div>
					<div class="count"><?php echo esc_html( sprintf( _n( '%s édifice', '%s édifices', $t->count, 'cloches-bretagne' ), number_format_i18n( $t->count ) ) ); ?></div>
				</a>
			<?php endforeach; ?>
		</div>
	</div></section>
	<?php endif; ?>

	<?php if ( $actus ) : ?>
	<section class="section"><div class="wrap">
		<div class="section-head">
			<div><span class="eyebrow">Le blog</span><h2>Dernières actualités</h2></div>
			<a class="btn btn-line" href="<?php echo esc_url( cdb_url_actualites() ); ?>">Toutes les actualités</a>
		</div>
		<div class="cards cards-3">
			<?php foreach ( $actus as $p ) { cdb_carte_actualite( $p ); } ?>
		</div>
	</div></section>
	<?php endif; ?>

	<section class="section alt"><div class="wrap split">
		<div>
			<?php if ( $prochains ) : ?>
				<span class="eyebrow">À paraître</span><h2>Prochains clochers</h2>
				<p style="color:var(--ink-2)">Ces relevés ont été réalisés et seront publiés prochainement.</p>
				<ul class="upcoming">
					<?php foreach ( $prochains as $ligne ) :
						$num = preg_match( '/\((\d{2,3})\)\s*$/', $ligne, $m ) ? $m[1] : '';
						$nom = trim( preg_replace( '/\(\d{2,3}\)\s*$/', '', $ligne ) );
						?>
						<li><span class="badge"><?php echo esc_html( $num ?: '·' ); ?></span><span><?php echo esc_html( $nom ); ?></span><span class="soon">Bientôt</span></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<span class="eyebrow">Le projet</span><h2>Un inventaire vivant</h2>
				<p style="color:var(--ink-2)">Chaque visite de clocher enrichit l'inventaire : photos, mesures, inscriptions et enregistrements sonores.</p>
			<?php endif; ?>
		</div>
		<div class="yt-panel">
			<span class="eyebrow" style="color:#e6c79c">Sur YouTube</span>
			<h2>Écouter les sonneries</h2>
			<p>Chaque visite est filmée : volées, tintements et angélus, enregistrés au plus près des cloches.</p>
			<?php if ( $video ) : ?>
				<div class="video"><iframe src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $video ); ?>" title="Vidéo mise en avant" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>
			<?php endif; ?>
			<?php if ( cdb_mod( 'youtube' ) ) : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( cdb_mod( 'youtube' ) ); ?>" target="_blank" rel="noopener">S'abonner à la chaîne</a>
			<?php endif; ?>
		</div>
	</div></section>
</main>
<?php
get_footer();
