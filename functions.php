<?php
/**
 * Cloches de Bretagne — thème sur mesure.
 */

defined( 'ABSPATH' ) || exit;

define( 'CDB_VERSION', '1.1.0' );
define( 'CDB_DIR', get_template_directory() );
define( 'CDB_URI', get_template_directory_uri() );
define( 'CDB_AUTEUR', 'Vincent Crochu-Lozac\'hmeur' );
define( 'CDB_CHAINE', 'Vincent l\'amoureux des cloches' );

require CDB_DIR . '/inc/helpers.php';
require CDB_DIR . '/inc/edifices.php';
require CDB_DIR . '/inc/customizer.php';
require CDB_DIR . '/inc/admin.php';
require CDB_DIR . '/inc/import.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'cloches-bretagne' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_image_size( 'cdb-carte', 960, 640, true );
	register_nav_menus( array(
		'principal' => 'Menu principal',
		'pied'      => 'Pied de page (ressources)',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'cdb-main', CDB_URI . '/assets/css/main.css', array(), CDB_VERSION );
	wp_enqueue_script( 'cdb-site', CDB_URI . '/assets/js/site.js', array(), CDB_VERSION, array( 'strategy' => 'defer' ) );
} );

add_action( 'wp_head', function () {
	echo '<link rel="icon" href="' . esc_url( CDB_URI . '/assets/favicon.svg' ) . '" type="image/svg+xml">' . "\n";
}, 2 );

/* Le site se maintient seul : WordPress, extensions et thèmes se mettent à jour automatiquement. */
add_filter( 'allow_major_auto_core_updates', '__return_true' );
add_filter( 'auto_update_plugin', '__return_true' );
add_filter( 'auto_update_theme', '__return_true' );

/* Description pour les moteurs de recherche et aperçu lors d'un partage (Facebook, messageries). */
add_action( 'wp_head', function () {
	$objet = is_singular() ? get_queried_object() : null;
	if ( $objet ) {
		$titre = get_the_title( $objet );
		$desc  = has_excerpt( $objet ) ? get_the_excerpt( $objet ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $objet->post_content ) ), 30 );
		$image = get_the_post_thumbnail_url( $objet, 'large' );
		$url   = get_permalink( $objet );
	} else {
		$titre = wp_get_document_title();
		$desc  = cdb_mod( 'hero_texte' );
		$image = cdb_mod( 'hero_image' );
		$url   = home_url( '/' );
	}
	$meta = array(
		'og:type'      => $objet ? 'article' : 'website',
		'og:site_name' => get_bloginfo( 'name' ),
		'og:locale'    => 'fr_FR',
		'og:title'     => $titre,
		'og:description' => $desc,
		'og:url'       => $url,
		'og:image'     => $image,
	);
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	foreach ( array_filter( $meta ) as $propriete => $valeur ) {
		echo '<meta property="' . esc_attr( $propriete ) . '" content="' . esc_attr( $valeur ) . '">' . "\n";
	}
}, 5 );

/* Menu par défaut tant qu'aucun menu n'a été créé dans Apparence > Menus. */
function cdb_menu_fallback() {
	$liens = array(
		array( home_url( '/' ), 'Accueil', is_front_page() ),
		array( get_post_type_archive_link( 'edifice' ), 'Les édifices', is_post_type_archive( 'edifice' ) || is_singular( 'edifice' ) || is_tax( 'departement' ) ),
	);
	if ( cdb_url_actualites() ) {
		$liens[] = array( cdb_url_actualites(), 'Actualités', is_home() || is_singular( 'post' ) );
	}
	foreach ( array( 'carte' => 'Carte', 'qui-suis-je' => 'Qui suis-je ?' ) as $slug => $titre ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$liens[] = array( get_permalink( $page ), $titre, is_page( $page->ID ) );
		}
	}
	foreach ( $liens as list( $url, $titre, $actif ) ) {
		printf( '<li><a href="%s"%s>%s</a></li>', esc_url( $url ), $actif ? ' aria-current="page"' : '', esc_html( $titre ) );
	}
}

/* Liens du pied de page tant qu'aucun menu « Pied de page » n'a été créé. */
function cdb_menu_pied_fallback() {
	$liens = array( 'liens-utiles' => 'Liens utiles', 'les-amis-des-cloches-en-bretagne' => 'Les amis des cloches en Bretagne', 'autres-edifices' => 'Autres édifices' );
	foreach ( $liens as $slug => $titre ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $page ) ), esc_html( $titre ) );
		}
	}
}
