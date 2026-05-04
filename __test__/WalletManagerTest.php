<?php
declare(strict_types=1);

namespace Tests;

require __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use WrappedCBDC\WalletManager;
use WrappedCBDC\utils\CryptoWallet;

final class WalletManagerTest extends TestCase
{
    private WalletManager $wallet;

    /** Well-known test mnemonic (BIP-39 test vector #0). */
    private const TEST_MNEMONIC = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';

    protected function setUp(): void
    {
        $this->wallet = new WalletManager();
    }

    // ─── Wallet Structure ──────────────────────────────────────────

    #[Test]
    public function generateWallet_returns_required_keys(): void
    {
        $result = $this->wallet->generateWallet('eth');

        $this->assertArrayHasKey('mnemonic', $result);
        $this->assertArrayHasKey('privateKey', $result);
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayHasKey('network', $result);
    }

    #[Test]
    public function generateWallet_mnemonic_is_12_words(): void
    {
        $result = $this->wallet->generateWallet('bsc');

        $words = explode(' ', $result['mnemonic']);
        $this->assertCount(12, $words);
    }

    // ─── Deterministic Derivation ──────────────────────────────────

    #[Test]
    public function generateWalletFromMnemonic_is_deterministic(): void
    {
        $first  = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');
        $second = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');

        $this->assertSame($first['privateKey'], $second['privateKey']);
        $this->assertSame($first['address'], $second['address']);
    }

    // ─── ETH / EVM Chains ──────────────────────────────────────────

    #[Test]
    public function eth_wallet_address_starts_with_0x(): void
    {
        $result = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');

        $this->assertStringStartsWith('0x', $result['address']);
        $this->assertEquals(42, strlen($result['address'])); // 0x + 40 hex chars
        $this->assertEquals('ETH', $result['network']);
    }

    #[Test]
    public function eth_private_key_is_64_hex_chars(): void
    {
        $result = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');

        $this->assertEquals(64, strlen($result['privateKey']));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['privateKey']);
    }

    #[Test]
    public function bsc_and_eth_share_same_derivation_path(): void
    {
        $eth = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');
        $bsc = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'bsc');

        $this->assertSame($eth['privateKey'], $bsc['privateKey']);
        $this->assertSame($eth['address'], $bsc['address']);
    }

    #[Test]
    public function matic_and_eth_share_same_derivation_path(): void
    {
        $eth   = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');
        $matic = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'matic');

        $this->assertSame($eth['address'], $matic['address']);
    }

    #[Test]
    public function base_and_eth_share_same_derivation_path(): void
    {
        $eth  = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');
        $base = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'base');

        $this->assertSame($eth['address'], $base['address']);
    }

    // ─── TRON ──────────────────────────────────────────────────────

    #[Test]
    public function trx_wallet_address_starts_with_T(): void
    {
        $result = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'trx');

        $this->assertStringStartsWith('T', $result['address']);
        $this->assertEquals(34, strlen($result['address']));
        $this->assertEquals('TRX', $result['network']);
    }

    #[Test]
    public function trx_has_different_key_than_eth(): void
    {
        $eth = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');
        $trx = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'trx');

        $this->assertNotSame($eth['privateKey'], $trx['privateKey']);
    }

    // ─── XBN / Stellar ─────────────────────────────────────────────

    #[Test]
    public function xbn_wallet_address_starts_with_G(): void
    {
        $result = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'xbn');

        $this->assertStringStartsWith('G', $result['address']);
        $this->assertEquals(56, strlen($result['address']));
        $this->assertEquals('XBN', $result['network']);
    }

    #[Test]
    public function xbn_private_key_starts_with_S(): void
    {
        $result = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'xbn');

        $this->assertStringStartsWith('S', $result['privateKey']);
    }

    // ─── Case Insensitivity ────────────────────────────────────────

    #[Test]
    public function network_parameter_is_case_insensitive(): void
    {
        $lower = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');
        $upper = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'ETH');

        $this->assertSame($lower['address'], $upper['address']);
    }

    // ─── Address Validation ────────────────────────────────────────

    #[Test]
    public function validateAddress_accepts_valid_eth_address(): void
    {
        $wallet = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'eth');

        $this->assertTrue(CryptoWallet::validateAddress($wallet['address'], 'eth'));
    }

    #[Test]
    public function validateAddress_rejects_invalid_eth_address(): void
    {
        $this->assertFalse(CryptoWallet::validateAddress('not-an-address', 'eth'));
        $this->assertFalse(CryptoWallet::validateAddress('0x', 'eth'));
        $this->assertFalse(CryptoWallet::validateAddress('0xZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ', 'eth'));
    }

    #[Test]
    public function validateAddress_accepts_valid_trx_address(): void
    {
        $wallet = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'trx');

        $this->assertTrue(CryptoWallet::validateAddress($wallet['address'], 'trx'));
    }

    #[Test]
    public function validateAddress_rejects_invalid_trx_address(): void
    {
        $this->assertFalse(CryptoWallet::validateAddress('not-a-tron-address', 'trx'));
    }

    #[Test]
    public function validateAddress_accepts_valid_xbn_address(): void
    {
        $wallet = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'xbn');

        $this->assertTrue(CryptoWallet::validateAddress($wallet['address'], 'xbn'));
    }

    #[Test]
    public function validateAddress_rejects_invalid_xbn_address(): void
    {
        $this->assertFalse(CryptoWallet::validateAddress('GINVALID', 'xbn'));
    }

    #[Test]
    public function validateAddress_works_for_base_network(): void
    {
        $wallet = $this->wallet->generateWalletFromMnemonic(self::TEST_MNEMONIC, 'base');

        $this->assertTrue(CryptoWallet::validateAddress($wallet['address'], 'base'));
    }

    // ─── Unsupported Network ───────────────────────────────────────

    #[Test]
    public function generateWallet_throws_for_unsupported_network(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported network');

        $this->wallet->generateWallet('SOLANA');
    }

    // ─── Fresh Wallet Uniqueness ───────────────────────────────────

    #[Test]
    public function generateWallet_produces_unique_wallets(): void
    {
        $first  = $this->wallet->generateWallet('eth');
        $second = $this->wallet->generateWallet('eth');

        $this->assertNotSame($first['mnemonic'], $second['mnemonic']);
        $this->assertNotSame($first['privateKey'], $second['privateKey']);
        $this->assertNotSame($first['address'], $second['address']);
    }
}
