# SMTP2GO Mailer

A reusable WordPress MU-plugin that configures the bundled PHPMailer for SMTP2GO. It loads automatically and has no WordPress admin settings.

## Site configuration

Configure non-secret, site-specific settings in `config.php`:

```php
return array(
	'enabled'    => true,
	'host'       => 'mail.smtp2go.com',
	'port'       => 587,
	'encryption' => 'tls',
	'from_email' => 'website@example.com',
	'from_name'  => 'Example Site',
);
```

The sender address must belong to a domain verified by SMTP2GO. Existing `Reply-To` headers are preserved.

## Credentials

The plugin reads credentials in this order:

1. `SMTP2GO_USERNAME` and `SMTP2GO_PASSWORD`
2. Files specified by `SMTP2GO_USERNAME_FILE` and `SMTP2GO_PASSWORD_FILE`
3. `/run/secrets/smtp2go_username` and `/run/secrets/smtp2go_password`

Use Docker secrets in production. Never add credentials to `config.php`, Compose files or Git.

Example Compose configuration:

```yaml
services:
  wordpress:
    secrets:
      - smtp2go_username
      - smtp2go_password

secrets:
  smtp2go_username:
    file: ../../secrets/example/smtp2go_username
  smtp2go_password:
    file: ../../secrets/example/smtp2go_password
```

Recreate the WordPress container after changing its Compose configuration. Administrators see a warning when SMTP is enabled but credentials are unavailable.