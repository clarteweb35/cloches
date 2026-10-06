<?php
/**
 * Confort d'administration : formulaire des cloches, guide sur le tableau de bord, menu épuré.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'edifice' === get_current_screen()->post_type ) {
		wp_enqueue_style( 'cdb-admin', CDB_URI . '/assets/css/admin.css', array(), CDB_VERSION );
		wp_enqueue_script( 'cdb-admin', CDB_URI . '/assets/js/admin.js', array(), CDB_VERSION, true );
	}
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'edifice_page_cdb-import' === $hook ) {
		wp_enqueue_script( 'cdb-import', CDB_URI . '/assets/js/import.js', array(), CDB_VERSION, true );
	}
} );

/* Le blog s'appelle « Actualités » : un vocabulaire simple pour Vincent. */
add_filter( 'post_type_labels_post', function ( $l ) {
	$l->name               = 'Actualités';
	$l->singular_name      = 'Actualité';
	$l->menu_name          = 'Actualités';
	$l->name_admin_bar     = 'Actualité';
	$l->all_items          = 'Toutes les actualités';
	$l->add_new            = 'Écrire une actualité';
	$l->add_new_item       = 'Écrire une actualité';
	$l->edit_item          = 'Modifier l\'actualité';
	$l->new_item           = 'Nouvelle actualité';
	$l->view_item          = 'Voir l\'actualité';
	$l->search_items       = 'Rechercher une actualité';
	$l->not_found          = 'Aucune actualité';
	$l->not_found_in_trash = 'Aucune actualité dans la corbeille';
	return $l;
} );
add_action( 'admin_menu', function () {
	global $menu;
	foreach ( $menu as $i => $entree ) {
		if ( 'edit.php' === $entree[2] ) {
			$menu[ $i ][6] = 'dashicons-megaphone'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
	remove_menu_page( 'edit-comments.php' ); // pas de commentaires sur ce site
} );
add_action( 'admin_bar_menu', function ( $bar ) {
	$bar->remove_node( 'comments' );
}, 999 );
add_filter( 'comments_open', '__return_false' );
add_filter( 'pings_open', '__return_false' );

/* Guide pas à pas sur le tableau de bord. */
add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'cdb_guide', 'Mode d\'emploi — Cloches de Bretagne', function () {
		$nouveau = admin_url( 'post-new.php?post_type=edifice' );
		$liste   = admin_url( 'edit.php?post_type=edifice' );
		$actu    = admin_url( 'post-new.php' );
		$perso   = admin_url( 'customize.php?autofocus[panel]=cdb' );
		$a_compl = get_posts( array( 'post_type' => 'edifice', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_cdb_ancien_lien' ) );
		$a_compl = array_filter( $a_compl, function ( $id ) {
			return ! cdb_cloches( $id ) && '' === trim( get_post_field( 'post_content', $id ) );
		} );
		?>
		<p>
				<a class="button button-primary button-hero" href="<?php echo esc_url( $nouveau ); ?>">+ Ajouter un édifice</a>
				<a class="button button-hero" href="<?php echo esc_url( $actu ); ?>">Écrire une actualité</a>
			</p>
		<h3><strong>Publier un nouveau clocher</strong></h3>
		<ol>
			<li>Cliquez sur <strong>Ajouter un édifice</strong>.</li>
			<li>Tapez le <strong>nom de l'édifice</strong> en haut (ex. : Église Saint-Martin).</li>
			<li>Écrivez l'<strong>histoire</strong> dans la grande zone de texte. Vous pouvez y glisser des photos.</li>
			<li>À droite : cochez le <strong>département</strong>, choisissez la <strong>photo principale</strong> et écrivez un court <strong>extrait</strong> (résumé d'une phrase).</li>
			<li>En bas : la <strong>commune</strong>, le lien de la <strong>vidéo YouTube</strong>, les <strong>sources et remerciements</strong>, puis <strong>+ Ajouter une cloche</strong> pour chaque cloche (avec le lien de son album photos).</li>
			<li>Cliquez sur <strong>Publier</strong>. C'est en ligne !</li>
		</ol>
		<h3><strong>Écrire une actualité (le blog)</strong></h3>
		<ol>
			<li>Cliquez sur <strong>Écrire une actualité</strong>.</li>
			<li>Tapez le <strong>titre</strong>, puis le texte. Pour ajouter une photo ou une vidéo YouTube, cliquez sur le <strong>+</strong> et choisissez « Image » ou « YouTube » (ou collez simplement l'adresse de la vidéo).</li>
			<li>À droite : choisissez l'<strong>image mise en avant</strong>, c'est elle qui illustre l'actualité sur l'accueil.</li>
			<li>Cliquez sur <strong>Publier</strong>. L'actualité apparaît sur l'accueil et dans la page Actualités.</li>
		</ol>
		<p>Astuce : l'application gratuite <strong>WordPress</strong> (Android et iPhone) permet d'écrire une actualité depuis un téléphone.</p>
		<h3><strong>Autres réglages</strong></h3>
		<ul>
			<li><a href="<?php echo esc_url( $liste ); ?>">Tous les édifices</a> : modifier une fiche existante.</li>
			<li><a href="<?php echo esc_url( $perso ); ?>">Personnaliser la page d'accueil</a> : photo du bandeau, « prochains clochers », vidéo mise en avant, liens.</li>
			<li><strong>Pages</strong> : modifier « Qui suis-je ? », « Carte », « Liens utiles », les mentions légales…</li>
			<li><strong>Apparence &gt; Menus</strong> : changer les liens du haut et du bas du site.</li>
		</ul>
		<?php if ( ! cdb_mod( 'email' ) ) : ?>
			<p style="background:#fcf6e8;padding:10px 12px;border-left:4px solid #9a6a32">
				<strong>À faire :</strong> renseigner votre <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=cdb_liens' ) ); ?>">adresse e-mail de contact</a>. Elle s'affichera dans le pied de page et les mentions légales.
			</p>
		<?php endif; ?>
		<?php if ( $a_compl ) : ?>
			<p style="background:#fcf6e8;padding:10px 12px;border-left:4px solid #9a6a32">
				<strong><?php echo count( $a_compl ); ?> fiches</strong> importées de l'ancien site restent à compléter
				(colonne « Cloches » : <em>à compléter</em> dans la <a href="<?php echo esc_url( $liste ); ?>">liste</a>).
			</p>
		<?php endif; ?>
		<?php
	} );
} );

/* Le guide en premier sur le tableau de bord. */
add_action( 'wp_dashboard_setup', function () {
	global $wp_meta_boxes;
	$w = $wp_meta_boxes['dashboard']['normal']['core']['cdb_guide'] ?? null;
	if ( $w ) {
		unset( $wp_meta_boxes['dashboard']['normal']['core']['cdb_guide'] );
		$wp_meta_boxes['dashboard']['normal']['high']['cdb_guide'] = $w;
	}
}, 20 );
