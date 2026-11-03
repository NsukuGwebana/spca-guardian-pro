<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WorkflowTest extends TestCase
{
    public static function allowedMoves(): array
    {
        return [
            'book a home visit'        => ['Pending', 'Home visit'],
            'reject straight away'     => ['Pending', 'Rejected'],
            'approve after the visit'  => ['Home visit', 'Approved'],
            'reject after the visit'   => ['Home visit', 'Rejected'],
            'hand over'                => ['Approved', 'Adopted'],
            'reject at handover'       => ['Approved', 'Rejected'],
        ];
    }

    public static function blockedMoves(): array
    {
        return [
            'skip the home visit'      => ['Pending', 'Approved'],
            'skip straight to adopted' => ['Pending', 'Adopted'],
            'go backwards'             => ['Approved', 'Pending'],
            'reopen an adoption'       => ['Adopted', 'Pending'],
            'reopen a rejection'       => ['Rejected', 'Approved'],
            'unknown status'           => ['Lost', 'Approved'],
            'same status'              => ['Pending', 'Pending'],
        ];
    }

    #[DataProvider('allowedMoves')]
    public function testAllowedMoves(string $from, string $to): void
    {
        $this->assertTrue(adoption_can_move($from, $to));
    }

    #[DataProvider('blockedMoves')]
    public function testBlockedMoves(string $from, string $to): void
    {
        $this->assertFalse(adoption_can_move($from, $to));
    }

    public function testFinishedApplicationsCannotMove(): void
    {
        $this->assertSame([], ADOPTION_FLOW['Adopted']);
        $this->assertSame([], ADOPTION_FLOW['Rejected']);
    }

    public function testAnimalStatusFollowsTheApplication(): void
    {
        $this->assertSame('Pending', animal_status_for('Pending'));
        $this->assertSame('Pending', animal_status_for('Home visit'));
        $this->assertSame('Pending', animal_status_for('Approved'));
        $this->assertSame('Adopted', animal_status_for('Adopted'));
        $this->assertSame('Available', animal_status_for('Rejected'));
    }

    public function testEveryStatusHasHelpTextForTheApplicant(): void
    {
        foreach (array_keys(ADOPTION_FLOW) as $status) {
            $this->assertNotSame('', adoption_status_help($status), $status);
        }
    }

    public function testHomeVisitEmailIncludesTheDate(): void
    {
        [$subject, $body] = adoption_email('Home visit', 'Thandi Mokoena', 'Bella', '2026-03-14 10:30:00');
        $this->assertStringContainsString('Bella', $subject);
        $this->assertStringContainsString('Saturday 14 March 2026 at 10:30', $body);
    }

    public function testApprovalEmailAsksForTheIdDocument(): void
    {
        [, $body] = adoption_email('Approved', 'Thandi', 'Bella');
        $this->assertStringContainsString('bring your ID document', $body);
    }

    public function testEmailEscapesNamesAndNotes(): void
    {
        [, $body] = adoption_email('Rejected', '<script>x</script>', 'Bella', null, 'Yard <b>too small</b>');
        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
        $this->assertStringContainsString('Yard &lt;b&gt;too small&lt;/b&gt;', $body);
    }
}
