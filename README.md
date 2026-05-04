---
noteId: "42162a90346b11f1863d074aaebb462f"
tags: []

---

# CNGnManager

CNGnManager is a PHP library for interacting with the CNGN API. It provides a simple interface for various operations such as checking balance, bridging between chains, depositing for redemption, managing virtual accounts, and more.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Networks](#networks)
- [Available Methods](#available-methods)
- [Testing](#testing)
- [Return Values](#return-values)
- [Error Handling](#error-handling)
- [Types](#types)
- [Security](#security)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)

## Installation

To install CNGnManager and its dependencies, run:

```bash
composer require wrappedcbdc/cngn-php-library
```

## Usage

First, import the `CNGnManager` class using its namespace.

```php
<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use WrappedCBDC\CNGnManager;
```

Then, create an instance of `CNGnManager` with your credentials:

```php
// Replace these with your actual credentials
$apiKey        = 'cngn_live_sk**********';
$encryptionKey = 'your-encryption-key';
$privateKey    = file_get_contents('/path/to/your/private-key.pem');

// Or inline:
// $privateKey = "-----BEGIN OPENSSH PRIVATE KEY-----\n...\n-----END OPENSSH PRIVATE KEY-----";

$manager = new CNGnManager($apiKey, $privateKey, $encryptionKey);
```

Optionally define a small helper to print results during development:

```php
function printResult(string $label, string $json): void
{
    echo "\n━━━ $label ━━━\n";
    $decoded = json_decode($json, true);
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
```

> See [`example.php`](example.php) for a runnable script that calls every endpoint below.

## Networks

The library supports multiple blockchain networks:

- `Network::BSC` - Binance Smart Chain
- `Network::ATC` - Asset Chain
- `Network::XBN` - Bantu Chain
- `Network::ETH` - Ethereum
- `Network::MATIC` - Polygon (Matic)
- `Network::TRX` - Tron
- `Network::BASE` - Base

## Available Methods

### CNGnManager Methods

The examples below mirror [`example.php`](example.php) and use the `printResult()` helper from the [Usage](#usage) section. Replace `printResult()` with `echo` if you don't need pretty-printed output.

### GET Endpoints

#### 1. Get Balance

```php
printResult('Get Balance', $manager->getBalance());
```

#### 2. Get Transaction History

```php
// page 1, 10 per page
printResult('Transaction History', $manager->getTransactionHistory(1, 10));
```

#### 3. Get Banks

```php
printResult('Get Banks', $manager->getBanks());
```

#### 4. Get Virtual Account

```php
printResult('Get Virtual Account', $manager->getVirtualAccount());
```

#### 5. Get Supported Networks

```php
printResult('Get Supported Networks', $manager->getSupportedNetworks());

// With blockchain details
printResult('Get Supported Networks (with blockchain)', $manager->getSupportedNetworks(includeBlockchain: true));
```

#### 6. Get Whitelisted Addresses

```php
printResult('Get Whitelisted Addresses', $manager->getWhitelistedAddresses());

// With network details
printResult('Get Whitelisted Addresses (with network)', $manager->getWhitelistedAddresses(includeNetwork: true));
```

#### 7. Verify Withdrawal

```php
printResult('Verify Withdrawal', $manager->verifyWithdraw('WTH-your-transaction-ref'));
```

### POST / PUT Endpoints

#### 8. Validate Account

```php
/**
 * @param array{bankCode: string, accountNumber: string} $data
 */
printResult('Validate Account', $manager->validateAccount([
    'bankCode'      => '044',
    'accountNumber' => '0123456789',
]));
```

> **NOTE:** Use the `getBanks()` method to fetch the list of banks and their codes.

#### 9. Redeem Assets

```php
/**
 * @param array{amount: int, bankCode: string, accountNumber: string, saveDetails?: bool} $data
 */
printResult('Redeem Assets', $manager->redeemAssets([
    'amount'        => 1000,
    'bankCode'      => '044',
    'accountNumber' => '0123456789',
    'saveDetails'   => false,
]));
```

> **NOTE:** Use the `getBanks()` method to fetch the list of banks and their codes.

#### 10. Withdraw (cNGN to external wallet)

```php
/**
 * @param array{amount: int, address: string, networkId: string, shouldSaveAddress?: bool} $data
 */
// Use a networkId from getSupportedNetworks()
printResult('Withdraw', $manager->withdraw([
    'amount'            => 100,
    'address'           => '0xYourWalletAddress',
    'networkId'         => 'your-network-id',
    'shouldSaveAddress' => false,
]));
```

> **NOTE:** Use the `getSupportedNetworks()` method to retrieve valid network IDs.

#### 11. Bridge Assets

```php
/**
 * @param array{destinationNetworkId: string, destinationAddress: string, originNetworkId: string, senderAddress?: string, callbackUrl?: string} $data
 */
// Use networkIds from getSupportedNetworks()
printResult('Bridge Assets', $manager->bridgeAssets([
    'destinationNetworkId' => 'destination-network-id',
    'destinationAddress'   => '0xDestinationAddress',
    'originNetworkId'      => 'origin-network-id',
    'callbackUrl'          => 'https://your-domain.com/webhook',
]));
```

> **NOTE:** Use the `getSupportedNetworks()` method to retrieve valid network IDs.

#### 12. Whitelist Address

```php
/**
 * @param array{address: string, networkId: string} $data
 */
// Use a networkId from getSupportedNetworks()
printResult('Whitelist Address', $manager->whitelistAddress([
    'address'   => '0xAddressToWhitelist',
    'networkId' => 'your-network-id',
]));
```

#### 13. Update Bank Account

```php
/**
 * @param array{bankName: string, bankAccountName: string, bankAccountNumber: string} $data
 */
printResult('Update Bank Account', $manager->updateBankAccount([
    'bankName'          => 'Access Bank',
    'bankAccountName'   => 'John Doe Enterprises',
    'bankAccountNumber' => '0123456789',
]));
```

## Testing

This project uses PHPUnit for testing. To run the tests:

```bash
composer run test
```

This will run all tests in the `__test__` directory.

### Test Structure

The tests are located in the `__test__` directory and cover:

- All GET and POST/PUT endpoint calls
- Encryption and decryption (AESCrypto round-trip)
- Error handling (client exceptions, network errors, unexpected errors)
- JSON return format validation for all methods

## Return Values

All responses are returned as JSON strings. Decode them with:

```php
$data = json_decode($response);        // as object
$data = json_decode($response, true);  // as array
```

## Error Handling

The library catches all API errors and returns them as JSON strings with error details instead of throwing exceptions:

```json
{
    "success": false,
    "error": "API request failed",
    "message": "Error description",
    "status_code": 400
}
```

## Types

The library includes PHP constant classes for all parameters:

- `Network` - blockchain network constants
- `AssetType` - asset type constants (`FIAT`, `WRAPPED`, `ENAIRA`)
- `ProviderType` - provider constants (`KORAPAY`, `BUDPAY`)

All methods accepting `array $data` include PHPDoc `@param` annotations with typed array shapes for IDE autocompletion.

## Security

This library uses AES-256-CBC encryption for request payloads and Ed25519 (Curve25519) decryption for response data. Ensure that your `encryptionKey` and `privateKey` are kept secure.

## Contributing

Contributions, issues, and feature requests are welcome. Feel free to check the [issues page](https://github.com/wrappedcbdc/cngn-php-library/issues) if you want to contribute.

To contribute:
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Create a Pull Request

## Support

For support, please:
- Open an issue in the GitHub repository
- Check existing documentation
- Contact the support team

## License

[MIT](https://choosealicense.com/licenses/mit/)
