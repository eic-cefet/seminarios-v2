<x-mail::message>
# Falha na exportação de dados

Olá, **{{ $userName }}**!

Ocorreu um erro ao processar sua solicitação de exportação de dados pessoais.
Já fomos notificados e faremos uma nova tentativa em breve.

Caso prefira, você pode abrir uma nova solicitação diretamente na tela de perfil do sistema.

Em caso de dúvida, entre em contato com o Encarregado pelo Tratamento de Dados em
[{{ config('lgpd.encarregado.email') }}](mailto:{{ config('lgpd.encarregado.email') }}).

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
