<?php
/**
 * AuditLogger Class Tests
 *
 * @package AI360RealEstate
 * @subpackage Tests\Unit\Logging
 */

namespace AI360RealEstate\Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use AI360RealEstate\Logging\AuditLogger;

/**
 * Test AuditLogger class functionality.
 */
class AuditLoggerTest extends TestCase {

	/**
	 * Test that action constants are defined.
	 */
	public function test_action_constants_are_defined(): void {
		$this->assertEquals( 'create', AuditLogger::ACTION_CREATE );
		$this->assertEquals( 'update', AuditLogger::ACTION_UPDATE );
		$this->assertEquals( 'delete', AuditLogger::ACTION_DELETE );
		$this->assertEquals( 'view', AuditLogger::ACTION_VIEW );
		$this->assertEquals( 'login', AuditLogger::ACTION_LOGIN );
		$this->assertEquals( 'logout', AuditLogger::ACTION_LOGOUT );
		$this->assertEquals( 'sync', AuditLogger::ACTION_SYNC );
		$this->assertEquals( 'ai_optimize', AuditLogger::ACTION_AI_OPT );
		$this->assertEquals( 'export', AuditLogger::ACTION_EXPORT );
		$this->assertEquals( 'import', AuditLogger::ACTION_IMPORT );
	}

	/**
	 * Test that entity type constants are defined.
	 */
	public function test_entity_type_constants_are_defined(): void {
		$this->assertEquals( 'project', AuditLogger::ENTITY_PROJECT );
		$this->assertEquals( 'property', AuditLogger::ENTITY_PROPERTY );
		$this->assertEquals( 'connector', AuditLogger::ENTITY_CONNECTOR );
		$this->assertEquals( 'user', AuditLogger::ENTITY_USER );
		$this->assertEquals( 'settings', AuditLogger::ENTITY_SETTINGS );
	}

	/**
	 * Test that log method exists.
	 */
	public function test_log_method_exists(): void {
		$this->assertTrue( method_exists( AuditLogger::class, 'log' ) );
	}

	/**
	 * Test that log_create method exists.
	 */
	public function test_log_create_method_exists(): void {
		$this->assertTrue( method_exists( AuditLogger::class, 'log_create' ) );
	}

	/**
	 * Test that log_update method exists.
	 */
	public function test_log_update_method_exists(): void {
		$this->assertTrue( method_exists( AuditLogger::class, 'log_update' ) );
	}

	/**
	 * Test that log_delete method exists.
	 */
	public function test_log_delete_method_exists(): void {
		$this->assertTrue( method_exists( AuditLogger::class, 'log_delete' ) );
	}

	/**
	 * Test that get_entries method exists.
	 */
	public function test_get_entries_method_exists(): void {
		$this->assertTrue( method_exists( AuditLogger::class, 'get_entries' ) );
	}

	/**
	 * Test that count_entries method exists.
	 */
	public function test_count_entries_method_exists(): void {
		$this->assertTrue( method_exists( AuditLogger::class, 'count_entries' ) );
	}

	/**
	 * Test that cleanup method exists.
	 */
	public function test_cleanup_method_exists(): void {
		$this->assertTrue( method_exists( AuditLogger::class, 'cleanup' ) );
	}
}
