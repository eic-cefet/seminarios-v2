<x-mail::message>
# Sua opinião é importante!

Olá, **{{ $userName }}**!

@if($seminars->count() === 1)
Você participou do seminário abaixo e gostaríamos de saber sua opinião:
@else
Você participou dos seminários abaixo e gostaríamos de saber sua opinião:
@endif

@foreach($seminars as $seminar)
<x-mail::panel>
**{{ $seminar->name }}**

@if($seminar->scheduled_at)
{{ $seminar->scheduled_at->format('d/m/Y \à\s H:i') }}
@endif
@if($seminar->seminarLocation)
 - {{ $seminar->seminarLocation->name }}
@endif
</x-mail::panel>
@endforeach

Sua avaliação nos ajuda a melhorar a qualidade dos nossos eventos. A avaliação leva menos de 1 minuto.

<x-mail::button :url="$evaluationUrl">
Avaliar Seminário{{ $seminars->count() > 1 ? 's' : '' }}
</x-mail::button>

Você tem até 30 dias após a realização do seminário para enviar sua avaliação.

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
