<x-mail::message>
@php($g = \App\Support\PresentationTypeGrammar::for($seminar->seminarType?->name))
# {{ \Illuminate\Support\Str::ucfirst($g->noun()) }} {{ $g->agree('Reagendado', 'Reagendada') }}

Olá, **{{ $userName }}**!

{{ \Illuminate\Support\Str::ucfirst($g->definite()) }} **{{ $seminar->name }}** foi {{ $g->agree('reagendado', 'reagendada') }}:

<x-mail::panel>
**Data anterior:** <del>{{ $oldScheduledAt->format('d/m/Y') }} às {{ $oldScheduledAt->format('H:i') }}</del>

**Nova data:** {{ $newScheduledAt->format('d/m/Y') }} às {{ $newScheduledAt->format('H:i') }} (horário de Brasília)
@if($seminar->seminarLocation)

**Local:** {{ $seminar->seminarLocation->name }}
@endif
@if($seminar->room_link)

**Link:** {{ $seminar->room_link }}
@endif
</x-mail::panel>

<x-mail::button :url="url('/seminario/' . $seminar->slug)">
Ver Detalhes da Apresentação
</x-mail::button>

**Anexamos o arquivo .ics atualizado para você atualizar o evento no seu calendário.**

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
