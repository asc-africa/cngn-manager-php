# CNGnManager

CNGnManager is a PHP library for interacting with a CNGN API. It provides a simple interface for various operations such as checking balance, swapping between chains, depositing for redemption, creating virtual accounts, and more.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Available Methods](#available-methods)
- [Testing](#testing)
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

First, import the `CNGnManager` class using it namespace WrappedCBDC\CNGNManager: and all necessary constants.

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

#NOTE: You can as well get your private key from a file using
$sshPrivateKey = file_get_contents("/path/to/sshkey.key");

$manager = new CNGnManager($apiKey, $sshPrivateKey, $encryptionKey);

// Example: Get balance
$balance = $manager->getBalance();
echo $balance;
```
## Networks

The library supports multiple blockchain networks:

- `Network.BSC` - Binance Smart Chain
- `Network.ATC` - Asset Chain
- `Network.XBN` - Bantu Chain
- `Network.ETTH` - Ethereum
- `Network.MATIC` - Polygon (Matic)
- `Network.TRX` - Tron
- `Network.BASE` - Base


## Available Methods

### cNGNManager Methods

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
echo $transaction;
```

#### Withdraw from chains

```php
$swapParams = [
    "amount"=> 100,
    "address" => '0x1234...',
    "network" => Network::BSC,
    "shouldSaveAddress" => true
];

$swapResult =  $manager->withdraw($swapParams);
echo $swapResult;
```

#### Redeem Asset

```php
$depositParams = [
    "amount"=> 1000,
    "bankCode"=> '011',
    "accountNumber"=> '1234567890'
    "saveDetails" => true
];

$depositResult = $manager->redeemAssets($depositParams);
echo $depositResult;
```
NOTE: to get bank codes please use the getBanks method to fetch the list of banks and ther codes 

#### Create Virtual Account

```php
$mintParams = [
    "provider"=> ProviderType::KORAPAY,
    "bank_code" => '011'
];

$virtualAccount = $manager->createVirtualAccount($mintParams);
echo $virtualAccount;
```
NOTE: before creating the virtual account you need to have updated your BVN on the dashboard


#### Bridge Assets 

```php
$swapData = [
    "destinationNetwork"=> Network::BSC,
    "destinationAddress" => "0x123....",
    "originNetwork" => Network::ETH
    "callbackUrl" => 'https://your-callback-url.com'
];

$swapResult = $manager->swapAsset($swapData);
echo $swapResult;
```
NOTE: before creating the virtual account you need to have updated your BVN on the dashboard



#### Update Business

Address Options:
- "xbnAddress": "string";
- "bscAddress": "string";
- "atcAddress": "string";
- "polygonAddress": "string";
- "ethAddress": "string";
- "tronAddress": "string";
- "baseAddress": "string";
- "bantuUserId": "string";

```php
$updateData: [
    "walletAddress" => [
        "bscAddress" => '0x1234...',
    ],
    "bankDetails" => [
        "bankName" => 'Example Bank',
        "bankAccountName" => 'Test Account',
        "bankAccountNumber" => '1234567890'
    ]
];

$updateResult = $manager->updateExternalAccounts($updateData);
echo $updateResult;
```

#### Get banks
```php

$banklist = $manager->getBanks();
print($banklist)

```

### WalletManager Methods

#### Generate Wallet Address
Not Available a the moment 
<!-- ```python
    wallet = WalletManager.generate_wallet_address(Network.bsc);
```

Response format:
```php
 {
    "mnemonic" : "string";
    "address": "string";
    "network": Network;
    "privateKey": "string";
}
``` -->


## Testing

This project uses Jest for testing. To run the tests, follow these steps:

1. Run the test command:

   ```bash
   composer run test
   ```

   This will run all tests in the `__tests__` directory.

### Test Structure

The tests are located in the `__tests__` directory. They cover various aspects of the CNGnManager class, including:

- API calls for different endpoints (GET and POST requests)
- Encryption and decryption of data
- Error handling for various scenarios

## Return Values

All responses are returned as a Json string you have to decode it to an object with; `$data = json_decode($response)` or to an array with; `$data = json_decode($response, true)` .

## Error Handling

The library uses a custom error handling mechanism. All API errors are caught and thrown as `Error` objects with descriptive messages.

## Types

The library includes python constant classes for all parameters:

- `Network` - token network
- `AssetType` - Asset constants
- `ProviderType` - provider constants

## Security

This library uses AES encryption for request payloads and Ed25519 decryption for response data. Ensure that your `encryptionKey` and `privateKey` are kept secure.

## Contributing

Contributions, issues, and feature requests are welcome. Feel free to check [issues page](https://github.com/wrappedcbdc/cngn-php-library/issues) if you want to contribute.

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
