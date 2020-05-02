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

namespace OmegaCode\JwtSecuredApiGraphQL\GraphQL\Registry;

use InvalidArgumentException;

abstract class AbstractRegistry
{
    protected array $items = [];

    protected string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @param mixed $item
     */
    public function add($item, string $key): void
    {
        if (!$item instanceof $this->type) {
            throw new InvalidArgumentException("The given item is not of configuraed type \"$this->type\"");
        }
        if (array_key_exists($key, $this->items)) {
            throw new InvalidArgumentException("The given key \"$key\" already is present");
        }
        $this->items[$key] = $item;
    }

    public function remove(string $key): void
    {
        if (!array_key_exists($key, $this->items)) {
            throw new InvalidArgumentException("The given key \"$key\" does not exist");
        }
        unset($this->items[$key]);
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        if (!array_key_exists($key, $this->items)) {
            throw new InvalidArgumentException("The given key \"$key\" does not exist");
        }

        return $this->items[$key];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function clear(): void
    {
        $this->items = [];
    }
}
