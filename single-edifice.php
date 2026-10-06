<?php
get_header();

while ( have_posts() ) :
	the_post();
	$id      = get_the_ID();
	$commune = cdb_commune( $id );
	$dept    = cdb_edifice_dept( $id );
	$cloches = cdb_cloches( $id );
	$video   = cdb_youtube_id( get_post_meta( $id, '_cdb_video', true ) );
	$ancien  = get_post_meta( $id, '_cdb_ancien_lien', true );
	$histoire = trim( get_the_content() );
	$poids   = array_filter( array_map( 'floatval', wp_list_pluck( $cloches, 'poids' ) ) );
	$max     = $poids ? max( $poids ) : 0;
	$notes   = array_filter( wp_list_pluck( $cloches, 'note' ) );
	$archive = get_post_type_archive_link( 'edifice' );
	$sources = trim( (string) get_post_meta( $id, '_cdb_sources', true ) );
	?>
<main id="main">
	<div class="wrap"><div class="fiche-hero<?php echo has_post_thumbnail() ? '' : ' sans-photo'; ?>">
		<div>
			<div class="crumbs">
				<a href="<?php echo esc_url( $archive ); ?>">Édifices</a>
				<?php if ( $dept ) : ?> › <a href="<?php echo esc_url( get_term_link( $dept ) ); ?>"><?php echo esc_html( $dept->name ); ?></a><?php endif; ?>
				› <?php echo esc_html( $commune ); ?>
			</div>
			<?php if ( $dept ) : ?><span class="eyebrow"><?php echo esc_html( cdb_dept_label( $dept ) ); ?></span><?php endif; ?>
			<h1><?php echo esc_html( $commune ); ?></h1>
			<p class="lead"><?php the_title(); ?></p>
			<?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
			<?php if ( $cloches ) : ?>
				<div class="facts">
					<div><b><?php echo count( $cloches ); ?></b><span><?php echo 1 === count( $cloches ) ? 'cloche' : 'cloches'; ?></span></div>
					<?php if ( $poids ) : ?><div><b><?php echo esc_html( cdb_nombre( array_sum( $poids ) ) ); ?> kg</b><span>de bronze</span></div><?php endif; ?>
					<?php if ( $notes ) : ?><div><b><?php echo esc_html( implode( ' · ', $notes ) ); ?></b><span>accord</span></div><?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large', array( 'class' => 'fiche-photo' ) ); } ?>
	</div></div>

	<?php if ( ! $cloches && '' === $histoire && $ancien ) : ?>
		<div class="wrap" style="padding-bottom:88px">
			<div class="notice">
				<h3>Fiche en cours de mise en ligne</h3>
				<p>Le relevé complet de cet édifice est consultable sur l'ancien site en attendant sa publication ici.</p>
				<a class="btn btn-primary" href="<?php echo esc_url( $ancien ); ?>" target="_blank" rel="noopener">Voir l'article <?php echo cdb_icon( 'fleche' ); // phpcs:ignore ?></a>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $video ) : ?>
	<section class="section alt"><div class="wrap">
		<span class="eyebrow">Écouter</span><h2>La sonnerie en vidéo</h2>
		<div class="video"><iframe src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $video ); ?>" title="<?php echo esc_attr( 'Sonnerie — ' . $commune ); ?>" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>
	</div></section>
	<?php endif; ?>

	<?php if ( '' !== $histoire ) : ?>
	<section class="section"><div class="wrap">
		<span class="eyebrow">Histoire</span><h2>L'édifice</h2>
		<div class="prose entry"><?php the_content(); ?></div>
	</div></section>
	<?php endif; ?>

	<?php if ( $cloches ) : ?>
	<section class="section alt"><div class="wrap">
		<span class="eyebrow">Relevé campanaire</span><h2>Les cloches</h2>
		<div class="table-scroll" style="margin:28px 0 32px"><table class="compare">
			<thead><tr><th>#</th><th>Nom</th><th>Note</th><th>Poids</th><th>Diamètre</th><th>Fondeur</th><th style="width:20%"><span class="screen-reader-text">Poids relatif</span></th></tr></thead>
			<tbody>
				<?php foreach ( $cloches as $i => $c ) : ?>
					<tr>
						<td><?php echo (int) $i + 1; ?></td>
						<td><b><?php echo esc_html( $c['nom'] ); ?></b></td>
						<td><?php echo esc_html( $c['note'] ); ?></td>
						<td class="num"><?php echo '' !== $c['poids'] ? esc_html( cdb_nombre( $c['poids'] ) . ' kg' ) : '—'; ?></td>
						<td class="num"><?php echo '' !== $c['diametre'] ? esc_html( cdb_nombre( $c['diametre'] ) . ' mm' ) : '—'; ?></td>
						<td><?php echo esc_html( $c['fondeur'] ); ?></td>
						<td><?php if ( $max && '' !== $c['poids'] ) : ?><div class="bar"><i style="width:<?php echo esc_attr( round( $c['poids'] / $max * 100 ) ); ?>%"></i></div><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table></div>

		<div class="bells">
			<?php foreach ( $cloches as $i => $c ) : ?>
				<article class="bell">
					<div class="bell-top"><h3><?php echo esc_html( $c['nom'] ?: 'Cloche ' . ( $i + 1 ) ); ?></h3><span class="note"><?php echo esc_html( $c['note'] ); ?></span></div>
					<div class="sub">Cloche <?php echo (int) $i + 1; ?><?php echo $c['annee'] ? ' · ' . esc_html( $c['annee'] ) : ''; ?></div>
					<dl>
						<?php
						$lignes = array(
							'Fondeur'     => $c['fondeur'],
							'Poids'       => '' !== $c['poids'] ? cdb_nombre( $c['poids'] ) . ' kg' : '',
							'Diamètre'    => '' !== $c['diametre'] ? cdb_nombre( $c['diametre'] ) . ' mm' : '',
							'Parrainage'  => $c['parrains'],
							'Remarque'    => $c['remarque'],
						);
						foreach ( $lignes as $label => $val ) :
							if ( '' === (string) $val ) {
								continue;
							}
							?>
							<dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $val ); ?></dd>
						<?php endforeach; ?>
					</dl>
					<?php if ( $c['inscription'] ) : ?><blockquote><?php echo nl2br( esc_html( $c['inscription'] ) ); ?></blockquote><?php endif; ?>
					<?php if ( $c['album'] ) : ?><a class="btn btn-line" href="<?php echo esc_url( $c['album'] ); ?>" target="_blank" rel="noopener">Voir les photos</a><?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div></section>
	<?php endif; ?>

	<?php if ( '' !== $sources ) : ?>
	<section class="section"><div class="wrap">
		<span class="eyebrow">Pour aller plus loin</span><h2>Sources et remerciements</h2>
		<div class="prose sources"><?php echo wp_kses_post( wpautop( make_clickable( esc_html( $sources ) ) ) ); ?></div>
	</div></section>
	<?php endif; ?>
</main>
	<?php
endwhile;

get_footer();
