<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main">Aller au contenu</a>
<header class="site-header"><div class="wrap">
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php echo cdb_icon( 'cloche' ); // phpcs:ignore ?>
		<span class="brand-name"><?php bloginfo( 'name' ); ?><small><?php bloginfo( 'description' ); ?></small></span>
	</a>
	<button class="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="nav"><?php echo cdb_icon( 'menu' ); // phpcs:ignore ?></button>
	<nav class="nav" id="nav" aria-label="Menu principal"><ul>
		<?php
		wp_nav_menu( array(
			'theme_location' => 'principal',
			'container'      => false,
			'items_wrap'     => '%3$s',
			'depth'          => 1,
			'fallback_cb'    => 'cdb_menu_fallback',
		) );
		?>
	</ul></nav>
</div></header>
