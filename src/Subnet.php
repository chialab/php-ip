<?php
declare(strict_types=1);

namespace Chialab\Ip;

/**
 * Class representing a CIDR block.
 */
final class Subnet implements \JsonSerializable
{
    /**
     * First address of this subnet.
     *
     * @var \Chialab\Ip\Address
     */
    private Address $firstAddress;

    /**
     * Netmask to apply to IP Addresses.
     *
     * @var \Chialab\Ip\Address
     */
    private Address $netmask;

    /**
     * Prefix length, in bits.
     *
     * @var int
     */
    private int $prefix;

    /**
     * Construct a subnet parsing a CIDR block.
     *
     * @param string $cidr CIDR block.
     * @return self
     */
    public static function parse(string $cidr): self
    {
        $parts = \explode('/', $cidr);
        if (\count($parts) !== 2) {
            throw new \InvalidArgumentException(sprintf('Invalid CIDR block: %s', $cidr));
        }

        $address = Address::parse($parts[0]);
        $prefix = \filter_var($parts[1], \FILTER_VALIDATE_INT);
        if ($prefix === false) {
            throw new \InvalidArgumentException(sprintf('Invalid CIDR block: %s', $cidr));
        }

        return new self($address, $prefix);
    }

    /**
     * Instantiate a new subnet.
     *
     * @param \Chialab\Ip\Address $address Base IP address.
     * @param int $prefix Prefix length, in bits.
     */
    public function __construct(Address $address, int $prefix)
    {
        $this->prefix = $prefix;
        $this->netmask = Address::netmask($address->getProtocolVersion(), $this->prefix);
        $this->firstAddress = $address->applyNetmask($this->netmask);
    }

    /**
     * Get first address in subnet.
     *
     * @return \Chialab\Ip\Address
     */
    public function getFirstAddress(): Address
    {
        return $this->firstAddress;
    }

    /**
     * Get block prefix length, in bits.
     *
     * @return int
     */
    public function getPrefixLength(): int
    {
        return $this->prefix;
    }

    /**
     * Get netmask for this CIDR block.
     *
     * @return \Chialab\Ip\Address
     */
    public function getNetmask(): Address
    {
        return $this->netmask;
    }

    /**
     * Check if an IP address is within this CIDR block.
     *
     * @param \Chialab\Ip\Address $address IP Address.
     * @return bool
     */
    public function contains(Address $address): bool
    {
        return $this->firstAddress->getProtocolVersion() === $address->getProtocolVersion() && $this->firstAddress->equals($address->applyNetmask($this->netmask));
    }

    /**
     * Check if this subnet strictly contains the requested subnet.
     *
     * @param \Chialab\Ip\Subnet $subnet Subnet to test.
     * @return bool
     */
    public function hasSubnet(self $subnet): bool
    {
        return $this->contains($subnet->firstAddress) && $this->prefix < $subnet->prefix;
    }

    /**
     * Check if two subnets represent the same subnet.
     *
     * @param \Chialab\Ip\Subnet $subnet Subnet to compare against.
     * @return bool
     */
    public function equals(self $subnet): bool
    {
        return $this->firstAddress->equals($subnet->firstAddress) && $this->prefix === $subnet->prefix;
    }

    /**
     * Return CIDR block in human-readable form.
     *
     * @return string
     */
    public function __toString(): string
    {
        return \sprintf('%s/%d', $this->firstAddress, $this->prefix);
    }

    /**
     * Return CIDR block in human-readable form.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return (string)$this;
    }
}
