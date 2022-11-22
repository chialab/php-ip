<?php
declare(strict_types=1);

namespace Chialab\Ip\Test;

use Chialab\Ip\Address;
use Chialab\Ip\ProtocolVersion;
use PHPUnit\Framework\TestCase;

/**
 * Test {@see \Chialab\Ip\Address}.
 *
 * @coversDefaultClass \Chialab\Ip\Address
 */
class AddressTest extends TestCase
{
    /**
     * Data provider for {@see AddressTest::testParse()} test case.
     *
     * @return array{\Chialab\Ip\ProtocolVersion, int[], string}[]
     */
    public function parseProvider(): array
    {
        return [
            '10.0.1.1' => [ProtocolVersion::ipv4(), [0x0a000101], '10.0.1.1'],
            'fec0::fe1d' => [ProtocolVersion::ipv6(), [0xfec00000, 0, 0, 0xfe1d], 'fec0::fe1d'],
            '192.168.1.254' => [ProtocolVersion::ipv4(), [0xc0a801fe], '192.168.1.254'],
        ];
    }

    /**
     * Test {@see Address::parse()} method.
     *
     * @param \Chialab\Ip\ProtocolVersion $expectedVersion Expected IP address version.
     * @param int[] $expectedUint32 Expected Uint32 array.
     * @param string $address Human-readable IP address.
     * @return void
     * @dataProvider parseProvider()
     * @covers ::parse()
     * @covers ::__construct()
     * @covers ::getProtocolVersion()
     * @covers ::getAddress()
     * @covers ::unpack()
     * @covers ::__toString()
     * @covers ::jsonSerialize()
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testParse(ProtocolVersion $expectedVersion, array $expectedUint32, string $address): void
    {
        $ipAddress = Address::parse($address);
        static::assertSame($expectedVersion, $ipAddress->getProtocolVersion());
        static::assertSame($address, $ipAddress->getAddress());
        static::assertSame($expectedUint32, $ipAddress->unpack());
        static::assertSame($address, (string)$ipAddress);
        static::assertJsonStringEqualsJsonString(json_encode($address), json_encode($ipAddress));
    }

    /**
     * Test {@see Address::parse()} method with an invalid input.
     *
     * @return void
     * @covers ::parse()
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testParseInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IP address: not an IP address');

        Address::parse('not an IP address');
    }

    /**
     * Data provider for {@see AddressTest::testFromBits()} test case.
     *
     * @return array{string|\Exception, \Chialab\Ip\ProtocolVersion, int[]}[]
     */
    public function fromBitsProvider(): array
    {
        return [
            '127.0.0.1' => ['127.0.0.1', ProtocolVersion::ipv4(), [0x7f000001]],
            '192.168.192.168' => ['192.168.192.168', ProtocolVersion::ipv4(), [0xc0a8c0a8]],
            'too few bits IPv4' => [new \InvalidArgumentException('Wrong number of 32bit integers given: expected 1, got 0'), ProtocolVersion::ipv4(), []],
            'too many bits IPv4' => [new \InvalidArgumentException('Wrong number of 32bit integers given: expected 1, got 2'), ProtocolVersion::ipv4(), [0x7f000001, 0x7f000001]],
            '64bit integer' => [new \InvalidArgumentException('Not a valid 32bit integer: 0xffffffffffffff'), ProtocolVersion::ipv4(), [0xffffffffffffff]],

            'ffff:ffff::' => ['ffff:ffff::', ProtocolVersion::ipv6(), [0xffffffff, 0, 0, 0]],
            'fec0::fe1d' => ['fec0::fe1d', ProtocolVersion::ipv6(), [0xfec00000, 0, 0, 0xfe1d]],
            'too few bits IPv6' => [new \InvalidArgumentException('Wrong number of 32bit integers given: expected 4, got 1'), ProtocolVersion::ipv6(), [0x7f000001]],
            'too many bits IPv6' => [new \InvalidArgumentException('Wrong number of 32bit integers given: expected 4, got 5'), ProtocolVersion::ipv6(), [0xfec00000, 0, 0, 0, 0xfe1d]],
        ];
    }

    /**
     * Test {@see Address::fromBits()} method.
     *
     * @param string|\Exception $expected Expected outcome.
     * @param \Chialab\Ip\ProtocolVersion $version Internet protocol version.
     * @param int[] $uint32 Array of unsigned 32bit integers representing address bits.
     * @return void
     * @dataProvider fromBitsProvider()
     * @covers ::fromBits()
     * @uses \Chialab\Ip\Address::__construct()
     * @uses \Chialab\Ip\Address::getAddress()
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testFromBits($expected, ProtocolVersion $version, array $uint32): void
    {
        if ($expected instanceof \Exception) {
            $this->expectExceptionObject($expected);
        }

        $actual = Address::fromBits($version, ...$uint32);

        static::assertSame($expected, $actual->getAddress());
    }

    /**
     * Data provider for {@see AddressTest::testNetmask()} test case.
     *
     * @return array{int[]|\Exception, \Chialab\Ip\ProtocolVersion, int}[]
     */
    public function netmaskProvider(): array
    {
        return [
            'negative IPv4' => [new \InvalidArgumentException('Prefix length must be between 0 and 32, got -1'), ProtocolVersion::ipv4(), -1],
            'negative IPv6' => [new \InvalidArgumentException('Prefix length must be between 0 and 128, got -1'), ProtocolVersion::ipv6(), -1],
            'oob IPv4' => [new \InvalidArgumentException('Prefix length must be between 0 and 32, got 36'), ProtocolVersion::ipv4(), 36],
            'oob IPv6' => [new \InvalidArgumentException('Prefix length must be between 0 and 128, got 130'), ProtocolVersion::ipv6(), 130],

            'IPv4 /32' => [[0xffffffff], ProtocolVersion::ipv4(), 32],
            'IPv4 /30' => [[0xfffffffc], ProtocolVersion::ipv4(), 30],
            'IPv4 /28' => [[0xfffffff0], ProtocolVersion::ipv4(), 28],
            'IPv4 /24' => [[0xffffff00], ProtocolVersion::ipv4(), 24],
            'IPv4 /16' => [[0xffff0000], ProtocolVersion::ipv4(), 16],
            'IPv4 /0' => [[0], ProtocolVersion::ipv4(), 0],

            'IPv6 /128' => [[0xffffffff, 0xffffffff, 0xffffffff, 0xffffffff], ProtocolVersion::ipv6(), 128],
            'IPv6 /64' => [[0xffffffff, 0xffffffff, 0, 0], ProtocolVersion::ipv6(), 64],
            'IPv6 /32' => [[0xffffffff, 0, 0, 0], ProtocolVersion::ipv6(), 32],
            'IPv6 /0' => [[0, 0, 0, 0], ProtocolVersion::ipv6(), 0],
        ];
    }

    /**
     * Test {@see ProtocolVersion::getMask()} method.
     *
     * @param int[]|\Exception $expected Expected outcome.
     * @param \Chialab\Ip\ProtocolVersion $version Protocol version.
     * @param int $prefix Prefix length in bits.
     * @return void
     * @dataProvider netmaskProvider()
     * @covers ::netmask()
     * @uses \Chialab\Ip\Address::fromBits()
     * @uses \Chialab\Ip\Address::__construct()
     * @uses \Chialab\Ip\Address::unpack()
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testNetmask($expected, ProtocolVersion $version, int $prefix): void
    {
        if ($expected instanceof \Exception) {
            $this->expectExceptionObject($expected);
        }

        $actual = Address::netmask($version, $prefix)->unpack();

        static::assertSame($expected, $actual);
    }

    /**
     * Data provider for {@see AddressTest::testApplyNetmask()} test case.
     *
     * @return array{string|\Exception, \Chialab\Ip\Address, \Chialab\Ip\Address}[]
     */
    public function applyNetmaskProvider(): array
    {
        return [
            '192.168.1.1/0' => ['0.0.0.0', Address::parse('192.168.1.1'), Address::netmask(ProtocolVersion::ipv4(), 0)],
            '192.168.1.1/16' => ['192.168.0.0', Address::parse('192.168.1.1'), Address::netmask(ProtocolVersion::ipv4(), 16)],
            '10.1.42.254/8' => ['10.0.0.0', Address::parse('10.1.42.254'), Address::netmask(ProtocolVersion::ipv4(), 8)],
            '10.1.42.254/24' => ['10.1.42.0', Address::parse('10.1.42.254'), Address::netmask(ProtocolVersion::ipv4(), 24)],
            '10.1.42.254/30' => ['10.1.42.252', Address::parse('10.1.42.254'), Address::netmask(ProtocolVersion::ipv4(), 30)],
            '10.1.42.254/36' => [new \InvalidArgumentException('Cannot apply an IPv6 netmask to an IPv4 address'), Address::parse('10.1.42.254'), Address::netmask(ProtocolVersion::ipv6(), 36)],
            'fec0::c0a8:01fe/0' => ['::', Address::parse('fec0::c0a8:01fe'), Address::netmask(ProtocolVersion::ipv6(), 0)],
            'fec0::c0a8:01fe/120' => ['fec0::c0a8:100', Address::parse('fec0::c0a8:01fe'), Address::netmask(ProtocolVersion::ipv6(), 120)],
            'fec0::c0a8:01fe/32' => ['fec0::', Address::parse('fec0::c0a8:02fe'), Address::netmask(ProtocolVersion::ipv6(), 32)],
            'fec0::c0a8:01fe/8' => ['fe00::', Address::parse('fec0::c0a8:02fe'), Address::netmask(ProtocolVersion::ipv6(), 8)],
            'fec0::c0a8:01fe/130' => [new \InvalidArgumentException('Cannot apply an IPv4 netmask to an IPv6 address'), Address::parse('fec0::c0a8:02fe'), Address::netmask(ProtocolVersion::ipv4(), 8)],
        ];
    }

    /**
     * Test {@see Address::applyNetmask()} method.
     *
     * @param string|\Exception $expected Expected outcome.
     * @param \Chialab\Ip\Address $address IP address.
     * @param \Chialab\Ip\Address $netmask Netmask.
     * @return void
     * @dataProvider applyNetmaskProvider()
     * @covers ::applyNetmask()
     * @uses \Chialab\Ip\Address::fromBits()
     * @uses \Chialab\Ip\Address::__construct()
     * @uses \Chialab\Ip\Address::getProtocolVersion()
     * @uses \Chialab\Ip\Address::unpack()
     * @uses \Chialab\Ip\Address::getAddress()
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testApplyNetmask($expected, Address $address, Address $netmask): void
    {
        if ($expected instanceof \Exception) {
            $this->expectExceptionObject($expected);
        }

        $actual = $address->applyNetmask($netmask);
        static::assertNotSame($address, $actual);
        static::assertEquals($expected, $actual->getAddress());
    }

    /**
     * Data provider for {@see AddressTest::testEquals()} test case.
     *
     * @return array{bool, \Chialab\Ip\Address, \Chialab\Ip\Address}[]
     */
    public function equalsProvider(): array
    {
        return [
            'mixed versions' => [false, Address::parse('192.168.0.1'), Address::parse('fec0::1')],
            'same IPv4' => [true, Address::parse('192.168.0.1'), Address::parse('192.168.0.1')],
            'different IPv4' => [false, Address::parse('192.168.0.1'), Address::parse('10.42.0.1')],
            'same IPv6' => [true, Address::parse('fec0::fe1d'), Address::parse('fec0::fe1d')],
            'different IPv6' => [false, Address::parse('fec0::fe1d'), Address::parse('fec0::0cef')],
        ];
    }

    /**
     * Test {@see Address::equals()} method.
     *
     * @param bool $expected Expected outcome.
     * @param \Chialab\Ip\Address $first First address.
     * @param \Chialab\Ip\Address $second Second address.
     * @return void
     * @dataProvider equalsProvider()
     * @covers ::equals()
     */
    public function testEquals(bool $expected, Address $first, Address $second): void
    {
        $actual = $first->equals($second);
        static::assertSame($expected, $actual);

        $symmetric = $second->equals($first);
        static::assertSame($expected, $symmetric);
    }
}
