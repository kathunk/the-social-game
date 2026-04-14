<?php

use App\Events\UserCreated;
use App\Events\UserSubscribedToNewsletter;
use App\Jobs\SubscribeUserToNewsletter;
use App\Services\CatacombianNewsletterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Thunk\Verbs\Facades\Verbs;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.catacombian.newsletter_token' => 'test-token',
    ]);
});

it('dispatches a newsletter subscribe job when a user subscribes', function () {
    Bus::fake();
    Verbs::commitImmediately();

    $user_id = UserCreated::fire(
        name: 'Jane Doe',
        email: 'jane@example.com',
        encrypted_password: bcrypt('password'),
    )->user_id;

    UserSubscribedToNewsletter::fire(user_id: $user_id);

    Bus::assertDispatched(SubscribeUserToNewsletter::class, function ($job) {
        return $job->email === 'jane@example.com'
            && $job->name === 'Jane Doe';
    });
});

it('posts the subscriber to catacombian with the right payload and bearer token', function () {
    Http::fake([
        'catacombian.com/api/newsletter/subscribers' => Http::response(['status' => 'created'], 201),
    ]);

    $service = new CatacombianNewsletterService;
    expect($service->subscribe('test@example.com', 'Test User'))->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://catacombian.com/api/newsletter/subscribers'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request->hasHeader('Accept', 'application/json')
            && $request['email'] === 'test@example.com'
            && $request['name'] === 'Test User'
            && $request['source'] === 'the-social-game';
    });
});

it('treats 200 already_subscribed as success', function () {
    Http::fake([
        'catacombian.com/api/newsletter/subscribers' => Http::response(['status' => 'already_subscribed'], 200),
    ]);

    $service = new CatacombianNewsletterService;
    expect($service->subscribe('test@example.com'))->toBeTrue();
});

it('does not throw and returns false on non-2xx responses', function () {
    Http::fake([
        'catacombian.com/api/newsletter/subscribers' => Http::response(['error' => 'bad email'], 422),
    ]);

    $service = new CatacombianNewsletterService;
    expect($service->subscribe('not-an-email'))->toBeFalse();
});

it('does not throw on 401 bad token', function () {
    Http::fake([
        'catacombian.com/api/newsletter/subscribers' => Http::response(['error' => 'unauthorized'], 401),
    ]);

    $service = new CatacombianNewsletterService;
    expect($service->subscribe('test@example.com'))->toBeFalse();
});

it('no-ops when the token is not configured', function () {
    config(['services.catacombian.newsletter_token' => null]);
    Http::fake();

    $service = new CatacombianNewsletterService;

    expect($service->isConfigured())->toBeFalse();
    expect($service->subscribe('x@example.com'))->toBeFalse();

    Http::assertNothingSent();
});

it('signup still succeeds when the newsletter HTTP call fails', function () {
    Http::fake([
        'catacombian.com/api/newsletter/subscribers' => Http::response(['error' => 'boom'], 500),
    ]);
    Verbs::commitImmediately();

    $user_id = UserCreated::fire(
        name: 'Jane Doe',
        email: 'jane@example.com',
        encrypted_password: bcrypt('password'),
    )->user_id;

    UserSubscribedToNewsletter::fire(user_id: $user_id);

    expect(true)->toBeTrue();
});
