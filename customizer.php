<?php
/**
 * Réglages modifiables dans Apparence > Personnaliser > « Cloches de Bretagne ».
 */

defined( 'ABSPATH' ) || exit;

function cdb_defauts() {
	return array(
		'hero_image'    => '',
		'hero_surtitre' => 'Inventaire campanaire · depuis 2015',
		'hero_titre'    => 'Les voix de bronze de Bretagne',
		'hero_texte'    => 'Clocher après clocher, le relevé des cloches des églises et chapelles bretonnes : leur histoire, leurs fondeurs, leurs inscriptions, et le son de leurs volées.',
		'nb_cloches'    => '',
		'nb_edifices'   => '',
		'a_paraitre'    => '',
		'video_accueil' => '',
		'youtube'       => 'https://www.youtube.com/channel/UCS46PbsI2C1U1CsxqMljhxg',
		'facebook'      => 'https://www.facebook.com/profile.php?id=100012749742581',
		'email'         => '',
	);
}

function cdb_mod( $cle ) {
	$defauts = cdb_defauts();
	return get_theme_mod( 'cdb_' . $cle, $defauts[ $cle ] ?? '' );
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$wp->add_panel( 'cdb', array( 'title' => 'Cloches de Bretagne', 'priority' => 20 ) );

	$sections = array(
		'cdb_accueil' => array( 'Page d\'accueil', array(
			'hero_image'    => array( 'Photo du bandeau', 'image' ),
			'hero_surtitre' => array( 'Petit texte au-dessus du titre', 'text' ),
			'hero_titre'    => array( 'Titre principal', 'text' ),
			'hero_texte'    => array( 'Texte d\'introduction', 'textarea' ),
			'a_paraitre'    => array( 'Prochains clochers (un par ligne, ex. : Maxent (35))', 'textarea' ),
			'video_accueil' => array( 'Vidéo YouTube mise en avant (adresse)', 'url' ),
		) ),
		'cdb_chiffres' => array( 'Chiffres clés', array(
			'nb_cloches'  => array( 'Nombre de cloches (vide = calcul automatique)', 'number' ),
			'nb_edifices' => array( 'Nombre d\'édifices (vide = calcul automatique)', 'number' ),
		) ),
		'cdb_liens' => array( 'Liens et contact', array(
			'youtube'  => array( 'Chaîne YouTube', 'url' ),
			'facebook' => array( 'Page Facebook', 'url' ),
			'email'    => array( 'Adresse e-mail de contact', 'email' ),
		) ),
	);

	$nettoyage = array(
		'text'     => 'sanitize_text_field',
		'textarea' => 'sanitize_textarea_field',
		'url'      => 'esc_url_raw',
		'image'    => 'esc_url_raw',
		'email'    => 'sanitize_email',
	);

	foreach ( $sections as $id => list( $titre, $champs ) ) {
		$wp->add_section( $id, array( 'title' => $titre, 'panel' => 'cdb' ) );
		foreach ( $champs as $cle => list( $label, $type ) ) {
			$wp->add_setting( 'cdb_' . $cle, array(
				'default'           => cdb_defauts()[ $cle ],
				'sanitize_callback' => 'number' === $type ? function ( $v ) { return '' === $v ? '' : absint( $v ); } : $nettoyage[ $type ],
			) );
			if ( 'image' === $type ) {
				$wp->add_control( new WP_Customize_Image_Control( $wp, 'cdb_' . $cle, array( 'label' => $label, 'section' => $id ) ) );
			} else {
				$wp->add_control( 'cdb_' . $cle, array( 'label' => $label, 'section' => $id, 'type' => $type ) );
			}
		}
	}
} );
