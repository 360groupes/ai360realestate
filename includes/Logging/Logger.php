<?php
/**
 * Logger Class
 *
 * Centralized logging system for the plugin.
 *
 * @package AI360RealEstate
 * @subpackage Logging
 * @since 0.1.0
 */

namespace AI360RealEstate\Logging;

// Seguridad: Bloquear acceso directo
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class.
 *
 * Provides centralized logging functionality with PSR-3 compatible interface.
 *
 * @since 0.1.0
 */
class Logger {

	/**
	 * Log levels.
	 */
	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';

	/**
	 * Valid log levels.
	 *
	 * @var array
	 */
	private static $valid_levels = array(
		self::EMERGENCY,
		self::ALERT,
		self::CRITICAL,
		self::ERROR,
		self::WARNING,
		self::NOTICE,
		self::INFO,
		self::DEBUG,
	);

	/**
	 * Log handler instance.
	 *
	 * @var LogHandler|null
	 */
	private static $handler = null;

	/**
	 * Initialize the logger.
	 *
	 * @since 0.1.0
	 * @param LogHandler $handler Log handler instance.
	 * @return void
	 */
	public static function init( LogHandler $handler ): void {
		self::$handler = $handler;
	}

	/**
	 * Get the log handler.
	 *
	 * @since 0.1.0
	 * @return LogHandler Log handler instance.
	 */
	private static function get_handler(): LogHandler {
		if ( null === self::$handler ) {
			self::$handler = new LogHandler();
		}

		return self::$handler;
	}

	/**
	 * System is unusable.
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function emergency( string $message, array $context = array() ): void {
		self::log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function alert( string $message, array $context = array() ): void {
		self::log( self::ALERT, $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function critical( string $message, array $context = array() ): void {
		self::log( self::CRITICAL, $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action.
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( self::ERROR, $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( self::WARNING, $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function notice( string $message, array $context = array() ): void {
		self::log( self::NOTICE, $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( self::INFO, $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @since 0.1.0
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function debug( string $message, array $context = array() ): void {
		self::log( self::DEBUG, $message, $context );
	}

	/**
	 * Log with arbitrary level.
	 *
	 * @since 0.1.0
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function log( string $level, string $message, array $context = array() ): void {
		// Validate log level
		if ( ! self::is_valid_level( $level ) ) {
			$level = self::INFO;
		}

		// Check if this level should be logged based on configuration
		if ( ! self::should_log( $level ) ) {
			return;
		}

		// Interpolate context values into message
		$message = self::interpolate( $message, $context );

		// Pass to handler
		self::get_handler()->handle( $level, $message, $context );
	}

	/**
	 * Check if a log level is valid.
	 *
	 * @since 0.1.0
	 * @param string $level Log level.
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_level( string $level ): bool {
		return in_array( $level, self::$valid_levels, true );
	}

	/**
	 * Check if a log level should be logged based on configuration.
	 *
	 * @since 0.1.0
	 * @param string $level Log level.
	 * @return bool True if should be logged, false otherwise.
	 */
	private static function should_log( string $level ): bool {
		$min_level = get_option( 'ai360re_log_level', self::INFO );

		$level_priority = array(
			self::DEBUG     => 0,
			self::INFO      => 1,
			self::NOTICE    => 2,
			self::WARNING   => 3,
			self::ERROR     => 4,
			self::CRITICAL  => 5,
			self::ALERT     => 6,
			self::EMERGENCY => 7,
		);

		$current_priority = $level_priority[ $level ] ?? 1;
		$min_priority     = $level_priority[ $min_level ] ?? 1;

		return $current_priority >= $min_priority;
	}

	/**
	 * Interpolate context values into message placeholders.
	 *
	 * @since 0.1.0
	 * @param string $message Message with placeholders.
	 * @param array  $context Context values.
	 * @return string Interpolated message.
	 */
	private static function interpolate( string $message, array $context = array() ): string {
		// Build a replacement array with braces around the context keys
		$replace = array();
		foreach ( $context as $key => $val ) {
			// Check that the value can be cast to string
			if ( ! is_array( $val ) && ( ! is_object( $val ) || method_exists( $val, '__toString' ) ) ) {
				$replace[ '{' . $key . '}' ] = $val;
			}
		}

		// Interpolate replacement values into the message and return
		return strtr( $message, $replace );
	}

	/**
	 * Get all valid log levels.
	 *
	 * @since 0.1.0
	 * @return array Array of valid log levels.
	 */
	public static function get_valid_levels(): array {
		return self::$valid_levels;
	}

	/**
	 * Clear old log entries based on retention policy.
	 *
	 * @since 0.1.0
	 * @param int $days Number of days to retain logs.
	 * @return int Number of entries deleted.
	 */
	public static function cleanup( int $days = 30 ): int {
		return self::get_handler()->cleanup( $days );
	}
}
