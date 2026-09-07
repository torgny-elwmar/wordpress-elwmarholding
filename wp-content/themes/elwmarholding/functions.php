<?php
/**
 * Elwmar Holding AB Theme Functions
 *
 * @package ElwmarHolding
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ELWMARHOLDING_VERSION', '3.0.3' );
define( 'ELWMARHOLDING_DIR', get_template_directory() );
define( 'ELWMARHOLDING_URI', get_template_directory_uri() );

/**
 * Theme setup.
 */
function elwmarholding_setup(): void {
	load_theme_textdomain( 'elwmarholding', ELWMARHOLDING_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'block-template-parts' );

	// Custom logo support.
	add_theme_support(
		'custom-logo',
		array(
			'height'               => 56,
			'width'                => 240,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => false,
		)
	);

	// Custom header — SVG favicon via wp_head.
	add_theme_support( 'custom-header' );

	// Set image sizes.
	add_image_size( 'elwmarholding-card',  800, 450, true );
	add_image_size( 'elwmarholding-hero', 1920, 800, true );
	add_image_size( 'elwmarholding-thumb', 400, 400, true );
}
add_action( 'after_setup_theme', 'elwmarholding_setup' );

/**
 * Default content for the editable block navigation.
 */
function elwmarholding_default_navigation_content(): string {
	$links = array(
		array( 'label' => __( 'Start', 'elwmarholding' ), 'url' => home_url( '/' ) ),
		array( 'label' => __( 'Om oss', 'elwmarholding' ), 'url' => home_url( '/om-oss/' ) ),
		array( 'label' => __( 'Nyheter', 'elwmarholding' ), 'url' => home_url( '/nyheter/' ) ),
		array( 'label' => __( 'Kontakt', 'elwmarholding' ), 'url' => home_url( '/kontakt/' ) ),
	);
	$content = '';

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

	return $content;
}

/**
 * Return the persistent WordPress navigation used by the header.
 *
 * The ID is stored separately so administrators may rename the menu without
 * disconnecting it from the theme.
 */
function elwmarholding_get_primary_navigation_id( bool $create = false ): int {
	$language    = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';
	$option_name = $language && 'sv' !== $language
		? 'elwmarholding_primary_navigation_id_' . sanitize_key( $language )
		: 'elwmarholding_primary_navigation_id';
	$navigation_id = absint( get_option( $option_name ) );
	if ( $navigation_id && 'wp_navigation' === get_post_type( $navigation_id ) ) {
		return $navigation_id;
	}
	if ( 'elwmarholding_primary_navigation_id' !== $option_name ) {
		return absint( get_option( 'elwmarholding_primary_navigation_id' ) );
	}

	$existing = get_posts(
		array(
			'post_type'      => 'wp_navigation',
			'post_status'    => array( 'publish', 'draft' ),
			'title'          => 'Huvudmeny',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $existing ) {
		$navigation_id = absint( $existing[0] );
		update_option( 'elwmarholding_primary_navigation_id', $navigation_id );
		return $navigation_id;
	}

	if ( ! $create ) {
		return 0;
	}

	$navigation_id = wp_insert_post(
		array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => __( 'Huvudmeny', 'elwmarholding' ),
			'post_name'    => 'huvudmeny',
			'post_content' => elwmarholding_default_navigation_content(),
		),
		true
	);

	if ( is_wp_error( $navigation_id ) ) {
		return 0;
	}

	update_option( 'elwmarholding_primary_navigation_id', (int) $navigation_id );
	return (int) $navigation_id;
}

/** Create the editable menu on activation, with an admin fallback for upgrades. */
function elwmarholding_ensure_primary_navigation(): void {
	elwmarholding_get_primary_navigation_id( true );
}
add_action( 'after_switch_theme', 'elwmarholding_ensure_primary_navigation' );
add_action( 'admin_init', 'elwmarholding_ensure_primary_navigation' );

/**
 * Make the block-theme navigation easy to find from Appearance in admin.
 */
function elwmarholding_navigation_admin_menu(): void {
	global $submenu;
	$submenu['themes.php'][] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		__( 'Huvudmeny', 'elwmarholding' ),
		'edit_theme_options',
		'site-editor.php?path=/navigation',
	);
}
add_action( 'admin_menu', 'elwmarholding_navigation_admin_menu' );

/**
 * Render a compact language selector when Polylang has languages configured.
 */
function elwmarholding_current_page_has_translation( string $language_slug ): bool {
	if ( ! function_exists( 'pll_get_post' ) ) {
		return true;
	}

	$source_id = 0;
	if ( is_front_page() ) {
		$source_id = absint( get_option( 'page_on_front' ) );
	} elseif ( is_home() ) {
		$source_id = absint( get_option( 'page_for_posts' ) );
	} elseif ( is_singular() ) {
		$source_id = get_queried_object_id();
	}

	return ! $source_id || (bool) pll_get_post( $source_id, $language_slug );
}

/** Render the compact language selector. */
function elwmarholding_language_switcher(): string {
	$languages = function_exists( 'pll_the_languages' )
		? pll_the_languages(
			array(
				'raw'                    => 1,
				'hide_if_empty'          => 0,
				'hide_if_no_translation' => 0,
			)
		)
		: array();
	$languages = is_array( $languages ) ? $languages : array();

	if ( count( $languages ) < 2 ) {
		$request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
		$current_slug = str_starts_with( trailingslashit( $request_path ), '/en/' ) ? 'en' : 'sv';
		$fallbacks    = array(
			'sv' => array(
				'slug'         => 'sv',
				'name'         => __( 'Svenska', 'elwmarholding' ),
				'url'          => home_url( '/' ),
				'flag'         => ELWMARHOLDING_URI . '/assets/images/flags/se.png',
				'current_lang' => 'sv' === $current_slug,
			),
			'en' => array(
				'slug'         => 'en',
				'name'         => __( 'English', 'elwmarholding' ),
				'url'          => home_url( '/en/' ),
				'flag'         => ELWMARHOLDING_URI . '/assets/images/flags/gb.png',
				'current_lang' => 'en' === $current_slug,
			),
		);

		foreach ( $languages as $language ) {
			$slug = isset( $language['slug'] ) ? (string) $language['slug'] : '';
			if ( isset( $fallbacks[ $slug ] ) ) {
				$fallbacks[ $slug ] = array_merge( $fallbacks[ $slug ], $language );
			}
		}
		$languages = array_values( $fallbacks );
	}

	$output = '<nav class="language-switcher" aria-label="' . esc_attr__( 'Välj språk', 'elwmarholding' ) . '"><ul>';
	foreach ( $languages as $language ) {
		$slug       = isset( $language['slug'] ) ? (string) $language['slug'] : '';
		$name       = isset( $language['name'] ) ? (string) $language['name'] : $slug;
		$url        = isset( $language['url'] ) ? (string) $language['url'] : '';
		$flag_url   = isset( $language['flag'] ) ? (string) $language['flag'] : '';
		$site_scheme = (string) wp_parse_url( home_url( '/' ), PHP_URL_SCHEME );
		$flag_url   = $flag_url && $site_scheme ? set_url_scheme( $flag_url, $site_scheme ) : $flag_url;
		$is_current = ! empty( $language['current_lang'] );
		$unavailable = ! $is_current && ( ! empty( $language['no_translation'] ) || ! elwmarholding_current_page_has_translation( $slug ) );
		if ( ! $slug || ! $url ) {
			continue;
		}

		$flag = $flag_url
			? '<img src="' . esc_url( $flag_url ) . '" alt="" width="28" height="19">'
			: esc_html( strtoupper( $slug ) );

		$output .= '<li>';
		if ( $unavailable ) {
			/* translators: %s: Language name. */
			$unavailable_label = sprintf( __( '%s – översättning saknas', 'elwmarholding' ), $name );
			$output .= '<span class="language-switcher__unavailable" role="img" aria-label="' . esc_attr( $unavailable_label ) . '" title="' . esc_attr( $unavailable_label ) . '">' . $flag . '</span>';
		} else {
			$output .= '<a href="' . esc_url( $url ) . '" lang="' . esc_attr( $slug ) . '" hreflang="' . esc_attr( $slug ) . '" aria-label="' . esc_attr( $name ) . '"';
			if ( $is_current ) {
				$output .= ' aria-current="page"';
			}
			$output .= '>' . $flag . '</a>';
		}
		$output .= '</li>';
	}

	return $output . '</ul></nav>';
}

/** Register database-aware theme blocks that must render on every request. */
function elwmarholding_register_dynamic_blocks(): void {
	register_block_type(
		'elwmarholding/language-switcher',
		array( 'render_callback' => 'elwmarholding_language_switcher' )
	);
	register_block_type(
		'elwmarholding/contact-form',
		array( 'render_callback' => 'elwmarholding_contact_form_shortcode' )
	);
	register_block_type(
		'elwmarholding/footer-icons',
		array( 'render_callback' => 'elwmarholding_footer_icons_shortcode' )
	);
	register_block_type(
		'elwmarholding/footer-company',
		array( 'render_callback' => 'elwmarholding_footer_company_shortcode' )
	);
}
add_action( 'init', 'elwmarholding_register_dynamic_blocks' );

/**
 * Keep an unconfigured secondary-language home from falling through to the
 * posts index. The language flag becomes a link as soon as a translated front
 * page has been published and connected in Polylang.
 */
function elwmarholding_redirect_untranslated_language_home(): void {
	if ( ! function_exists( 'pll_current_language' ) || ( ! is_front_page() && ! is_home() ) ) {
		return;
	}

	$current_language = (string) pll_current_language( 'slug' );
	$default_language = (string) pll_default_language( 'slug' );
	$front_page_id    = absint( get_option( 'page_on_front' ) );
	if ( ! $current_language || $current_language === $default_language || ( $front_page_id && pll_get_post( $front_page_id, $current_language ) ) ) {
		return;
	}

	$default_home = pll_home_url( $default_language );
	if ( $default_home ) {
		wp_safe_redirect( $default_home, 302 );
		exit;
	}
}
add_action( 'template_redirect', 'elwmarholding_redirect_untranslated_language_home', 1 );

/** Explain the content-language dependency to administrators. */
function elwmarholding_multilingual_admin_notice(): void {
	if ( function_exists( 'pll_the_languages' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-info"><p>
		<?php
		printf(
			/* translators: %s: Link to the plugin installer. */
			wp_kses_post( __( 'Elwmar Holding AB är förberett för språkval. Installera och konfigurera <a href="%s">Polylang</a> för att skapa översatta sidor och visa språkväxlaren.', 'elwmarholding' ) ),
			esc_url( admin_url( 'plugin-install.php?s=Polylang&tab=search&type=term' ) )
		);
		?>
	</p></div>
	<?php
}
add_action( 'admin_notices', 'elwmarholding_multilingual_admin_notice' );

/**
 * Enqueue theme assets.
 */
function elwmarholding_enqueue_assets(): void {
	// Brand typography: Fraunces for voice, Inter for structure, Yellowtail for signatures.
	wp_enqueue_style(
		'elwmarholding-google-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&family=Inter:wght@400;500;600&family=Yellowtail&display=swap',
		array(),
		null
	);

	// Main stylesheet (theme.json generates block styles; style.css holds the header).
	wp_enqueue_style(
		'elwmarholding-style',
		get_stylesheet_uri(),
		array( 'elwmarholding-google-fonts' ),
		ELWMARHOLDING_VERSION
	);

	// Theme supplemental CSS.
	wp_enqueue_style(
		'elwmarholding-theme',
		ELWMARHOLDING_URI . '/assets/css/theme.css',
		array( 'elwmarholding-style' ),
		ELWMARHOLDING_VERSION
	);

	wp_enqueue_style(
		'elwmarholding-brand',
		ELWMARHOLDING_URI . '/assets/css/brand.css',
		array( 'elwmarholding-theme' ),
		ELWMARHOLDING_VERSION
	);

	wp_enqueue_script(
		'elwmarholding-theme',
		ELWMARHOLDING_URI . '/assets/js/theme.js',
		array( 'wp-i18n' ),
		ELWMARHOLDING_VERSION,
		true
	);

	wp_set_script_translations(
		'elwmarholding-theme',
		'elwmarholding',
		ELWMARHOLDING_DIR . '/languages'
	);
}
add_action( 'wp_enqueue_scripts', 'elwmarholding_enqueue_assets' );

/**
 * Allow SVG uploads in the media library (restricted to admins/editors).
 *
 * @param array<string,string> $mimes Allowed MIME types.
 * @return array<string,string>
 */
function elwmarholding_allow_svg_uploads( array $mimes ): array {
	if ( current_user_can( 'manage_options' ) ) {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
	}
	return $mimes;
}
add_filter( 'upload_mimes', 'elwmarholding_allow_svg_uploads' );

/**
 * Fix SVG display in the media library.
 *
 * @param array<string,mixed>|false $response      Attachment data.
 * @param WP_Post                   $attachment     Attachment post.
 * @param array<int>|false          $meta           Attachment meta.
 * @return array<string,mixed>|false
 */
function elwmarholding_fix_svg_thumb( $response, WP_Post $attachment, $meta ) {
	if ( $response && 'image/svg+xml' === $response['mime'] ) {
		$response['sizes'] = array(
			'full' => array(
				'url'    => $response['url'],
				'width'  => 240,
				'height' => 56,
			),
		);
	}
	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'elwmarholding_fix_svg_thumb', 10, 3 );

/**
 * Customise the excerpt length.
 *
 * @return int Number of words.
 */
function elwmarholding_excerpt_length(): int {
	return 25;
}
add_filter( 'excerpt_length', 'elwmarholding_excerpt_length' );

/**
 * Replace the default "…" excerpt suffix.
 *
 * @return string
 */
function elwmarholding_excerpt_more(): string {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'elwmarholding_excerpt_more' );

/**
 * Remove the Gutenberg block library CSS on the front end (theme.json covers it).
 * Only active for block-theme context.
 */
function elwmarholding_dequeue_block_css(): void {
	// Keep core block styles but remove unused default patterns CSS.
	wp_dequeue_style( 'wp-block-library-theme' );
}
add_action( 'wp_enqueue_scripts', 'elwmarholding_dequeue_block_css', 100 );

/**
 * Add preconnect hints for Google Fonts.
 */
function elwmarholding_preconnect_google_fonts(): void {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . PHP_EOL;
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . PHP_EOL;
}
add_action( 'wp_head', 'elwmarholding_preconnect_google_fonts', 1 );

/** Use the bundled brand mark as the site icon across deployed environments. */
function elwmarholding_site_icon(): void {
	$icon_url = ELWMARHOLDING_URI . '/assets/images/elwmar-brand-logo.png';
	printf( '<link rel="icon" href="%s" sizes="512x512">' . PHP_EOL, esc_url( $icon_url ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . PHP_EOL, esc_url( $icon_url ) );
}

/** Prevent a database-synced site icon from overriding the bundled brand icon. */
function elwmarholding_replace_wordpress_site_icon(): void {
	remove_action( 'wp_head', 'wp_site_icon', 99 );
}
add_action( 'after_setup_theme', 'elwmarholding_replace_wordpress_site_icon', 20 );
add_action( 'wp_head', 'elwmarholding_site_icon', 2 );

/**
 * Set the content width for embedded content.
 *
 * @global int $content_width
 */
function elwmarholding_content_width(): void {
	$GLOBALS['content_width'] = 740;
}
add_action( 'after_setup_theme', 'elwmarholding_content_width', 0 );

/**
 * Return the current language's version of a page identified by its Swedish slug.
 */
function elwmarholding_get_translated_page_url( string $slug ): string {
	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post && function_exists( 'pll_get_post' ) && function_exists( 'pll_current_language' ) ) {
		$translated_id = pll_get_post( $page->ID, pll_current_language( 'slug' ) );
		if ( $translated_id ) {
			return (string) get_permalink( $translated_id );
		}
	}

	return $page instanceof WP_Post ? (string) get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/** Return the current language's home URL. */
function elwmarholding_get_home_url(): string {
	if ( function_exists( 'pll_home_url' ) && function_exists( 'pll_current_language' ) ) {
		return (string) pll_home_url( pll_current_language( 'slug' ) );
	}

	return home_url( '/' );
}

/**
 * Return the company information page URL.
 */
function elwmarholding_get_about_url(): string {
	return elwmarholding_get_translated_page_url( 'om-oss' );
}

/**
 * Return the configured WordPress privacy page URL.
 */
function elwmarholding_get_privacy_url(): string {
	return elwmarholding_get_translated_page_url( 'integritet' );
}

/**
 * Return the contact page URL.
 */
function elwmarholding_get_contact_url(): string {
	return elwmarholding_get_translated_page_url( 'kontakt' );
}

/**
 * Render a portable link to the contact page from database content.
 *
 * @param array<string,string> $attributes Shortcode attributes.
 */
function elwmarholding_contact_link_shortcode( array $attributes = array() ): string {
	$attributes = shortcode_atts(
		array( 'label' => __( 'Kontakta oss', 'elwmarholding' ) ),
		$attributes,
		'elwmarholding_contact_link'
	);

	return sprintf(
		'<a href="%s">%s</a>',
		esc_url( elwmarholding_get_contact_url() ),
		esc_html( $attributes['label'] )
	);
}
add_shortcode( 'elwmarholding_contact_link', 'elwmarholding_contact_link_shortcode' );

/**
 * Return an inline icon. The SVG is decorative; the surrounding control owns
 * the accessible name.
 *
 * @param string $icon Icon identifier.
 */
function elwmarholding_footer_icon_svg( string $icon ): string {
	$paths = array(
		'mail'      => '<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M3 5.5h18v13H3zM3.7 6.2 12 13l8.3-6.8"/>',
		'about'     => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 4.25a1.35 1.35 0 1 1 0 2.7 1.35 1.35 0 0 1 0-2.7Zm1.4 11.5h-2.8v-7.2h2.8v7.2Z"/>',
		'privacy'   => '<path d="M12 2.2 4.5 5.1v5.6c0 4.8 3.1 9.2 7.5 11.1 4.4-1.9 7.5-6.3 7.5-11.1V5.1L12 2.2Zm0 3 4.6 1.7v3.8c0 3.2-1.8 6.3-4.6 7.9-2.8-1.6-4.6-4.7-4.6-7.9V6.9L12 5.2Zm0 2.9a2.1 2.1 0 0 0-2.1 2.1v1h-.7v4.4h5.6v-4.4h-.7v-1A2.1 2.1 0 0 0 12 8.1Zm0 1.3c.5 0 .8.4.8.8v1h-1.6v-1c0-.4.3-.8.8-.8Z"/>',
		'feed'      => '<path d="M5.1 16.7a2.2 2.2 0 1 0 0 4.4 2.2 2.2 0 0 0 0-4.4ZM3 9.2v3.1a8.7 8.7 0 0 1 8.7 8.7h3.1C14.8 14.5 9.5 9.2 3 9.2ZM3 3v3.1C11.2 6.1 17.9 12.8 17.9 21H21C21 11.1 12.9 3 3 3Z"/>',
		'facebook'  => '<path d="M13.7 22v-9h3l.5-3.5h-3.5V7.3c0-1 .3-1.7 1.8-1.7h1.9V2.5c-.3 0-1.5-.1-2.8-.1-2.8 0-4.7 1.7-4.7 4.8v2.3H6.8V13h3.1v9h3.8Z"/>',
		'instagram' => '<path d="M12 2.2c3.2 0 3.6 0 4.9.1 3.3.2 4.8 1.7 5 5 .1 1.2.1 1.6.1 4.8s0 3.6-.1 4.9c-.2 3.3-1.7 4.8-5 5-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-3.3-.2-4.8-1.7-5-5C2 15.7 2 15.3 2 12.1s0-3.6.1-4.8c.2-3.3 1.7-4.8 5-5 1.3-.1 1.7-.1 4.9-.1Zm0 1.8c-3.1 0-3.5 0-4.7.1-2.4.1-3.3 1.2-3.4 3.4-.1 1.2-.1 1.6-.1 4.6s0 3.5.1 4.7c.1 2.3 1.1 3.3 3.4 3.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c2.3-.1 3.3-1.1 3.4-3.4.1-1.2.1-1.6.1-4.7s0-3.4-.1-4.6c-.1-2.3-1.1-3.3-3.4-3.4C15.5 4 15.1 4 12 4Zm0 3.1a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 8.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Zm5.2-9.6a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/>',
		'linkedin'  => '<path d="M5.3 7.8A2.3 2.3 0 1 0 5.3 3a2.3 2.3 0 0 0 0 4.7ZM3.3 21h4V9.2h-4V21Zm6.4 0h4v-5.9c0-1.6.3-3.1 2.3-3.1s2 1.8 2 3.2V21h4v-6.5c0-3.2-.7-5.7-4.5-5.7-1.8 0-3.1 1-3.6 2h-.1V9.2H9.7V21Z"/>',
	);

	$path = $paths[ $icon ] ?? '';

	return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/**
 * Render the compact footer icon navigation.
 */
function elwmarholding_footer_icons_shortcode(): string {
	$social_urls = get_option( 'elwmarholding_social_urls', array() );
	$social_urls = is_array( $social_urls ) ? $social_urls : array();
	$footer_urls = get_option( 'elwmarholding_footer_urls', array() );
	$footer_urls = is_array( $footer_urls ) ? $footer_urls : array();
	$about_url   = esc_url_raw( (string) ( $footer_urls['about'] ?? '' ) );
	$privacy_url = esc_url_raw( (string) ( $footer_urls['privacy'] ?? '' ) );
	$feed_url    = esc_url_raw( (string) ( $footer_urls['feed'] ?? '' ) );
	$items       = array(
		array( 'icon' => 'about', 'label' => __( 'Om oss', 'elwmarholding' ), 'url' => $about_url ?: elwmarholding_get_about_url() ),
		array( 'icon' => 'privacy', 'label' => __( 'Integritet', 'elwmarholding' ), 'url' => $privacy_url ?: elwmarholding_get_privacy_url() ),
		array( 'icon' => 'feed', 'label' => __( 'RSS-flöde', 'elwmarholding' ), 'url' => $feed_url ?: get_bloginfo( 'rss2_url' ) ),
		array( 'icon' => 'mail', 'label' => __( 'Kontakta oss', 'elwmarholding' ), 'url' => elwmarholding_get_contact_url() ),
		array( 'icon' => 'linkedin', 'label' => 'LinkedIn', 'url' => $social_urls['linkedin'] ?? '', 'social' => true ),
		array( 'icon' => 'facebook', 'label' => 'Facebook', 'url' => $social_urls['facebook'] ?? '', 'social' => true ),
		array( 'icon' => 'instagram', 'label' => 'Instagram', 'url' => $social_urls['instagram'] ?? '', 'social' => true ),
	);

	$output = '<nav class="footer-icon-nav" aria-label="' . esc_attr__( 'Kontakt, företagsinformation och sociala medier', 'elwmarholding' ) . '"><ul class="footer-icon-list">';

	foreach ( $items as $item ) {
		$label = (string) $item['label'];
		$icon  = elwmarholding_footer_icon_svg( (string) $item['icon'] );
		$url   = (string) $item['url'];

		$output .= '<li>';
		if ( $url ) {
			$rel     = ! empty( $item['social'] ) ? ' rel="me"' : '';
			$output .= '<a class="footer-icon" href="' . esc_url( $url ) . '" title="' . esc_attr( $label ) . '"' . $rel . '><span class="screen-reader-text">' . esc_html( $label ) . '</span>' . $icon . '</a>';
		} else {
			/* translators: %s: Name of the social network or footer destination. */
			$pending = sprintf( __( '%s – länk läggs till senare', 'elwmarholding' ), $label );
			$output .= '<span class="footer-icon is-disabled" role="img" aria-label="' . esc_attr( $pending ) . '" title="' . esc_attr( $pending ) . '">' . $icon . '</span>';
		}
		$output .= '</li>';
	}

	return $output . '</ul></nav>';
}
add_shortcode( 'elwmarholding_footer_icons', 'elwmarholding_footer_icons_shortcode' );

/** Render database-backed company details in the footer. */
function elwmarholding_footer_company_shortcode(): string {
	$footer_urls        = get_option( 'elwmarholding_footer_urls', array() );
	$footer_urls        = is_array( $footer_urls ) ? $footer_urls : array();
	$organization_number = sanitize_text_field( (string) ( $footer_urls['organization_number'] ?? '' ) );
	$email              = sanitize_email( (string) ( $footer_urls['email'] ?? '' ) );
	$output             = '<div class="footer-company"><strong>' . esc_html__( 'Elwmar Holding AB', 'elwmarholding' ) . '</strong>';

	if ( $organization_number ) {
		$output .= '<span>' . sprintf(
			/* translators: %s: Company registration number. */
			esc_html__( 'Organisationsnummer: %s', 'elwmarholding' ),
			esc_html( $organization_number )
		) . '</span>';
	}
	if ( $email ) {
		$output .= '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
	}

	return $output . '</div>';
}
add_shortcode( 'elwmarholding_footer_company', 'elwmarholding_footer_company_shortcode' );

/**
 * Sanitize footer social profile settings.
 *
 * @param mixed $value Submitted setting value.
 * @return array<string,string>
 */
function elwmarholding_sanitize_social_urls( $value ): array {
	$value = is_array( $value ) ? $value : array();
	$clean = array();

	foreach ( array( 'facebook', 'instagram', 'linkedin' ) as $network ) {
		$clean[ $network ] = isset( $value[ $network ] ) ? esc_url_raw( trim( (string) $value[ $network ] ) ) : '';
	}

	return $clean;
}

/**
 * Sanitize configurable footer destinations.
 *
 * Empty URLs retain the corresponding automatic WordPress destination.
 *
 * @param mixed $value Submitted setting value.
 * @return array<string,string>
 */
function elwmarholding_sanitize_footer_urls( $value ): array {
	$value = is_array( $value ) ? $value : array();

	return array(
		'email'               => isset( $value['email'] ) ? sanitize_email( (string) $value['email'] ) : '',
		'organization_number' => isset( $value['organization_number'] ) ? sanitize_text_field( (string) $value['organization_number'] ) : '',
		'about'               => isset( $value['about'] ) ? esc_url_raw( trim( (string) $value['about'] ) ) : '',
		'privacy'             => isset( $value['privacy'] ) ? esc_url_raw( trim( (string) $value['privacy'] ) ) : '',
		'feed'                => isset( $value['feed'] ) ? esc_url_raw( trim( (string) $value['feed'] ) ) : '',
	);
}

/**
 * Register database-backed theme settings for social profiles.
 */
function elwmarholding_register_theme_settings(): void {
	register_setting(
		'elwmarholding_theme_settings',
		'elwmarholding_footer_urls',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'elwmarholding_sanitize_footer_urls',
			'default'           => array(),
		)
	);

	register_setting(
		'elwmarholding_theme_settings',
		'elwmarholding_social_urls',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'elwmarholding_sanitize_social_urls',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'elwmarholding_register_theme_settings' );

/**
 * Add the theme settings page below Appearance.
 */
function elwmarholding_add_theme_settings_page(): void {
	add_theme_page(
		__( 'Elwmar Holding AB – temainställningar', 'elwmarholding' ),
		__( 'Temainställningar', 'elwmarholding' ),
		'edit_theme_options',
		'elwmarholding-settings',
		'elwmarholding_render_theme_settings_page'
	);
}
add_action( 'admin_menu', 'elwmarholding_add_theme_settings_page' );

/**
 * Render the theme settings screen.
 */
function elwmarholding_render_theme_settings_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$urls = get_option( 'elwmarholding_social_urls', array() );
	$urls = is_array( $urls ) ? $urls : array();
	$footer_urls = get_option( 'elwmarholding_footer_urls', array() );
	$footer_urls = is_array( $footer_urls ) ? $footer_urls : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Elwmar Holding AB – temainställningar', 'elwmarholding' ); ?></h1>
		<form action="options.php" method="post">
			<?php settings_fields( 'elwmarholding_theme_settings' ); ?>
			<h2><?php esc_html_e( 'Företagslänkar', 'elwmarholding' ); ?></h2>
			<p><?php esc_html_e( 'Lämna en webbadress tom för att använda WordPress automatiska mål för sidan eller flödet.', 'elwmarholding' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="elwmarholding-email"><?php esc_html_e( 'E-postadress', 'elwmarholding' ); ?></label></th>
					<td><input class="regular-text" type="email" id="elwmarholding-email" name="elwmarholding_footer_urls[email]" value="<?php echo esc_attr( $footer_urls['email'] ?? 'info@elwmarholding.se' ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="elwmarholding-organization-number"><?php esc_html_e( 'Organisationsnummer', 'elwmarholding' ); ?></label></th>
					<td><input class="regular-text" type="text" id="elwmarholding-organization-number" name="elwmarholding_footer_urls[organization_number]" value="<?php echo esc_attr( $footer_urls['organization_number'] ?? '' ); ?>" placeholder="XXXXXX-XXXX"></td>
				</tr>
				<?php
				$footer_fields = array(
					'about'   => array( __( 'Om oss', 'elwmarholding' ), elwmarholding_get_about_url() ),
					'privacy' => array( __( 'Integritet', 'elwmarholding' ), elwmarholding_get_privacy_url() ),
					'feed'    => array( __( 'RSS-flöde', 'elwmarholding' ), get_bloginfo( 'rss2_url' ) ),
				);
				foreach ( $footer_fields as $key => $field ) :
					?>
					<tr>
						<th scope="row"><label for="elwmarholding-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
						<td>
							<input class="regular-text code" type="url" id="elwmarholding-<?php echo esc_attr( $key ); ?>" name="elwmarholding_footer_urls[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $footer_urls[ $key ] ?? '' ); ?>" placeholder="<?php echo esc_attr( $field[1] ); ?>">
							<p class="description"><?php /* translators: %s: Automatically selected destination URL. */ printf( esc_html__( 'Automatiskt mål: %s', 'elwmarholding' ), esc_html( $field[1] ) ); ?></p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<h2><?php esc_html_e( 'Sociala profiler', 'elwmarholding' ); ?></h2>
			<p><?php esc_html_e( 'En tom adress visar ikonen i foten utan att göra den klickbar.', 'elwmarholding' ); ?></p>
			<table class="form-table" role="presentation">
				<?php foreach ( array( 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn' ) as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="elwmarholding-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="regular-text code" type="url" id="elwmarholding-<?php echo esc_attr( $key ); ?>" name="elwmarholding_social_urls[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $urls[ $key ] ?? '' ); ?>" placeholder="https://"></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Get the contact form recipient from the database-backed theme settings.
 */
function elwmarholding_contact_recipient(): string {
	$footer_urls = get_option( 'elwmarholding_footer_urls', array() );
	$email       = is_array( $footer_urls ) ? sanitize_email( (string) ( $footer_urls['email'] ?? '' ) ) : '';

	return $email ?: 'info@elwmarholding.se';
}

/**
 * Return a multibyte-safe string length.
 *
 * @param string $value Text to measure.
 */
function elwmarholding_text_length( string $value ): int {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
}

/**
 * Render and process the lightweight contact form.
 *
 * Submissions are emailed and are not stored in WordPress.
 */
function elwmarholding_contact_form_shortcode(): string {
	$values = array(
		'name'    => '',
		'email'   => '',
		'company' => '',
		'message' => '',
	);
	$errors  = array();
	$success = false;

	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['elwmarholding_contact_submit'] ) ) {
		$values['name']    = isset( $_POST['ga_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ga_contact_name'] ) ) : '';
		$values['email']   = isset( $_POST['ga_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['ga_contact_email'] ) ) : '';
		$values['company'] = isset( $_POST['ga_contact_company'] ) ? sanitize_text_field( wp_unslash( $_POST['ga_contact_company'] ) ) : '';
		$values['message'] = isset( $_POST['ga_contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ga_contact_message'] ) ) : '';
		$honeypot          = isset( $_POST['ga_contact_website'] ) ? trim( (string) wp_unslash( $_POST['ga_contact_website'] ) ) : '';
		$nonce             = isset( $_POST['_ga_contact_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ga_contact_nonce'] ) ) : '';
		$started_at        = isset( $_POST['contact_form_started_at'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_form_started_at'] ) ) : '';
		$signature         = isset( $_POST['contact_form_signature'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_form_signature'] ) ) : '';

		if ( Elwmar_Contact_Form_Protection::is_automated( 'elwmarholding', $honeypot, $started_at, $signature ) ) {
			// Silently accept automated submissions without sending mail.
			$success = true;
		} elseif ( ! wp_verify_nonce( $nonce, 'elwmarholding_contact' ) ) {
			$errors['form'] = __( 'Formuläret kunde inte verifieras. Ladda om sidan och försök igen.', 'elwmarholding' );
		} else {
			if ( elwmarholding_text_length( $values['name'] ) < 2 || elwmarholding_text_length( $values['name'] ) > 100 ) {
				$errors['name'] = __( 'Ange ditt namn.', 'elwmarholding' );
			}
			if ( ! is_email( $values['email'] ) ) {
				$errors['email'] = __( 'Ange en giltig e-postadress.', 'elwmarholding' );
			}
			if ( elwmarholding_text_length( $values['company'] ) > 150 ) {
				$errors['company'] = __( 'Företagsnamnet är för långt.', 'elwmarholding' );
			}
			if ( elwmarholding_text_length( $values['message'] ) < 10 || elwmarholding_text_length( $values['message'] ) > 5000 ) {
				$errors['message'] = __( 'Meddelandet behöver innehålla mellan 10 och 5 000 tecken.', 'elwmarholding' );
			}

			if ( ! $errors && Elwmar_Contact_Form_Protection::is_rate_limited( 'elwmarholding', $values['email'] ) ) {
				$success = true;
				$values  = array_fill_keys( array_keys( $values ), '' );
			} elseif ( ! $errors ) {
				/* translators: %s: Name entered in the contact form. */
				$subject = sprintf( __( 'Ny kontaktförfrågan från %s', 'elwmarholding' ), $values['name'] );
				$body    = implode(
					"\n",
					array(
						/* translators: %s: Name entered in the contact form. */
						sprintf( __( 'Namn: %s', 'elwmarholding' ), $values['name'] ),
						/* translators: %s: Email address entered in the contact form. */
						sprintf( __( 'E-post: %s', 'elwmarholding' ), $values['email'] ),
						/* translators: %s: Company entered in the contact form, or a translated fallback. */
						sprintf( __( 'Företag: %s', 'elwmarholding' ), $values['company'] ?: __( 'Ej angivet', 'elwmarholding' ) ),
						'',
						__( 'Meddelande:', 'elwmarholding' ),
						$values['message'],
					)
				);
				$headers = array( 'Reply-To: ' . $values['email'] );

				if ( wp_mail( elwmarholding_contact_recipient(), $subject, $body, $headers ) ) {
					$success = true;
					$values  = array_fill_keys( array_keys( $values ), '' );
				} else {
					$errors['form'] = __( 'Meddelandet kunde inte skickas just nu. Vänta en liten stund och försök igen.', 'elwmarholding' );
				}
			}
		}
	}

	$privacy_url = elwmarholding_get_privacy_url();
	ob_start();
	?>
	<div class="ga-contact-form-wrap" id="kontaktformular">
		<?php if ( $success ) : ?>
			<div class="ga-form-notice is-success" role="status" tabindex="-1">
				<strong><?php esc_html_e( 'Tack!', 'elwmarholding' ); ?></strong>
				<?php esc_html_e( 'Ditt meddelande är skickat. Vi återkommer så snart vi kan.', 'elwmarholding' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $errors ) : ?>
			<div class="ga-form-notice is-error" role="alert">
				<strong><?php esc_html_e( 'Kontrollera formuläret:', 'elwmarholding' ); ?></strong>
				<ul>
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( ! $success ) : ?>
			<form class="ga-contact-form" method="post" action="#kontaktformular">
				<?php wp_nonce_field( 'elwmarholding_contact', '_ga_contact_nonce' ); ?>
				<?php echo Elwmar_Contact_Form_Protection::fields( 'elwmarholding' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="ga-contact-form__grid">
					<div class="ga-form-field">
						<label for="ga-contact-name"><?php esc_html_e( 'Namn', 'elwmarholding' ); ?> <span aria-hidden="true">*</span></label>
						<input id="ga-contact-name" name="ga_contact_name" type="text" value="<?php echo esc_attr( $values['name'] ); ?>" autocomplete="name" maxlength="100" required<?php echo isset( $errors['name'] ) ? ' aria-invalid="true"' : ''; ?>>
					</div>
					<div class="ga-form-field">
						<label for="ga-contact-email"><?php esc_html_e( 'E-post', 'elwmarholding' ); ?> <span aria-hidden="true">*</span></label>
						<input id="ga-contact-email" name="ga_contact_email" type="email" value="<?php echo esc_attr( $values['email'] ); ?>" autocomplete="email" maxlength="254" required<?php echo isset( $errors['email'] ) ? ' aria-invalid="true"' : ''; ?>>
					</div>
				</div>
				<div class="ga-form-field">
					<label for="ga-contact-company"><?php esc_html_e( 'Företag', 'elwmarholding' ); ?> <span class="ga-optional"><?php esc_html_e( 'frivilligt', 'elwmarholding' ); ?></span></label>
					<input id="ga-contact-company" name="ga_contact_company" type="text" value="<?php echo esc_attr( $values['company'] ); ?>" autocomplete="organization" maxlength="150"<?php echo isset( $errors['company'] ) ? ' aria-invalid="true"' : ''; ?>>
				</div>
				<div class="ga-form-field">
					<label for="ga-contact-message"><?php esc_html_e( 'Vad vill du ha hjälp med?', 'elwmarholding' ); ?> <span aria-hidden="true">*</span></label>
					<textarea id="ga-contact-message" name="ga_contact_message" rows="6" minlength="10" maxlength="5000" required<?php echo isset( $errors['message'] ) ? ' aria-invalid="true"' : ''; ?>><?php echo esc_textarea( $values['message'] ); ?></textarea>
				</div>
				<div class="ga-contact-honeypot" aria-hidden="true">
					<label for="ga-contact-website"><?php esc_html_e( 'Lämna detta fält tomt', 'elwmarholding' ); ?></label>
					<input id="ga-contact-website" name="ga_contact_website" type="text" value="" tabindex="-1" autocomplete="off">
				</div>
				<p class="ga-contact-privacy" id="ga-contact-privacy"><?php /* translators: %s: URL to the privacy page. */ printf( wp_kses( __( 'När du skickar formuläret behandlar vi uppgifterna för att besvara din förfrågan. Läs mer under <a href="%s">Integritet</a>.', 'elwmarholding' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( $privacy_url ) ); ?></p>
				<button class="ga-contact-submit" type="submit" name="elwmarholding_contact_submit" value="1"><?php esc_html_e( 'Skicka meddelande', 'elwmarholding' ); ?> <span aria-hidden="true">↗</span></button>
			</form>
		<?php endif; ?>
	</div>
	<?php

	return (string) ob_get_clean();
}
add_shortcode( 'elwmarholding_contact_form', 'elwmarholding_contact_form_shortcode' );

/**
 * Keep selected public paths usable in installations that use plain permalinks.
 *
 * wp-env routes the path through WordPress even when the database expects a
 * query-string URL. Limit the compatibility redirects to known theme routes
 * so no other site routes are changed.
 */
function elwmarholding_redirect_plain_page_paths(): void {
	if ( is_admin() || get_option( 'permalink_structure' ) ) {
		return;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	$home_path    = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$request_path = trim( $request_path, '/' );
	$home_path    = trim( $home_path, '/' );

	if ( $home_path && str_starts_with( $request_path, $home_path . '/' ) ) {
		$request_path = substr( $request_path, strlen( $home_path ) + 1 );
	}

	$request_path = trim( $request_path, '/' );

	if ( ! in_array( $request_path, array( 'kontakt', 'blog', 'tjanster', 'arbetssatt' ), true ) ) {
		return;
	}

	$page = get_page_by_path( $request_path );
	if ( ! $page instanceof WP_Post ) {
		return;
	}

	wp_safe_redirect( add_query_arg( 'page_id', $page->ID, home_url( '/' ) ), 302 );
	exit;
}
add_action( 'template_redirect', 'elwmarholding_redirect_plain_page_paths', 1 );
