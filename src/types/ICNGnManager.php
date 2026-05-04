<?php
declare(strict_types=1);
namespace WrappedCBDC\types;

interface ICNGnManager {
    public function getBalance(): string;

    public function getTransactionHistory(int $page, int $limit): string;

    /**
     * @param array{amount: int, address: string, networkId: string, shouldSaveAddress?: bool} $data
     */
    public function withdraw(array $data): string;

    public function getBanks(): string;

    /**
     * @param array{amount: int, bankCode: string, accountNumber: string, saveDetails?: bool} $data
     */
    public function redeemAssets(array $data): string;

    public function getVirtualAccount(): string;

    /**
     * @param array{bankName: string, bankAccountName: string, bankAccountNumber: string} $data
     */
    public function updateBankAccount(array $data): string;

    /**
     * @param array{destinationNetworkId: string, destinationAddress: string, originNetworkId: string, senderAddress?: string, callbackUrl?: string} $data
     */
    public function bridgeAssets(array $data): string;

    /**
     * @param string $tnxRef
     */
    public function verifyWithdraw(string $tnxRef): string;

    public function getWhitelistedAddresses(bool $includeNetwork = false): string;

    public function getSupportedNetworks(bool $includeBlockchain = false): string;

    /**
     * @param array{bankCode: string, accountNumber: string} $data
     */
    public function validateAccount(array $data): string;

    /**
     * @param array{address: string, networkId: string} $data
     */
    public function whitelistAddress(array $data): string;
};


