<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Shipstation;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class ShipstationApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Shipstation
 */
final class ShipstationApplicationTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = '8919bb213aab47b48f7bb07f1ce1e25c';
    public const API_SECRET = '996ab3153f154499a38221d22375424b';

    public const token = 'ODkxOWJiMjEzYWFiNDdiNDhmN2JiMDdmMWNlMWUyNWM6OTk2YWIzMTUzZjE1NDQ5OWEzODIyMWQyMjM3NTQyNGI=';

    /**
     * @throws DateTimeException
     */
    public function testAutorize(): void
    {
        $shipstationApplication = self::$container->get('hbpf.application.shipstation');
        $shipstationApplication;
        $applicationInstall = new ApplicationInstall();
        $applicationInstall = $applicationInstall->setSettings([
            BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                [
                    BasicApplicationAbstract::USER     => self::API_KEY,
                    BasicApplicationAbstract::PASSWORD => self::API_SECRET,
                ],
        ]);

        $applicationInstall->setUser('user');
        $applicationInstall->setKey('shipstation');
        $this->pf($applicationInstall);
    }

    /**
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws CurlException
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $application        = self::$container->get('hbpf.application.shipstation');
        $applicationInstall = DataProvider::getBasicAppInstall(
            $application->getKey(),
            self::API_KEY,
            self::API_SECRET
        );

        $applicationInstall = $applicationInstall->setSettings([
            BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                [
                    BasicApplicationAbstract::USER     => self::API_KEY,
                    BasicApplicationAbstract::PASSWORD => self::API_SECRET,
                ],
        ]);
        $subscription       = new WebhookSubscription('test', 'node', 'xxx', ['name' => 0]);

        $requestSub = $application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $subscription,
            sprintf('%s/webhook/topologies/%s/nodes/%s/token/%s',
                rtrim('www.xx.cz', '/'),
                $subscription->getTopology(),
                $subscription->getNode(),
                bin2hex(random_bytes(25)))
        );

        $requestUn = $application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            '358'
        );

        self::assertEquals(
            $requestSub->getUriString(),
            'https://ssapi.shipstation.com/webhooks/subscribe'
        );

        self::assertEquals(
            $requestUn->getUriString(),
            'https://ssapi.shipstation.com/webhooks/358'
        );

    }

}