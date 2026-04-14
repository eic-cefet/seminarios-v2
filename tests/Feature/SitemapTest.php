<?php

use App\Models\Seminar;
use App\Models\Subject;
use App\Models\Workshop;

it('returns a sitemap with public content routes', function () {
    config(['app.url' => 'https://seminarios.example.com/base']);

    $subject = Subject::factory()->create(['name' => 'Inteligência Artificial']);
    $workshop = Workshop::factory()->create(['name' => 'Workshop React']);
    $seminar = Seminar::factory()->create([
        'active' => true,
        'slug' => 'advanced-react',
        'workshop_id' => $workshop->id,
    ]);
    $seminar->subjects()->attach($subject);
    Seminar::factory()->inactive()->create(['slug' => 'inactive-seminar']);

    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();

    expect($response->headers->get('Content-Type'))
        ->toContain('application/xml');

    $content = $response->getContent();

    expect($content)
        ->toContain('<urlset')
        ->toContain('<loc>https://seminarios.example.com/base/</loc>')
        ->toContain('<loc>https://seminarios.example.com/base/apresentacoes</loc>')
        ->toContain('<loc>https://seminarios.example.com/base/topicos</loc>')
        ->toContain('<loc>https://seminarios.example.com/base/workshops</loc>')
        ->toContain('<loc>https://seminarios.example.com/base/seminario/advanced-react</loc>')
        ->toContain("<loc>https://seminarios.example.com/base/topico/{$subject->slug}</loc>")
        ->toContain("<loc>https://seminarios.example.com/base/workshop/{$workshop->slug}</loc>")
        ->not->toContain('inactive-seminar')
        ->not->toContain('/login')
        ->not->toContain('/admin');
});

it('returns robots.txt with an absolute sitemap url', function () {
    config(['app.url' => 'https://seminarios.example.com/base']);

    $response = $this->get('/robots.txt');

    $response->assertSuccessful();

    expect($response->headers->get('Content-Type'))
        ->toContain('text/plain');

    $content = $response->getContent();

    expect($content)
        ->toContain('User-agent: *')
        ->toContain('Disallow: /admin')
        ->toContain('Sitemap: https://seminarios.example.com/base/sitemap.xml')
        ->not->toContain('Sitemap: sitemap.xml');
});

it('sets pt-BR lang and proper title on public shell', function () {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertSee('<html lang="pt-BR">', false)
        ->assertSee('<title>Seminários EIC do CEFET-RJ</title>', false);
});

it('marks admin shell as noindex', function () {
    $response = $this->get('/admin');

    $response->assertSuccessful()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('excludes empty subjects and workshops from the sitemap', function () {
    $emptySubject = Subject::factory()->create();
    $subjectWithInactiveSeminar = Subject::factory()->create();
    $inactiveSeminar = Seminar::factory()->inactive()->create();
    $inactiveSeminar->subjects()->attach($subjectWithInactiveSeminar);

    $emptyWorkshop = Workshop::factory()->create();
    $workshopWithInactiveSeminar = Workshop::factory()->create();
    Seminar::factory()->inactive()->create([
        'workshop_id' => $workshopWithInactiveSeminar->id,
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();

    $content = $response->getContent();

    expect($content)
        ->not->toContain("/topico/{$emptySubject->slug}")
        ->not->toContain("/topico/{$subjectWithInactiveSeminar->slug}")
        ->not->toContain("/workshop/{$emptyWorkshop->slug}")
        ->not->toContain("/workshop/{$workshopWithInactiveSeminar->slug}");
});
