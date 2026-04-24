<x-mail::message>
# Solicitação de exclusão de conta cancelada

Olá, **{{ $userName }}**!

Sua solicitação de exclusão de conta foi cancelada. Sua conta permanece ativa e você pode continuar utilizando o Sistema de Seminários da EIC normalmente.

<x-mail::panel>
Se não foi você que realizou este login, redefina sua senha imediatamente.
</x-mail::panel>

Em caso de dúvidas, entre em contato com nosso Encarregado pelo Tratamento de Dados em
[{{ config('lgpd.encarregado.email') }}](mailto:{{ config('lgpd.encarregado.email') }}).

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
