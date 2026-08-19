<?php
/**
 * Title: 404 content
 * Slug: elwmarholding/hidden-404
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"main","className":"site-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|100","bottom":"var:preset|spacing|100"}}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<main class="wp-block-group site-main">
	<!-- wp:paragraph {"align":"center","className":"eyebrow"} --><p class="has-text-align-center eyebrow"><?php echo esc_html__( 'Felsväng', 'elwmarholding' ); ?></p><!-- /wp:paragraph -->
	<!-- wp:heading {"textAlign":"center","level":1,"fontSize":"3xl"} --><h1 class="wp-block-heading has-text-align-center has-3-xl-font-size"><?php echo esc_html__( '404 — Sidan hittades inte', 'elwmarholding' ); ?></h1><!-- /wp:heading -->
	<!-- wp:paragraph {"textAlign":"center","style":{"color":{"text":"var:preset|color|text-muted"},"typography":{"fontSize":"var:preset|font-size|lg"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|70"}}}} --><p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--text-muted);font-size:var(--wp--preset--font-size--lg);margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--70)"><?php echo esc_html__( 'Sidan du söker finns inte längre, eller har fått en ny adress. Vi hjälper dig tillbaka på rätt spår.', 'elwmarholding' ); ?></p><!-- /wp:paragraph -->
	<!-- wp:search {"label":"<?php echo esc_attr__( 'Sök på webbplatsen', 'elwmarholding' ); ?>","showLabel":true,"buttonText":"<?php echo esc_attr__( 'Sök', 'elwmarholding' ); ?>","buttonUseIcon":true,"align":"center","style":{"border":{"radius":"var:custom|border-radius|full"}}} /-->
	<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|70"}}},"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--70)"><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"style":{"border":{"radius":"var:custom|border-radius|full"}}} --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/" style="border-radius:var(--wp--custom--border-radius--full)"><?php echo esc_html__( '← Tillbaka till startsidan', 'elwmarholding' ); ?></a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->
</main>
<!-- /wp:group -->
