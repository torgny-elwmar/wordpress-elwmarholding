<?php
/**
 * Title: Header
 * Slug: elwmarholding/header
 * Inserter: no
 */
$navigation_id = elwmarholding_get_primary_navigation_id();
?>
<!-- wp:group {"tagName":"header","className":"site-header","layout":{"type":"constrained"}} -->
<header class="wp-block-group site-header">
	<!-- wp:group {"className":"site-header__inner","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group site-header__inner">
		<!-- wp:html -->
		<a class="brand" href="<?php echo esc_url( elwmarholding_get_home_url() ); ?>"><img src="<?php echo esc_url( ELWMARHOLDING_URI . '/assets/images/elwmar-brand-logo-full.png' ); ?>" alt="<?php echo esc_attr__( 'Elwmar Holding AB – startsida', 'elwmarholding' ); ?>" width="7357" height="2362"></a>
		<!-- /wp:html -->
		<!-- wp:group {"className":"site-header__actions","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
		<div class="wp-block-group site-header__actions">
			<!-- wp:elwmarholding/language-switcher /-->
			<?php if ( $navigation_id ) : ?>
			<!-- wp:navigation {"ref":<?php echo (int) $navigation_id; ?>,"overlayMenu":"always","className":"primary-navigation","metadata":{"name":"<?php echo esc_attr__( 'Huvudmeny', 'elwmarholding' ); ?>"},"layout":{"type":"flex","justifyContent":"right"}} /-->
			<?php else : ?>
			<!-- wp:navigation {"overlayMenu":"always","className":"primary-navigation","metadata":{"name":"<?php echo esc_attr__( 'Huvudmeny', 'elwmarholding' ); ?>"},"layout":{"type":"flex","justifyContent":"right"}} -->
				<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'Om oss', 'elwmarholding' ); ?>","url":"/om-oss/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'Verksamheter', 'elwmarholding' ); ?>","url":"/verksamheter/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'Insikter', 'elwmarholding' ); ?>","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'Kontakt', 'elwmarholding' ); ?>","url":"/kontakt/","kind":"custom","isTopLevelLink":true} /-->
			<!-- /wp:navigation -->
			<?php endif; ?>
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
