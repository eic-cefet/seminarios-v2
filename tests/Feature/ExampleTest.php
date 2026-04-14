<?php

test('the public shell exposes the expected seo defaults', function () {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertSee('<html lang="pt-BR">', false)
        ->assertSee('<title>Seminários EIC do CEFET-RJ</title>', false);
});

test('the admin shell is marked as noindex', function () {
    $response = $this->get('/admin');

    $response->assertSuccessful()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});
