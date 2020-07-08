<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\RequestOptions;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;

/**
 * Class GoogleDriveUploadFileConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector
 */
final class GoogleDriveUploadFileConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    /**
     * @var string
     */
    protected string $fileName = 'my.txt';

    /**
     * @var string
     */
    protected string $folder = 'id';

    /**
     * @var ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * @var CurlManager
     */
    private CurlManager $sender;

    /**
     * GoogleDriveUploadFileConnector constructor.
     *
     * @param DocumentManager $dm
     * @param CurlManager     $sender
     */
    public function __construct(DocumentManager $dm, CurlManager $sender)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
        $this->sender     = $sender;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'google-drive.upload-file';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws OnRepeatException
     * @throws ApplicationInstallException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUserAppByHeaders($dto);
        $tmpFileName        = sprintf('/tmp/%s', uniqid('file_', FALSE));
        file_put_contents($tmpFileName, $dto->getData());

        $multipart = [
            RequestOptions::MULTIPART => [
                [
                    'name'     => 'metadata',
                    'contents' => Json::encode(['name' => $this->fileName, 'parents' => [$this->folder]]),
                    'headers'  => ['Content-Type' => 'application/json; charset=UTF-8'],
                ],
                [
                    'name'     => 'file',
                    'contents' => fopen($tmpFileName, 'r'),
                    'headers'  => ['Content-Type' => 'application/octet-stream'],
                ],
            ],
        ];

        try {
            $request = $this->getApplication()
                ->getRequestDto(
                    $applicationInstall,
                    CurlManager::METHOD_POST,
                    sprintf('%s/upload/drive/v3/files?uploadType=multipart', GoogleDriveApplication::BASE_URL)
                )
                ->setDebugInfo($dto);

            $response = $this->sender->send($request, $multipart);

            $this->evaluateStatusCode($response->getStatusCode(), $dto);

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        } finally {
            unlink($tmpFileName);
        }

        return $dto;
    }

}
