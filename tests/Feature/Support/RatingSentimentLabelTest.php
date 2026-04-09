<?php

use App\Models\Rating;
use App\Support\RatingSentimentLabel;

describe('RatingSentimentLabel::fromText', function () {
    it('returns null for empty or unrecognized sentiment text', function () {
        expect(RatingSentimentLabel::fromText(null))->toBeNull();
        expect(RatingSentimentLabel::fromText(''))->toBeNull();
        expect(RatingSentimentLabel::fromText('   '))->toBeNull();
        expect(RatingSentimentLabel::fromText('Resumo sem marcador conhecido.'))->toBeNull();
    });

    it('derives the positive label', function () {
        expect(RatingSentimentLabel::fromText('Sentimento positivo.'))->toBe('positive');
    });

    it('derives the negative label', function () {
        expect(RatingSentimentLabel::fromText('Sentimento negativo.'))->toBe('negative');
    });

    it('derives the neutral label', function () {
        expect(RatingSentimentLabel::fromText('Sentimento neutro.'))->toBe('neutral');
    });

    it('derives the mixed label from explicit mixed text', function () {
        expect(RatingSentimentLabel::fromText('Sentimento misto.'))->toBe('mixed');
    });

    it('derives the mixed label when positive and negative markers coexist', function () {
        expect(RatingSentimentLabel::fromText('Sentimento positivo e negativo ao mesmo tempo.'))
            ->toBe('mixed');
    });
});

describe('RatingSentimentLabel::applyFilter', function () {
    it('filters positive sentiments', function () {
        $positive = Rating::factory()->create(['sentiment' => 'Sentimento positivo.']);
        Rating::factory()->create(['sentiment' => 'Sentimento negativo.']);
        Rating::factory()->create(['sentiment' => 'Sentimento misto.']);

        $result = RatingSentimentLabel::applyFilter(Rating::query()->orderBy('id'), 'positive')
            ->pluck('id')
            ->all();

        expect($result)->toBe([$positive->id]);
    });

    it('filters negative sentiments', function () {
        Rating::factory()->create(['sentiment' => 'Sentimento positivo.']);
        $negative = Rating::factory()->create(['sentiment' => 'Sentimento negativo.']);
        Rating::factory()->create(['sentiment' => 'Sentimento misto.']);

        $result = RatingSentimentLabel::applyFilter(Rating::query()->orderBy('id'), 'negative')
            ->pluck('id')
            ->all();

        expect($result)->toBe([$negative->id]);
    });

    it('filters neutral sentiments', function () {
        Rating::factory()->create(['sentiment' => 'Sentimento positivo.']);
        $neutral = Rating::factory()->create(['sentiment' => 'Sentimento neutro.']);
        Rating::factory()->create(['sentiment' => 'Sentimento negativo.']);

        $result = RatingSentimentLabel::applyFilter(Rating::query()->orderBy('id'), 'neutral')
            ->pluck('id')
            ->all();

        expect($result)->toBe([$neutral->id]);
    });

    it('filters mixed sentiments from explicit or combined markers', function () {
        Rating::factory()->create(['sentiment' => 'Sentimento positivo.']);
        $explicitMixed = Rating::factory()->create(['sentiment' => 'Sentimento misto.']);
        $combinedMixed = Rating::factory()->create([
            'sentiment' => 'Sentimento positivo com pontos negativos.',
        ]);

        $result = RatingSentimentLabel::applyFilter(Rating::query()->orderBy('id'), 'mixed')
            ->pluck('id')
            ->all();

        expect($result)->toBe([$explicitMixed->id, $combinedMixed->id]);
    });

    it('filters null label sentiments for null or unrecognized text', function () {
        $nullSentiment = Rating::factory()->create(['sentiment' => null]);
        $unknownSentiment = Rating::factory()->create([
            'sentiment' => 'Resumo sem marcador conhecido.',
        ]);
        Rating::factory()->create(['sentiment' => 'Sentimento positivo.']);
        Rating::factory()->create(['sentiment' => 'Sentimento neutro.']);

        $result = RatingSentimentLabel::applyFilter(Rating::query()->orderBy('id'), 'null')
            ->pluck('id')
            ->all();

        expect($result)->toBe([$nullSentiment->id, $unknownSentiment->id]);
    });

    it('returns the original query for unknown labels', function () {
        $first = Rating::factory()->create(['sentiment' => 'Sentimento positivo.']);
        $second = Rating::factory()->create(['sentiment' => 'Sentimento negativo.']);

        $result = RatingSentimentLabel::applyFilter(Rating::query()->orderBy('id'), 'unexpected')
            ->pluck('id')
            ->all();

        expect($result)->toBe([$first->id, $second->id]);
    });
});
