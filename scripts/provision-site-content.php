<?php
/**
 * Provision the initial Elwmar Holding page set in WordPress.
 *
 * Run inside a WordPress container:
 * php /tmp/provision-site-content.php [--dry-run] [--force-content]
 */

if ( ! defined( 'ABSPATH' ) ) {
	$wordpress_load = getenv( 'WORDPRESS_LOAD_PATH' ) ?: '/var/www/html/wp-load.php';
	if ( ! is_file( $wordpress_load ) ) {
		fwrite( STDERR, "WordPress loader was not found at {$wordpress_load}.\n" );
		exit( 1 );
	}

	$_SERVER['HTTP_HOST']   = getenv( 'ELWMARHOLDING_IMPORT_HOST' ) ?: 'localhost';
	$_SERVER['REQUEST_URI'] = '/';
	$_SERVER['HTTPS']       = 'on';

	require_once $wordpress_load;
}

if ( ! function_exists( 'PLL' ) || ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) ) {
	fwrite( STDERR, "Polylang must be active before provisioning content.\n" );
	exit( 1 );
}

$arguments     = isset( $argv ) && is_array( $argv ) ? $argv : array();
$dry_run       = '1' === getenv( 'ELWMARHOLDING_DRY_RUN' ) || in_array( '--dry-run', $arguments, true );
$force_content = '1' === getenv( 'ELWMARHOLDING_FORCE_CONTENT' ) || in_array( '--force-content', $arguments, true );
define( 'ELWMARHOLDING_PROVISION_DRY_RUN', $dry_run );
define( 'ELWMARHOLDING_PROVISION_FORCE_CONTENT', $force_content );
$GLOBALS['elwmarholding_provision_changes'] = array();

/** Record or print a planned operation. */
function elwmarholding_provision_log( string $message ): void {
	$GLOBALS['elwmarholding_provision_changes'][] = $message;
	echo $message . PHP_EOL;
}

/** Return a language object by slug. */
function elwmarholding_provision_language( string $slug ) {
	return PLL()->model->get_language( $slug );
}

/** Ensure a Polylang language exists. */
function elwmarholding_provision_ensure_language( array $args ) {
	$existing = elwmarholding_provision_language( $args['slug'] );
	if ( $existing ) {
		return $existing;
	}
	if ( ELWMARHOLDING_PROVISION_DRY_RUN ) {
		elwmarholding_provision_log( "Would create language {$args['slug']}." );
		return null;
	}

	$result = PLL()->model->add_language( $args );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}
	elwmarholding_provision_log( "Created language {$args['slug']}." );
	return $result;
}

/** Find a managed post by stable provisioning key. */
function elwmarholding_provision_find_post( string $key, string $post_type = 'page' ): ?WP_Post {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 1,
			'meta_key'       => '_elwmarholding_provision_key',
			'meta_value'     => $key,
		)
	);

	return $posts ? $posts[0] : null;
}

/** Create or update a managed page while preserving later editorial changes. */
function elwmarholding_provision_page( string $key, string $language, array $data ): int {
	$existing       = elwmarholding_provision_find_post( "page:{$key}:{$language}" );
	$content        = (string) $data['post_content'];
	$new_checksum   = hash( 'sha256', $content );
	$post_data      = array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'post_title'     => $data['post_title'],
		'post_name'      => $data['post_name'],
		'post_excerpt'   => '',
		'post_parent'    => 0,
		'menu_order'     => $data['menu_order'] ?? 0,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	);

	if ( $existing ) {
		$saved_checksum  = (string) get_post_meta( $existing->ID, '_elwmarholding_provision_checksum', true );
		$current_checksum = hash( 'sha256', (string) $existing->post_content );
		$can_update       = ELWMARHOLDING_PROVISION_FORCE_CONTENT || ! $saved_checksum || hash_equals( $saved_checksum, $current_checksum );
		$post_data['ID']  = $existing->ID;
		if ( $can_update ) {
			$post_data['post_content'] = $content;
		} else {
			$new_checksum = $current_checksum;
			elwmarholding_provision_log( "Preserved manually edited content for {$key}:{$language}." );
		}
	} else {
		$post_data['post_content'] = $content;
	}

	if ( ELWMARHOLDING_PROVISION_DRY_RUN ) {
		elwmarholding_provision_log( sprintf( 'Would %s page %s:%s.', $existing ? 'update' : 'create', $key, $language ) );
		return $existing ? $existing->ID : 0;
	}

	$result = $existing ? wp_update_post( wp_slash( $post_data ), true ) : wp_insert_post( wp_slash( $post_data ), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}

	$post_id = (int) $result;
	update_post_meta( $post_id, '_elwmarholding_provision_key', "page:{$key}:{$language}" );
	update_post_meta( $post_id, '_elwmarholding_provision_checksum', $new_checksum );
	pll_set_post_language( $post_id, $language );
	elwmarholding_provision_log( sprintf( '%s page %s:%s as post %d.', $existing ? 'Updated' : 'Created', $key, $language, $post_id ) );
	return $post_id;
}

/** Create or update one language-specific WordPress navigation. */
function elwmarholding_provision_navigation( string $language, string $title, array $links ): int {
	$key      = "navigation:{$language}";
	$existing = elwmarholding_provision_find_post( $key, 'wp_navigation' );
	$content  = '';
	foreach ( $links as $link ) {
		$content .= '<!-- wp:navigation-link ' . wp_json_encode(
			array(
				'label'          => $link['label'],
				'url'            => $link['url'],
				'kind'           => 'custom',
				'isTopLevelLink' => true,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . ' /-->';
	}

	if ( ELWMARHOLDING_PROVISION_DRY_RUN ) {
		elwmarholding_provision_log( sprintf( 'Would %s navigation %s.', $existing ? 'update' : 'create', $language ) );
		return $existing ? $existing->ID : 0;
	}

	$post_data = array(
		'post_type'    => 'wp_navigation',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => 'sv' === $language ? 'huvudmeny' : 'main-navigation',
		'post_content' => $content,
	);
	if ( $existing ) {
		$post_data['ID'] = $existing->ID;
		$result          = wp_update_post( wp_slash( $post_data ), true );
	} else {
		$result = wp_insert_post( wp_slash( $post_data ), true );
	}
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}

	$post_id = (int) $result;
	update_post_meta( $post_id, '_elwmarholding_provision_key', $key );
	if ( PLL()->model->is_translated_post_type( 'wp_navigation' ) ) {
		pll_set_post_language( $post_id, $language );
	}
	elwmarholding_provision_log( sprintf( '%s navigation %s as post %d.', $existing ? 'Updated' : 'Created', $language, $post_id ) );
	return $post_id;
}

/** Resolve a permalink after pages and language assignments exist. */
function elwmarholding_provision_permalink( int $post_id ): string {
	$url = get_permalink( $post_id );
	return is_string( $url ) ? $url : home_url( '/' );
}

/** Remove untouched WordPress starter pages that collide with managed content. */
function elwmarholding_provision_remove_starter_content(): void {
	$starter_pages = array(
		'sample-page'    => 'Sample Page',
		'privacy-policy' => 'Privacy Policy',
	);

	foreach ( $starter_pages as $slug => $title ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page instanceof WP_Post || $title !== $page->post_title || get_post_meta( $page->ID, '_elwmarholding_provision_key', true ) ) {
			continue;
		}
		if ( ELWMARHOLDING_PROVISION_DRY_RUN ) {
			elwmarholding_provision_log( "Would remove WordPress starter page {$slug}." );
			continue;
		}
		wp_delete_post( $page->ID, true );
		elwmarholding_provision_log( "Removed WordPress starter page {$slug}." );
	}
}

/** Remove obsolete, unprovisioned navigation posts after replacements exist. */
function elwmarholding_provision_remove_duplicate_navigation( array $keep_ids ): void {
	$navigation_posts = get_posts(
		array(
			'post_type'      => 'wp_navigation',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => -1,
		)
	);

	foreach ( $navigation_posts as $navigation ) {
		if ( in_array( $navigation->ID, $keep_ids, true ) || get_post_meta( $navigation->ID, '_elwmarholding_provision_key', true ) ) {
			continue;
		}
		if ( ELWMARHOLDING_PROVISION_DRY_RUN ) {
			elwmarholding_provision_log( "Would remove obsolete navigation {$navigation->ID}." );
			continue;
		}
		wp_delete_post( $navigation->ID, true );
		elwmarholding_provision_log( "Removed obsolete navigation {$navigation->ID}." );
	}
}

$home_sv = <<<'HTML'
<!-- wp:group {"align":"full","className":"ga-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull ga-hero"><!-- wp:group {"className":"ga-hero__inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group ga-hero__inner"><!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Familjeägt sedan 2020</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"ga-hero__title"} --><h1 class="wp-block-heading ga-hero__title">Vi bygger det som <em>består</em>.</h1><!-- /wp:heading -->
<!-- wp:group {"className":"ga-hero__lead","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} --><div class="wp-block-group ga-hero__lead"><!-- wp:paragraph --><p>Elwmar Holding samlar och utvecklar bolag med en gedigen grund. Vårt ägarskap är långsiktigt, vår metod är strukturerad och vårt hantverk är noggrant.</p><!-- /wp:paragraph -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/om-oss/">Läs om oss</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/kontakt/">Kontakta oss</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->
<!-- wp:group {"align":"full","className":"ga-section","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull ga-section"><!-- wp:group {"className":"section-heading","layout":{"type":"constrained"}} --><div class="wp-block-group section-heading"><!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Vårt perspektiv</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Långsiktigt ägande med tydlig riktning.</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Vi tror på stabila verksamheter, yrkesskicklighet och beslut som håller över tid. Varje bolag ska få utvecklas på egna meriter med stöd av en engagerad och ansvarsfull ägare.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->
HTML;

$home_en = <<<'HTML'
<!-- wp:group {"align":"full","className":"ga-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull ga-hero"><!-- wp:group {"className":"ga-hero__inner","layout":{"type":"constrained"}} -->
<div class="wp-block-group ga-hero__inner"><!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Family-owned since 2020</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"ga-hero__title"} --><h1 class="wp-block-heading ga-hero__title">We build what <em>endures</em>.</h1><!-- /wp:heading -->
<!-- wp:group {"className":"ga-hero__lead","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} --><div class="wp-block-group ga-hero__lead"><!-- wp:paragraph --><p>Elwmar Holding brings together and develops companies with solid foundations. Our ownership is long-term, our approach is structured and our craft is meticulous.</p><!-- /wp:paragraph -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/en/about-us/">About us</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/en/contact/">Contact us</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->
<!-- wp:group {"align":"full","className":"ga-section","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull ga-section"><!-- wp:group {"className":"section-heading","layout":{"type":"constrained"}} --><div class="wp-block-group section-heading"><!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Our perspective</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Long-term ownership with a clear direction.</h2><!-- /wp:heading --><!-- wp:paragraph --><p>We believe in stable businesses, professional expertise and decisions that stand the test of time. Each company should develop on its own merits with the support of an engaged and responsible owner.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->
HTML;

$about_sv = <<<'HTML'
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Om Elwmar Holding</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"fontSize":"lg"} --><p class="has-lg-font-size">Elwmar Holding är ett familjeägt holdingbolag med ett långsiktigt perspektiv på ägande och utveckling.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Vi räknar i generationer.</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Vi investerar tid, kapital och engagemang i verksamheter som har en stabil grund och potential att utvecklas vidare. Vår roll är att skapa tydlighet i riktningen, bidra med struktur och ge ledningar utrymme att bygga hållbart.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Så arbetar vi</h2><!-- /wp:heading -->
<!-- wp:list --><ul class="wp-block-list"><li><strong>Långsiktighet:</strong> beslut ska vara bra även bortom nästa kvartal.</li><li><strong>Ansvar:</strong> tydliga mandat och respekt för varje verksamhets särart.</li><li><strong>Hantverk:</strong> kvalitet, yrkesskicklighet och genomförande väger tungt.</li></ul><!-- /wp:list -->
HTML;

$about_en = <<<'HTML'
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">About Elwmar Holding</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"fontSize":"lg"} --><p class="has-lg-font-size">Elwmar Holding is a family-owned holding company with a long-term perspective on ownership and development.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">We think in generations.</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>We invest time, capital and commitment in businesses with solid foundations and the potential to develop further. Our role is to provide clarity of direction, contribute structure and give management teams room to build sustainably.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">How we work</h2><!-- /wp:heading -->
<!-- wp:list --><ul class="wp-block-list"><li><strong>Long-term thinking:</strong> decisions should remain sound beyond the next quarter.</li><li><strong>Responsibility:</strong> clear mandates and respect for the character of each business.</li><li><strong>Craft:</strong> quality, professional expertise and delivery matter.</li></ul><!-- /wp:list -->
HTML;

$contact_sv = <<<'HTML'
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Kontakta oss</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"fontSize":"lg"} --><p class="has-lg-font-size">Har du en verksamhet, idé eller fråga som du vill diskutera? Berätta kort vad det gäller så återkommer vi.</p><!-- /wp:paragraph -->
HTML;

$contact_en = <<<'HTML'
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Contact us</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"fontSize":"lg"} --><p class="has-lg-font-size">Do you have a business, an idea or a question you would like to discuss? Tell us briefly what it concerns and we will get back to you.</p><!-- /wp:paragraph -->
HTML;

$privacy_sv = <<<'HTML'
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Integritet</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Elwmar Holding AB är personuppgiftsansvarig för personuppgifter som behandlas via denna webbplats. Frågor om behandlingen kan skickas till <a href="mailto:info@elwmarholding.se">info@elwmarholding.se</a>.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">När du kontaktar oss</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>När du kontaktar oss behandlar vi de uppgifter du själv lämnar, exempelvis namn, kontaktuppgifter, företag och innehållet i ditt meddelande. Uppgifterna används för att hantera din förfrågan och vidta åtgärder inför en möjlig affärsrelation.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Lagring och mottagare</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Uppgifterna sparas inte längre än vad som behövs för ändamålet eller följer av lag. De kan behandlas av leverantörer som tillhandahåller webbhotell, e-post och teknisk support. Vi säljer inte personuppgifter.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Tekniska uppgifter och kakor</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Webbplatsen och dess driftleverantörer kan behandla IP-adress och tekniska logguppgifter för säkerhet, felsökning och tillförlitlig drift. WordPress använder nödvändiga kakor för inloggade administratörer.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Dina rättigheter</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Du kan begära tillgång till, rättelse eller radering av dina personuppgifter och i vissa fall invända mot eller begränsa behandlingen. Du har också rätt att lämna klagomål till Integritetsskyddsmyndigheten.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p><em>Senast uppdaterad: 19 augusti 2026.</em></p><!-- /wp:paragraph -->
HTML;

$privacy_en = <<<'HTML'
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Privacy</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Elwmar Holding AB is the data controller for personal data processed through this website. Questions about the processing can be sent to <a href="mailto:info@elwmarholding.se">info@elwmarholding.se</a>.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">When you contact us</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>When you contact us, we process the information you provide, such as your name, contact details, company and the contents of your message. The information is used to handle your enquiry and take steps before a possible business relationship.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Retention and recipients</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>The information is not retained longer than necessary for these purposes or as required by law. It may be processed by providers of web hosting, email and technical support. We do not sell personal data.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Technical data and cookies</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>The website and its hosting providers may process IP addresses and technical log data for security, troubleshooting and reliable operation. WordPress uses essential cookies for signed-in administrators.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Your rights</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>You may request access to, correction or erasure of your personal data and, in certain cases, object to or restrict the processing. You also have the right to lodge a complaint with the Swedish Authority for Privacy Protection.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p><em>Last updated: 19 August 2026.</em></p><!-- /wp:paragraph -->
HTML;

try {
	$language_specs = array(
		array( 'locale' => 'sv_SE', 'name' => 'Svenska', 'slug' => 'sv', 'flag' => 'se', 'term_group' => 0 ),
		array( 'locale' => 'en_GB', 'name' => 'English', 'slug' => 'en', 'flag' => 'gb', 'term_group' => 1 ),
	);
	foreach ( $language_specs as $language_spec ) {
		elwmarholding_provision_ensure_language( $language_spec );
	}
	if ( ELWMARHOLDING_PROVISION_DRY_RUN && ( ! elwmarholding_provision_language( 'sv' ) || ! elwmarholding_provision_language( 'en' ) ) ) {
		elwmarholding_provision_log( 'Dry run stopped before page planning because languages must exist to calculate language-aware URLs.' );
		echo wp_json_encode( array( 'dry_run' => true, 'changes' => $GLOBALS['elwmarholding_provision_changes'] ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
		exit( 0 );
	}

	if ( ! ELWMARHOLDING_PROVISION_DRY_RUN ) {
		PLL()->model->update_default_lang( 'sv' );
	}
	elwmarholding_provision_remove_starter_content();

	$page_specs = array(
		'home' => array(
			'sv' => array( 'post_title' => 'Startsida', 'post_name' => 'startsida', 'post_content' => $home_sv, 'menu_order' => 0 ),
			'en' => array( 'post_title' => 'Home', 'post_name' => 'home', 'post_content' => $home_en, 'menu_order' => 0 ),
		),
		'about' => array(
			'sv' => array( 'post_title' => 'Om oss', 'post_name' => 'om-oss', 'post_content' => $about_sv, 'menu_order' => 10 ),
			'en' => array( 'post_title' => 'About us', 'post_name' => 'about-us', 'post_content' => $about_en, 'menu_order' => 10 ),
		),
		'contact' => array(
			'sv' => array( 'post_title' => 'Kontakta oss', 'post_name' => 'kontakt', 'post_content' => $contact_sv, 'menu_order' => 20 ),
			'en' => array( 'post_title' => 'Contact us', 'post_name' => 'contact', 'post_content' => $contact_en, 'menu_order' => 20 ),
		),
		'privacy' => array(
			'sv' => array( 'post_title' => 'Integritetspolicy', 'post_name' => 'integritet', 'post_content' => $privacy_sv, 'menu_order' => 30 ),
			'en' => array( 'post_title' => 'Privacy policy', 'post_name' => 'privacy-policy', 'post_content' => $privacy_en, 'menu_order' => 30 ),
		),
	);

	$pages = array();
	foreach ( $page_specs as $key => $translations ) {
		$pages[ $key ] = array();
		foreach ( $translations as $language => $page_data ) {
			$pages[ $key ][ $language ] = elwmarholding_provision_page( $key, $language, $page_data );
		}
		if ( ! ELWMARHOLDING_PROVISION_DRY_RUN ) {
			pll_save_post_translations( $pages[ $key ] );
		}
	}

	if ( ! ELWMARHOLDING_PROVISION_DRY_RUN ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $pages['home']['sv'] );
		update_option( 'page_for_posts', 0 );
		update_option( 'wp_page_for_privacy_policy', $pages['privacy']['sv'] );
		update_option( 'blogname', 'Elwmar Holding AB' );
		update_option( 'blogdescription', '' );
	}

	$navigation_sv = elwmarholding_provision_navigation(
		'sv',
		'Huvudmeny',
		array(
			array( 'label' => 'Start', 'url' => (string) pll_home_url( 'sv' ) ),
			array( 'label' => 'Om oss', 'url' => elwmarholding_provision_permalink( $pages['about']['sv'] ) ),
			array( 'label' => 'Kontakta oss', 'url' => elwmarholding_provision_permalink( $pages['contact']['sv'] ) ),
		)
	);
	$navigation_en = elwmarholding_provision_navigation(
		'en',
		'Main navigation',
		array(
			array( 'label' => 'Home', 'url' => (string) pll_home_url( 'en' ) ),
			array( 'label' => 'About us', 'url' => elwmarholding_provision_permalink( $pages['about']['en'] ) ),
			array( 'label' => 'Contact us', 'url' => elwmarholding_provision_permalink( $pages['contact']['en'] ) ),
		)
	);

	if ( ! ELWMARHOLDING_PROVISION_DRY_RUN ) {
		if ( PLL()->model->is_translated_post_type( 'wp_navigation' ) ) {
			pll_save_post_translations( array( 'sv' => $navigation_sv, 'en' => $navigation_en ) );
		}
		update_option( 'elwmarholding_primary_navigation_id', $navigation_sv );
		update_option( 'elwmarholding_primary_navigation_id_en', $navigation_en );
		flush_rewrite_rules( false );
	}
	elwmarholding_provision_remove_duplicate_navigation( array( $navigation_sv, $navigation_en ) );

	echo wp_json_encode(
		array(
			'dry_run'     => ELWMARHOLDING_PROVISION_DRY_RUN,
			'pages'       => $pages,
			'navigation'  => array( 'sv' => $navigation_sv, 'en' => $navigation_en ),
			'default_lang'=> ELWMARHOLDING_PROVISION_DRY_RUN ? null : pll_default_language( 'slug' ),
			'changes'     => $GLOBALS['elwmarholding_provision_changes'],
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	) . PHP_EOL;
} catch ( Throwable $error ) {
	fwrite( STDERR, $error->getMessage() . PHP_EOL );
	exit( 1 );
}
