<?php

declare(strict_types=1);

namespace Chronhub\Messager;

use Illuminate\Support\ServiceProvider;
use Chronhub\Messager\Support\Clock\Clock;
use Chronhub\Messager\Support\Facade\Report;
use Chronhub\Messager\Message\Alias\MessageAlias;
use Chronhub\Messager\Support\Facade\AliasMessage;
use Illuminate\Contracts\Support\DeferrableProvider;
use Chronhub\Messager\Message\Factory\MessageFactory;
use Chronhub\Messager\Message\Serializer\MessageSerializer;

final class MessagerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    private string $messagerPath = __DIR__.'/../config/messager.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([$this->messagerPath => config_path('messager.php')]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->messagerPath, 'messager');

        $this->app->singleton(MessagerManager::class);
        $this->app->alias(MessagerManager::class, Report::SERVICE_NAME);

        $config = config('messager');

        $this->app->bind(Clock::class, $config['clock']);

        $message = $config['messaging'];

        $this->app->bind(MessageFactory::class, $message['factory']);
        $this->app->bind(MessageSerializer::class, $message['serializer']);

        $this->app->bind(MessageAlias::class, $message['alias']);
        $this->app->alias(MessageAlias::class, AliasMessage::SERVICE_NAME);
    }

    public function provides(): array
    {
        return [
            MessagerManager::class,
            MessageFactory::class,
            MessageSerializer::class,
            MessageAlias::class,
            AliasMessage::SERVICE_NAME,
            Report::SERVICE_NAME,
        ];
    }
}
