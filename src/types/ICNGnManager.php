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
     * @param array{walletAddress?: array{bscAddress?: string, atcAddress?: string, xbnAddress?: string, ethAddress?: string, polygonAddress?: string, tronAddress?: string, baseAddress?: string, bantuUserId?: string}, bankDetails?: array{bankName: string, bankAccountName: string, bankAccountNumber: string}} $data
     */
    public function updateExternalAccounts(array $data): string;

    /**
     * @param array{destinationNetworkId: string, destinationAddress: string, originNetworkId: string, callbackUrl: string} $data
     */
    public function swapAssets(array $data): string;

    /**
     * @param array{amount: int, originNetworkId: string, destinationNetworkId: string, destinationAddress: string} $data
     */
    public function swapQuote(array $data): string;

    /**
     * @param string $tnxRef
     */
    public function verifyWithdraw(string $tnxRef): string;

    public function getWhitelistedAddresses(): string;

    public function getSupportedNetworks(): string;

    /**
     * @param array{bankCode: string, accountNumber: string} $data
     */
    public function validateAccount(array $data): string;

    /**
     * @param array{address: string, network: string} $data
     */
    public function whitelistAddress(array $data): string;
};


