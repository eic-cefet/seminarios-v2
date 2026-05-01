<?php

use App\Support\MarkdownStripper;

it('returns empty string for null or empty input', function () {
    expect(MarkdownStripper::strip(null))->toBe('');
    expect(MarkdownStripper::strip(''))->toBe('');
});

it('removes heading markers', function () {
    expect(MarkdownStripper::strip('# Resumo'))->toBe('Resumo');
    expect(MarkdownStripper::strip('### Sub'))->toBe('Sub');
});

it('removes bold and italic markers', function () {
    expect(MarkdownStripper::strip('Texto com **negrito** aqui'))->toBe('Texto com negrito aqui');
    expect(MarkdownStripper::strip('Texto com __negrito__ aqui'))->toBe('Texto com negrito aqui');
    expect(MarkdownStripper::strip('Texto com *itálico* aqui'))->toBe('Texto com itálico aqui');
    expect(MarkdownStripper::strip('Texto com _itálico_ aqui'))->toBe('Texto com itálico aqui');
});

it('extracts text from links and images', function () {
    expect(MarkdownStripper::strip('Ver [docs](https://x.com/y)'))->toBe('Ver docs');
    expect(MarkdownStripper::strip('![alt](http://x/img.png)'))->toBe('alt');
});

it('removes inline and block code', function () {
    expect(MarkdownStripper::strip('Use `npm` para instalar'))->toBe('Use npm para instalar');
    expect(MarkdownStripper::strip("antes\n```\nbloco\n```\ndepois"))->toBe('antes depois');
});

it('removes list, blockquote, and rule markers', function () {
    expect(MarkdownStripper::strip("- item 1\n- item 2"))->toBe('item 1 item 2');
    expect(MarkdownStripper::strip("1. primeiro\n2. segundo"))->toBe('primeiro segundo');
    expect(MarkdownStripper::strip('> citação'))->toBe('citação');
    expect(MarkdownStripper::strip("texto\n---\nmais"))->toBe('texto mais');
});

it('handles a realistic seminar description', function () {
    $input = "# Resumo\nEste trabalho investiga a relação entre a **estrutura das redes de passe no futebol** e o desempenho.";

    expect(MarkdownStripper::strip($input))
        ->toBe('Resumo Este trabalho investiga a relação entre a estrutura das redes de passe no futebol e o desempenho.');
});
