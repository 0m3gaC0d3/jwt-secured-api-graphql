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

namespace OmegaCode\JwtSecuredApiGraphQL\Service;

use Exception;
use GraphQL\Error\FormattedError;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Exception\ClientAwareInterface;

class GraphQLErrorFormatterService extends FormattedError implements GraphQLErrorFormatterServiceInterface
{
    /**
     * @param bool|int $debugFlags
     * @param ?string  $internalErrorMessage
     */
    public function format(Exception $exception, $debugFlags = false, ?string $internalErrorMessage = null): array
    {
        return [
            $this->formatException($exception, $debugFlags),
        ];
    }

    /**
     * @param bool|int $debugFlags
     * @param ?string  $internalErrorMessage
     */
    protected static function formatException(Exception $e, $debugFlags, ?string $internalErrorMessage = null): array
    {
        $formattedError = parent::createFromException($e, $debugFlags, $internalErrorMessage);
        if ($e instanceof ClientAwareInterface && $e->isClientSafe()) {
            $formattedError['type'] = $e->getExceptionType();
        }

        return $formattedError;
    }
}
