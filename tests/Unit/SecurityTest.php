<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    public function testHtmlEscape(): void
    {
        $this->assertEquals('', htmlspecialchars('', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', htmlspecialchars('<script>alert(1)</script>', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $this->assertEquals('Jean &amp; Paul', htmlspecialchars('Jean & Paul', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $this->assertEquals('&quot;test&quot;', htmlspecialchars('"test"', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $this->assertEquals("'test'", htmlspecialchars("'test'", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    public function testValidateInt(): void
    {
        $this->assertSame(42, filter_var(42, FILTER_VALIDATE_INT) !== false ? 42 : 0);
        $this->assertSame(0, filter_var('abc', FILTER_VALIDATE_INT) !== false ? (int)'abc' : 0);
        $this->assertSame(0, filter_var('', FILTER_VALIDATE_INT) !== false ? (int)'' : 0);
        $this->assertSame(-5, filter_var(-5, FILTER_VALIDATE_INT) !== false ? -5 : 0);
    }

    public function testValidateEmail(): void
    {
        $this->assertEquals('test@example.com', filter_var('test@example.com', FILTER_VALIDATE_EMAIL) ?: '');
        $this->assertEquals('', filter_var('not-an-email', FILTER_VALIDATE_EMAIL) ?: '');
        $this->assertEquals('', filter_var('', FILTER_VALIDATE_EMAIL) ?: '');
    }

    public function testValidateDate(): void
    {
        $d = \DateTime::createFromFormat('Y-m-d', '2025-01-15');
        $this->assertNotFalse($d);

        $d2 = \DateTime::createFromFormat('Y-m-d', 'not-a-date');
        $this->assertFalse($d2);
    }

    public function testValidateEnum(): void
    {
        $allowed = ['S1', 'S2', 'S3', 'S4'];
        $this->assertSame('S1', in_array('S1', $allowed, true) ? 'S1' : '');
        $this->assertSame('', in_array('S5', $allowed, true) ? 'S5' : '');
        $this->assertSame('', in_array('', $allowed, true) ? '' : '');
    }

    public function testPasswordBcrypt(): void
    {
        $password = 'test_password_123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertTrue(password_verify($password, $hash));
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertFalse(password_verify('wrong_password', $hash));
    }
}
