IP Address library for PHP
==========================

This library for PHP 7.4+ builds an abstraction over management of
Internet Protocol versions, addresses and CIDR blocks.

Using this library makes it easy to check if an IP address belongs to a subnet or not.

## Installation

Installing this library can be done via Composer:

```console
$ composer require chialab/ip
```

## Usage

```php
use Chialab\Ip;

$address = Ip\Address::parse('192.168.1.1');
var_dump($address->getProtocolVersion() === Ip\ProtocolVersion::ipv4()); // bool(true)
var_dump($address->getProtocolVersion() === Ip\ProtocolVersion::ipv6()); // bool(false)

$subnet = Ip\Subnet::parse('fec0::1/16');
var_dump((string)$subnet->getFirstAddress()); // string(6): "fec0::"
var_dump((string)$subnet->getNetmask()); // string(6) "ffff::"
var_dump($subnet->contains($address)); // bool(false)
var_dump($subnet->contains(Ip\Address::parse('fec0:fe08:0123:4567:89ab:cdef:1234:5678'))); // bool(true)
var_dump($subnet->hasSubnet(Ip\Subnet::parse('fec0:fe08::/32'))); // bool(true)
```
