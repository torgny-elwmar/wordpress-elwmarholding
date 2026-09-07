# Elwmar Contact Form Protection

Reusable must-use plugin for custom WordPress contact forms. It requires no external service, account or API key.

## Protection

- HMAC-signed form timestamp with a minimum completion time of 3 seconds
- Tokens expire after 24 hours
- Maximum 3 valid submissions per email address in 15 minutes
- Maximum 10 valid submissions per client IP in 15 minutes
- Cloudflare-aware client IP detection

The form remains responsible for its WordPress nonce, honeypot field, input validation and mail delivery.

## API

Render the signed hidden fields inside the form:

```php
echo Elwmar_Contact_Form_Protection::fields( 'unique-form-id' );
```

Silently reject a filled honeypot or invalid timing token before input validation:

```php
Elwmar_Contact_Form_Protection::is_automated( 'unique-form-id', $honeypot, $started_at, $signature );
```

Check and increment limits after input validation and before sending mail:

```php
Elwmar_Contact_Form_Protection::is_rate_limited( 'unique-form-id', $email );
```

The plugin file is shared with the Terrivo, Green Acceleration and Elwmar Holding repositories. Keep all copies byte-identical when changing behavior or version.