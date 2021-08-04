<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Salesforce;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class SalesforceApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Salesforce
 */
final class SalesforceApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID = '123****';

    /**
     * @var SalesforceApplication
     */
    private SalesforceApplication $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        $this->setApplication();
        self::assertEquals(
            ApplicationTypeEnum::CRON,
            $this->application->getApplicationType(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getKey
     */
    public function testGetKey(): void
    {
        $this->setApplication();
        self::assertEquals(
            'salesforce',
            $this->application->getKey(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getName
     */
    public function testGetName(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Salesforce',
            $this->application->getName(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getDescription
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Salesforce is one of the largest CRM platform.',
            $this->application->getDescription(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getSettingsForm
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
                    'instance_name',
                ],
            );
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getRequestDto
     *
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getKey());
        $this->pfd($applicationInstall);

        $dto = $this->application->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_POST,
            'https://yourInstance.salesforce.com/services/data/v20.0/sobjects/Account/',
            'body',
        );

        self::assertEquals(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer token123',
            ],
            $dto->getHeaders(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getAuthUrl
     */
    public function testGetAuthUrl(): void
    {
        $this->setApplication();
        self::assertEquals(
            'https://login.salesforce.com/services/oauth2/authorize',
            $this->application->getAuthUrl(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getTokenUrl
     */
    public function testGetTokenUrl(): void
    {
        $this->setApplication();
        self::assertEquals(
            'https://login.salesforce.com/services/oauth2/token',
            $this->application->getTokenUrl(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::authorize
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getKey(),
            'user',
            'token123',
            self::CLIENT_ID,
        );
        $this->pfd($applicationInstall);
        self::assertEquals(TRUE, $this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall);
    }

    /**
     *
     */
    private function setApplication(): void
    {
        $this->mockRedirect('https://login.salesforce.com/services/oauth2/authorize', self::CLIENT_ID);
        $this->application = self::getContainer()->get('hbpf.application.salesforce');
    }

}
