<?php
/**
 * LogHandler Class Tests
 *
 * @package AI360RealEstate
 * @subpackage Tests\Unit\Logging
 */

namespace AI360RealEstate\Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use AI360RealEstate\Logging\LogHandler;

/**
 * Test LogHandler class functionality.
 */
class LogHandlerTest extends TestCase {

	/**
	 * Test that destination constants are defined.
	 */
	public function test_destination_constants_are_defined(): void {
		$this->assertEquals( 'database', LogHandler::DESTINATION_DATABASE );
		$this->assertEquals( 'file', LogHandler::DESTINATION_FILE );
		$this->assertEquals( 'both', LogHandler::DESTINATION_BOTH );
	}

	/**
	 * Test that LogHandler can be instantiated.
	 */
	public function test_log_handler_can_be_instantiated(): void {
		$handler = new LogHandler();
		$this->assertInstanceOf( LogHandler::class, $handler );
	}

	/**
	 * Test that LogHandler can be instantiated with destination.
	 */
	public function test_log_handler_can_be_instantiated_with_destination(): void {
		$handler = new LogHandler( LogHandler::DESTINATION_FILE );
		$this->assertInstanceOf( LogHandler::class, $handler );
	}

	/**
	 * Test that handle method exists.
	 */
	public function test_handle_method_exists(): void {
		$this->assertTrue( method_exists( LogHandler::class, 'handle' ) );
	}

	/**
	 * Test that get_log_files method exists.
	 */
	public function test_get_log_files_method_exists(): void {
		$this->assertTrue( method_exists( LogHandler::class, 'get_log_files' ) );
	}

	/**
	 * Test that cleanup method exists.
	 */
	public function test_cleanup_method_exists(): void {
		$this->assertTrue( method_exists( LogHandler::class, 'cleanup' ) );
	}

	/**
	 * Test that read_log method exists.
	 */
	public function test_read_log_method_exists(): void {
		$this->assertTrue( method_exists( LogHandler::class, 'read_log' ) );
	}
}
