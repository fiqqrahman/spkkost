<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
use RuntimeException;

/**
 * Creates the application schema and the database-backed session table.
 * Run this migration against a fresh database. It does not import legacy data.
 */
class CreateApplicationTables extends Migration
{
    public function up(): void
    {
        $this->assertFreshDatabase();

        $this->createUsersTable();
        $this->createCampusesTable();
        $this->createCriteriasTable();
        $this->createFeaturesTable();
        $this->createKostsTable();
        $this->createKostFeaturesTable();
        $this->createBookingsTable();
        $this->createPaymentsTable();
        $this->createSessionsTable();
    }

    /**
     * Avoid silently treating an existing, unmanaged schema as this baseline.
     */
    private function assertFreshDatabase(): void
    {
        $applicationTables = [
            'users',
            'campuses',
            'criterias',
            'features',
            'kosts',
            'kost_features',
            'bookings',
            'payments',
            'ci_sessions',
        ];

        $existingTables = array_values(array_filter(
            $applicationTables,
            fn (string $table): bool => $this->db->tableExists($table),
        ));

        if ($existingTables !== []) {
            throw new RuntimeException(
                'The initial schema migration requires a fresh database. Existing tables found: '
                . implode(', ', $existingTables)
                . '. Back up and reconcile the existing schema before migrating.',
            );
        }
    }

    public function down(): void
    {
        foreach ([
            'ci_sessions',
            'payments',
            'bookings',
            'kost_features',
            'kosts',
            'features',
            'criterias',
            'campuses',
            'users',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createUsersTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $this->forge->addField("role ENUM('owner','tenant') NOT NULL DEFAULT 'owner'");
        $this->forge->addField([
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users');
    }

    private function createCampusesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'latitude' => ['type' => 'DECIMAL', 'constraint' => '10,8'],
            'longitude' => ['type' => 'DECIMAL', 'constraint' => '11,8'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('campuses');
    }

    private function createCriteriasTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'code' => ['type' => 'CHAR', 'constraint' => 3],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addField("type ENUM('benefit','cost') NOT NULL");
        $this->forge->addField([
            'weight' => ['type' => 'DECIMAL', 'constraint' => '3,2'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('criterias');
    }

    private function createFeaturesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'criteria_id' => ['type' => 'INT'],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'point' => ['type' => 'INT', 'constraint' => 10],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('criteria_id');
        $this->forge->addForeignKey('criteria_id', 'criterias', 'id', '', 'CASCADE');
        $this->forge->createTable('features');
    }

    private function createKostsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'price' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'latitude' => ['type' => 'DECIMAL', 'constraint' => '10,8'],
            'longitude' => ['type' => 'DECIMAL', 'constraint' => '11,8'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_full' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'image' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('kosts');
    }

    private function createKostFeaturesTable(): void
    {
        $this->forge->addField([
            'kost_id' => ['type' => 'INT'],
            'feature_id' => ['type' => 'INT'],
        ]);
        $this->forge->addKey(['kost_id', 'feature_id'], true);
        $this->forge->addKey('feature_id');
        $this->forge->addForeignKey('kost_id', 'kosts', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('feature_id', 'features', 'id', '', 'CASCADE');
        $this->forge->createTable('kost_features');
    }

    private function createBookingsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'user_id' => ['type' => 'INT'],
            'kost_id' => ['type' => 'INT'],
            'occupant_count' => ['type' => 'INT', 'constraint' => 10, 'default' => 1],
            'campus_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'identity_doc' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addField("status ENUM('pending','approved','rejected','cancelled','termination_requested','terminated') NOT NULL DEFAULT 'pending'");
        $this->forge->addField([
            'rejection_note' => ['type' => 'TEXT', 'null' => true],
            'termination_reason' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addField('updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('kost_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('kost_id', 'kosts', 'id', '', 'CASCADE');
        $this->forge->createTable('bookings');
    }

    private function createPaymentsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'booking_id' => ['type' => 'INT'],
            'invoice_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'period_month' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'proof_image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'payment_date' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'note' => ['type' => 'TEXT', 'null' => true],
            'due_date' => ['type' => 'DATE', 'null' => true],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addField('updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('invoice_code');
        $this->forge->addKey('booking_id');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', '', 'CASCADE');
        $this->forge->createTable('payments');
    }

    private function createSessionsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'VARCHAR', 'constraint' => 128],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'default' => '0.0.0.0'],
            'timestamp' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'data' => ['type' => 'BLOB'],
        ]);
        $this->forge->addKey(['id', 'ip_address'], true);
        $this->forge->addKey('timestamp');
        $this->forge->createTable('ci_sessions');
    }
}
