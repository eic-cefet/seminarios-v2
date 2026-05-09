<x-mail::message>
# Sua opinião é importante!

Olá, **{{ $userName }}**!

@if($seminars->count() === 1)
Você participou da apresentação abaixo e gostaríamos de saber sua opinião:
@else
Você participou das apresentações abaixo e gostaríamos de saber sua opinião:
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
{{ $seminars->count() > 1 ? 'Avaliar Apresentações' : 'Avaliar Apresentação' }}
</x-mail::button>

Você tem até 30 dias após a realização da apresentação para enviar sua avaliação.

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
