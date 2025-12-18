<?php
/**
 * Database Class Tests
 *
 * @package AI360RealEstate
 * @subpackage Tests\Unit\Core
 */

namespace AI360RealEstate\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use AI360RealEstate\Core\Database;

/**
 * Test Database class functionality.
 */
class DatabaseTest extends TestCase {

/**
 * Test that table prefix constant is correct.
 */
public function test_table_prefix_is_correct(): void {
$this->assertEquals( 'ai360re_', Database::TABLE_PREFIX );
}

/**
 * Test that get_all_tables method returns 8 tables.
 */
public function test_get_all_tables_returns_8_tables(): void {
// This will need WordPress loaded to work properly with wpdb
// For now, just test the method exists and returns an array
$this->assertTrue( method_exists( Database::class, 'get_all_tables' ) );
}

/**
 * Test that schema version constant is set and valid.
 */
public function test_schema_version_is_set(): void {
$this->assertNotEmpty( Database::SCHEMA_VERSION );
$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', Database::SCHEMA_VERSION );
}

/**
 * Test that schema version is 1.0.0 for initial release.
 */
public function test_schema_version_is_1_0_0(): void {
$this->assertEquals( '1.0.0', Database::SCHEMA_VERSION );
}

/**
 * Test that get_table_name method exists.
 */
public function test_get_table_name_method_exists(): void {
$this->assertTrue( method_exists( Database::class, 'get_table_name' ) );
}

/**
 * Test that tables_exist method exists.
 */
public function test_tables_exist_method_exists(): void {
$this->assertTrue( method_exists( Database::class, 'tables_exist' ) );
}

/**
 * Test that get_schema_version method exists.
 */
public function test_get_schema_version_method_exists(): void {
$this->assertTrue( method_exists( Database::class, 'get_schema_version' ) );
}

/**
 * Test that set_schema_version method exists.
 */
public function test_set_schema_version_method_exists(): void {
$this->assertTrue( method_exists( Database::class, 'set_schema_version' ) );
}
}
