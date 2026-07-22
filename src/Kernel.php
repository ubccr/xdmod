<?php

declare(strict_types=1);

namespace CCR;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 *
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Gets the application root dir (path of the project's composer file).
     *
     * @return string
     */
    public function getProjectDir(): string
    {
        return BASE_DIR;
    }
}
