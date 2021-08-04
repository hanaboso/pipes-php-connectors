<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\Connector\AirtableNewRecordConnector;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\MockCurlMethod;

/**
 * Class AirtableNewRecordConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable\Connector
 */
final class AirtableNewRecordConnectorTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = 'keyfb******LvKNJI';
    public const BASE_ID    = 'appX**********XpN';
    public const TABLE_NAME = 'V******.com';

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockCurl([new MockCurlMethod(200, 'response200.json', [])]);

        $airtableNewRecordConnector = $this->setApplicationAndMock(self::API_KEY);

        $newRecordFile = File::getContent(sprintf('%s/Data/newRecord.json', __DIR__));

        $response = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto('airtable', 'user', $newRecordFile),
        );

        self::assertSuccessProcessResponse($response, 'response200.json');
    }

    /**
     * @throws Exception
     */
    public function testProcessActionNoFields(): void
    {
        $this->mockCurl([new MockCurlMethod(500, 'response500.json', [])]);

        $airtableNewRecordConnector = $this->setApplicationAndMock(self::API_KEY);
        $newRecordFileNoFields      = File::getContent(sprintf('%s/Data/newRecordNoFields.json', __DIR__));
        $response                   = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto('airtable', 'user', $newRecordFileNoFields),
        );

        self::assertFailedProcessResponse($response, 'response500.json');

        self::assertEquals(ProcessDto::STOP_AND_FAILED, $response->getHeaders()['pf-result-code']);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionNoBaseId(): void
    {
        $airtableNewRecordConnector = $this->setApplicationAndMock();

        $newRecordFile = File::getContent(sprintf('%s/Data/newRecord.json', __DIR__));

        $response = $airtableNewRecordConnector->processAction(
            DataProvider::getProcessDto('airtable', 'user', $newRecordFile),
        );

        self::assertFailedProcessResponse($response, 'newRecord.json');

        self::assertEquals(ProcessDto::STOP_AND_FAILED, $response->getHeaders()['pf-result-code']);
    }

    /**
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $airtableNewRecordConnector = $this->setApplication();

        self::expectException(ConnectorException::class);
        $airtableNewRecordConnector->processEvent(DataProvider::getProcessDto('airtable', 'user', ''));
    }

    /**
     *
     */
    public function testGetId(): void
    {
        $airtableNewRecordConnector = $this->setApplication();
        self::assertEquals(
            'airtable_new_record',
            $airtableNewRecordConnector->getId(),
        );
    }

    /**
     * @return mixed[]
     */
    public function getDataProvider(): array
    {
        return [
            [200, TRUE],
            [500, FALSE],
        ];
    }

    /**
     * @return AirtableNewRecordConnector
     */
    private function setApplication(): AirtableNewRecordConnector
    {
        $app                        = self::getContainer()->get('hbpf.application.airtable');
        $airtableNewRecordConnector = new AirtableNewRecordConnector(
            self::getContainer()->get('hbpf.transport.curl_manager'),
            $this->dm,
        );

        $airtableNewRecordConnector->setApplication($app);

        return $airtableNewRecordConnector;
    }

    /**
     * @param string|null $baseId
     *
     * @return AirtableNewRecordConnector
     * @throws Exception
     */
    private function setApplicationAndMock(?string $baseId = NULL): AirtableNewRecordConnector
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                    [
                        BasicApplicationAbstract::TOKEN => self::API_KEY,
                    ],
                ApplicationAbstract::FORM                         => [
                    AirtableApplication::BASE_ID    => $baseId,
                    AirtableApplication::TABLE_NAME => self::TABLE_NAME,
                ],
            ],
        );

        $applicationInstall->setUser('user');
        $applicationInstall->setKey('airtable');
        $this->pfd($applicationInstall);
        $this->dm->clear();

        return $this->setApplication();
    }

}
