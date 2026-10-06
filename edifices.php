<?php
/**
 * Type de contenu « Édifice », taxonomie « Département » et formulaire des cloches.
 */

defined( 'ABSPATH' ) || exit;

const CDB_CHAMPS_CLOCHE = array(
	'nom'         => array( 'Nom de baptême', 'text', 'Ex. : Henriette-Marie' ),
	'note'        => array( 'Note', 'text', 'Ex. : Fa#3' ),
	'annee'       => array( 'Année', 'text', 'Ex. : 1860 — refondue en 1977' ),
	'fondeur'     => array( 'Fondeur', 'text', 'Ex. : Paccard, Annecy-le-Vieux' ),
	'poids'       => array( 'Poids (kg)', 'number', '' ),
	'diametre'    => array( 'Diamètre (mm)', 'number', '' ),
	'parrains'    => array( 'Parrain & marraine', 'text', '' ),
	'inscription' => array( 'Inscription', 'textarea', 'Recopiez le texte gravé sur la cloche' ),
	'remarque'    => array( 'Remarque', 'textarea', 'Facultatif' ),
	'album'       => array( 'Album photos (adresse)', 'url', 'Collez le lien de l\'album de cette cloche' ),
);

add_action( 'init', function () {
	register_post_type( 'edifice', array(
		'labels'        => array(
			'name'               => 'Édifices',
			'singular_name'      => 'Édifice',
			'menu_name'          => 'Édifices',
			'all_items'          => 'Tous les édifices',
			'add_new'            => 'Ajouter un édifice',
			'add_new_item'       => 'Ajouter un édifice',
			'edit_item'          => 'Modifier l\'édifice',
			'new_item'           => 'Nouvel édifice',
			'view_item'          => 'Voir l\'édifice',
			'search_items'       => 'Rechercher un édifice',
			'not_found'          => 'Aucun édifice',
			'not_found_in_trash' => 'Aucun édifice dans la corbeille',
			'featured_image'     => 'Photo principale',
			'set_featured_image' => 'Choisir la photo principale',
			'item_published'     => 'Édifice publié.',
			'item_updated'       => 'Édifice mis à jour.',
		),
		'public'        => true,
		'show_in_rest'  => true,
		'has_archive'   => 'edifices',
		'rewrite'       => array( 'slug' => 'edifice', 'with_front' => false ),
		'menu_position' => 4,
		'menu_icon'     => 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3v3"/><path d="M9 22c0-8 1-14 7-14s7 6 7 14"/><path d="M6 22h20l-1.5 3h-17z"/><circle cx="16" cy="28" r="1.6"/></svg>' ),
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
	) );

	register_taxonomy( 'departement', 'edifice', array(
		'labels'            => array(
			'name'          => 'Départements',
			'singular_name' => 'Département',
			'add_new_item'  => 'Ajouter un département',
			'edit_item'     => 'Modifier le département',
			'all_items'     => 'Tous les départements',
			'search_items'  => 'Rechercher',
		),
		'hierarchical'      => true, // cases à cocher dans l'éditeur, plus simple qu'un champ libre
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => array( 'slug' => 'departement', 'with_front' => false ),
	) );
} );

/* Le catalogue affiche tous les édifices sur une seule page. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_post_type_archive( 'edifice' ) || $q->is_tax( 'departement' ) ) ) {
		$q->set( 'posts_per_page', -1 );
	}
} );

/* Titre : « Nom de l'édifice » plutôt que « Ajouter un titre ». */
add_filter( 'enter_title_here', function ( $texte, $post ) {
	return 'edifice' === $post->post_type ? 'Nom de l\'édifice (ex. : Église Saint-Martin)' : $texte;
}, 10, 2 );

/* ---------- Numéro de département ---------- */

add_action( 'departement_add_form_fields', function () {
	?>
	<div class="form-field">
		<label for="cdb_numero">Numéro</label>
		<input type="text" name="cdb_numero" id="cdb_numero" maxlength="3" style="width:6em">
		<p>Ex. : 35. Sert à trier et à afficher les départements.</p>
	</div>
	<?php
} );

add_action( 'departement_edit_form_fields', function ( $term ) {
	?>
	<tr class="form-field">
		<th scope="row"><label for="cdb_numero">Numéro</label></th>
		<td><input type="text" name="cdb_numero" id="cdb_numero" maxlength="3" style="width:6em" value="<?php echo esc_attr( cdb_numero( $term ) ); ?>">
		<p class="description">Ex. : 35. Sert à trier et à afficher les départements.</p></td>
	</tr>
	<?php
} );

$cdb_save_numero = function ( $term_id ) {
	if ( isset( $_POST['cdb_numero'] ) && current_user_can( 'manage_categories' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- formulaire de terme protégé par WP
		update_term_meta( $term_id, 'cdb_numero', sanitize_text_field( wp_unslash( $_POST['cdb_numero'] ) ) );
	}
};
add_action( 'created_departement', $cdb_save_numero );
add_action( 'edited_departement', $cdb_save_numero );

/* ---------- Formulaire « Commune, vidéo et cloches » ---------- */

add_action( 'add_meta_boxes_edifice', function () {
	add_meta_box( 'cdb_edifice', 'Commune, vidéo et cloches', 'cdb_metabox', 'edifice', 'normal', 'high' );
} );

function cdb_metabox( $post ) {
	wp_nonce_field( 'cdb_edifice', 'cdb_nonce' );
	$cloches = cdb_cloches( $post->ID );
	$ancien  = get_post_meta( $post->ID, '_cdb_ancien_lien', true );
	?>
	<div class="cdb-box">
		<div class="cdb-grid cdb-grid-2">
			<p><label for="cdb_commune">Commune</label>
				<input type="text" id="cdb_commune" name="cdb_commune" value="<?php echo esc_attr( get_post_meta( $post->ID, '_cdb_commune', true ) ); ?>" placeholder="Ex. : Tresbœuf" required>
				<span class="description">Pour un hameau ou une commune déléguée : « Domagné (Chaumeré) ».</span></p>
			<p><label for="cdb_video">Vidéo YouTube</label>
				<input type="url" id="cdb_video" name="cdb_video" value="<?php echo esc_attr( get_post_meta( $post->ID, '_cdb_video', true ) ); ?>" placeholder="https://www.youtube.com/watch?v=…">
				<span class="description">Collez simplement l'adresse de la vidéo.</span></p>
		</div>

		<p><label for="cdb_sources">Sources et remerciements</label>
			<textarea id="cdb_sources" name="cdb_sources" rows="5" placeholder="Remerciements, puis un lien par ligne pour les sources."><?php echo esc_textarea( get_post_meta( $post->ID, '_cdb_sources', true ) ); ?></textarea>
			<span class="description">Les adresses web deviennent des liens automatiquement. Affiché tout en bas de la fiche.</span></p>

		<?php if ( $ancien ) : ?>
			<p class="cdb-ancien">Fiche importée de l'ancien site. Tant qu'elle n'a ni texte ni cloche, le site renvoie vers <a href="<?php echo esc_url( $ancien ); ?>" target="_blank" rel="noopener">l'article d'origine</a>.</p>
		<?php endif; ?>

		<h3 class="cdb-h">Les cloches</h3>
		<p class="description">Ajoutez les cloches de la plus grosse à la plus petite. Les flèches permettent de changer l'ordre.</p>
		<div id="cdb-cloches">
			<?php
			foreach ( $cloches as $i => $c ) {
				cdb_ligne_cloche( $i, $c );
			}
			?>
		</div>
		<p><button type="button" class="button button-primary button-large" id="cdb-ajouter">+ Ajouter une cloche</button></p>
		<template id="cdb-modele"><?php cdb_ligne_cloche( '__i__', array() ); ?></template>
	</div>
	<?php
}

function cdb_ligne_cloche( $i, $c ) {
	?>
	<fieldset class="cdb-cloche">
		<legend>
			<span class="cdb-num">Cloche</span>
			<span class="cdb-actions">
				<button type="button" class="button cdb-monter" aria-label="Monter">↑</button>
				<button type="button" class="button cdb-descendre" aria-label="Descendre">↓</button>
				<button type="button" class="button-link button-link-delete cdb-supprimer">Supprimer</button>
			</span>
		</legend>
		<div class="cdb-grid">
			<?php foreach ( CDB_CHAMPS_CLOCHE as $cle => list( $label, $type, $aide ) ) :
				$name  = "cdb_cloches[{$i}][{$cle}]";
				$id    = "cdb-{$i}-{$cle}";
				$value = $c[ $cle ] ?? '';
				?>
				<p class="cdb-champ cdb-<?php echo esc_attr( $type ); ?>">
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
					<?php if ( 'textarea' === $type ) : ?>
						<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="2" placeholder="<?php echo esc_attr( $aide ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
					<?php else : ?>
						<input type="<?php echo esc_attr( $type ); ?>" <?php echo 'number' === $type ? 'step="any" min="0"' : ''; ?> id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $aide ); ?>">
					<?php endif; ?>
				</p>
			<?php endforeach; ?>
		</div>
	</fieldset>
	<?php
}

add_action( 'save_post_edifice', function ( $post_id ) {
	if ( ! isset( $_POST['cdb_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['cdb_nonce'] ), 'cdb_edifice' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_cdb_commune', sanitize_text_field( wp_unslash( $_POST['cdb_commune'] ?? '' ) ) );
	update_post_meta( $post_id, '_cdb_video', esc_url_raw( wp_unslash( $_POST['cdb_video'] ?? '' ) ) );
	update_post_meta( $post_id, '_cdb_sources', sanitize_textarea_field( wp_unslash( $_POST['cdb_sources'] ?? '' ) ) );

	$brut    = isset( $_POST['cdb_cloches'] ) && is_array( $_POST['cdb_cloches'] ) ? wp_unslash( $_POST['cdb_cloches'] ) : array(); // phpcs:ignore -- nettoyé champ par champ ci-dessous
	$cloches = array();
	foreach ( $brut as $c ) {
		if ( ! is_array( $c ) ) {
			continue;
		}
		$ligne = array();
		foreach ( CDB_CHAMPS_CLOCHE as $cle => list( , $type ) ) {
			$v = $c[ $cle ] ?? '';
			if ( 'number' === $type ) {
				$v = str_replace( array( ',', ' ' ), array( '.', '' ), $v );
				$v = is_numeric( $v ) ? $v + 0 : '';
			} elseif ( 'textarea' === $type ) {
				$v = sanitize_textarea_field( $v );
			} elseif ( 'url' === $type ) {
				$v = esc_url_raw( $v );
			} else {
				$v = sanitize_text_field( $v );
			}
			$ligne[ $cle ] = $v;
		}
		if ( '' !== implode( '', $ligne ) ) {
			$cloches[] = $ligne;
		}
	}
	update_post_meta( $post_id, '_cdb_cloches', $cloches );
} );

/* ---------- Colonnes de la liste des édifices ---------- */

add_filter( 'manage_edifice_posts_columns', function ( $cols ) {
	$nouv = array();
	foreach ( $cols as $k => $v ) {
		if ( 'title' === $k ) {
			$nouv['cdb_commune'] = 'Commune';
			$nouv[ $k ]          = 'Édifice';
			$nouv['cdb_cloches'] = 'Cloches';
			continue;
		}
		$nouv[ $k ] = $v;
	}
	return $nouv;
} );

add_action( 'manage_edifice_posts_custom_column', function ( $col, $post_id ) {
	if ( 'cdb_commune' === $col ) {
		echo '<strong>' . esc_html( get_post_meta( $post_id, '_cdb_commune', true ) ) . '</strong>';
	} elseif ( 'cdb_cloches' === $col ) {
		$n = count( cdb_cloches( $post_id ) );
		echo $n ? (int) $n : '<span style="color:#b32d2e">à compléter</span>';
	}
}, 10, 2 );

add_filter( 'manage_edit-edifice_sortable_columns', function ( $cols ) {
	$cols['cdb_commune'] = 'cdb_commune';
	return $cols;
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'edifice' === $q->get( 'post_type' ) ) {
		if ( 'cdb_commune' === $q->get( 'orderby' ) || ! $q->get( 'orderby' ) ) {
			// Tri par commune, sans masquer les édifices dont la commune n'est pas encore saisie.
			$q->set( 'meta_query', array(
				'relation' => 'OR',
				'commune'  => array( 'key' => '_cdb_commune', 'compare' => 'EXISTS' ),
				'sans'     => array( 'key' => '_cdb_commune', 'compare' => 'NOT EXISTS' ),
			) );
			$sens = isset( $_GET['orderby'] ) && 'desc' === strtolower( (string) $q->get( 'order' ) ) ? 'DESC' : 'ASC'; // phpcs:ignore
			$q->set( 'orderby', array( 'commune' => $sens, 'title' => 'ASC' ) );
		}
		// La recherche porte aussi sur la commune.
		if ( $q->get( 's' ) ) {
			add_filter( 'posts_search', 'cdb_recherche_commune', 10, 2 );
		}
	}
} );

function cdb_recherche_commune( $search, $q ) {
	global $wpdb;
	remove_filter( 'posts_search', 'cdb_recherche_commune', 10 );
	$like = '%' . $wpdb->esc_like( $q->get( 's' ) ) . '%';
	$ids  = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_cdb_commune' AND meta_value LIKE %s", $like ) );
	if ( $ids && $search ) {
		$search = preg_replace( '/^\s*AND\s*\(/', ' AND ( ' . $wpdb->posts . '.ID IN (' . implode( ',', array_map( 'intval', $ids ) ) . ') OR (', $search, 1 ) . ')';
	}
	return $search;
}
