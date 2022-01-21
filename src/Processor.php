<?php

declare(strict_types=1);

namespace Coddin\ManifestParser;

use Coddin\ManifestParser\Exception\ManifestDataException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Safe\Exceptions\JsonException;
use Safe\Exceptions\UrlException;

final class Processor
{
    private ClientInterface $client;

    public function __construct(
        ClientInterface $clientFactory
    ) {
        $this->client = $clientFactory;
    }

    /**
     * @throws ManifestDataException
     */
    public function getManifestData(string $manifestUrl): ManifestData
    {
        if (empty($manifestUrl)) {
            throw ManifestDataException::create('The manifest URL is not set');
        }

        try {
            $manifestUrlParsed = \Safe\parse_url($manifestUrl);
        } catch (UrlException $e) {
            throw ManifestDataException::create('Parsing the provided URL failed', $e);
        }

        // @codeCoverageIgnoreStart
        if (!is_array($manifestUrlParsed)) {
            throw new \LogicException('Parsing the URL did not result in an array');
        } // @codeCoverageIgnoreEnd

        try {
            $response = $this->client->request(
                'GET',
                $manifestUrl,
                [
                    CURLOPT_FOLLOWLOCATION => true,
                    'headers' => [
                        'Cache-Control: no-cache',
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            throw ManifestDataException::create(
                'Something went wrong when retrieving the manifest data, perhaps the URL is malformed',
                $e
            );
        }

        $body = $response->getBody()->getContents();

        if (empty($body)) {
            throw ManifestDataException::create('The manifest URL is working but returned an empty body');
        }

        try {
            $manifestData = \Safe\json_decode($body, true);
        } catch (JsonException $e) {
            throw ManifestDataException::create($e->getMessage(), $e);
        }

        if (!is_array($manifestData)) {
            throw ManifestDataException::create('Decoding the manifest JSON did not result in an array');
        }

        $scriptFiles = [];
        $styleSheets = [];

        $baseUrl = $manifestUrlParsed['scheme'] . '://' . $manifestUrlParsed['host'];

        if (isset($manifestUrlParsed['port'])) {
            $baseUrl .= ':' . $manifestUrlParsed['port'];
        }

        foreach ($manifestData as $file) {
            switch (true) {
                // TODO Change to str_ends_with as soon as this package only supports PHP ^8.x.
                case substr($file, -3) === '.js':
                    $scriptFiles[] = $baseUrl . $file;
                    break;
                case substr($file, -4) === '.css':
                    $styleSheets[] = $baseUrl . $file;
                    break;
            }
        }

        return ManifestData::create($scriptFiles, $styleSheets);
    }
}
