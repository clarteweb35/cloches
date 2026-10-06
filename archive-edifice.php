<?php
/**
 * Catalogue de tous les édifices, filtrable (recherche, département, type).
 * Aussi utilisé par taxonomy-departement.php, qui pré-sélectionne un département.
 */

get_header();

$preselection = isset( $cdb_dept_preselection ) ? $cdb_dept_preselection : '';
$edifices     = cdb_tous_edifices();
$depts        = cdb_departements();
$types        = array( 'Église', 'Chapelle', 'Cathédrale', 'Abbaye & couvent' );

// Regroupement par département (les édifices sans département vont dans « Autres »).
$groupes = array();
foreach ( $edifices as $p ) {
	$t = cdb_edifice_dept( $p->ID );
	$groupes[ $t ? $t->term_id : 0 ][] = $p;
}
$ordre = array_map( function ( $t ) { return $t->term_id; }, $depts );
$ordre[] = 0;

$titre = is_tax( 'departement' ) ? cdb_dept_label( get_queried_object() ) : 'Les édifices';
cdb_page_head( 'Inventaire', $titre, 'Recherchez une commune ou un édifice, ou filtrez par département et par type de bâtiment.' );
?>
<main id="main" class="wrap catalogue" data-preselection="<?php echo esc_attr( $preselection ); ?>">
	<div class="filters">
		<label class="search"><?php echo cdb_icon( 'search' ); // phpcs:ignore ?>
			<input id="cdb-q" type="search" placeholder="Commune, saint, édifice…" aria-label="Rechercher" autocomplete="off"></label>
	</div>
	<div class="chips" role="group" aria-label="Département">
		<button class="chip" data-group="dept" data-value="" aria-pressed="true">Tous</button>
		<?php foreach ( $depts as $t ) : if ( ! $t->count ) { continue; } ?>
			<button class="chip" data-group="dept" data-value="<?php echo esc_attr( $t->term_id ); ?>" aria-pressed="false"><?php echo esc_html( cdb_dept_label( $t ) ); ?></button>
		<?php endforeach; ?>
	</div>
	<div class="chips" role="group" aria-label="Type d'édifice" style="margin-top:8px">
		<button class="chip" data-group="type" data-value="" aria-pressed="true">Tous types</button>
		<?php foreach ( $types as $type ) : ?>
			<button class="chip" data-group="type" data-value="<?php echo esc_attr( $type ); ?>" aria-pressed="false"><?php echo esc_html( $type ); ?></button>
		<?php endforeach; ?>
	</div>
	<p class="result-count" id="cdb-count" aria-live="polite"><?php echo esc_html( count( $edifices ) ); ?> édifices</p>

	<div id="cdb-results">
		<?php foreach ( $ordre as $tid ) :
			if ( empty( $groupes[ $tid ] ) ) {
				continue;
			}
			$term = $tid ? get_term( $tid ) : null;
			?>
			<section class="group" data-dept="<?php echo esc_attr( $tid ); ?>">
				<h2><?php echo esc_html( $term ? cdb_dept_label( $term ) : 'Autres' ); ?> <span class="n"><?php echo count( $groupes[ $tid ] ); ?></span></h2>
				<ul class="list">
					<?php foreach ( $groupes[ $tid ] as $p ) :
						$commune = cdb_commune( $p->ID );
						$n       = count( cdb_cloches( $p->ID ) );
						$cherche = strtolower( remove_accents( $commune . ' ' . $p->post_title ) );
						?>
						<li data-type="<?php echo esc_attr( cdb_type( $p->post_title ) ); ?>" data-q="<?php echo esc_attr( $cherche ); ?>">
							<a href="<?php echo esc_url( get_permalink( $p ) ); ?>">
								<span><span class="commune"><?php echo esc_html( $commune ); ?></span><span class="edifice"><?php echo esc_html( $p->post_title ); ?></span></span>
								<?php if ( $n ) : ?><span class="pill"><?php echo esc_html( sprintf( _n( '%s cloche', '%s cloches', $n, 'cloches-bretagne' ), $n ) ); ?></span><?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
	</div>
	<p class="empty" id="cdb-vide" hidden>Aucun édifice ne correspond à cette recherche.</p>
</main>
<?php
get_footer();
