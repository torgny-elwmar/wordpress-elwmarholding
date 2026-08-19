<?php
/**
 * Title: Insights index
 * Slug: elwmarholding/insights-home
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"main","className":"site-main insights-page","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|90"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group site-main insights-page">
	<!-- wp:group {"className":"archive-header insights-header","style":{"color":{"background":"var:preset|color|surface"},"spacing":{"padding":{"top":"var:preset|spacing|70","right":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|70"},"margin":{"bottom":"var:preset|spacing|70"}},"border":{"left":{"color":"var:preset|color|primary","width":"10px"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group archive-header insights-header has-background" style="border-left-color:var(--wp--preset--color--primary);border-left-width:10px;background-color:var(--wp--preset--color--surface);margin-bottom:var(--wp--preset--spacing--70);padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--70)">
		<!-- wp:paragraph {"className":"section-kicker","style":{"color":{"text":"var:preset|color|primary"},"typography":{"fontFamily":"var:preset|font-family|heading","fontWeight":"800","letterSpacing":"0.1em","textTransform":"uppercase"}}} --><p class="section-kicker has-text-color" style="color:var(--wp--preset--color--primary);font-family:var(--wp--preset--font-family--heading);font-weight:800;letter-spacing:0.1em;text-transform:uppercase"><?php echo esc_html__( 'Senaste nytt', 'elwmarholding' ); ?></p><!-- /wp:paragraph -->
		<!-- wp:heading {"level":1,"style":{"typography":{"fontFamily":"var:preset|font-family|heading","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}}} --><h1 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30);font-family:var(--wp--preset--font-family--heading);font-weight:800"><?php echo esc_html__( 'Insikter.', 'elwmarholding' ); ?></h1><!-- /wp:heading -->
		<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|text-muted"},"typography":{"fontSize":"var:preset|font-size|lg","lineHeight":"1.55"}}} --><p class="has-text-color" style="color:var(--wp--preset--color--text-muted);font-size:var(--wp--preset--font-size--lg);line-height:1.55"><?php echo esc_html__( 'Perspektiv, nyheter och konkreta erfarenheter om strategi, innovation och genomförande.', 'elwmarholding' ); ?></p><!-- /wp:paragraph -->
	</div><!-- /wp:group -->
	<!-- wp:query {"queryId":3,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-query">
		<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|70"}},"layout":{"type":"grid","columnCount":2}} -->
			<!-- wp:group {"className":"post-card","style":{"border":{"width":"1px","color":"var:preset|color|border","style":"solid"},"spacing":{"padding":{"top":"0","right":"0","bottom":"var:preset|spacing|60","left":"0"}},"color":{"background":"var:preset|color|surface"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group post-card has-background" style="border-color:var(--wp--preset--color--border);border-style:solid;border-width:1px;background-color:var(--wp--preset--color--surface);padding-top:0;padding-right:0;padding-bottom:var(--wp--preset--spacing--60);padding-left:0">
				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /-->
				<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|60","bottom":"0","left":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} --><div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--60);padding-bottom:0;padding-left:var(--wp--preset--spacing--60)">
					<!-- wp:post-terms {"term":"category","style":{"color":{"text":"var:preset|color|primary"},"typography":{"fontWeight":"700","fontSize":"var:preset|font-size|xs","textTransform":"uppercase","letterSpacing":"0.08em"}}} /-->
					<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontFamily":"var:preset|font-family|heading","fontWeight":"700"},"color":{"text":"var:preset|color|text"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"0"}}},"fontSize":"xl"} /-->
					<!-- wp:post-excerpt {"moreText":"<?php echo esc_attr__( 'Läs insikten →', 'elwmarholding' ); ?>","style":{"color":{"text":"var:preset|color|text-muted"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} /-->
					<!-- wp:post-date {"format":"j F Y","style":{"color":{"text":"var:preset|color|text-muted"},"typography":{"fontSize":"var:preset|font-size|sm"}}} /-->
				</div><!-- /wp:group -->
			</div><!-- /wp:group -->
		<!-- /wp:post-template -->
		<!-- wp:query-no-results --><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Fler insikter är på väg.', 'elwmarholding' ); ?></h2><!-- /wp:heading --></div><!-- /wp:group --><!-- /wp:query-no-results -->
		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70"}}},"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--70)"><!-- wp:query-pagination {"paginationArrow":"arrow","layout":{"type":"flex","justifyContent":"center"}} --><!-- wp:query-pagination-previous {"label":"<?php echo esc_attr__( 'Nyare insikter', 'elwmarholding' ); ?>"} /--><!-- wp:query-pagination-numbers /--><!-- wp:query-pagination-next {"label":"<?php echo esc_attr__( 'Äldre insikter', 'elwmarholding' ); ?>"} /--><!-- /wp:query-pagination --></div><!-- /wp:group -->
	</div><!-- /wp:query -->
</main>
<!-- /wp:group -->
