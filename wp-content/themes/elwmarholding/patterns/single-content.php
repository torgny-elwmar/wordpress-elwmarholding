<?php
/**
 * Title: Single post content
 * Slug: elwmarholding/single-content
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"main","className":"site-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|90"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group site-main">
	<!-- wp:group {"className":"single-post","layout":{"type":"constrained","contentSize":"740px"}} -->
	<div class="wp-block-group single-post">
		<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} --><div class="wp-block-group"><!-- wp:post-terms {"term":"category"} /--><!-- wp:post-date /--></div><!-- /wp:group -->
		<!-- wp:post-title {"level":1} /-->
		<!-- wp:post-excerpt {"className":"single-post__excerpt"} /-->
		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}},"border":{"top":{"color":"var:preset|color|border","width":"1px","style":"solid"},"bottom":{"color":"var:preset|color|border","width":"1px","style":"solid"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} --><div class="wp-block-group"><!-- wp:post-author {"showAvatar":true,"avatarSize":40,"showBio":false} /--><!-- wp:post-terms {"term":"post_tag","separator":" · "} /--></div><!-- /wp:group -->
		<!-- wp:post-featured-image {"style":{"spacing":{"margin":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|70"}}},"aspectRatio":"16/9"} /-->
		<!-- wp:post-content {"style":{"spacing":{"blockGap":"var:preset|spacing|60"}}} /-->
		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:separator /--><!-- wp:post-navigation-link {"type":"previous","label":"<?php echo esc_attr__( '← Föregående', 'elwmarholding' ); ?>"} /--><!-- wp:post-navigation-link {"type":"next","label":"<?php echo esc_attr__( 'Nästa →', 'elwmarholding' ); ?>","textAlign":"right"} /--></div><!-- /wp:group -->
		<!-- wp:post-comments-form /-->
	</div><!-- /wp:group -->
</main>
<!-- /wp:group -->
