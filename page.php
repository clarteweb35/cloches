<?php
get_header();

while ( have_posts() ) :
	the_post();
	cdb_page_head( '', get_the_title() );
	?>
	<main id="main" class="wrap section">
		<div class="entry"><?php the_content(); ?></div>
	</main>
	<?php
endwhile;

get_footer();
