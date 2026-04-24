<x-mail::message>
# Sua conta foi removida

Olá, **{{ $userName }}**!

Sua conta no Sistema de Seminários da EIC foi removida conforme solicitado.

<x-mail::panel>
Dados pessoais foram pseudonimizados; certificados e registros acadêmicos de presença foram preservados como documentação institucional.
</x-mail::panel>

Caso tenha dúvidas, entre em contato com o Encarregado em
[{{ config('lgpd.encarregado.email') }}](mailto:{{ config('lgpd.encarregado.email') }}).

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
