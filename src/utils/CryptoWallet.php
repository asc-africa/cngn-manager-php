<?php

namespace WrappedCBDC\utils;

use Exception;
use kornrunner\Keccak;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\Point\UncompressedPointSerializer;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

class CryptoWallet
{
    private const DERIVATION_PATHS = [
        'ETH'   => "m/44'/60'/0'/0/0",
        'BSC'   => "m/44'/60'/0'/0/0",
        'ATC'   => "m/44'/60'/0'/0/0",
        'MATIC' => "m/44'/60'/0'/0/0",
        'BASE'  => "m/44'/60'/0'/0/0",
        'TRX'   => "m/44'/195'/0'/0/0",
        'XBN'   => "m/44'/148'/0'",
    ];

    private const TRON_ADDRESS_PREFIX = '41';

    /**
     * Generate a new HD wallet with a fresh mnemonic for the given network.
     */
    public static function generateWalletWithMnemonicDetails(string $network): array
    {
        $network = strtoupper($network);
        self::assertSupportedNetwork($network);

        $mnemonic = Mnemonic::generate12WordsMnemonic();
        $words = implode(' ', $mnemonic->words);

        return self::generateWalletFromMnemonic($words, $network);
    }

    /**
     * Restore / derive a wallet from an existing mnemonic phrase.
     */
    public static function generateWalletFromMnemonic(string $mnemonicPhrase, string $network): array
    {
        $network = strtoupper($network);
        self::assertSupportedNetwork($network);

        if ($network === 'XBN') {
            return self::generateXbnWallet($mnemonicPhrase);
        }

        $privateKey = self::derivePrivateKey($mnemonicPhrase, $network);
        $publicKey  = self::getPublicKeyUncompressed($privateKey);
        $address    = self::deriveAddress($publicKey, $network);

        return [
            'mnemonic'   => $mnemonicPhrase,
            'privateKey' => $privateKey,
            'address'    => $address,
            'network'    => $network,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  BIP-32 HD key derivation for secp256k1 chains
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Derive a private key from a mnemonic using BIP-32/BIP-44 derivation.
     */
    private static function derivePrivateKey(string $mnemonicPhrase, string $network): string
    {
        // BIP-39 seed (64 bytes)
        $seed = hash_pbkdf2('sha512', $mnemonicPhrase, 'mnemonic', 2048, 64, true);

        // BIP-32 master key
        $hmac       = hash_hmac('sha512', $seed, 'Bitcoin seed', true);
        $masterKey  = substr($hmac, 0, 32);
        $chainCode  = substr($hmac, 32, 32);

        $path = self::DERIVATION_PATHS[$network];
        $segments = self::parsePath($path);

        $key   = $masterKey;
        $chain = $chainCode;

        $generator = EccFactory::getSecgCurves()->generator256k1();
        $order     = $generator->getOrder();

        foreach ($segments as [$index, $hardened]) {
            if ($hardened) {
                // Hardened child: HMAC-SHA512(chainCode, 0x00 || key || index)
                $data = "\x00" . $key . pack('N', $index + 0x80000000);
            } else {
                // Normal child: HMAC-SHA512(chainCode, serP(point(key)) || index)
                $point = $generator->mul(gmp_init(bin2hex($key), 16));
                $serializer = new UncompressedPointSerializer();
                $pubHex = $serializer->serialize($point);
                // Compress the public key (02/03 prefix + 32-byte x)
                $compressed = self::compressPublicKey($pubHex);
                $data = hex2bin($compressed) . pack('N', $index);
            }

            $hmac  = hash_hmac('sha512', $data, $chain, true);
            $il    = gmp_init(bin2hex(substr($hmac, 0, 32)), 16);
            $chain = substr($hmac, 32, 32);

            $parentInt = gmp_init(bin2hex($key), 16);
            $childInt  = gmp_mod(gmp_add($il, $parentInt), $order);

            $key = hex2bin(str_pad(gmp_strval($childInt, 16), 64, '0', STR_PAD_LEFT));
        }

        return bin2hex($key);
    }

    /**
     * Parse a BIP-44 derivation path string into segments.
     * Returns array of [index, hardened] pairs.
     */
    private static function parsePath(string $path): array
    {
        $parts = explode('/', $path);
        array_shift($parts); // remove 'm'

        $segments = [];
        foreach ($parts as $part) {
            $hardened = str_ends_with($part, "'");
            $index = (int) rtrim($part, "'");
            $segments[] = [$index, $hardened];
        }
        return $segments;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Public key & address derivation
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get uncompressed public key hex (with 04 prefix) from a private key hex.
     */
    private static function getPublicKeyUncompressed(string $privateKeyHex): string
    {
        $generator  = EccFactory::getSecgCurves()->generator256k1();
        $point      = $generator->mul(gmp_init($privateKeyHex, 16));
        $serializer = new UncompressedPointSerializer();
        return $serializer->serialize($point);
    }

    /**
     * Compress an uncompressed public key (04 || x || y) to (02/03 || x).
     */
    private static function compressPublicKey(string $uncompressedHex): string
    {
        // Remove 04 prefix
        $xy = substr($uncompressedHex, 2);
        $x  = substr($xy, 0, 64);
        $y  = substr($xy, 64, 64);

        $prefix = (gmp_intval(gmp_mod(gmp_init($y, 16), gmp_init(2))) === 0) ? '02' : '03';
        return $prefix . $x;
    }

    /**
     * Derive the blockchain address from an uncompressed public key.
     */
    private static function deriveAddress(string $publicKeyHex, string $network): string
    {
        if (in_array($network, ['ETH', 'BSC', 'MATIC', 'ATC', 'BASE'], true)) {
            return self::getEthereumStyleAddress($publicKeyHex);
        }

        if ($network === 'TRX') {
            return self::getTronAddress($publicKeyHex);
        }

        throw new Exception("Unsupported network for address derivation: {$network}");
    }

    /**
     * EIP-55 checksummed Ethereum-style address from uncompressed public key.
     */
    private static function getEthereumStyleAddress(string $publicKeyHex): string
    {
        // Strip the 04 prefix
        $clean = (substr($publicKeyHex, 0, 2) === '04') ? substr($publicKeyHex, 2) : $publicKeyHex;
        $hash  = Keccak::hash(hex2bin($clean), 256);
        return '0x' . substr($hash, -40);
    }

    /**
     * TRON Base58Check address from uncompressed public key.
     */
    private static function getTronAddress(string $publicKeyHex): string
    {
        $clean = (substr($publicKeyHex, 0, 2) === '04') ? substr($publicKeyHex, 2) : $publicKeyHex;
        $hash  = Keccak::hash(hex2bin($clean), 256);

        // Take last 20 bytes and prepend TRON prefix
        $addressHex = self::TRON_ADDRESS_PREFIX . substr($hash, -40);
        $addressBin = hex2bin($addressHex);

        // Double SHA-256 checksum
        $checksum = substr(hash('sha256', hash('sha256', $addressBin, true), true), 0, 4);

        return self::base58Encode($addressBin . $checksum);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  XBN / Stellar wallet (Ed25519, using Stellar SDK)
    // ──────────────────────────────────────────────────────────────────────────

    private static function generateXbnWallet(string $mnemonicPhrase): array
    {
        $mnemonic = Mnemonic::mnemonicFromWords($mnemonicPhrase);
        $keyPair  = KeyPair::fromMnemonic($mnemonic, 0);

        return [
            'mnemonic'   => $mnemonicPhrase,
            'privateKey' => $keyPair->getSecretSeed(),
            'address'    => $keyPair->getAccountId(),
            'network'    => 'XBN',
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Address validation
    // ──────────────────────────────────────────────────────────────────────────

    public static function validateAddress(string $address, string $network): bool
    {
        $network = strtoupper($network);

        switch ($network) {
            case 'ETH':
            case 'BSC':
            case 'MATIC':
            case 'ATC':
            case 'BASE':
                return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;

            case 'TRX':
                if (strlen($address) !== 34) return false;
                try {
                    $decoded  = self::base58Decode($address);
                    if (strlen($decoded) !== 25) return false;
                    if (substr($decoded, 0, 1) !== hex2bin(self::TRON_ADDRESS_PREFIX)) return false;
                    $body     = substr($decoded, 0, -4);
                    $checksum = substr($decoded, -4);
                    return substr(hash('sha256', hash('sha256', $body, true), true), 0, 4) === $checksum;
                } catch (Exception $e) {
                    return false;
                }

            case 'XBN':
                try {
                    KeyPair::fromAccountId($address);
                    return true;
                } catch (\Throwable $e) {
                    return false;
                }

            default:
                throw new Exception("Unsupported network: {$network}");
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Base58 encode / decode
    // ──────────────────────────────────────────────────────────────────────────

    private static function base58Encode(string $data): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

        $decimal = gmp_init(bin2hex($data), 16);
        $result  = '';

        while (gmp_cmp($decimal, 0) > 0) {
            [$decimal, $mod] = gmp_div_qr($decimal, 58);
            $result = $alphabet[gmp_intval($mod)] . $result;
        }

        // Preserve leading zero bytes
        for ($i = 0; $i < strlen($data) && $data[$i] === "\x00"; $i++) {
            $result = $alphabet[0] . $result;
        }

        return $result;
    }

    private static function base58Decode(string $data): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

        $decimal = gmp_init(0);
        foreach (str_split($data) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                throw new Exception('Invalid Base58 character');
            }
            $decimal = gmp_add(gmp_mul($decimal, 58), $pos);
        }

        $hex = gmp_strval($decimal, 16);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }
        $result = hex2bin($hex);

        // Restore leading zero bytes
        for ($i = 0; $i < strlen($data) && $data[$i] === $alphabet[0]; $i++) {
            $result = "\x00" . $result;
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private static function assertSupportedNetwork(string $network): void
    {
        if (!isset(self::DERIVATION_PATHS[$network])) {
            throw new Exception("Unsupported network: {$network}. Supported: " . implode(', ', array_keys(self::DERIVATION_PATHS)));
        }
    }
}
