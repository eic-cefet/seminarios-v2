<?php

it('throttles certificate lookups after 30 requests per minute', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->get('/certificado/nonexistent-'.$i)->assertNotFound();
    }

    $this->get('/certificado/nonexistent-final')->assertStatus(429);
});

it('throttles jpg certificate lookups under the same bucket as pdf', function () {
    for ($i = 0; $i < 15; $i++) {
        $this->get('/certificado/mixed-'.$i)->assertNotFound();
    }
    for ($i = 0; $i < 15; $i++) {
        $this->get('/certificado/mixed-'.$i.'/jpg')->assertNotFound();
    }

    $this->get('/certificado/overflow/jpg')->assertStatus(429);
});
