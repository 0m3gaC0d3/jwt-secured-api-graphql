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

namespace OmegaCode\JwtSecuredApiGraphQL\GraphQL;

use InvalidArgumentException;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Resolver\ResolverInterface;

class ResolverRegistry
{
    /**
     * @var array<ResolverInterface>
     */
    private array $resolvers = [];

    public function addResolver(ResolverInterface $resolver): void
    {
        if (empty($resolver->getType())) {
            throw new InvalidArgumentException('Method getType of given resolver can not be empty!');
        }
        if (!is_callable($resolver)) {
            throw new InvalidArgumentException('The given resolver ' . $resolver->getType() . ' is not callable! Add method __invoke to the resolver');
        }
        $this->resolvers[$resolver->getType()] = $resolver;
    }

    public function removeResolver(string $type): void
    {
        if (array_key_exists($type, $this->resolvers)) {
            unset($this->resolvers[$type]);
        }
    }

    public function getResolverByType(string $type): ResolverInterface
    {
        $resolver = $this->resolvers[$type] ?? null;
        if (!$resolver instanceof ResolverInterface) {
            throw new InvalidArgumentException("There is no resolver with type $type registered!");
        }

        return $resolver;
    }

    public function hasResolverForType(string $type): bool
    {
        $resolver = $this->resolvers[$type] ?? null;

        return $resolver instanceof ResolverInterface;
    }

    public function clear(): void
    {
        $this->resolvers = [];
    }
}
