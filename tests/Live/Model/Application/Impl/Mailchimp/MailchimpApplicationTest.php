<?php declare(strict_types=1);

namespace Tests\Live\Model\Application\Impl\Mailchimp;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class MailchimpApplicationTest
 *
 * @package Tests\Live\Model\Application\Impl\Mailchimp
 */
final class MailchimpApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $app = self::$container->get('hbpf.application.mailchimp');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'token123',
            '6748****7235',
            'f8fe8943e9b258b46d7220a5**********b67bd5178b71f738'
        );
        $applicationInstall = $applicationInstall->setSettings(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                    [
                        ApplicationInterface::REDIRECT_URL => 'xxxx',
                    ],
                MailchimpApplication::AUDIENCE_ID                 => 'c9e7f***5b',
            ]
        );
        $this->pf($applicationInstall);
        $app->authorize($applicationInstall);
    }

}
