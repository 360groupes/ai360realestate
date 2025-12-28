<?php
/**
 * AuditLogger Unit Tests
 *
 * @package AI360RealEstate
 * @subpackage Tests
 * @since 0.1.0
 */

namespace AI360RealEstate\Tests\Unit\Logging;

use AI360RealEstate\Logging\AuditLogger;
use PHPUnit\Framework\TestCase;

/**
 * AuditLogger test case.
 *
 * @since 0.1.0
 */
class AuditLoggerTest extends TestCase {

	/**
	 * Test getting singleton instance
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_instance() {
		$instance1 = AuditLogger::get_instance();
		$instance2 = AuditLogger::get_instance();

		$this->assertInstanceOf( AuditLogger::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test audit logger methods exist
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_audit_logger_methods_exist() {
		$logger = AuditLogger::get_instance();

		$this->assertTrue( method_exists( $logger, 'log' ) );
		$this->assertTrue( method_exists( $logger, 'log_create' ) );
		$this->assertTrue( method_exists( $logger, 'log_update' ) );
		$this->assertTrue( method_exists( $logger, 'log_delete' ) );
		$this->assertTrue( method_exists( $logger, 'get_entity_logs' ) );
		$this->assertTrue( method_exists( $logger, 'get_user_logs' ) );
		$this->assertTrue( method_exists( $logger, 'get_project_logs' ) );
		$this->assertTrue( method_exists( $logger, 'cleanup_old_logs' ) );
	}
}
