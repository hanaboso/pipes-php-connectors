<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;

/**
 * Class GetApplicationForRefreshBatchConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector
 */
final class GetApplicationForRefreshBatchConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    public const APPLICATION_ID = 'get_application_for_refresh';

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * GetApplicationForRefreshBatchConnector constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return self::APPLICATION_ID;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws DateTimeException
     * @throws MongoDBException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        // TODO batch connector v2
        $time = DateTimeUtils::getUtcDateTime('1 hour');

        /** @var ApplicationInstall[] $applications */
        $applications = $this->repository
            ->createQueryBuilder()
            ->select('_id')
            ->field('expires')->lte($time)
            ->field('expires')->notEqual(NULL)
            ->getQuery()
            ->execute();

        $ids = [];
        foreach ($applications as $app) {
            $ids[] = [self::APPLICATION_ID => $app->getId()];
        }
        $dto->setData(Json::encode($ids));

        return $dto;
    }

}
