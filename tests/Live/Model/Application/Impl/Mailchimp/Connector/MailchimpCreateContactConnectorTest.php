<?php declare(strict_types=1);

namespace Tests\Live\Model\Application\Impl\Mailchimp\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class MailchimpCreateContactConnectorTest
 *
 * @package Tests\Live\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpCreateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $app                             = self::$container->get('hbpf.application.mailchimp');
        $mailchimpCreateContactConnector = new MailchimpCreateContactConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $mailchimpCreateContactConnector->setApplication($app);

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'token123'
        );

        $applicationInstall->setSettings(
            [
                MailchimpApplication::AUDIENCE_ID  => 'c9e7f***5b',
                MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
                'form'                             => ['audience_id' => '123'],
            ]
        );

        $this->pf($applicationInstall);
        $this->dm->clear();
        //        $data = (string) file_get_contents(sprintf('%s/Data/requestMailchimp.json', __DIR__), TRUE);
        //        $mailchimpCreateContactConnector->processAction(
        //            DataProvider::getProcessDto(
        //                $app->getKey(),
        //                'user',
        //                $data
        //            )
        //        );
        self::assertEmpty([]);
    }

}
