<?php
declare(strict_types=1);

namespace Chialab\Ip\Test;

use Chialab\Ip\Address;
use Chialab\Ip\Subnet;
use PHPUnit\Framework\TestCase;

/**
 * Test {@see \Chialab\Ip\Subnet}.
 *
 * @coversDefaultClass \Chialab\Ip\Subnet
 */
class SubnetTest extends TestCase
{
    /**
     * Data provider for {@see SubnetTest::testParse()} test case.
     *
     * @return array{string, int, string, string}[]
     */
    public function parseProvider(): array
    {
        return [
            '10.0.1.1/24' => ['10.0.1.0', 24, '255.255.255.0', '10.0.1.1/24'],
            'fec0::fe1d/120' => ['fec0::fe00', 120, 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ff00', 'fec0::fe1d/120'],
            '192.168.1.254/16' => ['192.168.0.0', 16, '255.255.0.0', '192.168.1.254/16'],
        ];
    }

    /**
     * Test {@see Subnet::parse()} method.
     *
     * @param string $expectedAddress Expected IP address.
     * @param int $expectedPrefix Expected prefix length.
     * @param string $expectedNetmask Expected netmask.
     * @param string $cidrBlock Human-readable CIDR block address.
     * @return void
     * @dataProvider parseProvider()
     * @covers ::parse()
     * @covers ::__construct()
     * @covers ::getFirstAddress()
     * @covers ::getPrefixLength()
     * @covers ::getNetmask()
     * @covers ::__toString()
     * @covers ::jsonSerialize()
     * @uses \Chialab\Ip\Address
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testParse(string $expectedAddress, int $expectedPrefix, string $expectedNetmask, string $cidrBlock): void
    {
        $actual = Subnet::parse($cidrBlock);

        static::assertEquals($expectedAddress, $actual->getFirstAddress()->getAddress());
        static::assertSame($expectedPrefix, $actual->getPrefixLength());
        static::assertEquals($expectedNetmask, $actual->getNetmask()->getAddress());

        $expected = sprintf('%s/%d', $expectedAddress, $expectedPrefix);
        static::assertSame($expected, (string)$actual);
        static::assertJsonStringEqualsJsonString(json_encode($expected), json_encode($actual));
    }

    /**
     * Test {@see Subnet::parse()} method with an invalid input.
     *
     * @return void
     * @covers ::parse()
     */
    public function testParseInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CIDR block: not a CIDR block');

        Subnet::parse('not a CIDR block');
    }

    /**
     * Test {@see Subnet::parse()} method with a non-numeric prefix length.
     *
     * @return void
     * @covers ::parse()
     * @uses \Chialab\Ip\Address
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testParseInvalidNotNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CIDR block: 1.2.3.4/not numeric');

        Subnet::parse('1.2.3.4/not numeric');
    }

    /**
     * Test {@see Subnet::parse()} method with an invalid address.
     *
     * @return void
     * @covers ::parse()
     * @uses \Chialab\Ip\Address
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testParseInvalidAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IP address: not an IP address');

        Subnet::parse('not an IP address/123');
    }

    /**
     * Test {@see Subnet::parse()} method with an invalid prefix length.
     *
     * @return void
     * @covers ::parse()
     * @covers ::__construct()
     * @uses \Chialab\Ip\Address
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testParseInvalidPrefixLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prefix length must be between 0 and 32');

        Subnet::parse('1.2.3.4/123');
    }

    /**
     * Data provider for {@see SubnetTest::testGetLastAddress()} test case.
     *
     * @return array{string, \Chialab\Ip\Subnet}[]
     */
    public function getLastAddressProvider(): array
    {
        return [
            '192.168.0.0/16' => ['192.168.255.255', Subnet::parse('192.168.0.0/16')],
            '10.0.1.0/24' => ['10.0.1.255', Subnet::parse('10.0.1.1/24')],
            'fec0::/64' => ['fec0::ffff:ffff:ffff:ffff', Subnet::parse('fec0::/64')],
            'fec0::fe00/120' => ['fec0::feff', Subnet::parse('fec0::fe1d/120')],
        ];
    }

    /**
     * Test {@see Subnet::getLastAddress()} method.
     *
     * @param string $expected Expected result.
     * @param \Chialab\Ip\Subnet $subnet Subnet.
     * @return void
     * @dataProvider getLastAddressProvider()
     * @covers ::getLastAddress()
     * @uses \Chialab\Ip\Address
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testGetLastAddress(string $expected, Subnet $subnet): void
    {
        $actual = $subnet->getLastAddress();

        static::assertSame($expected, $actual->getAddress());
    }

    /**
     * Data provider for {@see SubnetTest::testContains()} test case.
     *
     * @return array{bool, \Chialab\Ip\Subnet, \Chialab\Ip\Address}[]
     */
    public function containsProvider(): array
    {
        return [
            'mixed versions' => [false, Subnet::parse('192.168.1.1/16'), Address::parse('::192.168.1.1')],
            'same /24 IPv4' => [true, Subnet::parse('192.168.1.1/24'), Address::parse('192.168.1.254')],
            'different /16 IPv4' => [false, Subnet::parse('10.1.1.1/16'), Address::parse('10.2.0.0')],
            'same /120 IPv6' => [true, Subnet::parse('fec0::c0a8:01fe/120'), Address::parse('fec0::c0a8:01fd')],
            'different /120 IPv6' => [false, Subnet::parse('fec0::c0a8:01fe/120'), Address::parse('fec0::c0a8:02fe')],
        ];
    }

    /**
     * Test {@see Subnet::contains()} method.
     *
     * @param bool|\Exception $expected Expected outcome.
     * @param \Chialab\Ip\Subnet $range CIDR block.
     * @param \Chialab\Ip\Address $address IP address.
     * @return void
     * @dataProvider containsProvider()
     * @covers ::contains()
     * @uses \Chialab\Ip\Address
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testContains($expected, Subnet $range, Address $address): void
    {
        if ($expected instanceof \Exception) {
            $this->expectExceptionObject($expected);
        }

        $actual = $range->contains($address);

        static::assertSame($expected, $actual);
    }

    /**
     * Data provider for {@see SubnetTest::testEquals()} test case.
     *
     * @return array{bool, \Chialab\Ip\Subnet, \Chialab\Ip\Subnet}[]
     */
    public function equalsProvider(): array
    {
        return [
            'mixed versions' => [false, Subnet::parse('192.168.0.1/24'), Subnet::parse('fec0::1/64')],
            'same IPv4' => [true, Subnet::parse('192.168.0.1/32'), Subnet::parse('192.168.0.1/32')],
            'different first address IPv4' => [false, Subnet::parse('192.168.0.1/24'), Subnet::parse('10.42.0.1/24')],
            'different prefix length IPv4' => [false, Subnet::parse('192.168.0.1/24'), Subnet::parse('192.168.0.1/28')],
            'same IPv6' => [true, Subnet::parse('fec0::fe1d/16'), Subnet::parse('fec0::d1ef/16')],
            'different first address IPv6' => [false, Subnet::parse('fec0::fe1d/112'), Subnet::parse('fec0::c0a8:01fe/112')],
            'different prefix length IPv6' => [false, Subnet::parse('fec0::fe1d/16'), Subnet::parse('fec0::d1ef/32')],
        ];
    }

    /**
     * Test {@see Subnet::equals()} method.
     *
     * @param bool $expected Expected outcome.
     * @param \Chialab\Ip\Subnet $first First subnet.
     * @param \Chialab\Ip\Subnet $second Second subnet.
     * @return void
     * @dataProvider equalsProvider()
     * @covers ::equals()
     * @uses \Chialab\Ip\Address
     */
    public function testEquals(bool $expected, Subnet $first, Subnet $second): void
    {
        $actual = $first->equals($second);
        static::assertSame($expected, $actual);

        $symmetric = $second->equals($first);
        static::assertSame($expected, $symmetric);
    }

    /**
     * Data provider for {@see SubnetTest::testHasSubnet()} test case.
     *
     * @return array{bool, \Chialab\Ip\Subnet, \Chialab\Ip\Subnet}[]
     */
    public function hasSubnetProvider(): array
    {
        return [
            'mixed versions' => [false, Subnet::parse('192.168.0.1/24'), Subnet::parse('fec0::1/64')],
            'not a subnet (same) IPv4' => [false, Subnet::parse('192.168.0.1/32'), Subnet::parse('192.168.0.1/32')],
            'not a subnet (disjoint) IPv4' => [false, Subnet::parse('192.168.0.1/24'), Subnet::parse('10.42.0.1/24')],
            'not a subnet (supernet) IPv4' => [false, Subnet::parse('192.168.0.1/32'), Subnet::parse('192.168.0.1/24')],
            'subnet IPv4' => [true, Subnet::parse('192.168.0.1/24'), Subnet::parse('192.168.0.1/28')],
            'not a subnet (same) IPv6' => [false, Subnet::parse('fec0::fe1d/16'), Subnet::parse('fec0::d1ef/16')],
            'not a subnet (disjoint) IPv6' => [false, Subnet::parse('fec0::fe1d/112'), Subnet::parse('fec0::c0a8:01fe/112')],
            'not a subnet (supernet) IPv6' => [false, Subnet::parse('fec0::fe1d/16'), Subnet::parse('fec0::d1ef/8')],
            'subnet IPv6' => [true, Subnet::parse('fec0::fe1d/16'), Subnet::parse('fec0::d1ef/32')],
        ];
    }

    /**
     * Test {@see Subnet::hasSubnet()} method.
     *
     * @param bool $expected Expected outcome.
     * @param \Chialab\Ip\Subnet $first First subnet.
     * @param \Chialab\Ip\Subnet $second Second subnet.
     * @return void
     * @dataProvider hasSubnetProvider()
     * @covers ::hasSubnet()
     * @uses \Chialab\Ip\Subnet::contains()
     * @uses \Chialab\Ip\Address
     * @uses \Chialab\Ip\ProtocolVersion
     */
    public function testHasSubnet(bool $expected, Subnet $first, Subnet $second): void
    {
        $actual = $first->hasSubnet($second);
        static::assertSame($expected, $actual);
    }
}
