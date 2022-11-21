<?php
declare(strict_types=1);

namespace Chialab\Ip;

/**
 * Internet Protocol version.
 */
final class ProtocolVersion
{
    private const V4 = 32;
    private const V6 = 128;

    /**
     * Protocol version name.
     *
     * @var string
     */
    private string $name;

    /**
     * Length of addresses for this protocol version, in bits.
     *
     * @var int
     */
    private int $bits;

    /**
     * Singleton instance for Internet Protocol v4.
     *
     * @var self
     */
    private static self $ipv4;

    /**
     * Singleton instance for Internet Protocol v6.
     *
     * @var self
     */
    private static self $ipv6;

    /**
     * Internet Protocol version 4.
     *
     * @return self
     */
    public static function ipv4(): self
    {
        return self::$ipv4 ?? (self::$ipv4 = new self('IPv4', self::V4));
    }

    /**
     * Internet Protocol version 4.
     *
     * @return self
     */
    public static function ipv6(): self
    {
        return self::$ipv6 ?? (self::$ipv6 = new self('IPv6', self::V6));
    }

    /**
     * Detect Internet Protocol version for given address.
     *
     * @param string $address IP address.
     * @return self
     * @throws \InvalidArgumentException Throws an exception if address is not reckognized as a valid IP.
     */
    public static function fromAddress(string $address): self
    {
        if (\filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false) {
            return self::ipv4();
        } elseif (\filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) !== false) {
            return self::ipv6();
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid IP address: %s', $address));
        }
    }

    /**
     * Private constructor.
     *
     * @param string $name Address protocol name.
     * @param self::V4|self::V6 $bits Address bits length.
     * @codeCoverageIgnore
     */
    private function __construct(string $name, int $bits)
    {
        $this->name = $name;
        $this->bits = $bits;
    }

    /**
     * Get address bits length.
     *
     * @return int
     */
    public function getBitsLength(): int
    {
        return $this->bits;
    }

    /**
     * Validate a prefix length.
     *
     * @param int $prefix Prefix length, in bits.
     * @return void
     * @throws \InvalidArgumentException Throws an exception if prefix is negative or longer than address length.
     */
    public function validatePrefixLength(int $prefix): void
    {
        if ($prefix < 0 || $prefix > $this->getBitsLength()) {
            throw new \InvalidArgumentException(sprintf('Prefix length must be between 0 and %d, got %d', $this->getBitsLength(), $prefix));
        }
    }

    /**
     * Private clone.
     *
     * @return void
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    /**
     * Represent protocol version as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
