<?php
declare(strict_types=1);

namespace Chialab\Ip\Test;

use Chialab\Ip\ProtocolVersion;
use PHPUnit\Framework\TestCase;

/**
 * Test {@see \Chialab\Ip\ProtocolVersion}.
 *
 * @coversDefaultClass \Chialab\Ip\ProtocolVersion
 */
class ProtocolVersionTest extends TestCase
{
    /**
     * Test accessing IPv4 singleton.
     *
     * @return void
     * @covers ::ipv4()
     * @covers ::__construct()
     * @covers ::getBitsLength()
     * @covers ::__toString()
     */
    public function testIpv4(): void
    {
        $ipv4 = ProtocolVersion::ipv4();

        static::assertSame(32, $ipv4->getBitsLength());
        static::assertSame('IPv4', (string)$ipv4);

        $other = ProtocolVersion::ipv4();
        static::assertSame($ipv4, $other);
    }

    /**
     * Test accessing IPv6 singleton.
     *
     * @return void
     * @covers ::ipv6()
     * @covers ::__construct()
     * @covers ::getBitsLength()
     * @covers ::__toString()
     */
    public function testIpv6(): void
    {
        $ipv6 = ProtocolVersion::ipv6();

        static::assertSame(128, $ipv6->getBitsLength());
        static::assertSame('IPv6', (string)$ipv6);

        $other = ProtocolVersion::ipv6();
        static::assertSame($ipv6, $other);
    }

    /**
     * Data provider for {@see ProtocolVersionTest::testFromAddress()} test case.
     *
     * @return array{\Chialab\Ip\ProtocolVersion|\Exception, string}[]
     */
    public function fromAddressProvider(): array
    {
        return [
            'IPv4' => [ProtocolVersion::ipv4(), '192.168.1.1'],
            'IPv6' => [ProtocolVersion::ipv6(), 'fec0::c0a8:01fe'],
            'invalid IP' => [new \InvalidArgumentException('Invalid IP address: foo'), 'foo'],
        ];
    }

    /**
     * Test {@see ProtocolVersion::fromAddress()} method.
     *
     * @param \Chialab\Ip\ProtocolVersion|\Exception $expected Expected outcome.
     * @param string $address IP address.
     * @return void
     * @dataProvider fromAddressProvider()
     * @covers ::fromAddress()
     * @uses \Chialab\Ip\ProtocolVersion::ipv4()
     * @uses \Chialab\Ip\ProtocolVersion::ipv6()
     */
    public function testFromAddress($expected, string $address): void
    {
        if ($expected instanceof \Exception) {
            $this->expectExceptionObject($expected);
        }

        $actual = ProtocolVersion::fromAddress($address);
        static::assertSame($expected, $actual);
    }

    /**
     * Data provider for {@see ProtocolVersionTest::testValidatePrefixLength()} test case.
     *
     * @return array{\Exception|null, \Chialab\Ip\ProtocolVersion, int}[]
     */
    public function validatePrefixLengthProvider(): array
    {
        return [
            'IPv4 /-1' => [new \InvalidArgumentException('Prefix length must be between 0 and 32, got -1'), ProtocolVersion::ipv4(), -1],
            'IPv4 /0' => [null, ProtocolVersion::ipv4(), 0],
            'IPv4 /17' => [null, ProtocolVersion::ipv4(), 17],
            'IPv4 /32' => [null, ProtocolVersion::ipv4(), 32],
            'IPv4 /36' => [new \InvalidArgumentException('Prefix length must be between 0 and 32, got 36'), ProtocolVersion::ipv4(), 36],

            'IPv6 /-1' => [new \InvalidArgumentException('Prefix length must be between 0 and 128, got -1'), ProtocolVersion::ipv6(), -1],
            'IPv6 /0' => [null, ProtocolVersion::ipv6(), 0],
            'IPv6 /17' => [null, ProtocolVersion::ipv6(), 17],
            'IPv6 /32' => [null, ProtocolVersion::ipv6(), 32],
            'IPv6 /96' => [null, ProtocolVersion::ipv6(), 96],
            'IPv6 /128' => [null, ProtocolVersion::ipv6(), 128],
            'IPv6 /130' => [new \InvalidArgumentException('Prefix length must be between 0 and 128, got 130'), ProtocolVersion::ipv6(), 130],
        ];
    }

    /**
     * Test {@see ProtocolVersion::validatePrefixLength()} method.
     *
     * @param \Exception|null $expected Expected error.
     * @param \Chialab\Ip\ProtocolVersion $ipVersion IP version.
     * @param int $bits Prefix length in bits.
     * @return void
     * @dataProvider validatePrefixLengthProvider()
     * @covers ::validatePrefixLength()
     * @uses \Chialab\Ip\ProtocolVersion::getBitsLength()
     */
    public function testValidatePrefixLength(?\Exception $expected, ProtocolVersion $ipVersion, int $bits): void
    {
        if ($expected instanceof \Exception) {
            $this->expectExceptionObject($expected);
        }

        $return = $ipVersion->validatePrefixLength($bits);
        static::assertTrue(true);
    }

    /**
     * Test that {@see ProtocolVersion::__clone()} is disallowed.
     *
     * @return void
     * @coversNothing
     */
    public function testClone(): void
    {
        $ipv4 = ProtocolVersion::ipv4();

        $this->expectError();
        $this->expectErrorMessageMatches(sprintf(
            '#^Call to private %s::__clone\(\) from (?:context|scope) \'?%s\'?$#',
            preg_quote(ProtocolVersion::class, '#'),
            preg_quote(self::class, '#'),
        ));
        $cloned = clone $ipv4;
    }
}
