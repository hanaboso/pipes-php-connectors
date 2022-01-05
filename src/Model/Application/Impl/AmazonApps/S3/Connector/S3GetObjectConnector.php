<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector;

use Aws\Exception\AwsException;
use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;

/**
 * Class S3GetObjectConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector
 */
final class S3GetObjectConnector extends S3ObjectConnectorAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws OnRepeatException
     * @throws ApplicationInstallException
     * @throws Exception
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $content = $this->getJsonContent($dto);
        $this->checkParameters([self::NAME], $content);

        $applicationInstall = $this->getApplicationInstall($dto);
        /** @var S3Application $application */
        $application = $this->getApplication();
        $client      = $application->getS3Client($applicationInstall);

        $path = sprintf('/tmp/%s', bin2hex(random_bytes(10)));
        File::putContent($path, '');

        try {
            $client->getObject(
                [
                    self::BUCKET => $this->getBucket($applicationInstall),
                    self::KEY    => $content[self::NAME],
                    self::TARGET => $path,
                ],
            );
        } catch (AwsException $e) {
            throw $this->createRepeatException($dto, $e);
        } finally {
            $fileContent = File::getContent($path);
            unlink($path);
        }

        return $this->setJsonContent($dto, [self::NAME => $content[self::NAME], self::CONTENT => $fileContent]);
    }

    /**
     * @return string
     */
    protected function getCustomId(): string
    {
        return 'get-object';
    }

}
