<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;

/**
 * Class GetApplicationForRefreshBatchTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector
 */
final class GetApplicationForRefreshBatchTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector::__construct
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector::processAction
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->pfd((new ApplicationInstall())->setExpires(DateTimeUtils::getUtcDateTime()));
        /** @var GetApplicationForRefreshBatchConnector $conn */
        $conn = self::getContainer()->get('hbpf.connector.batch-get_application_for_refresh');

        $dto = $conn->processAction(new ProcessDto());
        self::assertCount(1, Json::decode($dto->getData()));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector::getId
     */
    public function testGetId(): void
    {
        $application = self::getContainer()->get('hbpf.connector.batch-get_application_for_refresh');

        self::assertEquals('get_application_for_refresh', $application->getId());
    }

}
