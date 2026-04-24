<?php

it('exposes encarregado contact info from config', function () {
    config()->set('lgpd.encarregado.email', 'lgpd@example.br');

    expect(config('lgpd.encarregado.email'))->toBe('lgpd@example.br')
        ->and(config('lgpd.retention.audit_logs_days'))->toBeInt()
        ->and(config('lgpd.retention.sessions_days'))->toBeInt()
        ->and(config('lgpd.features.ai_sentiment_opt_in'))->toBeBool();
});
