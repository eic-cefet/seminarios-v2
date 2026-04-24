<x-mail::message>
# Solicitação de exclusão de conta recebida

Olá, **{{ $userName }}**!

Recebemos sua solicitação de exclusão de conta. O processo será concluído em **{{ $scheduledFor }}**.

Você ainda pode cancelar a exclusão a qualquer momento antes dessa data apenas fazendo login novamente no Sistema de Seminários da EIC.

<x-mail::panel>
Após a conclusão, seu nome e e-mail serão substituídos por valores genéricos e registros acadêmicos de presença serão preservados como documentação institucional, conforme nossa Política de Privacidade.
</x-mail::panel>

Se você não realizou esta solicitação, entre em contato com nosso Encarregado pelo Tratamento de Dados em
[{{ config('lgpd.encarregado.email') }}](mailto:{{ config('lgpd.encarregado.email') }}).

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
