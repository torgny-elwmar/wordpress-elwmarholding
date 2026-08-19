<?php
/**
 * Title: Search content
 * Slug: elwmarholding/search-content
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"main","className":"site-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|90"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group site-main">
	<!-- wp:group {"className":"search-header","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"},"margin":{"bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group search-header"><!-- wp:query-title {"type":"search"} /--><!-- wp:search {"label":"<?php echo esc_attr__( 'Ny sökning', 'elwmarholding' ); ?>","showLabel":false,"buttonText":"<?php echo esc_attr__( 'Sök', 'elwmarholding' ); ?>","buttonUseIcon":true} /--></div><!-- /wp:group -->
	<!-- wp:query {"queryId":2,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"relevance","inherit":true}} -->
	<div class="wp-block-query">
		<!-- wp:query-no-results --><!-- wp:paragraph --><p><?php echo esc_html__( 'Inga resultat hittades för din sökning. Försök med andra sökord.', 'elwmarholding' ); ?></p><!-- /wp:paragraph --><!-- /wp:query-no-results -->
		<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|60"}}} -->
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px","style":"solid"}}},"layout":{"type":"flex","orientation":"vertical"}} --><div class="wp-block-group"><!-- wp:post-terms {"term":"category"} /--><!-- wp:post-title {"isLink":true,"fontSize":"xl"} /--><!-- wp:post-excerpt {"moreText":"<?php echo esc_attr__( 'Läs mer →', 'elwmarholding' ); ?>"} /--><!-- wp:post-date /--></div><!-- /wp:group -->
		<!-- /wp:post-template -->
		<!-- wp:query-pagination {"paginationArrow":"arrow","layout":{"type":"flex","justifyContent":"center"}} --><!-- wp:query-pagination-previous /--><!-- wp:query-pagination-numbers /--><!-- wp:query-pagination-next /--><!-- /wp:query-pagination -->
	</div><!-- /wp:query -->
</main>
<!-- /wp:group -->
