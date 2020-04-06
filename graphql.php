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

require_once __DIR__ . '/vendor/autoload.php';

use GraphQL\Error\Debug;
use GraphQL\Error\FormattedError;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

(new \Symfony\Component\Dotenv\Dotenv())->loadEnv(__DIR__ . '/.env');

// Disable default PHP error reporting - we have better one for debug mode (see bellow)
ini_set('display_errors', 0);

$debug = false;
if (!empty($_GET['debug'])) {
    set_error_handler(function ($severity, $message, $file, $line) use (&$phpErrors) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    $debug = Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE;
}

\GraphQL\Error\FormattedError::setInternalErrorMessage('Unexpected error');
try {
    $container = new ContainerBuilder();
    $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/res/config'));
    $loader->load('services.xml');
    $container->compile();

    // Prepare context that will be available in all field resolvers (as 3rd argument):
    $appContext = new \OmegaCode\JwtSecuredApiGraphQL\GraphQL\Context();
    $appContext->setContainer($container);

    // Parse incoming query and variables
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true) ?: [];
    } else {
        $data = $_REQUEST;
    }

    $data += ['query' => null, 'variables' => null];

    if ($data['query'] === null) {
        throw new \Exception('Query can not be empty');
    }

    // Prepare resolver registry.
    $resolverRegistry = new \OmegaCode\JwtSecuredApiGraphQL\GraphQL\ResolverRegistry();
    $queryResolver = new \OmegaCode\JwtSecuredApiGraphQL\GraphQL\Resolver\QueryResolver();
    $bookResolver = new \OmegaCode\JwtSecuredApiGraphQL\GraphQL\Resolver\BookResolver();
    $resolverRegistry->addResolver($queryResolver);
    $resolverRegistry->addResolver($bookResolver);
    //...

    // Add resolvers
    $typeConfigDecorator = function (&$typeConfig, $typeDefinitionNode) use ($resolverRegistry) {
        $type = $typeConfig['name'];
        if (!$resolverRegistry->hasResolverForType($type)) {
            return $typeConfig;
        }
        $resolver = $resolverRegistry->getResolverByType($type);
        $typeConfig['resolveField'] = $resolver;

        return $typeConfig;
    };

    // Load schema (note the cached part here as well)
    $cacheDir = __DIR__ . '/var/cache/';
    $schemaFile = __DIR__ . '/res/graphql/schema.graphql';
    $cacheFilename = $cacheDir . 'cached_schema.php';
    if (!file_exists($cacheFilename)) {
        $document = Parser::parse(file_get_contents($schemaFile));
        file_put_contents($cacheFilename, "<?php\nreturn " . var_export(AST::toArray($document), true) . ";\n");
    } else {
        $document = AST::fromArray(require $cacheFilename);
    }
    $schema = BuildSchema::build($document, $typeConfigDecorator);

    $result = GraphQL::executeQuery(
        $schema,
        $data['query'],
        null,
        $appContext,
        (array) $data['variables']
    );
    $output = $result->toArray($debug);
    $httpStatus = 200;
} catch (\Exception $error) {
    $httpStatus = 500;
    $output['errors'] = [
        FormattedError::createFromException($error, $debug),
    ];
}

header('Content-Type: application/json', true, $httpStatus);
echo json_encode($output);
