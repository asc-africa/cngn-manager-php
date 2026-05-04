<?php
declare(strict_types=1);

namespace WrappedCBDC\types;

interface IWalletManager
{
    /**
     * Generate a new HD wallet for the given network.
     * Returns: ['mnemonic' => string, 'privateKey' => string, 'address' => string, 'network' => string]
     */
    public function generateWallet(string $network): array;

    /**
     * Restore a wallet from an existing mnemonic phrase.
     * Returns: ['mnemonic' => string, 'privateKey' => string, 'address' => string, 'network' => string]
     */
    public function generateWalletFromMnemonic(string $mnemonic, string $network): array;
}
