<?php
declare(strict_types=1);

namespace Chialab\Ip;

/**
 * IP address.
 */
final class Address implements \JsonSerializable
{
    /**
     * Protocol version for this address.
     *
     * @var \Chialab\Ip\ProtocolVersion
     */
    private ProtocolVersion $version;

    /**
     * Packed in-addr representation of this address.
     *
     * @var string
     */
    private string $packed;

    /**
     * Initialize a new IP address from its human-readable representation.
     *
     * @param string $address Packed address.
     * @return self
     */
    public static function parse(string $address): self
    {
        $version = ProtocolVersion::fromAddress($address);
        $packed = \inet_pton($address);
        if ($packed === false) {
            throw new \InvalidArgumentException(sprintf('Invalid IP address: %s', $address));
        }

        return new self($version, $packed);
    }

    /**
     * Instantiate a netmask for the given protocol and with the requested prefix length.
     *
     * @param \Chialab\Ip\ProtocolVersion $version Protocol version.
     * @param int $prefix Prefix length.
     * @return self
     */
    public static function netmask(ProtocolVersion $version, int $prefix): self
    {
        $version->validatePrefixLength($prefix);

        $bitsLength = $version->getBitsLength();
        $mask = [];
        for ($i = 0; $i < $bitsLength; $i += 32) {
            $mask[] = 0xffffffff & ~((1 << 32 - \min($prefix, 32)) - 1);
            $prefix -= 32;
        }

        $packed = \pack('N*', ...$mask);

        return new self($version, $packed);
    }

    /**
     * Initialize an IP address.
     *
     * @param \Chialab\Ip\ProtocolVersion $version Protocol version.
     * @param string $packed Packed representation of IP address.
     */
    private function __construct(ProtocolVersion $version, string $packed)
    {
        $this->version = $version;
        $this->packed = $packed;
    }

    /**
     * Unpack address into an array of 32bit unsigned integers.
     *
     * @return int[]
     */
    public function unpack(): array
    {
        return \array_values(\unpack('N*', $this->packed));
    }

    /**
     * Get address as a string.
     *
     * @return string
     */
    public function getAddress(): string
    {
        return \inet_ntop($this->packed);
    }

    /**
     * Get protocol version.
     *
     * @return \Chialab\Ip\ProtocolVersion
     */
    public function getProtocolVersion(): ProtocolVersion
    {
        return $this->version;
    }

    /**
     * Check if two addresses represent the same IP address.
     *
     * Check is performed using time-safe {@see \hash_equals()} function. However, it is important
     * to remember that if two addresses are from different protocols (e.g. an IPv4 address and an IPv6 address),
     * this function returns immediately without time safety guarantees.
     *
     * @param \Chialab\Ip\Address $address Address to compare against.
     * @return bool
     */
    public function equals(self $address): bool
    {
        return $this->version === $address->version && \hash_equals($this->packed, $address->packed);
    }

    /**
     * Return a copy of this IP address with the passed netmask applied.
     *
     * @example ```php
     * $ipAddress = IpAddress::parse('192.168.1.1');
     * $netmask = IpAddress::netMask($ipAddress->getProtocolVersion(), 16); // 255.255.0.0
     * $truncated = $ipAddress->withNetmask($netmask); // 192.168.0.0
     * ```
     * @param \Chialab\Ip\Address $netmask Netmask to apply.
     * @return self
     */
    public function applyNetmask(self $netmask): self
    {
        $version = $this->getProtocolVersion();
        if ($netmask->getProtocolVersion() !== $version) {
            throw new \InvalidArgumentException(sprintf('Cannot apply an %s netmask to an %s address', $netmask->getProtocolVersion(), $version));
        }

        $packed = \pack('N*', ...\array_map(
            fn (int $addrBits, int $netmaskBits): int => $addrBits & $netmaskBits,
            $this->unpack(),
            $netmask->unpack(),
        ));

        return new self($version, $packed);
    }

    /**
     * Return string representation of IP address.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getAddress();
    }

    /**
     * Return JSON serializable representation of IP address.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return (string)$this;
    }
}
