<?php
/**
 * Title: Post loop
 * Slug: elwmarholding/post-loop
 * Inserter: no
 */
?>
<!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":true},"layout":{"type":"constrained"}} -->
<div class="wp-block-query">
	<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|70"}},"layout":{"type":"grid","columnCount":2}} -->
		<!-- wp:group {"className":"post-card","style":{"border":{"width":"1px","color":"var:preset|color|border","style":"solid"},"spacing":{"padding":{"top":"0","right":"0","bottom":"var:preset|spacing|60","left":"0"}},"color":{"background":"var:preset|color|surface"}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group post-card has-background" style="border-color:var(--wp--preset--color--border);border-style:solid;border-width:1px;background-color:var(--wp--preset--color--surface);padding-top:0;padding-right:0;padding-bottom:var(--wp--preset--spacing--60);padding-left:0">
			<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /-->
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|60","bottom":"0","left":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} --><div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--60);padding-bottom:0;padding-left:var(--wp--preset--spacing--60)"><!-- wp:post-terms {"term":"category"} /--><!-- wp:post-title {"isLink":true,"fontSize":"xl"} /--><!-- wp:post-excerpt {"moreText":"<?php echo esc_attr__( 'Läs mer →', 'elwmarholding' ); ?>"} /--><!-- wp:post-date {"format":"j F Y"} /--></div><!-- /wp:group -->
		</div><!-- /wp:group -->
	<!-- /wp:post-template -->
	<!-- wp:query-no-results --><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Inga inlägg hittades', 'elwmarholding' ); ?></h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><?php echo esc_html__( 'Det verkar inte finnas något här. Försök gärna igen senare.', 'elwmarholding' ); ?></p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- /wp:query-no-results -->
	<!-- wp:query-pagination {"paginationArrow":"arrow","layout":{"type":"flex","justifyContent":"center"}} --><!-- wp:query-pagination-previous /--><!-- wp:query-pagination-numbers /--><!-- wp:query-pagination-next /--><!-- /wp:query-pagination -->
</div>
<!-- /wp:query -->
