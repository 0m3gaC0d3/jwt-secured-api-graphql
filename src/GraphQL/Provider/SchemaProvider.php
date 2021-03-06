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

namespace OmegaCode\JwtSecuredApiGraphQL\GraphQL\Provider;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Registry\ResolverRegistry;

class SchemaProvider implements SchemaProviderInterface
{
    protected const SCHEMA_FILE = APP_ROOT_PATH . '/res/graphql/schema.graphql';

    private ResolverRegistry $resolverRegistry;

    public function __construct(ResolverRegistry $resolverRegistry)
    {
        $this->resolverRegistry = $resolverRegistry;
    }

    public function buildSchema(): Schema
    {
        /** @var DocumentNode $source */
        $source = $this->getSchemaSource();

        return BuildSchema::build($source, $this->buildTypeConfiguration(), ['commentDescriptions' => true]);
    }

    private function getSchemaSource(): Node
    {
        $cacheFilePath = static::CACHE_DIR . static::CACHE_FILE_NAME;
        // @TODO Use symfony cache to store AST
        if (file_exists($cacheFilePath) && (bool) $_ENV['ENABLE_GRAPHQL_SCHEMA_CACHE']) {
            // Load AST from cached PHP file: var/cache/cached_schema.php
            return AST::fromArray(require $cacheFilePath);
        }
        $document = Parser::parse((string) file_get_contents(static::SCHEMA_FILE));
        if ((bool) $_ENV['ENABLE_GRAPHQL_SCHEMA_CACHE']) {
            // Save AST as PHP file: var/cache/cached_schema.php
            file_put_contents(
                $cacheFilePath,
                "<?php\nreturn " . var_export(AST::toArray($document), true) . ";\n"
            );
        }

        return $document;
    }

    private function buildTypeConfiguration(): callable
    {
        $resolverRegistry = $this->resolverRegistry;
        $typeConfigDecorator = function (&$typeConfig) use ($resolverRegistry) {
            $type = $typeConfig['name'];
            if (!$resolverRegistry->has($type)) {
                return $typeConfig;
            }
            $resolver = $resolverRegistry->get($type);
            $typeConfig['resolveField'] = $resolver;

            return $typeConfig;
        };

        return $typeConfigDecorator;
    }
}
