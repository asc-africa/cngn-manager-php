<?php
declare(strict_types=1);
namespace WrappedCBDC;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . "/utils/AESCrypto.php";
require __DIR__ . "/utils/Ed25519Crypto.php";

use WrappedCBDC\config\Constants;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use WrappedCBDC\utils\AESCrypto;
use GuzzleHttp\Exception\{
    ClientException,
    RequestException
};
use WrappedCBDC\types\ICNGnManager;
use WrappedCBDC\utils\Ed25519Crypto;


class CNGnManager implements ICNGnManager {
    protected const API_URL = "https://api.cngn.co";
    protected const API_CURRENT_VERSION = "v1";
    protected $client;
    public function __construct(private string $apiKey, private string $privateKey, private string $encryptionKey ){

       $this->client = new Client([
            'base_uri' => self::API_URL,
            'headers' => [
                'Authorization' => "Bearer $this->apiKey",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
       ]);


    }

    private function handleApiError($error){
        // handle error
    }

    private function __makeCalls(string $method, string $endpoint, array $data=[]): string{
        // make calls
        $AESCrypto = new AESCrypto();
        $Ed25519Crypto = new Ed25519Crypto();

        try {

            if(!empty($data)){
                $newdata = json_encode($data);
                $encrypt = $AESCrypto->encrypt($newdata, $this->encryptionKey);
                $data = $encrypt;
                $request = $this->client->request($method, $endpoint, [
                    'json' => $data
                ]);

            }else{
                $request = $this->client->request($method, $endpoint);
            }

            $response = json_decode($request->getBody()->getContents(), true);
            $decryptedResponse = $Ed25519Crypto->decryptWithPrivateKey($this->privateKey, $response["data"]);

            $response["data"] = json_decode($decryptedResponse, true);
            return json_encode($response);

        }catch (ClientException | RequestException $e) {
            // Handle and log error, return a JSON response with error details
            $errorBody = [
                'success' => false,
                'error' => 'API request failed',
                'message' => $e->getMessage(),
                'status_code' => $e->getCode(),
            ];

            if ($e->hasResponse()) {
                $resp = json_decode($e->getResponse()->getBody()->getContents(), true);
                $message = json_decode($resp['message'], true);
                
                $resp["message"] = $message;
                return json_encode($resp);
            }

            // Return error details as JSON without throwing the error
            return json_encode($errorBody);
        } catch (\Exception $e) {
            // Catch any other exceptions and return a JSON error
            return json_encode([
                'success' => false,
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getBalance(): string{
        return $this->__makeCalls("GET", "/".self::API_CURRENT_VERSION."/api/balance");
    }

    public function getTransactionHistory(int $page = 1, int $limit = 10): string{
        return $this->__makeCalls("GET", "/".self::API_CURRENT_VERSION."/api/transactions?page=$page&limit=$limit");
    }

    /**
     * @param array{provider: string, bank_code: string} $data
     */
    public function getVirtualAccount(): string{
        return $this->__makeCalls("GET", "/".self::API_CURRENT_VERSION."/api/virtual-account");
    }

    /**
     * @param array{amount: int, bankCode: string, accountNumber: string, saveDetails?: bool} $data
     */
    public function redeemAssets(array $data): string {
        return $this->__makeCalls("POST", "/".self::API_CURRENT_VERSION."/api/redeemAsset", $data);
    }

    public function verifyWithdraw(string $tnxRef): string{
        return $this->__makeCalls("GET", "/".self::API_CURRENT_VERSION."/api/withdraw/verify/$tnxRef");
    }

    /**
     * @param array{amount: int, address: string, networkId: string, shouldSaveAddress?: bool} $data
     */
    public function withdraw(array $data): string{
        return $this->__makeCalls("POST", "/".self::API_CURRENT_VERSION."/api/withdraw", $data);
    }

    public function getBanks(): string{
        return $this->__makeCalls("GET", "/".self::API_CURRENT_VERSION."/api/banks");
    }

    /**
     * @param array{destinationNetworkId: string, destinationAddress: string, originNetworkId: string, senderAddress?: string, callbackUrl?: string} $data
     */
    public function bridgeAssets(array $data): string{
        return $this->__makeCalls("POST", "/".self::API_CURRENT_VERSION."/api/bridge", $data);
    }

    /**
     * @param array{bankName: string, bankAccountName: string, bankAccountNumber: string} $data
     */
    public function updateBankAccount(array $data): string{
        return $this->__makeCalls("PUT", "/".self::API_CURRENT_VERSION."/api/bank-account", $data);
    }

    /**
     * @param array{address: string, networkId: string} $data
     */
    public function whitelistAddress(array $data): string{
        return $this->__makeCalls("POST", "/".self::API_CURRENT_VERSION."/api/whitelist", $data);
    }

    public function getWhitelistedAddresses(bool $includeNetwork = false): string{
        $query = $includeNetwork ? '?includeNetwork=true' : '';
        return $this->__makeCalls("GET", "/".self::API_CURRENT_VERSION."/api/whitelisted".$query);
    }

    public function getSupportedNetworks(bool $includeBlockchain = false): string{
        $query = $includeBlockchain ? '?includeBlockchain=true' : '';
        return $this->__makeCalls("GET", "/".self::API_CURRENT_VERSION."/api/networks".$query);
    }

    /**
     * @param array{bankCode: string, accountNumber: string} $data
     */
    public function validateAccount(array $data): string{
        return $this->__makeCalls("POST", "/".self::API_CURRENT_VERSION."/api/account/verify", $data);
    }
}

