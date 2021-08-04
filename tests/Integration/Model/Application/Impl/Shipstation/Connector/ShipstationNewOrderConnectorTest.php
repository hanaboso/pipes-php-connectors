<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shipstation\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector\ShipstationNewOrderConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\MockCurlMethod;

/**
 * Class ShipstationNewOrderConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shipstation\Connector
 */
final class ShipstationNewOrderConnectorTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = '79620d3760d**********18f8a35dec8';
    public const API_SECRET = '9cabe470**********751904f45f80e2';

    /**
     * @param int  $code
     * @param bool $isValid
     *
     * @throws Exception
     *
     * @dataProvider getDataProvider
     */
    public function testProcessEvent(int $code, bool $isValid): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    $code,
                    sprintf('response%s.json', $code),
                    [],
                ),
            ],
        );

        $app                          = self::getContainer()->get('hbpf.application.shipstation');
        $shipstationNewOrderConnector = new ShipstationNewOrderConnector(
            self::getContainer()->get('hbpf.transport.curl_manager'),
            $this->dm,
        );

        $shipstationNewOrderConnector->setApplication($app);

        $applicationInstall = DataProvider::getBasicAppInstall(
            $app->getKey(),
            self::API_KEY,
            self::API_SECRET,
        );

        $this->pfd($applicationInstall);
        $response = $shipstationNewOrderConnector->processEvent(
            DataProvider::getProcessDto(
                $app->getKey(),
                self::API_KEY,
                File::getContent(sprintf('%s/Data/newOrder.json', __DIR__)),
            ),
        );

        $responseNoUrl = $shipstationNewOrderConnector->processEvent(
            DataProvider::getProcessDto(
                $app->getKey(),
                self::API_KEY,
                File::getContent(sprintf('%s/Data/newOrderNoUrl.json', __DIR__)),
            ),
        );

        if ($isValid) {
            self::assertSuccessProcessResponse(
                $response,
                sprintf('response%s.json', $code),
            );
        } else {
            self::assertFailedProcessResponse(
                $response,
                sprintf('response%s.json', $code),
            );
        }

        self::assertEquals(ProcessDto::STOP_AND_FAILED, $responseNoUrl->getHeaders()['pf-result-code']);
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $app                          = self::getContainer()->get('hbpf.application.shipstation');
        $shipstationNewOrderConnector = new ShipstationNewOrderConnector(
            self::getContainer()->get('hbpf.transport.curl_manager'),
            $this->dm,
        );

        $shipstationNewOrderConnector->setApplication($app);

        $applicationInstall = DataProvider::getBasicAppInstall(
            $app->getKey(),
            self::API_KEY,
            self::API_SECRET,
        );

        $this->pfd($applicationInstall);
        self::expectException(ConnectorException::class);
        $shipstationNewOrderConnector->processAction(
            DataProvider::getProcessDto(
                $app->getKey(),
                self::API_KEY,
                File::getContent(sprintf('%s/Data/newOrder.json', __DIR__)),
            ),
        );
    }

    /**
     * @return mixed[]
     */
    public function getDataProvider(): array
    {
        return [
            [404, FALSE],
            [200, TRUE],
        ];
    }

    /**
     *
     */
    public function testGetId(): void
    {
        $shipstationNewOrderConnector = new ShipstationNewOrderConnector(
            self::getContainer()->get('hbpf.transport.curl_manager'),
            $this->dm,
        );
        self::assertEquals(
            'shipstation_new_order',
            $shipstationNewOrderConnector->getId(),
        );
    }

}
