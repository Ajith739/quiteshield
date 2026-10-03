<?php
/**
 * Authenticated encryption for per-user secrets.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH Crypto implementation.
 */
final class GWQSH_Crypto {
	/**
	 * Key.
	 */
	private static function key() {
		return hash( 'sha256', wp_salt( 'auth' ) . '|' . wp_salt( 'secure_auth' ) . '|' . wp_salt( 'logged_in' ), true );
	}
	/**
	 * Encrypt.
	 *
	 * @param mixed $plain Plain.
	 */
	public static function encrypt( $plain ) {
		if ( ! is_string( $plain ) || '' === $plain ) {
			return false;
		}
		try {
			if ( function_exists( 'sodium_crypto_secretbox' ) ) {
				$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
				return 'gwqsh1:s:' . base64_encode( $nonce . sodium_crypto_secretbox( $plain, $nonce, self::key() ) );
			}
			if ( function_exists( 'openssl_encrypt' ) ) {
				$nonce  = random_bytes( 12 );
				$tag    = '';
				$cipher = openssl_encrypt( $plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $nonce, $tag );
				return false === $cipher ? false : 'gwqsh1:o:' . base64_encode( $nonce . $tag . $cipher );
			}
		} catch ( Throwable $e ) {
			return false;
		}
		return false;
	}
	/**
	 * Decrypt.
	 *
	 * @param mixed $payload Payload.
	 */
	public static function decrypt( $payload ) {
		if ( ! is_string( $payload ) || ! preg_match( '/^(?:gwqsh1|gqs1):([so]):(.+)$/', $payload, $matches ) ) {
			return false;
		}
		$raw = base64_decode( $matches[2], true );
		if ( false === $raw ) {
			return false;
		}
		if ( 's' === $matches[1] && function_exists( 'sodium_crypto_secretbox_open' ) && strlen( $raw ) > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			$n = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
			return sodium_crypto_secretbox_open( substr( $raw, $n ), substr( $raw, 0, $n ), self::key() );
		}
		if ( 'o' === $matches[1] && function_exists( 'openssl_decrypt' ) && strlen( $raw ) > 28 ) {
			return openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ) );
		}
		return false;
	}
}
