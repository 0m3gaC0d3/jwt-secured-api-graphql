<?php

declare(strict_types=1);

use OmegaCode\JwtSecuredApiCore\Core\Kernel\HttpKernel;

(function () {
    require __DIR__ . '/../vendor/autoload.php';
    $envFile = $_ENV['APPLICATION_ENVIRONMENT'] === 'test' ? '.env.test' : '.env';
    (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(__DIR__ . '/../' . $envFile);
    define('APP_ROOT_PATH', dirname(__DIR__, 1) . '/');
    (new HttpKernel())->run();
})();
