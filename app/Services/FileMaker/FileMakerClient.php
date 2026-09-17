<?php

namespace App\Services\FileMaker;

use App\Services\FileMaker\Exceptions\FileMakerRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Throwable;

/**
 * Client XML Custom Web Publishing pour FileMaker Server 16.
 */
class FileMakerClient
{
    /**
     * Namespace du format fmresultset renvoyé par le Web Publishing Engine.
     */
    private const XML_NAMESPACE = 'http://www.filemaker.com/xml/fmresultset';

    /**
     * FileMaker renvoie 401 lorsqu'aucun enregistrement ne correspond à la
     * recherche. Ce n'est pas une erreur : c'est un résultat vide.
     */
    private const ERROR_NO_RECORDS_MATCH = 401;

    public function __construct(
        private readonly string $host,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
        private readonly int $connectTimeout = 5,
        private readonly int $timeout = 60,
        private readonly bool $verifySsl = true,
    ) {}

    /**
     * Récupère une page de l'ensemble des enregistrements du layout.
     */
    public function findAll(string $layout, int $skip = 0, int $max = 500, ?string $sortField = null): FileMakerResultSet
    {
        return $this->request('-findall', $layout, [], $skip, $max, $sortField);
    }

    /**
     * Récupère une page filtrée.
     *
     * Les critères sont passés tels quels à FileMaker, ce qui autorise ses
     * opérateurs de recherche natifs : '>=20260917103000', '==exact', 'debut*'.
     *
     * @param  array<string, string>  $criteria  Nom du champ FileMaker => expression de recherche.
     */
    public function findWhere(string $layout, array $criteria, int $skip = 0, int $max = 500, ?string $sortField = null): FileMakerResultSet
    {
        if ($criteria === []) {
            return $this->findAll($layout, $skip, $max, $sortField);
        }

        return $this->request('-find', $layout, $criteria, $skip, $max, $sortField);
    }

    /**
     * Nombre total d'enregistrements du layout, sans en rapatrier le contenu.
     */
    public function countAll(string $layout): int
    {
        return $this->findAll($layout, max: 1)->totalCount;
    }

    /**
     * @param  array<string, string>  $criteria
     */
    private function request(string $action, string $layout, array $criteria, int $skip, int $max, ?string $sortField): FileMakerResultSet
    {
        $query = $this->buildQuery($action, $layout, $criteria, $skip, $max, $sortField);

        $url = rtrim($this->host, '/').'/fmi/xml/fmresultset.xml?'.$query;

        $context = [
            'layout' => $layout,
            'action' => $action,
            'skip' => $skip,
            'max' => $max,
        ];

        try {
            $response = $this->pendingRequest()->get($url);
        } catch (ConnectionException $exception) {
            throw new FileMakerRequestException(
                'Connexion au serveur FileMaker impossible.',
                $context,
                previous: $exception,
            );
        }

        if (! $response->successful()) {
            throw new FileMakerRequestException(
                "Le serveur FileMaker a répondu HTTP {$response->status()}.",
                [...$context, 'status' => $response->status()],
                $response->status(),
            );
        }

        return $this->parse($response->body(), $context);
    }

    private function pendingRequest(): PendingRequest
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->when(! $this->verifySsl, fn (PendingRequest $request): PendingRequest => $request->withoutVerifying())
            ->retry([200, 1_000, 5_000], 0, $this->shouldRetry(), throw: false);
    }

    /**
     * Les lectures CWP sont idempotentes : on peut réessayer sans risque les
     * coupures réseau, les 5xx et les limitations de débit. Un 4xx métier,
     * lui, ne guérira pas d'une nouvelle tentative.
     */
    private function shouldRetry(): callable
    {
        return function (Throwable $exception): bool {
            if ($exception instanceof ConnectionException) {
                return true;
            }

            return $exception instanceof RequestException
                && ($exception->response->serverError() || $exception->response->status() === 429);
        };
    }

    /**
     * Le verbe d'action CWP ('-findall', '-find') est un paramètre sans valeur.
     * http_build_query produirait « -findall= », que le Web Publishing Engine
     * refuse : la chaîne est donc assemblée à la main.
     *
     * @param  array<string, string>  $criteria
     */
    private function buildQuery(string $action, string $layout, array $criteria, int $skip, int $max, ?string $sortField): string
    {
        $params = [
            '-db' => $this->database,
            '-lay' => $layout,
            '-skip' => (string) $skip,
            '-max' => (string) $max,
        ];

        if ($sortField !== null) {
            $params['-sortfield.1'] = $sortField;
            $params['-sortorder.1'] = 'ascend';
        }

        foreach ($criteria as $field => $expression) {
            $params[$field] = $expression;
        }

        $pairs = [];

        foreach ($params as $key => $value) {
            $pairs[] = rawurlencode($key).'='.rawurlencode($value);
        }

        $pairs[] = $action;

        return implode('&', $pairs);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function parse(string $body, array $context): FileMakerResultSet
    {
        try {
            $xml = new SimpleXMLElement($body, LIBXML_NONET);
        } catch (Throwable $exception) {
            throw new FileMakerRequestException(
                'La réponse du serveur FileMaker n\'est pas un XML exploitable.',
                $context,
                previous: $exception,
            );
        }

        $xml->registerXPathNamespace('fm', self::XML_NAMESPACE);

        $errorCode = $this->attribute($xml, '//fm:error', 'code');

        if ($errorCode === null) {
            throw new FileMakerRequestException(
                'La réponse du serveur FileMaker ne contient pas de code d\'erreur : format inattendu.',
                $context,
            );
        }

        if ((int) $errorCode === self::ERROR_NO_RECORDS_MATCH) {
            return new FileMakerResultSet(0, []);
        }

        if ((int) $errorCode !== 0) {
            throw new FileMakerRequestException(
                "FileMaker a renvoyé l'erreur {$errorCode}.",
                [...$context, 'filemaker_error' => (int) $errorCode],
                (int) $errorCode,
            );
        }

        $records = [];

        foreach ($xml->xpath('//fm:resultset/fm:record') ?: [] as $recordNode) {
            $records[] = $this->parseRecord($recordNode);
        }

        return new FileMakerResultSet(
            (int) ($this->attribute($xml, '//fm:datasource', 'total-count') ?? 0),
            $records,
        );
    }

    private function parseRecord(SimpleXMLElement $node): FileMakerRecord
    {
        $fields = [];

        foreach ($node->children(self::XML_NAMESPACE)->field as $fieldNode) {
            $values = [];

            foreach ($fieldNode->children(self::XML_NAMESPACE)->data as $dataNode) {
                $values[] = (string) $dataNode;
            }

            $name = (string) $fieldNode->attributes()->name;

            $fields[$name] = count($values) === 1 ? $values[0] : $values;
        }

        return new FileMakerRecord(
            (string) $node->attributes()->{'record-id'},
            (string) $node->attributes()->{'mod-id'},
            $fields,
        );
    }

    private function attribute(SimpleXMLElement $xml, string $path, string $attribute): ?string
    {
        $nodes = $xml->xpath($path);

        if (! is_array($nodes) || $nodes === []) {
            return null;
        }

        $value = $nodes[0]->attributes()->{$attribute} ?? null;

        return $value === null ? null : (string) $value;
    }
}
