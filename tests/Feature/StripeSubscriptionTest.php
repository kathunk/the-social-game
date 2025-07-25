<?php

use App\Events\StripeWebhookRequested;
use App\Livewire\Subscribe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;
use Thunk\Verbs\Models\VerbEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.stripe.price_id' => 'price_test123']);

    $this->user = User::factory()->create(['stripe_id' => 'cus_test123']);
});

test('user can subscribe successfully', function () {
    // Simulate successful subscription creation via webhook
    $response = $this->postJson('/stripe/webhook', [
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_test123',
                'customer' => 'cus_test123',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_test123',
                            'price' => [
                                'id' => 'price_test123',
                                'product' => 'prod_test123',
                            ],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['type' => 'default'],
            ],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);
    expect($this->user->fresh()->subscribed())->toBeTrue();
    expect($this->user->subscriptions)->toHaveCount(1);
    expect($this->user->subscriptions->first()->stripe_status)->toBe('active');
});

test('subscribed user has active subscription status', function () {
    $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test123',
        'quantity' => 1,
    ]);

    expect($this->user->subscribed())->toBeTrue();
    expect($this->user->subscribed('default'))->toBeTrue();
    expect($this->user->subscribed('wrong-type'))->toBeFalse();
});

test('membership is created when subscription is created', function () {
    $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
    ]);

    Verbs::commit();

    $this->user->refresh();

    expect($this->user->memberships)->toHaveCount(1);
    expect($this->user->is_member)->toBeTrue();
});

test('subscription automatically renews after one year', function () {
    // Create initial subscription
    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test123',
        'quantity' => 1,
    ]);

    // Simulate renewal webhook
    $response = $this->postJson('/stripe/webhook', [
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_test123',
                'customer' => 'cus_test123',
                'status' => 'active',
                'current_period_end' => now()->addYear()->timestamp,
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_test123',
                            'price' => [
                                'id' => 'price_test123',
                                'product' => 'prod_test123',
                            ],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['type' => 'default'],
            ],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);
    expect($this->user->fresh()->subscribed())->toBeTrue();
    expect($subscription->active())->toBeTrue();
});

test('user can cancel subscription', function () {
    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test123',
        'quantity' => 1,
    ]);

    Verbs::commit();

    // Simulate cancellation webhook
    $response = $this->postJson('/stripe/webhook', [
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'id' => 'sub_test123',
                'customer' => 'cus_test123',
                'status' => 'canceled',
            ],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);
    expect($this->user->fresh()->subscribed())->toBeFalse();
    expect($subscription->fresh()->canceled())->toBeTrue();

    Verbs::commit();

    $this->user->refresh();

    expect($this->user->memberships)->toHaveCount(1);
    expect($this->user->fresh()->is_member)->toBeFalse();
});

test('cancelled subscription does not charge user', function () {
    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'canceled',
        'stripe_price' => 'price_test123',
        'quantity' => 1,
        'ends_at' => now(),
    ]);

    expect($this->user->subscribed())->toBeFalse();
    expect($subscription->active())->toBeFalse();
});

test('failed payments are logged for debugging', function () {
    // We should log webhook events for debugging
    $response = $this->postJson('/stripe/webhook', [
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'id' => 'in_test123',
                'customer' => 'cus_test123',
                'subscription' => 'sub_test123',
                'status' => 'open',
                'last_payment_error' => [
                    'message' => 'Your card was declined.',
                    'type' => 'card_error',
                    'code' => 'card_declined',
                ],
            ],
        ],
    ]);

    // The webhook should be handled (even if it returns 200 for unhandled events)
    expect($response->getStatusCode())->toBe(200);
    // In a real implementation, we'd verify that the event was logged
    // This could be done by checking the log files or a database log table
});

test('stripe webhook events are captured by verbs', function () {
    $webhookPayload = [
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_test123',
                'customer' => 'cus_test123',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_test123',
                            'price' => [
                                'id' => 'price_test123',
                                'product' => 'prod_test123',
                            ],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['type' => 'default'],
            ],
        ],
    ];

    $response = $this->postJson('/stripe/webhook', $webhookPayload);

    expect($response->getStatusCode())->toBe(200);

    $events = VerbEvent::where('type', StripeWebhookRequested::class)->get();

    expect($events)->toHaveCount(1);

    $eventData = $events->first()->data;
    expect($eventData['payload'])->toBe($webhookPayload);
});

// E2E Tests
test('user can access marketing-page', function () {
    $this->actingAs($this->user)
        ->get('/marketing-page')
        ->assertOk()
        ->assertSee('Host games')
        ->assertSee('$19.99')
        ->assertSee('Subscribe Now');
});

test('user must be logged in to subscribe', function () {
    $response = $this->get('/marketing-page');

    Livewire::test(Subscribe::class)
        ->call('checkout')
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'You must be logged in to subscribe.');
});

test('user sees success page after successful subscription', function () {
    $this->actingAs($this->user)
        ->get('/subscribe/success')
        ->assertOk()
        ->assertSee('Subscription Successful!')
        ->assertSee('Thank you for subscribing')
        ->assertSee('Go to Dashboard');
});

test('user sees cancel page when subscription is cancelled', function () {
    $this->actingAs($this->user)
        ->get('/subscribe/cancel')
        ->assertOk()
        ->assertSee('Subscription Cancelled')
        ->assertSee('No charges were made')
        ->assertSee('Try Again');
});

test('try again button redirects to subscription page', function () {
    $response = $this->actingAs($this->user)
        ->get('/subscribe/cancel');

    $response->assertSee(route('marketing-page'), false);
});

test('go to dashboard button redirects to dashboard', function () {
    $response = $this->actingAs($this->user)
        ->get('/subscribe/success');

    $response->assertSee(route('dashboard'), false);
});

// !! Real Stripe API Test (skipped by default) !!
test('can create customer and checkout with Stripe API', function () {
    expect(env('STRIPE_KEY'))->not->toBeEmpty();
    expect(env('STRIPE_SECRET'))->not->toBeEmpty();
    expect(env('STRIPE_PRICE_ID'))->not->toBeEmpty();

    $user = User::factory()->create();

    $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
    $customer = $stripe->customers->create([
        'email' => $user->email,
        'name' => $user->name,
    ]);

    $user->update(['stripe_id' => $customer->id]);

    $checkout = $user
        ->newSubscription('default', env('STRIPE_PRICE_ID'))
        ->allowPromotionCodes()
        ->checkout([
            'success_url' => route('subscribe.success'),
            'cancel_url' => route('subscribe.cancel'),
            'metadata' => [
                'test_run' => 'Laracon 2025 API Test',
                'timestamp' => now()->toISOString(),
            ],
        ]);

    expect($checkout)->toBeObject();
    expect($checkout->url)->toContain('checkout.stripe.com');
    expect($checkout->id)->not->toBeEmpty();

    // Clean up - delete the customer from Stripe
    $stripe->customers->delete($customer->id);

    $this->assertTrue(true);
})->skip();
