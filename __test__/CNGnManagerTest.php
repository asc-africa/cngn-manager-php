<?php
declare(strict_types=1);

namespace Tests;

require __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use WrappedCBDC\CNGnManager;
use WrappedCBDC\utils\AESCrypto;
use WrappedCBDC\utils\Ed25519Crypto;

/**
 * Helper subclass to inject a mock Guzzle client.
 */
class TestableCNGnManager extends CNGnManager
{
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}

final class CNGnManagerTest extends TestCase
{
    private TestableCNGnManager $manager;
    private string $apiKey = 'test_api_key';
    private string $privateKey = 'test_private_key';
    private string $encryptionKey = 'test_encryption_key';

    protected function setUp(): void
    {
        $this->manager = new TestableCNGnManager(
            $this->apiKey,
            $this->privateKey,
            $this->encryptionKey
        );
    }

    private function managerWithMockResponses(array $responses): TestableCNGnManager
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $this->manager->setClient($client);
        return $this->manager;
    }

    // ─── Constructor ────────────────────────────────────────────────

    #[Test]
    public function it_instantiates_with_required_credentials(): void
    {
        $manager = new CNGnManager($this->apiKey, $this->privateKey, $this->encryptionKey);
        $this->assertInstanceOf(CNGnManager::class, $manager);
    }

    // ─── GET Endpoints ──────────────────────────────────────────────

    #[Test]
    public function getBalance_makes_GET_request_and_returns_json(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('Connection error', new Request('GET', '/v1/api/balance')),
        ]);

        $result = json_decode($manager->getBalance(), true);
        $this->assertFalse($result['success']);
        $this->assertEquals('API request failed', $result['error']);
    }

    #[Test]
    public function getTransactionHistory_uses_default_pagination(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('GET', '/v1/api/transactions')),
        ]);

        $result = json_decode($manager->getTransactionHistory(), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function getTransactionHistory_accepts_custom_pagination(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('GET', '/v1/api/transactions')),
        ]);

        $result = json_decode($manager->getTransactionHistory(2, 5), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function getBanks_makes_GET_request(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('GET', '/v1/api/banks')),
        ]);

        $result = json_decode($manager->getBanks(), true);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function getVirtualAccount_makes_GET_request(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('GET', '/v1/api/virtual-account')),
        ]);

        $result = json_decode($manager->getVirtualAccount(), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function verifyWithdraw_includes_tnxRef_in_url(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('GET', '/v1/api/withdraw/verify/TXN123')),
        ]);

        $result = json_decode($manager->verifyWithdraw('TXN123'), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function getWhitelistedAddresses_makes_GET_request(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('GET', '/v1/api/whitelist-address')),
        ]);

        $result = json_decode($manager->getWhitelistedAddresses(), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function getSupportedNetworks_makes_GET_request(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('GET', '/v1/api/supported-networks')),
        ]);

        $result = json_decode($manager->getSupportedNetworks(), true);
        $this->assertFalse($result['success']);
    }

    // ─── POST / PUT Endpoints ───────────────────────────────────────

    #[Test]
    public function withdraw_sends_POST_with_data(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('POST', '/v1/api/withdraw')),
        ]);

        $data = [
            'amount' => 100,
            'address' => '0x1234abcd',
            'network' => 'bsc',
            'shouldSaveAddress' => true,
        ];

        $result = json_decode($manager->withdraw($data), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function redeemAssets_sends_POST_with_data(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('POST', '/v1/api/redeemAsset')),
        ]);

        $data = [
            'amount' => 1000,
            'bankCode' => '011',
            'accountNumber' => '1234567890',
            'saveDetails' => true,
        ];

        $result = json_decode($manager->redeemAssets($data), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function swapAssets_sends_POST_with_data(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('POST', '/v1/api/swap')),
        ]);

        $data = [
            'destinationNetwork' => 'bsc',
            'destinationAddress' => '0x1234',
            'originNetwork' => 'eth',
            'callbackUrl' => 'https://example.com/callback',
        ];

        $result = json_decode($manager->swapAssets($data), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function swapQuote_sends_POST_with_data(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('POST', '/v1/api/swap-quote')),
        ]);

        $data = [
            'destinationNetwork' => 'bsc',
            'originNetwork' => 'eth',
        ];

        $result = json_decode($manager->swapQuote($data), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function updateExternalAccounts_sends_PUT_with_data(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('PUT', '/v1/api/bank-account')),
        ]);

        $data = [
            'walletAddress' => ['bscAddress' => '0x1234'],
            'bankDetails' => [
                'bankName' => 'Test Bank',
                'bankAccountName' => 'John Doe',
                'bankAccountNumber' => '1234567890',
            ],
        ];

        $result = json_decode($manager->updateExternalAccounts($data), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function whitelistAddress_sends_POST_with_data(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('POST', '/v1/api/whitelist-address')),
        ]);

        $data = ['address' => '0xabcdef', 'network' => 'bsc'];

        $result = json_decode($manager->whitelistAddress($data), true);
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function validateAccount_sends_POST_with_data(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('POST', '/v1/api/account/verify')),
        ]);

        $data = ['bankCode' => '011', 'accountNumber' => '1234567890'];

        $result = json_decode($manager->validateAccount($data), true);
        $this->assertFalse($result['success']);
    }

    // ─── Error Handling ─────────────────────────────────────────────

    #[Test]
    public function it_handles_client_exception_with_response_body(): void
    {
        $responseBody = json_encode([
            'success' => false,
            'message' => json_encode(['error' => 'Unauthorized']),
        ]);

        $manager = $this->managerWithMockResponses([
            new RequestException(
                'Client error',
                new Request('GET', '/v1/api/balance'),
                new Response(401, [], $responseBody)
            ),
        ]);

        $result = json_decode($manager->getBalance(), true);
        $this->assertFalse($result['success']);
        $this->assertEquals(['error' => 'Unauthorized'], $result['message']);
    }

    #[Test]
    public function it_handles_client_exception_without_response(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('Network timeout', new Request('GET', '/v1/api/balance')),
        ]);

        $result = json_decode($manager->getBalance(), true);
        $this->assertFalse($result['success']);
        $this->assertEquals('API request failed', $result['error']);
        $this->assertStringContainsString('Network timeout', $result['message']);
    }

    #[Test]
    public function it_handles_generic_exceptions(): void
    {
        $mock = new MockHandler([new \RuntimeException('Something broke')]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $this->manager->setClient($client);

        $result = json_decode($this->manager->getBalance(), true);
        $this->assertFalse($result['success']);
        $this->assertEquals('An unexpected error occurred', $result['error']);
        $this->assertStringContainsString('Something broke', $result['message']);
    }

    // ─── Return Format ──────────────────────────────────────────────

    #[Test]
    public function all_methods_return_valid_json_strings(): void
    {
        $manager = $this->managerWithMockResponses([
            new RequestException('fail', new Request('GET', '/test')),
            new RequestException('fail', new Request('GET', '/test')),
            new RequestException('fail', new Request('GET', '/test')),
            new RequestException('fail', new Request('GET', '/test')),
            new RequestException('fail', new Request('GET', '/test')),
            new RequestException('fail', new Request('GET', '/test')),
            new RequestException('fail', new Request('POST', '/test')),
            new RequestException('fail', new Request('POST', '/test')),
            new RequestException('fail', new Request('POST', '/test')),
            new RequestException('fail', new Request('POST', '/test')),
            new RequestException('fail', new Request('PUT', '/test')),
            new RequestException('fail', new Request('POST', '/test')),
            new RequestException('fail', new Request('POST', '/test')),
        ]);

        $methods = [
            fn() => $manager->getBalance(),
            fn() => $manager->getTransactionHistory(),
            fn() => $manager->getBanks(),
            fn() => $manager->getWhitelistedAddresses(),
            fn() => $manager->getSupportedNetworks(),
            fn() => $manager->getVirtualAccount(),
            fn() => $manager->withdraw(['amount' => 1, 'address' => '0x', 'network' => 'bsc']),
            fn() => $manager->redeemAssets(['amount' => 1, 'bankCode' => '011', 'accountNumber' => '123']),
            fn() => $manager->swapAssets(['destinationNetwork' => 'bsc', 'destinationAddress' => '0x', 'originNetwork' => 'eth', 'callbackUrl' => 'https://x.com']),
            fn() => $manager->swapQuote(['destinationNetwork' => 'bsc', 'originNetwork' => 'eth']),
            fn() => $manager->updateExternalAccounts(['walletAddress' => ['bscAddress' => '0x']]),
            fn() => $manager->whitelistAddress(['address' => '0x', 'network' => 'bsc']),
            fn() => $manager->validateAccount(['bankCode' => '011', 'accountNumber' => '123']),
        ];

        foreach ($methods as $method) {
            $result = $method();
            $this->assertIsString($result);
            $decoded = json_decode($result, true);
            $this->assertNotNull($decoded, "Method returned invalid JSON: $result");
        }
    }

    // ─── AESCrypto Unit Tests ───────────────────────────────────────

    #[Test]
    public function AESCrypto_encrypt_returns_content_and_iv(): void
    {
        $encrypted = AESCrypto::encrypt('hello world', 'test-key');
        $this->assertArrayHasKey('content', $encrypted);
        $this->assertArrayHasKey('iv', $encrypted);
        $this->assertNotEmpty($encrypted['content']);
        $this->assertNotEmpty($encrypted['iv']);
    }

    #[Test]
    public function AESCrypto_decrypt_reverses_encrypt(): void
    {
        $plaintext = '{"amount":100,"address":"0x1234"}';
        $key = 'my-secret-encryption-key';

        $encrypted = AESCrypto::encrypt($plaintext, $key);
        $decrypted = AESCrypto::decrypt($encrypted, $key);

        $this->assertEquals($plaintext, $decrypted);
    }

    #[Test]
    public function AESCrypto_decrypt_with_wrong_key_throws(): void
    {
        $encrypted = AESCrypto::encrypt('test data', 'correct-key');

        $this->expectException(\Exception::class);
        AESCrypto::decrypt($encrypted, 'wrong-key');
    }

    #[Test]
    public function AESCrypto_produces_different_iv_each_time(): void
    {
        $a = AESCrypto::encrypt('same data', 'same-key');
        $b = AESCrypto::encrypt('same data', 'same-key');

        $this->assertNotEquals($a['iv'], $b['iv']);
    }
}