<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

final class SearchIndexUnavailable extends ServiceUnavailableHttpException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(3, 'Search is temporarily unavailable.', $previous);
    }
}
