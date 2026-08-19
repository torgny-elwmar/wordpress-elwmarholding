<?php
/**
 * Idempotently create and connect the English page set in Polylang.
 */

$_SERVER['HTTP_HOST']   = getenv( 'ELWMARHOLDING_IMPORT_HOST' ) ?: 'localhost';
$_SERVER['REQUEST_URI'] = '/';

require_once '/var/www/html/wp-load.php';

if ( ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) ) {
	fwrite( STDERR, "Polylang is not active.\n" );
	exit( 1 );
}

/**
 * Create or update an English translation and connect it to its Swedish source.
 *
 * @param int    $source_id Swedish source post ID.
 * @param string $title     English title.
 * @param string $slug      English slug.
 * @param string $content   English block content.
 */
function elwmarholding_import_english_page( int $source_id, string $title, string $slug, string $content ): int {
	$source = get_post( $source_id );
	if ( ! $source instanceof WP_Post || 'page' !== $source->post_type ) {
		throw new RuntimeException( "Swedish source page {$source_id} was not found." );
	}

	$english_id = (int) pll_get_post( $source_id, 'en' );
	$post_data  = array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'post_author'    => $source->post_author,
		'post_title'     => $title,
		'post_name'      => $slug,
		'post_content'   => $content,
		'post_excerpt'   => '',
		'post_parent'    => 0,
		'menu_order'     => $source->menu_order,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	);

	if ( $english_id ) {
		$post_data['ID'] = $english_id;
		$result          = wp_update_post( wp_slash( $post_data ), true );
	} else {
		$result = wp_insert_post( wp_slash( $post_data ), true );
	}

	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}

	$english_id = (int) $result;
	pll_set_post_language( $english_id, 'en' );
	pll_save_post_translations( array( 'sv' => $source_id, 'en' => $english_id ) );

	return $english_id;
}

/** Translate the editable Swedish home-page block document. */
function elwmarholding_english_home_content(): string {
	$source = get_post( 31 );
	if ( ! $source instanceof WP_Post ) {
		throw new RuntimeException( 'The Swedish home page was not found.' );
	}

	return strtr(
		$source->post_content,
		array(
			'Hållbar tillväxt. Full fart framåt.' => 'Sustainable growth. Full speed ahead.',
			'Vi gör grön<br><em>omställning</em><br>till affär.' => 'We turn the green<br><em>transition</em><br>into business.',
			'Elwmar Holding AB hjälper företag att gå från höga ambitioner till mätbara resultat — snabbare, smartare och med tydlig kommersiell riktning.' => 'Elwmar Holding AB helps companies move from bold ambitions to measurable results — faster, smarter and with a clear commercial direction.',
			'Accelerera nu' => 'Accelerate now',
			'<span>Strategi</span>' => '<span>Strategy</span>',
			'<span>Genomförande</span>' => '<span>Delivery</span>',
			'Där ambition får fäste' => 'Where ambition gains traction',
			'Från riktning<br>till rörelse.' => 'From direction<br>to momentum.',
			'Vi kombinerar affärsförståelse, specialistkompetens och ett obevekligt fokus på genomförande.' => 'We combine business insight, specialist expertise and a relentless focus on delivery.',
			'Strategisk<br>skärpa' => 'Strategic<br>clarity',
			'Vi identifierar de initiativ som skapar verklig effekt för både klimatet och affären.' => 'We identify the initiatives that create real impact for both the climate and the business.',
			'Utforska potentialen' => 'Explore the potential',
			'Snabbare<br>innovation' => 'Faster<br>innovation',
			'Vi omsätter idéer till testbara lösningar och bygger momentum utan onödig friktion.' => 'We turn ideas into testable solutions and build momentum without unnecessary friction.',
			'Skapa nästa steg' => 'Create the next step',
			'Mätbart<br>genomslag' => 'Measurable<br>impact',
			'Vi säkrar ansvar, tempo och uppföljning så att omställningen syns på sista raden.' => 'We secure ownership, pace and follow-up so the transition is visible on the bottom line.',
			'Gå från plan till effekt' => 'Move from plan to impact',
			'Vårt arbetssätt' => 'Our approach',
			'Mindre snack.<br><em>Mer verkstad.</em>' => 'Less talk.<br><em>More action.</em>',
			'Förändring behöver inte vara långsam. Vi samlar rätt människor runt rätt problem, fattar beslut på fakta och bygger fart genom konkreta leveranser.' => 'Change does not have to be slow. We bring the right people together around the right problem, make evidence-based decisions and build momentum through tangible delivery.',
			'<span>Rikta</span><small>Prioritera potentialen</small>' => '<span>Focus</span><small>Prioritise the potential</small>',
			'<span>Testa</span><small>Bevisa värdet snabbt</small>' => '<span>Test</span><small>Prove the value quickly</small>',
			'<span>Skala</span><small>Gör effekten bestående</small>' => '<span>Scale</span><small>Make the impact last</small>',
			'Perspektiv' => 'Perspectives',
			'Senaste<br>insikterna.' => 'Latest<br>insights.',
			'Se alla insikter' => 'View all insights',
			'Läs vidare' => 'Read more',
			'Redo när ni är' => 'Ready when you are',
			'Låt oss få<br>fart på det.' => 'Let’s build<br>momentum.',
			'Starta samtalet' => 'Start the conversation',
			'href="/kontakt"' => 'href="/en/contact/"',
			'href="/blog"' => 'href="/en/insights/"',
		)
	);
}

/** Translate a source page by replacing its complete editorial strings. */
function elwmarholding_translate_source_content( int $source_id, array $translations ): string {
	$source = get_post( $source_id );
	if ( ! $source instanceof WP_Post ) {
		throw new RuntimeException( "Source page {$source_id} was not found." );
	}

	return strtr( $source->post_content, $translations );
}

try {
	$pages = array();
	$pages['home'] = elwmarholding_import_english_page( 31, 'Home', 'home', elwmarholding_english_home_content() );
	$pages['services'] = elwmarholding_import_english_page(
		34,
		'Services',
		'services',
		elwmarholding_translate_source_content(
			34,
			array(
				'Vi hjälper företag att gå från höga ambitioner till prioriterade initiativ, testade lösningar och mätbara affärsresultat.' => 'We help companies move from bold ambitions to prioritised initiatives, tested solutions and measurable business results.',
				'Från riktning till rörelse.' => 'From direction to momentum.',
				'Vi kombinerar affärsförståelse, specialistkompetens och ett obevekligt fokus på genomförande. Insatsen formas efter nuläget, men målet är alltid detsamma: att skapa verklig effekt för både klimatet och affären.' => 'We combine business insight, specialist expertise and a relentless focus on delivery. The engagement is shaped around your current situation, but the goal is always the same: to create real impact for both the climate and the business.',
				'01. Strategisk skärpa' => '01. Strategic clarity',
				'Vi identifierar var potentialen är störst, prioriterar initiativen och översätter ambitionen till en tydlig kommersiell riktning.' => 'We identify where the potential is greatest, prioritise the initiatives and translate ambition into a clear commercial direction.',
				'02. Snabbare innovation' => '02. Faster innovation',
				'Vi omsätter idéer till testbara lösningar, samlar rätt kompetens och bygger momentum utan onödig friktion.' => 'We turn ideas into testable solutions, bring together the right expertise and build momentum without unnecessary friction.',
				'03. Mätbart genomslag' => '03. Measurable impact',
				'Vi säkrar ansvar, tempo och uppföljning så att omställningen leder till bestående förändring och syns på sista raden.' => 'We secure ownership, pace and follow-up so the transition creates lasting change and is visible on the bottom line.',
				'Diskutera ert nästa steg' => 'Discuss your next step',
				'href="/kontakt/"' => 'href="/en/contact/"',
			)
		)
	);
	$pages['approach'] = elwmarholding_import_english_page(
		35,
		'Our approach',
		'approach',
		elwmarholding_translate_source_content(
			35,
			array(
				'Förändring behöver inte vara långsam. Vi samlar rätt människor runt rätt problem, fattar beslut på fakta och bygger fart genom konkreta leveranser.' => 'Change does not have to be slow. We bring the right people together around the right problem, make evidence-based decisions and build momentum through tangible delivery.',
				'Mindre snack. Mer verkstad.' => 'Less talk. More action.',
				'Arbetet drivs i korta, tydliga steg. Varje steg ska ge ett bättre beslutsunderlag, ett synligt resultat eller en verifierad väg framåt.' => 'The work moves forward in short, clear steps. Each step must produce a better basis for decisions, a visible result or a verified way forward.',
				'01. Rikta' => '01. Focus',
				'Vi skapar en gemensam bild av nuläget, väljer rätt problem och prioriterar den potential som är viktigast för både affär och omställning.' => 'We establish a shared view of the current situation, choose the right problem and prioritise the potential that matters most to both the business and the transition.',
				'02. Testa' => '02. Test',
				'Vi gör idéerna konkreta, testar de viktigaste antagandena och bevisar värdet tidigt innan tid och kapital binds i full skala.' => 'We make ideas tangible, test the most important assumptions and prove value early before committing time and capital at full scale.',
				'03. Skala' => '03. Scale',
				'Vi bygger in ansvar, arbetssätt och uppföljning i verksamheten så att lösningen kan växa och effekten blir bestående.' => 'We embed ownership, ways of working and follow-up in the organisation so the solution can grow and the impact will last.',
				'Starta dialogen' => 'Start the conversation',
				'href="/kontakt/"' => 'href="/en/contact/"',
			)
		)
	);
	$pages['insights'] = elwmarholding_import_english_page( 33, 'Insights', 'insights', '' );
	$pages['contact']  = elwmarholding_import_english_page( 28, 'Contact', 'contact', '' );
	$pages['about'] = elwmarholding_import_english_page(
		24,
		'About us',
		'about-us',
		elwmarholding_translate_source_content(
			24,
			array(
				'Företagsuppgifter' => 'Company information',
				'Organisationsnummer:' => 'Company registration number:',
				'Momsregistreringsnummer:' => 'VAT registration number:',
				'Sverige' => 'Sweden',
				'Kontakt' => 'Contact',
				'E-post:' => 'Email:',
			)
		)
	);
	$pages['privacy'] = elwmarholding_import_english_page(
		21,
		'Privacy',
		'privacy',
		elwmarholding_translate_source_content(
			21,
			array(
				'Personuppgiftsansvarig' => 'Data controller',
				'Elwmar Holding AB, organisationsnummer 559390-3965, är personuppgiftsansvarig för behandling av personuppgifter på webbplatsen. Frågor om integritet skickas till' => 'Elwmar Holding AB, company registration number 559390-3965, is the data controller for personal data processed on this website. Privacy enquiries can be sent to',
				'När du kontaktar oss' => 'When you contact us',
				'När du kontaktar oss via e-post behandlar vi de uppgifter du själv lämnar, exempelvis namn, kontaktuppgifter och innehållet i ditt meddelande. Uppgifterna används för att hantera din förfrågan, vidta åtgärder inför ett eventuellt avtal och följa upp vår affärsrelation. Uppgifterna sparas inte längre än vad som behövs för ändamålet eller följer av lag.' => 'When you contact us by email, we process the information you provide, such as your name, contact details and the contents of your message. The information is used to handle your enquiry, take steps before entering into a potential agreement and follow up our business relationship. The information is not retained longer than necessary for these purposes or as required by law.',
				'Tekniska uppgifter och kakor' => 'Technical data and cookies',
				'Webbplatsen och dess driftleverantörer kan behandla IP-adress och tekniska logguppgifter för säkerhet, felsökning och tillförlitlig drift. WordPress använder nödvändiga kakor för inloggade administratörer. Om analysverktyg, marknadsföringskakor eller nya formulär läggs till ska denna information uppdateras och samtycke inhämtas när det krävs.' => 'The website and its hosting providers may process IP addresses and technical log data for security, troubleshooting and reliable operation. WordPress uses essential cookies for signed-in administrators. If analytics tools, marketing cookies or new forms are added, this information will be updated and consent obtained where required.',
				'Mottagare' => 'Recipients',
				'Personuppgifter kan behandlas av leverantörer som tillhandahåller webbhotell, e-post och teknisk support. Vi säljer inte personuppgifter.' => 'Personal data may be processed by providers of web hosting, email and technical support. We do not sell personal data.',
				'Dina rättigheter' => 'Your rights',
				'Du kan begära tillgång till, rättelse eller radering av dina personuppgifter och i vissa fall invända mot eller begränsa behandlingen. Kontakta oss via e-postadressen ovan. Du har också rätt att lämna klagomål till' => 'You may request access to, correction or erasure of your personal data and, in certain cases, object to or restrict its processing. Contact us using the email address above. You also have the right to lodge a complaint with the Swedish Authority for Privacy Protection,',
				'Integritetsskyddsmyndigheten' => 'IMY',
				'Senast uppdaterad: 17 augusti 2026.' => 'Last updated: 17 August 2026.',
			)
		)
	);

	$english_home = trailingslashit( (string) pll_home_url( 'en' ) );
	$navigation_content = '';
	foreach (
		array(
			array( 'label' => 'Services', 'url' => $english_home . 'services/' ),
			array( 'label' => 'Our approach', 'url' => $english_home . 'approach/' ),
			array( 'label' => 'Insights', 'url' => $english_home . 'insights/' ),
			array( 'label' => 'Start a conversation', 'url' => $english_home . 'contact/' ),
		) as $link
	) {
		$navigation_content .= '<!-- wp:navigation-link ' . wp_json_encode(
			array(
				'label'          => $link['label'],
				'url'            => $link['url'],
				'kind'           => 'custom',
				'isTopLevelLink' => true,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . ' /-->';
	}

	$navigation = get_page_by_path( 'main-navigation', OBJECT, 'wp_navigation' );
	$navigation_data = array(
		'post_type'    => 'wp_navigation',
		'post_status'  => 'publish',
		'post_title'   => 'Main navigation',
		'post_name'    => 'main-navigation',
		'post_content' => $navigation_content,
	);
	if ( $navigation instanceof WP_Post ) {
		$navigation_data['ID'] = $navigation->ID;
		$navigation_id         = wp_update_post( wp_slash( $navigation_data ), true );
	} else {
		$navigation_id = wp_insert_post( wp_slash( $navigation_data ), true );
	}
	if ( is_wp_error( $navigation_id ) ) {
		throw new RuntimeException( $navigation_id->get_error_message() );
	}
	update_option( 'elwmarholding_primary_navigation_id_en', (int) $navigation_id );
	flush_rewrite_rules( false );

	echo wp_json_encode( array( 'pages' => $pages, 'navigation' => (int) $navigation_id ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
} catch ( Throwable $error ) {
	fwrite( STDERR, $error->getMessage() . PHP_EOL );
	exit( 1 );
}