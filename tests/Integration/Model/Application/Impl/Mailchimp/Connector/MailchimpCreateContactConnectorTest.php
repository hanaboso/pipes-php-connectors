<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\MockCurlMethod;

/**
 * Class MailchimpCreateContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpCreateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @param int  $code
     * @param bool $isValid
     *
     * @throws Exception
     *
     * @dataProvider getDataProvider
     */
    public function testProcessAction(int $code, bool $isValid): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    $code,
                    'responseDatacenter.json',
                    [],
                ),
                new MockCurlMethod(
                    $code,
                    sprintf('response%s.json', $code),
                    [],
                ),
            ],
        );

        $app                             = self::getContainer()->get('hbpf.application.mailchimp');
        $mailchimpCreateContactConnector = new MailchimpCreateContactConnector(
            self::getContainer()->get('hbpf.transport.curl_manager'),
            $this->dm,
        );

        $mailchimpCreateContactConnector->setApplication($app);

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
            'user',
            'fa830d8d4308625ba**********de659',
        );

        $applicationInstall->addSettings(
            [
                ApplicationAbstract::FORM          => [
                    MailchimpApplication::AUDIENCE_ID => '2a8******8',
                ],
                MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
            ],
        );

        $this->pfd($applicationInstall);

        $dto      = DataProvider::getProcessDto(
            $app->getName(),
            'user',
            File::getContent(__DIR__ . sprintf('/Data/response%s.json', $code)),
        );
        $response = $mailchimpCreateContactConnector->processAction($dto);

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
    }

    /**
     * @return mixed[]
     */
    public function getDataProvider(): array
    {
        return [
            [400, FALSE],
            [200, TRUE],
        ];
    }

    /**
     * @throws Exception
     */
    public function testGetId(): void
    {
        $mailchimpCreateContactConnector = new MailchimpCreateContactConnector(
            self::getContainer()->get('hbpf.transport.curl_manager'),
            $this->dm,
        );
        self::assertEquals(
            'mailchimp_create_contact',
            $mailchimpCreateContactConnector->getId(),
        );
    }

}
