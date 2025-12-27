<?php
/**
 * Logger Class Tests
 *
 * @package AI360RealEstate
 * @subpackage Tests\Unit\Logging
 */

namespace AI360RealEstate\Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use AI360RealEstate\Logging\Logger;

/**
 * Test Logger class functionality.
 */
class LoggerTest extends TestCase {

	/**
	 * Test that all log level constants are defined.
	 */
	public function test_log_level_constants_are_defined(): void {
		$this->assertEquals( 'emergency', Logger::EMERGENCY );
		$this->assertEquals( 'alert', Logger::ALERT );
		$this->assertEquals( 'critical', Logger::CRITICAL );
		$this->assertEquals( 'error', Logger::ERROR );
		$this->assertEquals( 'warning', Logger::WARNING );
		$this->assertEquals( 'notice', Logger::NOTICE );
		$this->assertEquals( 'info', Logger::INFO );
		$this->assertEquals( 'debug', Logger::DEBUG );
	}

	/**
	 * Test that get_valid_levels returns all valid levels.
	 */
	public function test_get_valid_levels_returns_all_levels(): void {
		$levels = Logger::get_valid_levels();

		$this->assertIsArray( $levels );
		$this->assertCount( 8, $levels );
		$this->assertContains( 'emergency', $levels );
		$this->assertContains( 'alert', $levels );
		$this->assertContains( 'critical', $levels );
		$this->assertContains( 'error', $levels );
		$this->assertContains( 'warning', $levels );
		$this->assertContains( 'notice', $levels );
		$this->assertContains( 'info', $levels );
		$this->assertContains( 'debug', $levels );
	}

	/**
	 * Test that is_valid_level correctly validates log levels.
	 */
	public function test_is_valid_level_validates_correctly(): void {
		$this->assertTrue( Logger::is_valid_level( 'emergency' ) );
		$this->assertTrue( Logger::is_valid_level( 'alert' ) );
		$this->assertTrue( Logger::is_valid_level( 'critical' ) );
		$this->assertTrue( Logger::is_valid_level( 'error' ) );
		$this->assertTrue( Logger::is_valid_level( 'warning' ) );
		$this->assertTrue( Logger::is_valid_level( 'notice' ) );
		$this->assertTrue( Logger::is_valid_level( 'info' ) );
		$this->assertTrue( Logger::is_valid_level( 'debug' ) );

		$this->assertFalse( Logger::is_valid_level( 'invalid' ) );
		$this->assertFalse( Logger::is_valid_level( 'EMERGENCY' ) );
		$this->assertFalse( Logger::is_valid_level( '' ) );
	}

	/**
	 * Test that Logger has all PSR-3 methods.
	 */
	public function test_logger_has_psr3_methods(): void {
		$this->assertTrue( method_exists( Logger::class, 'emergency' ) );
		$this->assertTrue( method_exists( Logger::class, 'alert' ) );
		$this->assertTrue( method_exists( Logger::class, 'critical' ) );
		$this->assertTrue( method_exists( Logger::class, 'error' ) );
		$this->assertTrue( method_exists( Logger::class, 'warning' ) );
		$this->assertTrue( method_exists( Logger::class, 'notice' ) );
		$this->assertTrue( method_exists( Logger::class, 'info' ) );
		$this->assertTrue( method_exists( Logger::class, 'debug' ) );
		$this->assertTrue( method_exists( Logger::class, 'log' ) );
	}

	/**
	 * Test that Logger has init method.
	 */
	public function test_logger_has_init_method(): void {
		$this->assertTrue( method_exists( Logger::class, 'init' ) );
	}

	/**
	 * Test that Logger has cleanup method.
	 */
	public function test_logger_has_cleanup_method(): void {
		$this->assertTrue( method_exists( Logger::class, 'cleanup' ) );
	}
}
