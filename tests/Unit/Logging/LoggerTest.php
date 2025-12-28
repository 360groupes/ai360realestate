<?php
/**
 * Logger Unit Tests
 *
 * @package AI360RealEstate
 * @subpackage Tests
 * @since 0.1.0
 */

namespace AI360RealEstate\Tests\Unit\Logging;

use AI360RealEstate\Logging\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Logger test case.
 *
 * @since 0.1.0
 */
class LoggerTest extends TestCase {

	/**
	 * Test getting singleton instance
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_instance() {
		$instance1 = Logger::get_instance();
		$instance2 = Logger::get_instance();

		$this->assertInstanceOf( Logger::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test log levels exist
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_log_levels() {
		$this->assertEquals( 'debug', Logger::LEVEL_DEBUG );
		$this->assertEquals( 'info', Logger::LEVEL_INFO );
		$this->assertEquals( 'warning', Logger::LEVEL_WARNING );
		$this->assertEquals( 'error', Logger::LEVEL_ERROR );
	}

	/**
	 * Test logger methods exist
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_logger_methods_exist() {
		$logger = Logger::get_instance();

		$this->assertTrue( method_exists( $logger, 'debug' ) );
		$this->assertTrue( method_exists( $logger, 'info' ) );
		$this->assertTrue( method_exists( $logger, 'warning' ) );
		$this->assertTrue( method_exists( $logger, 'error' ) );
		$this->assertTrue( method_exists( $logger, 'log' ) );
		$this->assertTrue( method_exists( $logger, 'cleanup_old_logs' ) );
		$this->assertTrue( method_exists( $logger, 'get_recent_logs' ) );
	}
}
