<?php
/**
 * Site-specific, non-secret SMTP settings.
 *
 * Credentials are read from SMTP2GO_USERNAME and SMTP2GO_PASSWORD, or from
 * Docker secrets. Never add credentials to this file.
 */

return array(
	'enabled'    => true,
	'host'       => 'mail-eu.smtp2go.com',
	'port'       => 587,
	'encryption' => 'tls',
	'from_email' => 'website@elwmarholding.se',
	'from_name'  => 'elwmarholding.se',
);