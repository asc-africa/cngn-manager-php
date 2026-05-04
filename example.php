<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use WrappedCBDC\CNGnManager;

// ─── Configuration ──────────────────────────────────────────────────────────
// Replace these with your actual credentials
$apiKey        = 'cngn_live_sk**********';
$encryptionKey = 'your-encryption-key';
$privateKey    = file_get_contents('/path/to/your/private-key.pem');
// Or inline:
// $privateKey = "-----BEGIN OPENSSH PRIVATE KEY-----\n...\n-----END OPENSSH PRIVATE KEY-----";

$manager = new CNGnManager($apiKey, $privateKey, $encryptionKey);

/**
 * Helper to print endpoint results nicely.
 */
function printResult(string $label, string $json): void
{
    echo "\n━━━ $label ━━━\n";
    $decoded = json_decode($json, true);
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

// ─── GET Endpoints ──────────────────────────────────────────────────────────

// 1. Get Balance
printResult('Get Balance', $manager->getBalance());

// 2. Get Transaction History (page 1, 10 per page)
printResult('Transaction History', $manager->getTransactionHistory(1, 10));

// 3. Get Banks
printResult('Get Banks', $manager->getBanks());

// 4. Get Virtual Account
printResult('Get Virtual Account', $manager->getVirtualAccount());

// 5. Get Supported Networks
printResult('Get Supported Networks', $manager->getSupportedNetworks());

// 5b. Get Supported Networks (with blockchain details)
printResult('Get Supported Networks (with blockchain)', $manager->getSupportedNetworks(includeBlockchain: true));

// 6. Get Whitelisted Addresses
printResult('Get Whitelisted Addresses', $manager->getWhitelistedAddresses());

// 6b. Get Whitelisted Addresses (with network details)
printResult('Get Whitelisted Addresses (with network)', $manager->getWhitelistedAddresses(includeNetwork: true));

// 7. Verify Withdrawal
printResult('Verify Withdrawal', $manager->verifyWithdraw('WTH-your-transaction-ref'));

// ─── POST / PUT Endpoints ───────────────────────────────────────────────────

// 8. Validate Account
printResult('Validate Account', $manager->validateAccount([
    'bankCode'      => '044',
    'accountNumber' => '0123456789',
]));

// 9. Redeem Assets (cNGN to Naira)
printResult('Redeem Assets', $manager->redeemAssets([
    'amount'        => 1000,
    'bankCode'      => '044',
    'accountNumber' => '0123456789',
    'saveDetails'   => false,
]));

// 10. Withdraw (cNGN to external wallet)
// Use a networkId from getSupportedNetworks()
printResult('Withdraw', $manager->withdraw([
    'amount'           => 100,
    'address'          => '0xYourWalletAddress',
    'networkId'        => 'your-network-id',
    'shouldSaveAddress' => false,
]));

// 11. Bridge Assets (swap between chains)
// Use networkIds from getSupportedNetworks()
printResult('Bridge Assets', $manager->bridgeAssets([
    'destinationNetworkId' => 'destination-network-id',
    'destinationAddress'   => '0xDestinationAddress',
    'originNetworkId'      => 'origin-network-id',
    'callbackUrl'          => 'https://your-domain.com/webhook',
]));

// 12. Whitelist Address
// Use a networkId from getSupportedNetworks()
printResult('Whitelist Address', $manager->whitelistAddress([
    'address'   => '0xAddressToWhitelist',
    'networkId' => 'your-network-id',
]));

// 13. Update Bank Account
printResult('Update Bank Account', $manager->updateBankAccount([
    'bankName'          => 'Access Bank',
    'bankAccountName'   => 'John Doe Enterprises',
    'bankAccountNumber' => '0123456789',
]));
