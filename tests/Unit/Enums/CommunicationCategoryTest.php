<?php

use App\Enums\CommunicationCategory;

it('exposes WorkshopAnnouncements as a transactional category mapped to workshop_announcements column', function () {
    expect(CommunicationCategory::WorkshopAnnouncements->value)->toBe('workshop_announcements')
        ->and(CommunicationCategory::WorkshopAnnouncements->isTransactional())->toBeTrue()
        ->and(CommunicationCategory::WorkshopAnnouncements->column())->toBe('workshop_announcements')
        ->and(CommunicationCategory::WorkshopAnnouncements->defaultWhenMissing())->toBeTrue();
});
