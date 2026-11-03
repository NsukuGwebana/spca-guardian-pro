<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public static function saIds(): array
    {
        return [
            'valid ID'                 => ['8001015009087', true],
            'wrong check digit'        => ['8001015009088', false],
            'impossible date (32nd)'   => ['8001325009086', false],
            'impossible month (13)'    => ['8013015009083', false],
            'too short'                => ['800101500908', false],
            'letters'                  => ['80010150090A7', false],
        ];
    }

    #[DataProvider('saIds')]
    public function testSouthAfricanIdNumbers(string $id, bool $ok): void
    {
        $this->assertSame($ok, valid_sa_id($id));
    }

    public function testPassportNumbersAreAccepted(): void
    {
        $this->assertTrue(valid_identity_number('A12345678'));
        $this->assertTrue(valid_identity_number(' m00123456 '));
        $this->assertFalse(valid_identity_number('12345678'), 'digits only must be a 13-digit SA ID');
        $this->assertFalse(valid_identity_number('AB'));
    }

    public static function phones(): array
    {
        return [
            ['082 123 4567', true], ['0821234567', true], ['+27 82 123 4567', true],
            ['(046) 622-1234', true], ['12345', false], ['0021234567', false], ['+44 20 7946 0958', false],
        ];
    }

    #[DataProvider('phones')]
    public function testPhoneNumbers(string $phone, bool $ok): void
    {
        $this->assertSame($ok, valid_phone($phone));
    }

    public function testPasswordRules(): void
    {
        $this->assertSame([], password_problems('kennel42go'));
        $this->assertCount(1, password_problems('short1'));
        $this->assertCount(1, password_problems('onlyletters'));
        $this->assertCount(2, password_problems('abc'));
    }

    public function testEmail(): void
    {
        $this->assertTrue(valid_email('vet@spca.org.za'));
        $this->assertFalse(valid_email('not-an-email'));
    }
}
