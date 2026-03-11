<x-mail::message>
# Alteração de Data

Olá, **{{ $userName }}**!

O seminário em que você está inscrito teve sua data alterada:

<x-mail::panel>
**{{ $seminar->name }}**

~~**Data anterior:** {{ $oldScheduledAt->format('d/m/Y') }} às {{ $oldScheduledAt->format('H:i') }}~~

**Nova data:** {{ $seminar->scheduled_at->format('d/m/Y') }} às {{ $seminar->scheduled_at->format('H:i') }} (horário de Brasília)
@if($seminar->seminarLocation)
<br>**Local:** {{ $seminar->seminarLocation->name }}
@endif
@if($seminar->room_link)
<br>**Link:** {{ $seminar->room_link }}
@endif
</x-mail::panel>

<x-mail::button :url="url('/seminario/' . $seminar->slug)">
Ver Detalhes do Seminário
</x-mail::button>

**Anexamos o arquivo .ics atualizado para você atualizar o evento no seu calendário.**

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
