<?php
/**
 * Fonctions utilitaires partagées par les gabarits.
 */

defined( 'ABSPATH' ) || exit;

function cdb_icon( $nom ) {
	$icons = array(
		'cloche' => '<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 3v3"/><path d="M9 22c0-8 1-14 7-14s7 6 7 14"/><path d="M6 22h20l-1.5 3h-17z"/><circle cx="16" cy="28" r="1.6"/></svg>',
		'menu'   => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
		'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
		'fleche' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
	);
	return $icons[ $nom ] ?? '';
}

/* ---------- Départements ---------- */

function cdb_numero( $term ) {
	return $term ? (string) get_term_meta( $term->term_id, 'cdb_numero', true ) : '';
}

function cdb_dept_label( $term ) {
	$n = cdb_numero( $term );
	return ( '' !== $n ? $n . ' · ' : '' ) . $term->name;
}

/** Tous les départements, triés par numéro. */
function cdb_departements( $hide_empty = false ) {
	$terms = get_terms( array( 'taxonomy' => 'departement', 'hide_empty' => $hide_empty ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort( $terms, function ( $a, $b ) {
		return strnatcmp( cdb_numero( $a ) ?: '999', cdb_numero( $b ) ?: '999' );
	} );
	return $terms;
}

function cdb_edifice_dept( $post_id ) {
	$terms = get_the_terms( $post_id, 'departement' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/* ---------- Édifices ---------- */

function cdb_commune( $post_id ) {
	$c = get_post_meta( $post_id, '_cdb_commune', true );
	return '' !== $c ? $c : get_the_title( $post_id );
}

/** Les cloches d'un édifice, chaque cloche ayant toujours tous ses champs. */
function cdb_cloches( $post_id ) {
	$c = get_post_meta( $post_id, '_cdb_cloches', true );
	if ( ! is_array( $c ) ) {
		return array();
	}
	$vide = array_fill_keys( array_keys( CDB_CHAMPS_CLOCHE ), '' );
	return array_values( array_map( function ( $cloche ) use ( $vide ) {
		return array_merge( $vide, array_intersect_key( (array) $cloche, $vide ) );
	}, $c ) );
}

function cdb_type( $titre ) {
	$t = strtolower( remove_accents( $titre ) );
	if ( 0 === strpos( $t, 'chapelle' ) || 0 === strpos( $t, 'prieure' ) ) {
		return 'Chapelle';
	}
	if ( 0 === strpos( $t, 'cathedrale' ) || 0 === strpos( $t, 'basilique' ) ) {
		return 'Cathédrale';
	}
	if ( 0 === strpos( $t, 'abbaye' ) || false !== strpos( $t, 'abbatiale' ) || 0 === strpos( $t, 'couvent' ) || 0 === strpos( $t, 'augustines' ) ) {
		return 'Abbaye & couvent';
	}
	return 'Église';
}

function cdb_youtube_id( $url ) {
	return preg_match( '~(?:youtu\.be/|[?&]v=|/embed/|/shorts/|/live/)([\w-]{11})~', (string) $url, $m ) ? $m[1] : '';
}

function cdb_nombre( $n ) {
	return '' === $n || null === $n ? '' : number_format_i18n( (float) $n, floor( $n ) == $n ? 0 : 1 );
}

/** Tous les édifices publiés, triés par commune (ordre alphabétique sans accents). */
function cdb_tous_edifices() {
	$posts = get_posts( array(
		'post_type'      => 'edifice',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'no_found_rows'  => true,
	) );
	$cle = array();
	foreach ( $posts as $p ) {
		$cle[ $p->ID ] = strtolower( remove_accents( cdb_commune( $p->ID ) . ' ' . $p->post_title ) );
	}
	usort( $posts, function ( $a, $b ) use ( $cle ) {
		return strnatcmp( $cle[ $a->ID ], $cle[ $b->ID ] );
	} );
	return $posts;
}

/** Chiffres clés : valeur saisie dans le personnalisateur, sinon calcul automatique. */
function cdb_stats() {
	$auto = get_transient( 'cdb_stats' );
	if ( false === $auto ) {
		$ids  = get_posts( array( 'post_type' => 'edifice', 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'publish' ) );
		$auto = array( 'edifices' => count( $ids ), 'cloches' => 0 );
		foreach ( $ids as $id ) {
			$auto['cloches'] += count( cdb_cloches( $id ) );
		}
		set_transient( 'cdb_stats', $auto, DAY_IN_SECONDS );
	}
	return array(
		'cloches'  => (int) ( cdb_mod( 'nb_cloches' ) ?: $auto['cloches'] ),
		'edifices' => (int) ( cdb_mod( 'nb_edifices' ) ?: $auto['edifices'] ),
	);
}
add_action( 'save_post_edifice', function () { delete_transient( 'cdb_stats' ); } );
add_action( 'deleted_post', function () { delete_transient( 'cdb_stats' ); } );

/* ---------- Gabarits ---------- */

/** Carte cliquable : photo, étiquette, titre et ligne de détail. */
function cdb_carte( $post, $etiquette, $titre, $detail ) {
	$image = get_the_post_thumbnail_url( $post, 'cdb-carte' );
	?>
	<a class="card" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
		<?php if ( $image ) : ?>
			<div class="card-img" style="background-image:url('<?php echo esc_url( $image ); ?>')" role="img" aria-label="<?php echo esc_attr( $post->post_title ); ?>"></div>
		<?php else : ?>
			<div class="card-img card-img-vide"><?php echo cdb_icon( 'cloche' ); // phpcs:ignore ?></div>
		<?php endif; ?>
		<div class="card-body">
			<?php if ( $etiquette ) : ?><span class="tag"><?php echo esc_html( $etiquette ); ?></span><?php endif; ?>
			<h3><?php echo esc_html( $titre ); ?></h3>
			<div class="card-meta"><?php echo esc_html( $detail ); ?></div>
		</div>
	</a>
	<?php
}

function cdb_carte_edifice( $post ) {
	$dept = cdb_edifice_dept( $post->ID );
	cdb_carte( $post, $dept ? cdb_dept_label( $dept ) : '', cdb_commune( $post->ID ), $post->post_title );
}

function cdb_carte_actualite( $post ) {
	cdb_carte( $post, get_the_date( '', $post ), $post->post_title, wp_trim_words( get_the_excerpt( $post ), 24 ) );
}

function cdb_page_head( $eyebrow, $titre, $intro = '' ) {
	?>
	<div class="page-head"><div class="wrap">
		<?php if ( $eyebrow ) : ?><span class="eyebrow"><?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
		<h1><?php echo esc_html( $titre ); ?></h1>
		<?php if ( $intro ) : ?><p><?php echo esc_html( $intro ); ?></p><?php endif; ?>
	</div></div>
	<?php
}

/* ---------- Auteur et reprise des contenus ---------- */

/** Mention à citer en cas de reprise : nom complet, site et chaîne YouTube (seule condition posée par l'auteur). */
function cdb_credit() {
	$youtube = cdb_mod( 'youtube' );
	return sprintf(
		'%s — <a href="%s">%s</a>%s',
		esc_html( CDB_AUTEUR ),
		esc_url( home_url( '/' ) ),
		esc_html( preg_replace( '~^https?://~', '', untrailingslashit( home_url() ) ) ),
		$youtube ? sprintf( ' — chaîne YouTube <a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $youtube ), esc_html( CDB_CHAINE ) ) : ''
	);
}
add_shortcode( 'cdb_credit', 'cdb_credit' );

/** Moyen de contact : l'e-mail du personnalisateur, sinon la chaîne YouTube. */
function cdb_contact() {
	$email = cdb_mod( 'email' );
	if ( $email ) {
		return sprintf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( antispambot( $email ) ) );
	}
	return sprintf( '<a href="%s" target="_blank" rel="noopener">message sur la chaîne YouTube</a>', esc_url( cdb_mod( 'youtube' ) ) );
}
add_shortcode( 'cdb_contact', 'cdb_contact' );

/** Adresse de la page « Actualités », ou chaîne vide si elle n'existe pas encore. */
function cdb_url_actualites() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : '';
}
