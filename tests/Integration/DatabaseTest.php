<?php
use PHPUnit\Framework\TestCase;

/**
 * Runs against a real MySQL database (GitHub Actions starts one, imports
 * database/schema.sql and the migrations). Skipped when DB_HOST isn't set.
 */
final class DatabaseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!getenv('DB_HOST')) {
            return;
        }
        require_once __DIR__ . '/../../bootstrap.php';
        restore_error_handler();      // let PHPUnit report warnings itself
        restore_exception_handler();
    }

    protected function setUp(): void
    {
        if (!getenv('DB_HOST')) {
            $this->markTestSkipped('No test database (set DB_HOST to run).');
        }
    }

    public function testPreparedQueriesTreatInputAsData(): void
    {
        $row = q("SELECT COUNT(*) AS n FROM users WHERE username = ?", ["' OR '1'='1"])->fetch_assoc();
        $this->assertSame(0, (int)$row['n'], 'an SQL injection attempt matched no users');
    }

    public function testSecurityTablesFromTheMigrationExist(): void
    {
        foreach (['password_resets', 'login_attempts', 'activity_log'] as $table) {
            $found = q("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?", [$table])->fetch_assoc();
            $this->assertSame(1, (int)$found['n'], "$table is missing");
        }
    }

    public function testAdoptionsSupportTheWholePipeline(): void
    {
        $col = q("SHOW COLUMNS FROM adoptions LIKE 'status'")->fetch_assoc();
        foreach (array_keys(ADOPTION_FLOW) as $status) {
            $this->assertStringContainsString("'$status'", $col['Type']);
        }
    }

    public function testVolunteerHoursCanBeOwnedAndApproved(): void
    {
        foreach (['user_id', 'status'] as $col) {
            $found = q("SELECT COUNT(*) AS n FROM information_schema.columns
                        WHERE table_schema = DATABASE() AND table_name = 'community_engagement' AND column_name = ?", [$col])->fetch_assoc();
            $this->assertSame(1, (int)$found['n'], "community_engagement.$col is missing");
        }
    }

    public function testActivityIsLogged(): void
    {
        $_SESSION = ['username' => 'phpunit', 'userrole' => 'Administrator'];
        $marker = 'test-' . bin2hex(random_bytes(4));
        log_activity('Ran tests', $marker);
        $row = q("SELECT username, action FROM activity_log WHERE details = ?", [$marker])->fetch_assoc();
        $this->assertSame(['username' => 'phpunit', 'action' => 'Ran tests'], $row);
        q("DELETE FROM activity_log WHERE details = ?", [$marker]);
    }

    public function testOutputEscaping(): void
    {
        $this->assertSame('&lt;img src=x onerror=alert(1)&gt; &amp; &quot;q&quot; &#039;s&#039;',
            e('<img src=x onerror=alert(1)> & "q" \'s\''));
        $this->assertSame('', e(null));
    }
}
