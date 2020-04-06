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

use GraphQL\Error\FormattedError;
use GraphQL\GraphQL;
use OmegaCode\JwtSecuredApiCore\Action\AbstractAction;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Context;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Provider\SchemaProviderInterface;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Utility\DebugUtility;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Validator\RequestValidator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexAction extends AbstractAction
{
    protected ContainerInterface $container;

    protected SchemaProviderInterface $schemaProvider;

    public function __construct(
        ContainerInterface $container,
        SchemaProviderInterface $schemaProvider
    ) {
        $this->container = $container;
        $this->schemaProvider = $schemaProvider;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $debug = DebugUtility::getDebugFlagByEnv();
        $appContext = new Context();
        $appContext->setContainer($this->container);
        $appContext->setRequest($request);
        try {
            (new RequestValidator())->validate($request, (array) $request->getParsedBody());
            $data = (array) $request->getParsedBody();
            $result = GraphQL::executeQuery(
                $this->schemaProvider->buildSchema(),
                $data['query'] ?? '',
                null,
                $appContext,
                $data['variables'] ?? []
            );
            $output = $result->toArray($debug);
            $httpStatus = 200;
        } catch (\Exception $error) {
            $httpStatus = 500;
            $output['errors'] = [
                FormattedError::createFromException($error, $debug),
            ];
        }
        $response->getBody()->write((string) json_encode($output));
        $response = $response->withStatus($httpStatus)->withHeader('Content-type', 'application/json');

        return $response;
    }
}
