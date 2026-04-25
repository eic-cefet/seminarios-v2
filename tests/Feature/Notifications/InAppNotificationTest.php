<?php

use App\Models\User;
use App\Notifications\InAppNotification;

class FakeInAppNotification extends InAppNotification
{
    public function __construct(private string $t, private string $b, private ?string $u = null) {}

    protected function title(): string
    {
        return $this->t;
    }

    protected function body(): string
    {
        return $this->b;
    }

    protected function actionUrl(): ?string
    {
        return $this->u;
    }

    protected function category(): string
    {
        return 'fake';
    }
}

it('serializes database payload with title, body, category, action_url', function () {
    $user = User::factory()->create();
    $notification = new FakeInAppNotification('Hi', 'There', '/go');

    $payload = $notification->toDatabase($user);

    expect($payload)->toMatchArray([
        'category' => 'fake',
        'title' => 'Hi',
        'body' => 'There',
        'action_url' => '/go',
    ]);
});

it('uses the database channel only', function () {
    $user = User::factory()->create();
    $notification = new FakeInAppNotification('Hi', 'There');

    expect($notification->via($user))->toBe(['database']);
});
