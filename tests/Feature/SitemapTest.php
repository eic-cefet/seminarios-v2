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
        ->toContain("<loc>https://seminarios.example.com/base/topico/{$subject->id}</loc>")
        ->toContain("<loc>https://seminarios.example.com/base/workshop/{$workshop->id}</loc>")
        ->not->toContain('inactive-seminar')
        ->not->toContain('/login')
        ->not->toContain('/admin');
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
        ->not->toContain("/topico/{$emptySubject->id}")
        ->not->toContain("/topico/{$subjectWithInactiveSeminar->id}")
        ->not->toContain("/workshop/{$emptyWorkshop->id}")
        ->not->toContain("/workshop/{$workshopWithInactiveSeminar->id}");
});
