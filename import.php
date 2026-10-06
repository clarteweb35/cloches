<?php
/**
 * Import initial depuis l'ancien site Wix (Édifices > Import initial).
 *
 * L'import se fait par petits lots que le navigateur enchaîne tout seul : un hébergement mutualisé
 * limite la durée d'un appel, et chaque édifice télécharge sa photo.
 * Il peut être relancé sans risque : ce qui existe déjà n'est jamais écrasé ni dupliqué.
 */

defined( 'ABSPATH' ) || exit;

const CDB_IMPORT_LOT = 4; // édifices traités par appel

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=edifice', 'Import initial', 'Import initial', 'manage_options', 'cdb-import', 'cdb_import_page' );
} );

add_action( 'wp_ajax_cdb_import_lot', function () {
	check_ajax_referer( 'cdb_import' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Droits insuffisants.', 403 );
	}
	wp_send_json_success( cdb_import_lot( isset( $_POST['debut'] ) ? absint( $_POST['debut'] ) : 0 ) );
} );

/** Contenu de l'ancien site, préparé dans inc/inventaire.json. */
function cdb_inventaire() {
	static $data = null;
	if ( null === $data ) {
		$data = json_decode( file_get_contents( CDB_DIR . '/inc/inventaire.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $data;
}

function cdb_import_page() {
	$data  = cdb_inventaire();
	$deja  = get_option( 'cdb_import_date' );
	?>
	<div class="wrap">
		<h1>Import initial depuis l'ancien site</h1>
		<p>Cet outil crée en une fois :</p>
		<ul style="list-style:disc;margin-left:20px">
			<li>les départements ;</li>
			<li>les <strong><?php echo count( $data['edifices'] ); ?> édifices</strong> de l'ancien site, avec leur histoire, leurs cloches, leur vidéo, leurs sources et leur photo ;</li>
			<li>les pages (« Qui suis-je ? », « Carte », « Liens utiles »…), les mentions légales, le menu et la page « Actualités » ;</li>
			<li>les réglages de la page d'accueil.</li>
		</ul>
		<p>Cela prend quelques minutes : <strong>laissez cette page ouverte</strong> jusqu'au message « Import terminé ». Si elle se ferme en cours de route, relancez simplement l'import : il reprend là où il s'est arrêté, sans rien dupliquer ni écraser.</p>
		<?php if ( $deja ) : ?><p><em>Dernier import : <?php echo esc_html( $deja ); ?>.</em></p><?php endif; ?>
		<p><button type="button" class="button button-primary button-hero" id="cdb-import-go" data-nonce="<?php echo esc_attr( wp_create_nonce( 'cdb_import' ) ); ?>" data-total="<?php echo count( $data['edifices'] ); ?>">Lancer l'import</button></p>
		<p><progress id="cdb-import-barre" max="<?php echo count( $data['edifices'] ); ?>" value="0" style="width:100%;max-width:520px" hidden></progress></p>
		<p id="cdb-import-etat" aria-live="polite"></p>
		<ul id="cdb-import-journal" style="list-style:disc;margin-left:20px"></ul>
	</div>
	<?php
}

/** Un appel : la préparation générale la première fois, puis quelques édifices. */
function cdb_import_lot( $debut ) {
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 120 );
	}
	$data     = cdb_inventaire();
	$edifices = $data['edifices'];
	$journal  = array();

	if ( 0 === $debut ) {
		$journal = cdb_import_base( $data );
	}
	$terms = cdb_import_departements( $data );
	$fin   = min( count( $edifices ), $debut + CDB_IMPORT_LOT );
	for ( $n = $debut; $n < $fin; $n++ ) {
		$journal = array_merge( $journal, cdb_import_edifice( $edifices[ $n ], $terms, $data ) );
	}

	$termine = $fin >= count( $edifices );
	if ( $termine ) {
		update_option( 'cdb_import_date', wp_date( 'j F Y à H:i' ) );
		delete_transient( 'cdb_stats' );
		flush_rewrite_rules();
	}
	return array( 'suivant' => $fin, 'termine' => $termine, 'journal' => $journal );
}

/* ---------- Départements ---------- */

/** Les départements (créés s'ils n'existent pas) : numéro => identifiant du terme. */
function cdb_import_departements( $data ) {
	$terms = array();
	foreach ( $data['departements'] as $num => $nom ) {
		$t = term_exists( $nom, 'departement' );
		if ( ! $t ) {
			$t = wp_insert_term( $nom, 'departement', array( 'slug' => $num . '-' . sanitize_title( $nom ) ) );
		}
		if ( is_wp_error( $t ) ) {
			continue;
		}
		$terms[ (string) $num ] = (int) $t['term_id'];
		update_term_meta( $terms[ (string) $num ], 'cdb_numero', (string) $num );
	}
	return $terms;
}

/* ---------- Édifices ---------- */

/** Crée un édifice, ou complète sa photo si elle manquait à un import précédent. Renvoie les alertes éventuelles. */
function cdb_import_edifice( $e, $terms, $data ) {
	$libelle = $e['commune'] . ' — ' . $e['nom'];
	$existe  = get_posts( array( 'post_type' => 'edifice', 'post_status' => 'any', 'meta_key' => '_cdb_source', 'meta_value' => $e['slug'], 'fields' => 'ids', 'posts_per_page' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $existe ) {
		$id = $existe[0];
		if ( get_post_meta( $id, '_cdb_photo_a_reprendre', true ) && cdb_import_photo( $id, $e, $libelle ) ) {
			delete_post_meta( $id, '_cdb_photo_a_reprendre' );
		}
		return array();
	}

	$cloches = $e['cloches'] ?? array();
	$id      = wp_insert_post( array(
		'post_type'    => 'edifice',
		'post_status'  => 'publish',
		'post_title'   => $e['nom'],
		'post_name'    => sanitize_title( $libelle ),
		'post_content' => cdb_import_blocs( $e['histoire'] ?? array() ),
		'post_date'    => $e['date'] . ' 12:00:00',
	), true );
	if ( is_wp_error( $id ) ) {
		return array( 'Édifice non créé : ' . $libelle . ' (' . $id->get_error_message() . ')' );
	}
	update_post_meta( $id, '_cdb_source', $e['slug'] );
	update_post_meta( $id, '_cdb_commune', $e['commune'] );
	update_post_meta( $id, '_cdb_cloches', $cloches );
	update_post_meta( $id, '_cdb_video', $e['video'] ?? '' );
	update_post_meta( $id, '_cdb_sources', $e['sources'] ?? '' );
	if ( ! $cloches && empty( $e['histoire'] ) ) {
		// Rien n'a pu être repris : la fiche renvoie vers l'article d'origine en attendant.
		update_post_meta( $id, '_cdb_ancien_lien', trailingslashit( $data['ancien_site'] ) . 'post/' . rawurlencode( $e['slug'] ) );
	}
	if ( isset( $terms[ $e['dept'] ] ) ) {
		wp_set_object_terms( $id, $terms[ $e['dept'] ], 'departement' );
	}
	if ( ! empty( $e['image'] ) && ! cdb_import_photo( $id, $e, $libelle ) ) {
		update_post_meta( $id, '_cdb_photo_a_reprendre', 1 );
		return array( 'Photo non téléchargée pour ' . $libelle . ' : relancez l\'import à la fin pour la reprendre.' );
	}
	return array();
}

/** Texte de l'histoire : une liste de ["p", html] et ["h", niveau, html] devient des blocs WordPress. */
function cdb_import_blocs( $blocs ) {
	$html = '';
	foreach ( $blocs as $b ) {
		if ( 'h' === $b[0] ) {
			$niveau = (int) $b[1];
			$html  .= "<!-- wp:heading {\"level\":$niveau} -->\n<h$niveau class=\"wp-block-heading\">" . wp_kses_post( $b[2] ) . "</h$niveau>\n<!-- /wp:heading -->\n\n";
		} else {
			$html .= "<!-- wp:paragraph -->\n<p>" . wp_kses_post( $b[1] ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
	}
	return $html;
}

/** Photo principale ; pour une miniature YouTube sans version HD, on retombe sur la version standard. */
function cdb_import_photo( $post_id, $e, $legende ) {
	$urls = array( $e['image'] );
	if ( false !== strpos( $e['image'], 'maxresdefault' ) ) {
		$urls[] = str_replace( 'maxresdefault', 'hqdefault', $e['image'] );
	}
	foreach ( $urls as $url ) {
		$img = cdb_import_image( $url, $post_id, $legende, sanitize_title( $legende ) );
		if ( $img ) {
			set_post_thumbnail( $post_id, $img );
			return true;
		}
	}
	return false;
}

function cdb_import_image( $url, $post_id, $legende, $nom ) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = download_url( $url, 60 );
	if ( is_wp_error( $tmp ) ) {
		return 0;
	}
	$id = media_handle_sideload( array( 'name' => $nom . '.jpg', 'tmp_name' => $tmp ), $post_id, $legende );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $legende );
	return $id;
}

/* ---------- Préparation générale (premier appel) ---------- */

function cdb_import_base( $data ) {
	$journal = array();

	// Réglages d'accueil, sans écraser ce qui a déjà été saisi.
	foreach ( array( 'a_paraitre' => implode( "\n", $data['a_paraitre'] ), 'video_accueil' => $data['video_accueil'] ) as $cle => $val ) {
		if ( '' === (string) get_theme_mod( 'cdb_' . $cle, '' ) ) {
			set_theme_mod( 'cdb_' . $cle, $val );
		}
	}
	if ( ! get_theme_mod( 'cdb_hero_image' ) ) {
		$hero = cdb_import_image( $data['hero'], 0, 'Cloches dans un beffroi breton', 'bandeau-cloches' );
		if ( $hero ) {
			set_theme_mod( 'cdb_hero_image', wp_get_attachment_image_url( $hero, 'full' ) );
		} else {
			$journal[] = 'Photo du bandeau d\'accueil non téléchargée : à ajouter dans Apparence > Personnaliser.';
		}
	}

	// Pages.
	$pages = array_merge( $data['pages'], array(
		array( 'slug' => 'carte', 'titre' => 'Carte', 'html' => "<!-- wp:paragraph -->\n<p>Tous les édifices visités, repérés sur la carte. Cliquez sur un repère pour accéder à ses informations.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:html -->\n<iframe class=\"map-frame\" src=\"" . esc_url( $data['carte'] ) . "\" title=\"Carte des clochers visités\" loading=\"lazy\"></iframe>\n<!-- /wp:html -->" ),
		array( 'slug' => 'mentions-legales', 'titre' => 'Mentions légales', 'html' => cdb_import_contenu_mentions() ),
		array( 'slug' => 'actualites', 'titre' => 'Actualités', 'html' => '' ),
		array( 'slug' => 'accueil', 'titre' => 'Accueil', 'html' => '' ),
	) );
	$ids = array();
	foreach ( $pages as $p ) {
		$existante = get_page_by_path( $p['slug'] );
		if ( $existante ) {
			$ids[ $p['slug'] ] = $existante->ID;
			continue;
		}
		$ids[ $p['slug'] ] = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $p['slug'], 'post_title' => $p['titre'], 'post_content' => $p['html'] ) );
	}

	// Blog : la page « Actualités » et un premier article.
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['accueil'] );
		update_option( 'page_for_posts', $ids['actualites'] );
	}
	if ( ! get_page_by_path( 'bienvenue', OBJECT, 'post' ) ) {
		wp_insert_post( array( 'post_status' => 'publish', 'post_name' => 'bienvenue', 'post_title' => 'Bienvenue sur le nouveau site Cloches de Bretagne', 'post_content' => cdb_import_contenu_bienvenue() ) );
	}

	cdb_import_menus( $ids );

	// Contenu de démonstration installé par WordPress.
	foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $type ) {
		$demo = get_page_by_path( $slug, OBJECT, $type );
		if ( $demo ) {
			wp_delete_post( $demo->ID, true );
		}
	}

	// Réglages généraux de WordPress.
	update_option( 'blogname', 'Cloches de Bretagne' );
	update_option( 'blogdescription', 'Inventaire campanaire' );
	update_option( 'date_format', 'j F Y' );
	update_option( 'posts_per_page', 9 );
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	if ( '' === get_option( 'timezone_string' ) ) {
		update_option( 'timezone_string', 'Europe/Paris' );
	}

	$journal[] = 'Pages, menus, blog et réglages prêts.';
	return $journal;
}

/** Menus du haut et du bas du site, sauf si l'un a déjà été choisi dans Apparence > Menus. */
function cdb_import_menus( $ids ) {
	$emplacements = get_theme_mod( 'nav_menu_locations', array() );
	$menus        = array(
		'principal' => array(
			'Menu principal',
			array( 'accueil' => 'Accueil', '' => 'Les édifices', 'actualites' => 'Actualités', 'carte' => 'Carte', 'qui-suis-je' => 'Qui suis-je ?' ),
		),
		'pied'      => array(
			'Pied de page',
			array( 'liens-utiles' => 'Liens utiles', 'les-amis-des-cloches-en-bretagne' => 'Les amis des cloches en Bretagne', 'autres-edifices' => 'Autres édifices', 'mentions-legales' => 'Mentions légales' ),
		),
	);
	foreach ( $menus as $emplacement => list( $nom, $liens ) ) {
		if ( ! empty( $emplacements[ $emplacement ] ) ) {
			continue;
		}
		$menu = wp_get_nav_menu_object( $nom );
		if ( $menu ) {
			$menu_id = $menu->term_id;
		} else {
			$menu_id = wp_create_nav_menu( $nom );
			foreach ( $liens as $slug => $titre ) {
				// Une page du site, ou (clé vide) la liste des édifices.
				if ( '' !== (string) $slug && empty( $ids[ $slug ] ) ) {
					continue;
				}
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => $titre,
					'menu-item-status'    => 'publish',
					'menu-item-type'      => '' === (string) $slug ? 'post_type_archive' : 'post_type',
					'menu-item-object'    => '' === (string) $slug ? 'edifice' : 'page',
					'menu-item-object-id' => '' === (string) $slug ? 0 : $ids[ $slug ],
				) );
			}
		}
		$emplacements[ $emplacement ] = (int) $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $emplacements );
}

/* ---------- Textes rédigés pour le site ---------- */

function cdb_import_paragraphes( $textes ) {
	$html = '';
	foreach ( $textes as $t ) {
		$html .= "<!-- wp:paragraph -->\n<p>$t</p>\n<!-- /wp:paragraph -->\n\n";
	}
	return $html;
}

function cdb_import_titre( $texte ) {
	return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">$texte</h2>\n<!-- /wp:heading -->\n\n";
}

function cdb_import_contenu_bienvenue() {
	return cdb_import_paragraphes( array(
		'Les fiches des édifices, le relevé de chaque cloche, les vidéos et les albums photos sont désormais réunis sur un seul site, avec un catalogue que l\'on peut filtrer par département, par type d\'édifice ou par commune.',
		'Les nouvelles visites et les découvertes campanaires seront annoncées dans cette rubrique. Bonne visite !',
	) );
}

function cdb_import_contenu_mentions() {
	return cdb_import_titre( 'Éditeur du site' )
		. cdb_import_paragraphes( array(
			'Le site « Cloches de Bretagne » est édité, à titre non professionnel et bénévole, par <strong>' . esc_html( CDB_AUTEUR ) . '</strong>, qui en est le directeur de la publication.',
			'Contact : [cdb_contact].',
		) )
		. cdb_import_titre( 'Hébergement' )
		. cdb_import_paragraphes( array(
			'Le site est hébergé par OVH SAS, 2 rue Kellermann, 59100 Roubaix, France (<a href="https://www.ovhcloud.com" target="_blank" rel="noopener">www.ovhcloud.com</a>).',
		) )
		. cdb_import_titre( 'Reprise des contenus' )
		. cdb_import_paragraphes( array(
			'Les textes, les photos, les vidéos et les relevés de ce site sont l\'œuvre de ' . esc_html( CDB_AUTEUR ) . ', sauf mention contraire (notamment les sources citées au bas de chaque fiche).',
			'Leur reprise est <strong>libre et gratuite</strong>, à une seule condition : citer clairement le nom complet de l\'auteur, l\'adresse du site et sa chaîne YouTube, par exemple : <em>[cdb_credit]</em>.',
			'Sans cette mention, la reprise n\'est pas autorisée. Les albums photos liés depuis le site sont hébergés sur le service Zonerama et suivent les mêmes conditions.',
		) )
		. cdb_import_titre( 'Données personnelles et cookies' )
		. cdb_import_paragraphes( array(
			'Ce site ne contient ni publicité ni outil de mesure d\'audience, et ne dépose pas de cookie de suivi. Les polices de caractères sont hébergées sur le site lui-même.',
			'Les vidéos YouTube sont intégrées en mode de confidentialité renforcée (youtube-nocookie.com) : YouTube peut déposer des cookies lorsque vous lancez la lecture. Il en va de même pour la carte Google My Maps de la page « Carte ».',
			'Si vous écrivez à l\'éditeur, votre adresse e-mail est utilisée uniquement pour vous répondre et n\'est transmise à personne. Pour la faire effacer ou accéder aux informations conservées, écrivez au même contact.',
		) )
		. cdb_import_titre( 'Exactitude des informations' )
		. cdb_import_paragraphes( array(
			'Les poids, notes, dates et inscriptions sont relevés avec soin mais peuvent comporter des erreurs. N\'hésitez pas à les signaler via le contact ci-dessus.',
		) );
}
