<x-mail::message>
Olá, {{ $userName }}.

Recebemos uma solicitação de exclusão da sua conta no Sistema de Seminários da EIC. Para confirmar, clique no botão abaixo nas próximas horas.

<x-mail::button :url="$confirmUrl" color="red">
Confirmar exclusão
</x-mail::button>

Este link expira em **{{ $expiresAtFormatted }}**. Se você não reconhece esta solicitação, ignore este e-mail — sua conta permanecerá ativa.

Após a confirmação, você terá um período de carência de 30 dias durante o qual pode cancelar a exclusão apenas fazendo login novamente.
</x-mail::message>
