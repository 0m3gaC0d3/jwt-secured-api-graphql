![alt text](https://travis-ci.org/0m3gaC0d3/jwt-secured-api-graphql.svg?branch=master "Build status")

# GraphQL integration for JWT secured API Core
...

## Requirements
* PHP 7.4+
* composer
* openssl
* PHP extension ext-json

## Integrate GraphQL

### Add a subscriber

*config/services.yaml*

```yaml
  Vendor\MyProject\GraphQLResolverSubscriber:
    arguments:
      - '@service_container'
    tags:
      - 'kernel.event_subscriber'
```

*src/Subscriber/GraphQLResolverSubscriber.php*

```php
<?php
declare(strict_types=1);

namespace Vendor\MyProject\Subscriber;

use OmegaCode\JwtSecuredApiGraphQL\Event\ResolverCollectedEvent;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Registry\ResolverRegistry;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Resolver\ResolverInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vendor\MyProject\QueryResolver;

class GraphQLResolverSubscriber implements EventSubscriberInterface
{
    protected const RESOLVER_CLASSES = [
        QueryResolver::class
    ];

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResolverCollectedEvent::NAME => 'onCollected',
        ];
    }

    public function onCollected(ResolverCollectedEvent $event): void
    {
        $registry = $event->getResolverRegistry();
        $registry->clear();
        $this->addResolvers($registry);
    }

    protected function addResolvers(ResolverRegistry $registry): void
    {
        foreach (static::RESOLVER_CLASSES as $resolverClass) {
            /** @var ResolverInterface $resolverInstance */
            $resolverInstance = $this->container->get($resolverClass);
            $registry->add($resolverInstance, $resolverInstance->getType());
        }
    }
}
```

### Add a resolver

*src/GraphQL/Resolver/QueryResolver.php*

```php
<?php

declare(strict_types=1);

namespace Vendor\MyProject\GraphQL\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Context;
use OmegaCode\JwtSecuredApiGraphQL\GraphQL\Resolver\ResolverInterface;

class QueryResolver implements ResolverInterface
{
    public function __invoke($root, array $args, Context $context, ResolveInfo $info): ?string
    {
        if ($info->fieldName === 'greet') {
            $name = strip_tags($args['name']);

            return "Hello $name";
        }

        return null;
    }

    public function getType(): string
    {
        return 'Query';
    }
}
```

### Add schema

*res/graphql/schema.graphql*

```graphql
schema {
    query: Query
}

type Query {
    greet(name: String!): String
}
```

### Update .env

Add ``ENABLE_GRAPHQL_SCHEMA_CACHE="1"`` to .env files.
