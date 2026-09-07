<?php
/**
 * Plugin Name: Elwmar Contact Form Protection
 * Description: Shared timing and rate-limit protection for custom contact forms.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Elwmar_Contact_Form_Protection {
	private const MINIMUM_COMPLETION_SECONDS = 3;
	private const MAXIMUM_TOKEN_AGE_SECONDS = DAY_IN_SECONDS;
	private const RATE_LIMIT_WINDOW_SECONDS = 15 * MINUTE_IN_SECONDS;
	private const EMAIL_LIMIT = 3;
	private const IP_LIMIT = 10;

	public static function fields( string $form_id ): string {
		$started_at = (string) time();

		return sprintf(
			'<input type="hidden" name="contact_form_started_at" value="%1$s"><input type="hidden" name="contact_form_signature" value="%2$s">',
			esc_attr( $started_at ),
			esc_attr( self::signature( $form_id, $started_at ) )
		);
	}

	public static function is_automated( string $form_id, string $honeypot, string $started_at, string $signature ): bool {
		if ( '' !== $honeypot || ! ctype_digit( $started_at ) ) {
			return true;
		}

		if ( ! hash_equals( self::signature( $form_id, $started_at ), $signature ) ) {
			return true;
		}

		$elapsed = time() - (int) $started_at;

		return $elapsed < self::MINIMUM_COMPLETION_SECONDS || $elapsed > self::MAXIMUM_TOKEN_AGE_SECONDS;
	}

	public static function is_rate_limited( string $form_id, string $email ): bool {
		$limits = array(
			'email|' . strtolower( $email ) => self::EMAIL_LIMIT,
		);
		$client_ip = self::client_ip();
		if ( '' !== $client_ip ) {
			$limits[ 'ip|' . $client_ip ] = self::IP_LIMIT;
		}

		foreach ( $limits as $identity => $maximum ) {
			if ( (int) get_transient( self::transient_key( $form_id, $identity ) ) >= $maximum ) {
				return true;
			}
		}

		foreach ( $limits as $identity => $maximum ) {
			$transient_key = self::transient_key( $form_id, $identity );
			set_transient( $transient_key, (int) get_transient( $transient_key ) + 1, self::RATE_LIMIT_WINDOW_SECONDS );
		}

		return false;
	}

	private static function signature( string $form_id, string $started_at ): string {
		return hash_hmac( 'sha256', sanitize_key( $form_id ) . '|' . $started_at, wp_salt( 'nonce' ) );
	}

	private static function client_ip(): string {
		foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $server_key ) {
			if ( empty( $_SERVER[ $server_key ] ) ) {
				continue;
			}

			$value = trim( explode( ',', (string) wp_unslash( $_SERVER[ $server_key ] ) )[0] );
			if ( filter_var( $value, FILTER_VALIDATE_IP ) ) {
				return $value;
			}
		}

		return '';
	}

	private static function transient_key( string $form_id, string $identity ): string {
		return 'elwmar_cfp_' . hash( 'sha256', sanitize_key( $form_id ) . '|' . $identity );
	}
}