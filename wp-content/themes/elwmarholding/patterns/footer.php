<?php
/**
 * Title: Footer
 * Slug: elwmarholding/footer
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"footer","className":"site-footer","layout":{"type":"constrained"}} -->
<footer class="wp-block-group site-footer">
	<!-- wp:group {"className":"site-footer__top","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group site-footer__top">
		<!-- wp:elwmarholding/footer-company /-->
		<!-- wp:elwmarholding/footer-icons /-->
	</div>
	<!-- /wp:group -->
	<!-- wp:group {"className":"site-footer__bottom","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group site-footer__bottom"><!-- wp:paragraph --><p><?php echo esc_html( sprintf( '© %s Elwmar Holding AB', wp_date( 'Y' ) ) ); ?></p><!-- /wp:paragraph --></div>
	<!-- /wp:group -->
</footer>
<!-- /wp:group -->
