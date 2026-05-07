<x-mail::message>
@if($count === 1)
# Lembrete de {{ $singleSeminar->typeName() }}
@else
# Lembrete de {{ Str::ucfirst($collectionDescriptor->noun) }}
@endif

Olá, **{{ $userName }}**!

@if($count === 1)
Não esqueça! Você está inscrito {{ $singleSeminar->ifMasculine('no', 'na') }} {{ $singleSeminar->inlineName() }} que acontecerá **na próxima semana**:
@else
Não esqueça! Você está inscrito {{ $collectionDescriptor->ifMasculine('nos', 'nas') }} {{ $collectionDescriptor->noun }} que acontecerão **na próxima semana**:
@endif

@foreach($seminars as $seminar)
<x-mail::panel>
**{{ $seminar->name }}**

@if($seminar->scheduled_at)
**Data:** {{ $seminar->scheduled_at->format('d/m/Y') }}<br>
**Horário:** {{ $seminar->scheduled_at->format('H:i') }} (horário de Brasília)
@endif
@if($seminar->seminarLocation)
<br>**Local:** {{ $seminar->seminarLocation->name }}
@endif
@if($seminar->room_link)
<br>**Link:** {{ $seminar->room_link }}
@endif
</x-mail::panel>
@endforeach

@if($count === 1)
<x-mail::button :url="url('/seminario/' . $singleSeminar->slug)">
Ver Detalhes {{ $singleSeminar->ifMasculine('do', 'da') }} {{ $singleSeminar->typeName() }}
</x-mail::button>
@else
<x-mail::button :url="url('/perfil')">
Ver Minhas Inscrições
</x-mail::button>
@endif

@if($seminars->filter(fn($s) => $s->scheduled_at)->count() > 0)
**Anexamos {{ $seminars->filter(fn($s) => $s->scheduled_at)->count() > 1 ? 'os arquivos' : 'o arquivo' }} .ics para você adicionar {{ $seminars->filter(fn($s) => $s->scheduled_at)->count() > 1 ? 'os eventos' : 'o evento' }} ao seu calendário.**
@endif

Esperamos você!

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
