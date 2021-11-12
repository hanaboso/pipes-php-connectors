<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\Date\DateTimeUtils;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class ShoptetApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet
 */
final class ShoptetApplicationTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;
    use CustomAssertTrait;

    private const CLIENT_ID = '123****';

    /**
     * @var ShoptetApplication
     */
    private ShoptetApplication $application;

    /**
     * @throws Exception
     */
    public function testConstructor(): void
    {
        /** @var OAuth2Provider $provider */
        $provider = self::getContainer()->get('hbpf.providers.oauth2_provider');
        /** @var DocumentManager $dm */
        $dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        /** @var CurlManager $sender */
        $sender = self::getContainer()->get('hbpf.transport.curl_manager');
        new ShoptetApplication($provider, $dm, $sender, 'localhost');

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getApplicationType
     *
     * @throws Exception
     */
    public function testGetApplicationType(): void
    {
        $this->setApplication();
        self::assertEquals(ApplicationTypeEnum::WEBHOOK, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getName
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        $this->setApplication();
        self::assertEquals('shoptet', $this->application->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getPublicName
     *
     * @throws Exception
     */
    public function testGetPublicName(): void
    {
        $this->setApplication();
        self::assertEquals('Shoptet', $this->application->getPublicName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getDescription
     *
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals('Shoptet', $this->application->getDescription());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $this->setApplication();
        $fields = $this->application->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertContainsEquals(
                $field->getKey(),
                [
                    OAuth2ApplicationAbstract::CLIENT_ID,
                    OAuth2ApplicationAbstract::CLIENT_SECRET,
                    'eshopId',
                    'oauth_url',
                    'api_token_url',
                ],
            );
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getRequestDto
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getApiToken
     *
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $this->mockSender('{ "access_token": "___access token___", "expires_in": 3600}');

        $this->application = self::getContainer()->get('hbpf.application.shoptet');
        $dto               = $this->application->getRequestDto(
            (new ApplicationInstall())
                ->setSettings(
                    [
                        ApplicationInterface::AUTHORIZATION_SETTINGS => [
                            ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => '___access_token___'],
                        ],
                        ApplicationAbstract::FORM                    =>
                            [
                                'api_token_url' => 'https://12345.myshoptet.com/action/ApiOAuthServer/token',
                            ],
                    ],
                ),
            'POST',
            'https://example.com',
            '"{"data":"data"}"',
        );

        self::assertEquals('___access token___', $dto->getHeaders()['Shoptet-Access-Token']);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getAuthUrl
     *
     * @throws Exception
     */
    public function testGetAuthUrl(): void
    {
        $this->setApplication();
        self::assertEquals('', $this->application->getAuthUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getTokenUrl
     *
     * @throws Exception
     */
    public function testGetTokenUrl(): void
    {
        $this->setApplication();
        self::assertEquals('', $this->application->getTokenUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getAuthUrlWithServerUrl
     *
     * @throws Exception
     */
    public function testGetAuthUrlWithServerUrl(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->setSettings(
                [ApplicationAbstract::FORM => ['oauth_url' => 'https://12345.myshoptet.com/action/ApiOAuthServer/token']],
            );

        self::assertEquals(
            'https://12345.myshoptet.com/action/ApiOAuthServer/token',
            $this->application->getAuthUrlWithServerUrl($applicationInstall),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getTokenUrlWithServerUrl
     *
     * @throws Exception
     */
    public function testGetTokenUrlWithServerUrl(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->addSettings(
                [ApplicationAbstract::FORM => ['api_token_url' => 'https://12345.myshoptet.com/action/ApiOAuthServer/getAccessToken']],
            );

        self::assertEquals(
            'https://12345.myshoptet.com/action/ApiOAuthServer/getAccessToken',
            $this->application->getTokenUrlWithServerUrl($applicationInstall),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getWebhookSubscriptions
     *
     * @throws Exception
     */
    public function testGetWebhookSubscriptions(): void
    {
        $this->setApplication();
        self::assertNotEmpty($this->application->getWebhookSubscriptions());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getWebhookSubscribeRequestDto
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getApiTokenFromSettings
     *
     * @throws Exception
     */
    public function testGetWebhookSubscribeRequestDto(): void
    {
        $this->setApplication();
        $subscription = new WebhookSubscription(
            ShoptetApplication::SHOPTET_KEY,
            'Webhook',
            'shoptet-uninstall',
            ['event' => 'unsubscription'],
        );
        $dto          = $this->application->getWebhookSubscribeRequestDto(
            (new ApplicationInstall())
                ->setSettings(
                    [
                        'clientSettings' => [
                            'token' => [
                                'access_token' => '/token.a.b.c',
                                'expires_in'   => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                            ],
                        ],
                    ],
                ),
            $subscription,
            'www.nejaka.url',
        );

        self::assertEquals('{"event":"unsubscription","url":"www.nejaka.url"}', $dto->getBody());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getWebhookSubscribeRequestDto
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getApiTokenFromSettings
     *
     * @throws Exception
     */
    public function testGetWebhookSubscribeRequestDtoError(): void
    {
        $this->setApplication();

        $this->expectException(ApplicationInstallException::class);
        $subscription = new WebhookSubscription(
            ShoptetApplication::SHOPTET_KEY,
            'Webhook',
            'shoptet-uninstall',
            ['event' => 'unsubscription'],
        );
        $dto          = $this->application->getWebhookSubscribeRequestDto(
            new ApplicationInstall(),
            $subscription,
            'www.nejaka.url',
        );

        self::assertEquals('{"event":"unsubscription","url":"www.nejaka.url"}', $dto->getBody());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getWebhookUnsubscribeRequestDto
     *
     * @throws Exception
     */
    public function testGetWebhookUnsubscribeRequestDto(): void
    {
        $this->setApplication();
        $applicationInstall = (new ApplicationInstall())
            ->addSettings(
                [
                    'clientSettings' => [
                        'token' => [
                            'access_token' => '/token.a.b.c',
                            'expires_in'   => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                        ],
                    ],
                ],
            );
        $this->pfd($applicationInstall);
        $dto = $this->application->getWebhookUnsubscribeRequestDto($applicationInstall, '123');

        self::assertEquals('/token.a.b.c', $dto->getHeaders()['Shoptet-Access-Token']);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::processWebhookSubscribeResponse
     *
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(200, 'test', '{"data": "data"}', []),
            new ApplicationInstall(),
        );

        self::assertEquals('{"data": "data"}', $response);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::processWebhookUnsubscribeResponse
     *
     * @throws Exception
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(
                200,
                'test',
                '{"data": "data"}',
                [],
            ),
        );
        self::assertTrue($response);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getApiTokenDto
     * @throws Exception
     */
    public function testGetApiTokenDto(): void
    {
        $this->setApplication();
        $applicationInstall = (new ApplicationInstall())
            ->addSettings(
                [
                    ApplicationInterface::AUTHORIZATION_SETTINGS => [
                        ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => '___access_token___'],
                    ],
                    ApplicationAbstract::FORM                    =>
                        [
                            'api_token_url' => 'https://12345.myshoptet.com/action/ApiOAuthServer/token',
                        ],
                ],
            );
        $this->pfd($applicationInstall);
        $dto = $this->application->getApiTokenDto($applicationInstall);

        self::assertEquals(
            [
                'Authorization' => 'Bearer ___access_token___',
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            $dto->getHeaders(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::createDto
     * @throws Exception
     */
    public function testCreateDto(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->addSettings(
                [
                    ApplicationAbstract::FORM =>
                        [
                            'api_token_url' => 'https://12345.myshoptet.com/action/ApiOAuthServer/token',
                            'oauth_url'     => 'https://12345.myshoptet.com/action/ApiOAuthServer/getAccessToken',
                        ],
                ],
            );

        $crateDto = $this->invokeMethod(
            $this->application,
            'createDto',
            [$applicationInstall, 'https://127.0.0.66/api/api/plugins/shoptet'],
        );

        self::assertEquals('https://127.0.0.66/api/api/plugins/shoptet', $crateDto->getRedirectUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication::getTopologyUrl
     *
     * @throws Exception
     */
    public function testGetTopologyUrl(): void
    {
        $this->setApplication();

        self::assertEquals(
            'https://starting-point/topologies/123/nodes/Start/run-by-name',
            $this->application->getTopologyUrl('123'),
        );
    }

    /**
     * @throws Exception
     */
    private function setApplication(): void
    {
        $this->mockRedirect('https://12345.myshoptet.com/action/ApiOAuthServer/token', self::CLIENT_ID);
        $this->application = self::getContainer()->get('hbpf.application.shoptet');
    }

    /**
     * @param string $jsonContent
     *
     * @throws Exception
     */
    private function mockSender(string $jsonContent): void
    {
        $callback = static fn(): ResponseDto => new ResponseDto(
            200,
            'api token',
            $jsonContent,
            [
                'pf-user'        => 'user',
                'pf-application' => ShoptetApplication::SHOPTET_KEY,
            ],
        );

        $this->setProperty(
            self::getContainer()->get('hbpf.application.shoptet'),
            'sender',
            $this->prepareSender($callback),
        );
    }

}
