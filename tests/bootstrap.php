<?php
/**
 * PHPUnit bootstrap file
 *
 * @package AI360RealEstate
 */

// Define WordPress constants for testing
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

// Composer autoloader
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Definir constantes de prueba
define( 'AI360RE_TESTING', true );

// Define WordPress functions for testing if they don't exist
if ( ! function_exists( 'wp_upload_dir' ) ) {
	/**
	 * Mock wp_upload_dir for testing.
	 *
	 * @return array Upload directory information.
	 */
	function wp_upload_dir() {
		return array(
			'basedir' => '/tmp/uploads',
			'baseurl' => 'http://example.com/uploads',
		);
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	/**
	 * Mock wp_mkdir_p for testing.
	 *
	 * @param string $dir Directory to create.
	 * @return bool True on success, false on failure.
	 */
	function wp_mkdir_p( $dir ) {
		return @mkdir( $dir, 0755, true );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Mock wp_json_encode for testing.
	 *
	 * @param mixed $data Data to encode.
	 * @return string|false JSON encoded string or false.
	 */
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Mock get_option for testing.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default value.
	 * @return mixed Option value.
	 */
	function get_option( $option, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'error_log' ) ) {
	/**
	 * Mock error_log for testing.
	 *
	 * @param string $message Error message.
	 * @return bool True.
	 */
	function error_log( $message ) {
		return true;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * Mock current_time for testing.
	 *
	 * @param string $type Type of time (mysql, timestamp).
	 * @param bool   $gmt  Whether to use GMT.
	 * @return string|int Current time.
	 */
	function current_time( $type, $gmt = false ) {
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		return time();
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	/**
	 * Mock get_current_user_id for testing.
	 *
	 * @return int User ID.
	 */
	function get_current_user_id() {
		return 1;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Mock sanitize_text_field for testing.
	 *
	 * @param string $str String to sanitize.
	 * @return string Sanitized string.
	 */
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Mock wp_unslash for testing.
	 *
	 * @param string|array $value Value to unslash.
	 * @return string|array Unslashed value.
	 */
	function wp_unslash( $value ) {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Mock wp_parse_args for testing.
	 *
	 * @param array $args     Arguments to parse.
	 * @param array $defaults Default values.
	 * @return array Parsed arguments.
	 */
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
