<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InnoGE\LaravelMsGraphMail\Exceptions\ConfigurationMissing;
use InnoGE\LaravelMsGraphMail\Tests\Stubs\TestMail;
use InnoGE\LaravelMsGraphMail\Tests\Stubs\TestMailWithInlineImage;

it('sends html mails with microsoft graph', function () {
    Config::set('mail.mailers.microsoft-graph', [
        'transport' => 'microsoft-graph',
        'client_id' => 'foo_client_id',
        'client_secret' => 'foo_client_secret',
        'tenant_id' => 'foo_tenant_id',
        'from' => [
            'address' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ],
    ]);
    Config::set('mail.default', 'microsoft-graph');

    Cache::set('microsoft-graph-api-access-token', 'foo_access_token', 3600);

    Http::fake();

    Mail::to('caleb@livewire.com')
        ->bcc('tim@innoge.de')
        ->cc('nuno@laravel.com')
        ->send(new TestMail());

    Http::assertSent(function (Request $value) {
        expect($value)
            ->url()->toBe('https://graph.microsoft.com/v1.0/users/taylor@laravel.com/sendMail')
            ->hasHeader('Authorization', 'Bearer foo_access_token')->toBeTrue()
            ->body()->json()->toBe([
                'message' => [
                    'subject' => 'Dev Test',
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => '<b>Test</b>'.PHP_EOL,
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => 'caleb@livewire.com',
                            ],
                        ],
                    ],
                    'ccRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => 'nuno@laravel.com',
                            ],
                        ],
                    ],
                    'bccRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => 'tim@innoge.de',
                            ],
                        ],
                    ],
                    'replyTo' => [],
                    'sender' => [
                        'emailAddress' => [
                            'address' => 'taylor@laravel.com',
                        ],
                    ],
                    'attachments' => [
                        [
                            '@odata.type' => '#microsoft.graph.fileAttachment',
                            'name' => 'test-file-1.txt',
                            'contentType' => 'text',
                            'contentBytes' => 'Zm9vCg==',
                            'contentId' => 'test-file-1.txt',
                            'isInline' => false,
                        ],
                        [
                            '@odata.type' => '#microsoft.graph.fileAttachment',
                            'name' => 'test-file-2.txt',
                            'contentType' => 'text',
                            'contentBytes' => 'Zm9vCg==',
                            'contentId' => 'test-file-2.txt',
                            'isInline' => false,
                        ],
                    ],
                ],
                'saveToSentItems' => false,
            ]);

        return true;
    });
});

it('sends text mails with microsoft graph', function () {
    Config::set('mail.mailers.microsoft-graph', [
        'transport' => 'microsoft-graph',
        'client_id' => 'foo_client_id',
        'client_secret' => 'foo_client_secret',
        'tenant_id' => 'foo_tenant_id',
        'from' => [
            'address' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ],
    ]);
    Config::set('mail.default', 'microsoft-graph');

    Cache::set('microsoft-graph-api-access-token', 'foo_access_token', 3600);

    Http::fake();

    Mail::to('caleb@livewire.com')
        ->bcc('tim@innoge.de')
        ->cc('nuno@laravel.com')
        ->send(new TestMail(false));

    Http::assertSent(function (Request $value) {
        expect($value)
            ->url()->toBe('https://graph.microsoft.com/v1.0/users/taylor@laravel.com/sendMail')
            ->hasHeader('Authorization', 'Bearer foo_access_token')->toBeTrue()
            ->body()->json()->toBe([
                'message' => [
                    'subject' => 'Dev Test',
                    'body' => [
                        'contentType' => 'Text',
                        'content' => 'Test'.PHP_EOL,
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => 'caleb@livewire.com',
                            ],
                        ],
                    ],
                    'ccRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => 'nuno@laravel.com',
                            ],
                        ],
                    ],
                    'bccRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => 'tim@innoge.de',
                            ],
                        ],
                    ],
                    'replyTo' => [],
                    'sender' => [
                        'emailAddress' => [
                            'address' => 'taylor@laravel.com',
                        ],
                    ],
                    'attachments' => [
                        [
                            '@odata.type' => '#microsoft.graph.fileAttachment',
                            'name' => 'test-file-1.txt',
                            'contentType' => 'text',
                            'contentBytes' => 'Zm9vCg==',
                            'contentId' => 'test-file-1.txt',
                            'isInline' => false,
                        ],
                        [
                            '@odata.type' => '#microsoft.graph.fileAttachment',
                            'name' => 'test-file-2.txt',
                            'contentType' => 'text',
                            'contentBytes' => 'Zm9vCg==',
                            'contentId' => 'test-file-2.txt',
                            'isInline' => false,
                        ],
                    ],
                ],
                'saveToSentItems' => false,
            ]);

        return true;
    });
});

it('creates an oauth access token', function () {
    Config::set('mail.mailers.microsoft-graph', [
        'transport' => 'microsoft-graph',
        'client_id' => 'foo_client_id',
        'client_secret' => 'foo_client_secret',
        'tenant_id' => 'foo_tenant_id',
        'from' => [
            'address' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ],
    ]);
    Config::set('mail.default', 'microsoft-graph');

    Http::fake([
        'https://login.microsoftonline.com/foo_tenant_id/oauth2/v2.0/token' => Http::response(['access_token' => 'foo_access_token']),
        'https://graph.microsoft.com/v1.0*' => Http::response(['value' => []]),
    ]);

    Mail::to('caleb@livewire.com')
        ->send(new TestMail(false));

    Http::assertSent(function (Request $request) {
        if (Str::startsWith($request->url(), 'https://login.microsoftonline.com')) {
            expect($request)
                ->url()->toBe('https://login.microsoftonline.com/foo_tenant_id/oauth2/v2.0/token')
                ->isForm()->toBeTrue()
                ->body()->toBe('grant_type=client_credentials&client_id=foo_client_id&client_secret=foo_client_secret&scope=https%3A%2F%2Fgraph.microsoft.com%2F.default');
        }

        return true;
    });

    expect(Cache::get('microsoft-graph-api-access-token'))
        ->toBe('foo_access_token');
});

it('throws exceptions when config is missing', function (array $config, string $exceptionMessage) {
    Config::set('mail.mailers.microsoft-graph', $config);
    Config::set('mail.default', 'microsoft-graph');

    try {
        Mail::to('caleb@livewire.com')
            ->send(new TestMail(false));
    } catch (Exception $e) {
        expect($e)
            ->toBeInstanceOf(ConfigurationMissing::class)
            ->getMessage()->toBe($exceptionMessage);
    }
})->with(
    [
        [
            [
                'transport' => 'microsoft-graph',
                'client_id' => 'foo_client_id',
                'client_secret' => 'foo_client_secret',
                'tenant_id' => '',
                'from' => [
                    'address' => 'taylor@laravel.com',
                    'name' => 'Taylor Otwell',
                ],
            ],
            'The tenant id is missing from the configuration file.',
        ],
        [
            [
                'transport' => 'microsoft-graph',
                'client_id' => '',
                'client_secret' => 'foo_client_secret',
                'tenant_id' => 'foo_tenant_id',
                'from' => [
                    'address' => 'taylor@laravel.com',
                    'name' => 'Taylor Otwell',
                ],
            ],
            'The client id is missing from the configuration file.',
        ],
        [
            [
                'transport' => 'microsoft-graph',
                'client_id' => 'foo_client_id',
                'client_secret' => '',
                'tenant_id' => 'foo_tenant_id',
                'from' => [
                    'address' => 'taylor@laravel.com',
                    'name' => 'Taylor Otwell',
                ],
            ],
            'The client secret is missing from the configuration file.',
        ],
        [
            [
                'transport' => 'microsoft-graph',
                'client_id' => 'foo_client_id',
                'client_secret' => 'foo_client_secret',
                'tenant_id' => 'foo_tenant_id',
            ],
            'The mail from address is missing from the configuration file.',
        ],
    ]);

it('sends html mails with inline images with microsoft graph', function () {
    Config::set('mail.mailers.microsoft-graph', [
        'transport' => 'microsoft-graph',
        'client_id' => 'foo_client_id',
        'client_secret' => 'foo_client_secret',
        'tenant_id' => 'foo_tenant_id',
        'from' => [
            'address' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ],
    ]);
    Config::set('mail.default', 'microsoft-graph');
    Config::set('filesystems.default', 'local');
    Config::set('filesystems.disks.local.root', realpath(__DIR__.'/Resources/files'));

    Cache::set('microsoft-graph-api-access-token', 'foo_access_token', 3600);

    Http::fake();

    Mail::to('caleb@livewire.com')
        ->bcc('tim@innoge.de')
        ->cc('nuno@laravel.com')
        ->send(new TestMailWithInlineImage());

    Http::assertSent(function (Request $value) {
        // ContentId gets random generated, so get this value first and check for equality later
        $inlineImageContentId = json_decode($value->body())->message->attachments[1]->contentId;

        expect($value)
            ->url()->toBe('https://graph.microsoft.com/v1.0/users/taylor@laravel.com/sendMail')
            ->hasHeader('Authorization', 'Bearer foo_access_token')->toBeTrue()
            ->body()->json()->toBe([
                'message' => [
                    'subject' => 'Dev Test',
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => '<b>Test</b><img src="cid:' . $inlineImageContentId . '">'.PHP_EOL,
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => 'caleb@livewire.com',
                            ],
                        ],
                    ],
                    'ccRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => 'nuno@laravel.com',
                            ],
                        ],
                    ],
                    'bccRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => 'tim@innoge.de',
                            ],
                        ],
                    ],
                    'replyTo' => [],
                    'sender' => [
                        'emailAddress' => [
                            'address' => 'taylor@laravel.com',
                        ],
                    ],
                    'attachments' => [
                        [
                            '@odata.type' => '#microsoft.graph.fileAttachment',
                            'name' => 'test-file-1.txt',
                            'contentType' => 'text',
                            'contentBytes' => 'Zm9vCg==',
                            'contentId' => 'test-file-1.txt',
                            'isInline' => false,
                        ],
                        [
                            '@odata.type' => '#microsoft.graph.fileAttachment',
                            'name' => $inlineImageContentId,
                            'contentType' => 'image',
                            'contentBytes' => '/9j/4AAQSkZJRgABAQEASABIAAD//gATQ3JlYXRlZCB3aXRoIEdJTVD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQMEBAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT/wgARCABLAGQDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAj/xAAWAQEBAQAAAAAAAAAAAAAAAAAABQj/2gAMAwEAAhADEAAAAZ71TDAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAABw/9oACAEBAAEFAgL/xAAUEQEAAAAAAAAAAAAAAAAAAABw/9oACAEDAQE/AQL/xAAUEQEAAAAAAAAAAAAAAAAAAABw/9oACAECAQE/AQL/xAAUEAEAAAAAAAAAAAAAAAAAAABw/9oACAEBAAY/AgL/xAAUEAEAAAAAAAAAAAAAAAAAAABw/9oACAEBAAE/IQL/2gAMAwEAAgADAAAAEEkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkv/xAAUEQEAAAAAAAAAAAAAAAAAAABw/9oACAEDAQE/EAL/xAAUEQEAAAAAAAAAAAAAAAAAAABw/9oACAECAQE/EAL/xAAUEAEAAAAAAAAAAAAAAAAAAABw/9oACAEBAAE/EAL/2Q==',
                            'contentId' => $inlineImageContentId,
                            'isInline' => true,
                        ],
                    ],
                ],
                'saveToSentItems' => false,
            ]);

        return true;
    });
});
