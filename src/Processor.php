<?php

declare(strict_types=1);

namespace Coddin\ManifestParser;

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

    public function getManifestData(string $manifestUrl): ManifestData
    {
        if (empty($manifestUrl)) {
            return ManifestData::error('The manifest URL is not set');
        }

        try {
            $manifestUrlParsed = \Safe\parse_url($manifestUrl);
        } catch (UrlException $e) {
            return ManifestData::error('Parsing the provided URL failed');
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
            return ManifestData::error(
                'Something went wrong when retrieving the manifest data, perhaps the URL is malformed'
            );
        }

        $body = $response->getBody()->getContents();

        if (empty($body)) {
            return ManifestData::error('The manifest URL is working but returned an empty body');
        }

        try {
            $manifestData = \Safe\json_decode($body, true);
        } catch (JsonException $e) {
            return ManifestData::error($e->getMessage());
        }

        if (!is_array($manifestData)) {
            return ManifestData::error('Decoding the manifest JSON did not result in an array');
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
