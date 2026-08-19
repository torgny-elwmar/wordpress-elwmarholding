<?php
/**
 * Plugin Name: SMTP2GO Mailer
 * Description: Routes WordPress email through SMTP2GO using server-managed credentials.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SMTP2GO_MAILER_CONFIG_FILE', __DIR__ . '/smtp2go-mailer/config.php' );

/**
 * Return validated, non-secret SMTP settings.
 *
 * @return array{enabled:bool,host:string,port:int,encryption:string,from_email:string,from_name:string}
 */
function smtp2go_mailer_config(): array {
	$defaults = array(
		'enabled'    => false,
		'host'       => 'mail.smtp2go.com',
		'port'       => 587,
		'encryption' => 'tls',
		'from_email' => '',
		'from_name'  => '',
	);
	$config   = file_exists( SMTP2GO_MAILER_CONFIG_FILE )
		? require SMTP2GO_MAILER_CONFIG_FILE
		: array();
	$config   = is_array( $config ) ? array_merge( $defaults, $config ) : $defaults;

	$config['enabled']    = (bool) $config['enabled'];
	$config['host']       = sanitize_text_field( (string) $config['host'] );
	$config['port']       = absint( $config['port'] );
	$config['encryption'] = in_array( $config['encryption'], array( 'tls', 'ssl' ), true ) ? $config['encryption'] : 'tls';
	$config['from_email'] = sanitize_email( (string) $config['from_email'] );
	$config['from_name']  = sanitize_text_field( (string) $config['from_name'] );

	return $config;
}

/** Read a secret from an environment variable or its corresponding file. */
function smtp2go_mailer_secret( string $name ): string {
	$value = getenv( $name );
	if ( false !== $value && '' !== trim( $value ) ) {
		return trim( $value );
	}

	$file = getenv( $name . '_FILE' );
	if ( false === $file || '' === trim( $file ) ) {
		$default_files = array(
			'SMTP2GO_USERNAME' => '/run/secrets/smtp2go_username',
			'SMTP2GO_PASSWORD' => '/run/secrets/smtp2go_password',
		);
		$file = $default_files[ $name ] ?? '';
	}
	if ( false === $file || '' === trim( $file ) || ! is_readable( $file ) ) {
		return '';
	}

	$secret = file_get_contents( $file );

	return false === $secret ? '' : trim( $secret );
}

/** Return whether all settings required to send through SMTP2GO are available. */
function smtp2go_mailer_is_configured(): bool {
	$config = smtp2go_mailer_config();

	return $config['enabled']
		&& '' !== $config['host']
		&& $config['port'] > 0
		&& is_email( $config['from_email'] )
		&& '' !== smtp2go_mailer_secret( 'SMTP2GO_USERNAME' )
		&& '' !== smtp2go_mailer_secret( 'SMTP2GO_PASSWORD' );
}

/** Configure WordPress' bundled PHPMailer instance. */
function smtp2go_mailer_configure( $phpmailer ): void {
	if ( ! smtp2go_mailer_is_configured() ) {
		return;
	}

	$config = smtp2go_mailer_config();
	$phpmailer->isSMTP();
	$phpmailer->Host       = $config['host'];
	$phpmailer->Port       = $config['port'];
	$phpmailer->SMTPAuth   = true;
	$phpmailer->SMTPSecure = $config['encryption'];
	$phpmailer->Username   = smtp2go_mailer_secret( 'SMTP2GO_USERNAME' );
	$phpmailer->Password   = smtp2go_mailer_secret( 'SMTP2GO_PASSWORD' );
	$phpmailer->From       = $config['from_email'];
	$phpmailer->FromName   = $config['from_name'];
	$phpmailer->Timeout    = 15;
}
add_action( 'phpmailer_init', 'smtp2go_mailer_configure' );

/** Force a verified sender while preserving Reply-To headers. */
function smtp2go_mailer_from_email( string $email ): string {
	$config = smtp2go_mailer_config();

	return smtp2go_mailer_is_configured() ? $config['from_email'] : $email;
}
add_filter( 'wp_mail_from', 'smtp2go_mailer_from_email' );

/** Force the configured sender name. */
function smtp2go_mailer_from_name( string $name ): string {
	$config = smtp2go_mailer_config();

	return smtp2go_mailer_is_configured() ? $config['from_name'] : $name;
}
add_filter( 'wp_mail_from_name', 'smtp2go_mailer_from_name' );

/** Show administrators when credentials are missing or unreadable. */
function smtp2go_mailer_admin_notice(): void {
	$config = smtp2go_mailer_config();
	if ( ! $config['enabled'] || smtp2go_mailer_is_configured() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning"><p><?php echo esc_html__( 'SMTP2GO Mailer is enabled but cannot read SMTP2GO credentials from the server.', 'smtp2go-mailer' ); ?></p></div>
	<?php
}
add_action( 'admin_notices', 'smtp2go_mailer_admin_notice' );

/** Log transport failures without message bodies, recipients or credentials. */
function smtp2go_mailer_log_failure( WP_Error $error ): void {
	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		error_log( 'SMTP2GO Mailer: wp_mail failed (' . sanitize_key( $error->get_error_code() ) . ').' );
	}
}
add_action( 'wp_mail_failed', 'smtp2go_mailer_log_failure' );