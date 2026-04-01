<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * HashStatusPagePasswords Migration
 *
 * Re-hashes any existing plaintext passwords in the status_pages table
 * using bcrypt via password_hash().
 */
class HashStatusPagePasswords extends AbstractMigration
{
    /**
     * Up Method.
     *
     * Finds all status pages with non-null, non-empty passwords
     * that are not already bcrypt hashes, and hashes them.
     *
     * @return void
     */
    public function up(): void
    {
        $rows = $this->fetchAll(
            "SELECT id, password FROM status_pages WHERE password IS NOT NULL AND password != ''"
        );

        foreach ($rows as $row) {
            $password = $row['password'];

            // Skip if already a bcrypt hash (starts with $2y$ or $2a$ or $2b$)
            if (preg_match('/^\$2[yab]\$/', $password)) {
                continue;
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $this->execute(
                sprintf(
                    "UPDATE status_pages SET password = '%s' WHERE id = %d",
                    addslashes($hashed),
                    (int)$row['id']
                )
            );
        }
    }

    /**
     * Down Method.
     *
     * Cannot reverse a hash — this migration is irreversible.
     *
     * @return void
     */
    public function down(): void
    {
        // Irreversible: cannot recover plaintext passwords from hashes
    }
}
