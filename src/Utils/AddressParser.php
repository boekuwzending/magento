<?php

namespace Boekuwzending\Magento\Utils;

class AddressParser
{
    /**
     * @param string $address
     * @return object
     */
    function parseAddressLine(string $address): object
    {
        $number = null;
        $numberAddition = null;

        if (preg_match('/^\\s*(.+)\\s+(\\d+)\\s*(\\S*\\s+\\d+\\s*\\S*)$/', $address, $parts)
            || preg_match('/^\\s*(.+)\\s+(\\d+)\\s*(,\\s*.*)$/', $address, $parts)
            || preg_match('/^\\s*(.+)\\s+(\\d+)\\s*(.*)$/', $address, $parts)
        ) {
            $street = $parts[1];
            $number = $parts[2];
            $numberAddition = trim($parts[3]);
        } elseif (preg_match('/^\\s*(\\d+)(\\S*)\\s+(.*)$/', $address, $parts)) {
            $street = $parts[3];
            $number = $parts[1];
            $numberAddition = $parts[2];
        } elseif (preg_match('/^\\s*(.+\\D)\\s*(\\d+)\\s*(\\D+\\s*\\d*\\s*\\S*)$/', $address, $parts)
            || preg_match('/^\\s*(.+\\D)\\s*(\\d+)\\s*(.*)$/', $address, $parts)
        ) {
            $street = $parts[1];
            $number = $parts[2];
            $numberAddition = trim($parts[3]);
        } else {
            $street = $address;
        }

        return (object)[
            'street' => $street,
            'number' => $number,
            'numberAddition' => trim($numberAddition, '-')
        ];
    }
}