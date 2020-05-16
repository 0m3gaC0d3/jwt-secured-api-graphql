<?php

declare(strict_types=1);

/**
 * Bootstrap to run end to end tests.
 */
(function () {
    define('APP_ROOT_PATH', dirname(__DIR__, 3) . '/');
    require APP_ROOT_PATH . 'vendor/autoload.php';
    (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(APP_ROOT_PATH . '/.env.test');
    (new \OmegaCode\JwtSecuredApiCore\Core\Kernel\CliKernel())->run();
})();
