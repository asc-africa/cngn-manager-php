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

First, import the `CNGnManager` class using its namespace and all necessary constants.

```php
<?php declare(strict_types=1);
    require __DIR__ ."/vendor/autoload.php";
    use WrappedCBDC\CNGnManager;
    use WrappedCBDC\constants\{Network, ProviderType};
```

Then, create an instance of `CNGnManager` with your secrets:

```php
$apiKey = "cngn_live_sk**********";
$encryptionKey = "yourencryptionkey";
$sshPrivateKey = "-----BEGIN OPENSSH PRIVATE KEY-----
your ssh key
-----END OPENSSH PRIVATE KEY-----";

// NOTE: You can also get your private key from a file
$sshPrivateKey = file_get_contents("/path/to/sshkey.key");

$manager = new CNGnManager($apiKey, $sshPrivateKey, $encryptionKey);

// Example: Get balance
$balance = $manager->getBalance();
echo $balance;
```

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

#### Get Balance

```php
$balance = $manager->getBalance();
echo $balance;
```

#### Get Transaction History

```php
$page = 1;
$limit = 10;
$transactions = $manager->getTransactionHistory($page, $limit);
echo $transactions;
```

#### Get Banks

```php
$bankList = $manager->getBanks();
echo $bankList;
```

#### Get Virtual Account

```php
$virtualAccount = $manager->getVirtualAccount();
echo $virtualAccount;
```

#### Withdraw from Chains

```php
/**
 * @param array{amount: int, address: string, networkId: string, shouldSaveAddress?: bool} $data
 */
$withdrawParams = [
    "amount" => 100,
    "address" => '0x1234...',
    "networkId" => 'network-id-from-supported-networks',
    "shouldSaveAddress" => true,
];

$withdrawResult = $manager->withdraw($withdrawParams);
echo $withdrawResult;
```

> **NOTE:** Use the `getSupportedNetworks()` method to retrieve valid network IDs.

#### Redeem Asset

```php
/**
 * @param array{amount: int, bankCode: string, accountNumber: string, saveDetails?: bool} $data
 */
$redeemParams = [
    "amount" => 1000,
    "bankCode" => '011',
    "accountNumber" => '1234567890',
    "saveDetails" => true,
];

$redeemResult = $manager->redeemAssets($redeemParams);
echo $redeemResult;
```

> **NOTE:** Use the `getBanks()` method to fetch the list of banks and their codes.

#### Bridge Assets

```php
/**
 * @param array{destinationNetworkId: string, destinationAddress: string, originNetworkId: string, senderAddress?: string, callbackUrl?: string} $data
 */
$bridgeData = [
    "destinationNetworkId" => 'destination-network-id',
    "destinationAddress" => "0x123....",
    "originNetworkId" => 'origin-network-id',
    "callbackUrl" => 'https://your-callback-url.com',
];

$bridgeResult = $manager->bridgeAssets($bridgeData);
echo $bridgeResult;
```

> **NOTE:** Use the `getSupportedNetworks()` method to retrieve valid network IDs.

#### Update Bank Account

```php
/**
 * @param array{bankName: string, bankAccountName: string, bankAccountNumber: string} $data
 */
$updateData = [
    "bankName" => 'Example Bank',
    "bankAccountName" => 'Test Account',
    "bankAccountNumber" => '1234567890',
];

$updateResult = $manager->updateBankAccount($updateData);
echo $updateResult;
```

#### Whitelist Address

```php
/**
 * @param array{address: string, networkId: string} $data
 */
$whitelistData = [
    "address" => '0x1234...',
    "networkId" => 'network-id-from-supported-networks',
];

$result = $manager->whitelistAddress($whitelistData);
echo $result;
```

#### Get Whitelisted Addresses

```php
$addresses = $manager->getWhitelistedAddresses();
echo $addresses;

// Include network details
$addresses = $manager->getWhitelistedAddresses(includeNetwork: true);
echo $addresses;
```

#### Get Supported Networks

```php
$networks = $manager->getSupportedNetworks();
echo $networks;

// Include blockchain details
$networks = $manager->getSupportedNetworks(includeBlockchain: true);
echo $networks;
```

#### Validate Account

```php
/**
 * @param array{bankCode: string, accountNumber: string} $data
 */
$accountData = [
    "bankCode" => '011',
    "accountNumber" => '1234567890',
];

$validation = $manager->validateAccount($accountData);
echo $validation;
```

#### Verify Withdrawal

```php
$verification = $manager->verifyWithdraw('your-transaction-reference');
echo $verification;
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
