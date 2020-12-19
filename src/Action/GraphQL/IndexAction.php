<?php

/**
 * MIT License
 *
 * Copyright (c) 2020 Wolf Utz<wpu@hotmail.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace OmegaCode\JwtSecuredApiGraphQL\Action\GraphQL;

use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use OmegaCode\JwtSecuredApiCore\Action\AbstractAction;
use OmegaCode\JwtSecuredApiGraphQL\Event\DataLoaderCollectedEvent;
use OmegaCode\JwtSecuredApiGraphQL\Event\GraphQLResponseCreatedEvent;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Context;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Provider\SchemaProviderInterface;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Registry\DataLoaderRegistry;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Utility\DebugUtility;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Validator\RequestValidator;
use OmegaCode\JwtSecuredApiGraphQL\Service\GraphQLErrorFormatterServiceInterface;
use Overblog\DataLoader\Promise\Adapter\Webonyx\GraphQL\SyncPromiseAdapter;
use Overblog\PromiseAdapter\Adapter\WebonyxGraphQLSyncPromiseAdapter;
use Overblog\PromiseAdapter\PromiseAdapterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\EventDispatcher\EventDispatcher;

class IndexAction extends AbstractAction
{
    protected ContainerInterface $container;

    protected SchemaProviderInterface $schemaProvider;

    protected EventDispatcher $eventDispatcher;

    protected DataLoaderRegistry $dataLoaderRegistry;

    protected GraphQLErrorFormatterServiceInterface $errorFormatter;

    public function __construct(
        ContainerInterface $container,
        SchemaProviderInterface $schemaProvider,
        EventDispatcher $eventDispatcher,
        DataLoaderRegistry $dataLoaderRegistry,
        GraphQLErrorFormatterServiceInterface $errorFormatter
    ) {
        $this->container = $container;
        $this->schemaProvider = $schemaProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->dataLoaderRegistry = $dataLoaderRegistry;
        $this->errorFormatter = $errorFormatter;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $debug = DebugUtility::getDebugFlagByEnv();
        $graphQLSyncPromiseAdapter = new SyncPromiseAdapter();
        $promiseAdapter = new WebonyxGraphQLSyncPromiseAdapter($graphQLSyncPromiseAdapter);
        $this->dispatchDataLoaderEvent($promiseAdapter);
        try {
            (new RequestValidator())->validate($request, (array) $request->getParsedBody());
            $schema = $this->schemaProvider->buildSchema();
            $context = $this->buildContext($request);
            $config = ServerConfig::create()
                ->setSchema($schema)
                ->setContext($context)
                ->setQueryBatching(true)
                ->setPromiseAdapter($graphQLSyncPromiseAdapter);
            // Create server.
            $server = new StandardServer($config);
            /** @var Response $response */
            $response = $server->processPsrRequest($request, $response, $response->getBody());
            $response = $this->dispatchGraphQLResponseCreatedEvent($response);

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $exception) {
            $httpStatus = 500;
            $response->getBody()->write((string) json_encode([
                'errors' => $this->errorFormatter->format($exception, $debug),
            ]));

            return $response->withStatus($httpStatus)->withHeader('Content-type', 'application/json');
        }
    }

    protected function buildContext(RequestInterface $request): Context
    {
        $context = new Context();
        $context->setContainer($this->container);
        $context->setRequest($request);
        $context->setDataLoaderRegistry($this->dataLoaderRegistry);

        return $context;
    }

    protected function dispatchDataLoaderEvent(PromiseAdapterInterface $promiseAdapter): void
    {
        $event = new DataLoaderCollectedEvent($this->dataLoaderRegistry);
        $event->setPromiseAdapter($promiseAdapter);
        $this->eventDispatcher->dispatch($event, DataLoaderCollectedEvent::NAME);
    }

    protected function dispatchGraphQLResponseCreatedEvent(Response $response): Response
    {
        $event = new GraphQLResponseCreatedEvent($response);
        /** @var GraphQLResponseCreatedEvent $handledEvent */
        $handledEvent = $this->eventDispatcher->dispatch($event, GraphQLResponseCreatedEvent::NAME);

        return $handledEvent->getResponse();
    }
}
