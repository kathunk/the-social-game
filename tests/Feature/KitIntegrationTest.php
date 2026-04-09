<?php

use App\Events\UserCreated;
use App\Events\UserSubscribedToNewsletter;
use App\Events\UserUnsubscribedFromNewsletter;
use App\Jobs\SubscribeUserToKit;
use App\Jobs\UnsubscribeUserFromKit;
use App\Models\User;
use App\Services\KitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Thunk\Verbs\Facades\Verbs;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.kit.api_key' => 'kit_test_key',
        'services.kit.form_id' => '12345',
    ]);
});

it('dispatches a Kit subscribe job when a user subscribes to the newsletter', function () {
    Bus::fake();
    Verbs::commitImmediately();

    $user_id = UserCreated::fire(
        name: 'Jane Doe',
        email: 'jane@example.com',
        encrypted_password: bcrypt('password'),
    )->user_id;

    UserSubscribedToNewsletter::fire(user_id: $user_id);

    Bus::assertDispatched(SubscribeUserToKit::class, function ($job) {
        return $job->email === 'jane@example.com'
            && $job->name === 'Jane Doe';
    });
});

it('dispatches a Kit unsubscribe job when a user unsubscribes from the newsletter', function () {
    Bus::fake();
    Verbs::commitImmediately();

    $user_id = UserCreated::fire(
        name: 'Jane Doe',
        email: 'jane@example.com',
        encrypted_password: bcrypt('password'),
    )->user_id;

    UserUnsubscribedFromNewsletter::fire(user_id: $user_id);

    Bus::assertDispatched(UnsubscribeUserFromKit::class, function ($job) {
        return $job->email === 'jane@example.com';
    });
});

it('KitService::subscribe posts to the Kit form subscriber endpoint', function () {
    Http::fake([
        'api.kit.com/v4/forms/12345/subscribers' => Http::response(['subscriber' => ['id' => 1]], 200),
    ]);

    $service = new KitService;
    $result = $service->subscribe('test@example.com', 'Test User');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.kit.com/v4/forms/12345/subscribers'
            && $request['email_address'] === 'test@example.com'
            && $request['first_name'] === 'Test User'
            && $request->hasHeader('X-Kit-Api-Key', 'kit_test_key');
    });
});

it('KitService gracefully no-ops when not configured', function () {
    config([
        'services.kit.api_key' => null,
        'services.kit.form_id' => null,
    ]);
    Http::fake();

    $service = new KitService;

    expect($service->isConfigured())->toBeFalse();
    expect($service->subscribe('x@example.com'))->toBeFalse();
    expect($service->unsubscribe('x@example.com'))->toBeFalse();

    Http::assertNothingSent();
});

it('KitService::subscribe returns false on Kit API failure', function () {
    Http::fake([
        'api.kit.com/v4/forms/12345/subscribers' => Http::response(['error' => 'bad'], 422),
    ]);

    $service = new KitService;
    expect($service->subscribe('test@example.com'))->toBeFalse();
});
