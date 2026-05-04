<?php
declare(strict_types=1);

namespace WrappedCBDC;

use WrappedCBDC\types\IWalletManager;
use WrappedCBDC\utils\CryptoWallet;

class WalletManager implements IWalletManager
{
    /**
     * Generate a new HD wallet (mnemonic + private key + address) for the given network.
     */
    public function generateWallet(string $network): array
    {
        return CryptoWallet::generateWalletWithMnemonicDetails($network);
    }

    /**
     * Restore / derive a wallet from an existing mnemonic phrase.
     */
    public function generateWalletFromMnemonic(string $mnemonic, string $network): array
    {
        return CryptoWallet::generateWalletFromMnemonic($mnemonic, $network);
    }
}
