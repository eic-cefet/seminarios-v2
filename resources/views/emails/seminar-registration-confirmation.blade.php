<x-mail::message>
# Inscrição confirmada!

Olá, **{{ $userName }}**!

Sua inscrição na apresentação abaixo foi confirmada:

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

<x-mail::button :url="url('/seminario/' . $seminar->slug)">
Ver detalhes da apresentação
</x-mail::button>

Você receberá lembretes antes do evento. Caso não possa comparecer, cancele sua inscrição na página da apresentação.

Até logo,<br>
{{ config('mail.team_name') }}
</x-mail::message>
