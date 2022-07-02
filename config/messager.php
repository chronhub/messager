<?php

declare(strict_types=1);

return [
    'clock' => \Chronhub\Messager\Support\Clock\UniversalSystemClock::class,

    'messaging' => [
        'factory'    => \Chronhub\Messager\Message\Factory\GenericMessageFactory::class,
        'serializer' => \Chronhub\Messager\Message\Serializer\GenericMessageSerializer::class,
        'alias'      => \Chronhub\Messager\Message\Alias\AliasFromInflector::class,
        'decorators' => [
            \Chronhub\Messager\Message\Decorator\DefaultMessageDecorators::class,
        ],

        // default and sync can not be unset
        'producer' => [
            'default'     => 'sync',

            'sync'        => true,
            'per_message' => [
                'service' => \Chronhub\Messager\Message\Producer\PerMessageProducer::class,
                'queue'   => \Chronhub\Messager\Message\Producer\IlluminateQueue::class,
            ],
            'async'       => [
                // your registered service id (queue would not be used)
                // or the provided one
                'service' => \Chronhub\Messager\Message\Producer\AsyncAllMessageProducer::class,

                // default illuminate queue / nullable
                // or service id
                // or array['connection' => 'my_con , 'queue' => 'my_queue' ]
                'queue'   => \Chronhub\Messager\Message\Producer\IlluminateQueue::class,
            ],
        ],

        'subscribers' => [
            \Chronhub\Messager\Subscribers\MakeMessage::class,
        ],
    ],

    'reporting' => [

        /*
         * Reporter command
         */
        'command' => [
            'default' => [
                'service_id'     => null,
                'concrete'       => null,
                'tracker_id'     => null,
                'handler_method' => 'command',
                'messaging'      => [
                    'decorators'  => [],
                    'subscribers' => [
                        \Chronhub\Messager\Subscribers\LogDomainCommand::class,
                        \Chronhub\Messager\Subscribers\HandleCommand::class,
                    ],
                    'producer'    => 'default',
                ],
                'map'            => [],
            ],
        ],

        /*
         * Reporter event
         */
        'event'   => [
            'default' => [
                'service_id'     => null,
                'concrete'       => null,
                'tracker_id'     => null,
                'handler_method' => 'onEvent',
                'messaging'      => [
                    'decorators'  => [],
                    'subscribers' => [
                        \Chronhub\Messager\Subscribers\HandleEvent::class,
                    ],
                    'producer'    => 'default',
                ],
                'map'            => [],
            ],
        ],

        /*
         * Reporter query
         */
        'query'   => [
            'default' => [
                'service_id'     => null,
                'concrete'       => null,
                'tracker_id'     => null,
                'handler_method' => 'query',
                'messaging'      => [
                    'decorators'  => [],
                    'subscribers' => [
                        \Chronhub\Messager\Subscribers\HandleQuery::class,
                    ],
                    'producer'    => 'default',
                ],
                'map'            => [],
            ],
        ],
    ],
];
