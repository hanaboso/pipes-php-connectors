<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait;
use Hanaboso\Utils\System\PipesHeaders;
use JsonException;

/**
 * Class ShoptetUpdatedOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetUpdatedOrderConnector extends ShoptetConnectorAbstract
{

    use ProcessContentTrait;

    private const URL = 'api/orders/%s?include=notes';

    private const EVENT_INSTANCE = 'eventInstance';
    private const ORDER          = 'order';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shoptet-updated-order-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws ConnectorException
     * @throws OnRepeatException
     * @throws JsonException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dto
            ->addHeader(PipesHeaders::createKey(PipesHeaders::USER), (string) $this->getContentByKey($dto, 'eshopId'))
            ->addHeader(PipesHeaders::createKey(PipesHeaders::APPLICATION), ShoptetApplication::SHOPTET_KEY);

        try {
            $data = $this->processResponse(
                $this->sender->send(
                    $this->getApplication()->getRequestDto(
                        $this->repository->findUserAppByHeaders($dto),
                        CurlManager::METHOD_GET,
                        $this->getUrl(self::URL, $this->getContentByKey($dto, self::EVENT_INSTANCE)),
                    )->setDebugInfo($dto),
                )->getJsonBody(),
                $dto,
            )[self::DATA][self::ORDER];

            return $this->setJsonContent($dto, $data);
        } catch (CurlException $e) {
            throw $this->createRepeatException($dto, $e);
        }
    }

}
