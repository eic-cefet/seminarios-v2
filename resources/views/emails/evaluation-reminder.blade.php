<x-mail::message>
# Sua opinião é importante!

Olá, **{{ $userName }}**!

@if($count === 1)
Você participou {{ $singleSeminar->ifMasculine('do', 'da') }} {{ $singleSeminar->inlineName() }} abaixo e gostaríamos de saber sua opinião:
@else
Você participou {{ $collectionDescriptor->ifMasculine('dos', 'das') }} {{ $collectionDescriptor->noun }} abaixo e gostaríamos de saber sua opinião:
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
@if($count === 1)
Avaliar {{ $singleSeminar->typeName() }}
@else
Avaliar {{ Str::ucfirst($collectionDescriptor->noun) }}
@endif
</x-mail::button>

@if($count === 1)
Você tem até 30 dias após a realização {{ $singleSeminar->ifMasculine('do', 'da') }} {{ $singleSeminar->inlineName() }} para enviar sua avaliação.
@else
Você tem até 30 dias após a realização {{ $collectionDescriptor->ifMasculine('dos', 'das') }} {{ $collectionDescriptor->noun }} para enviar sua avaliação.
@endif

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
