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
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\ResolverRegistry;

class SchemaProvider implements SchemaProviderInterface
{
    protected const CACHE_DIR = __DIR__ . '/../../var/cache/';

    protected const SCHEMA_FILE = __DIR__ . '/../../res/graphql/schema.graphql';

    protected const CACHE_FILE_NAME = 'cached_schema.php';

    private ResolverRegistry $resolverRegistry;

    public function __construct(ResolverRegistry $resolverRegistry)
    {
        $this->resolverRegistry = $resolverRegistry;
    }

    public function buildSchema(): Schema
    {
        /** @var DocumentNode $source */
        $source = $this->getSchemaSource();

        return BuildSchema::build($source, $this->buildTypeConfiguration());
    }

    private function getSchemaSource(): Node
    {
        $cacheFilePath = static::CACHE_DIR . static::CACHE_FILE_NAME;
        if (!file_exists($cacheFilePath)) {
            $document = Parser::parse((string) file_get_contents(static::SCHEMA_FILE));
            file_put_contents(
                $cacheFilePath,
                "<?php\nreturn " . var_export(AST::toArray($document), true) . ";\n"
            );

            return $document;
        }

        return AST::fromArray(require static::CACHE_DIR . static::CACHE_FILE_NAME);
    }

    private function buildTypeConfiguration(): callable
    {
        $resolverRegistry = $this->resolverRegistry;
        $typeConfigDecorator = function (&$typeConfig) use ($resolverRegistry) {
            $type = $typeConfig['name'];
            if (!$resolverRegistry->hasResolverForType($type)) {
                return $typeConfig;
            }
            $resolver = $resolverRegistry->getResolverByType($type);
            $typeConfig['resolveField'] = $resolver;

            return $typeConfig;
        };

        return $typeConfigDecorator;
    }
}
