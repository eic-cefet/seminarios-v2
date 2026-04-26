<x-mail::message>
# Lembrete: você está apresentando amanhã

Olá, **{{ $speakerName }}**!

Lembre-se: amanhã você está apresentando o seminário abaixo.

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
<br>**Link da sala:** {{ $seminar->room_link }}
@endif
</x-mail::panel>

<x-mail::button :url="url('/seminario/' . $seminar->slug)">
Ver detalhes
</x-mail::button>

Boa apresentação!<br>
{{ config('mail.team_name') }}
</x-mail::message>
