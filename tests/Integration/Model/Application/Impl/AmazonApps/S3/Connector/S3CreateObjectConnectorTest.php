<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\AmazonApps\S3\Connector;

use Aws\Command;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector\S3CreateObjectConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class S3CreateObjectConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\AmazonApps\S3\Connector
 */
final class S3CreateObjectConnectorTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const KEY  = 's3';
    private const USER = 'user';

    /**
     * @var S3CreateObjectConnector
     */
    private $connector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::$container->get('hbpf.connector.s3-create-object');
    }

    /**
     * @covers S3CreateObjectConnector::processAction
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test', 'content' => 'Content']))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);
        $dto = $this->connector->processAction($dto);

        self::assertEquals('Test', Json::decode($dto->getData())['name']);
    }

    /**
     * @covers S3CreateObjectConnector::processAction
     * @throws Exception
     */
    public function testProcessActionMissingName(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 's3-create-object': Required parameter 'name' is not provided!"
        );

        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData(Json::encode(['content' => 'Content']))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

        $this->connector->processAction($dto);
    }

    /**
     * @covers S3CreateObjectConnector::processAction
     * @throws Exception
     */
    public function testProcessActionMissingContent(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 's3-create-object': Required parameter 'content' is not provided!"
        );

        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test']))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

        $this->connector->processAction($dto);
    }

    /**
     * @covers S3CreateObjectConnector::processAction
     * @throws Exception
     */
    public function testProcessActionException(): void
    {
        self::assertException(
            OnRepeatException::class,
            0,
            "Connector 's3-create-object': Aws\S3\Exception\S3Exception: Something gone wrong!"
        );

        $this->createApplication();

        /** @var S3Client|MockObject $client */
        $client = self::createPartialMock(S3Client::class, ['__call']);
        $client->method('__call')->willReturnCallback(
            function (): void {
                throw new S3Exception('Something gone wrong!', new Command('Unknown'));
            }
        );

        /** @var S3Application|MockObject $application */
        $application = self::createPartialMock(S3Application::class, ['getS3Client']);
        $application->method('getS3Client')->willReturn($client);
        $this->setProperty($this->connector, 'application', $application);

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test', 'content' => 'Content']))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

        $this->connector->processAction($dto);
    }

    /**
     * @throws Exception
     */
    private function createApplication(): void
    {
        $application = (new ApplicationInstall())
            ->setKey(self::KEY)
            ->setUser(self::USER)
            ->setSettings(
                [
                    S3Application::FORM => [
                        S3Application::KEY      => 'Key',
                        S3Application::SECRET   => 'Secret',
                        S3Application::REGION   => 'eu-central-1',
                        S3Application::BUCKET   => 'Bucket',
                        S3Application::ENDPOINT => 'http://fakes3:4567',
                    ],
                ]
            );

        $this->dm->persist($application);
        $this->dm->flush();
    }

}