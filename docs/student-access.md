# Acesso dos alunos
Requisito aprovado: professor escolhe métodos de acesso. Decisão proposta: configuração por turma, permitindo múltiplos métodos.
- Individual: credenciais próprias, condicionado a matrícula ativa em turma não arquivada com método habilitado.
- Compartilhado: senha por turma armazenada somente como hash; seleção de aluno em contexto autorizado pelo professor, sem exposição pública de lista de menores. Avisar que senha compartilhada não comprova identidade individual.
- Remoto: computador inicia desafio temporário; professor autenticado seleciona turma e aluno e autoriza código. Desafio vinculado ao navegador solicitante por segredo aleatório, expiração curta, consumo atômico único, limitação persistente de tentativas e auditoria. Polling HTTPS compatível com hospedagem compartilhada.
Todos os métodos verificam tenant, matrícula, conta, turma e suspensão. Sessões identificam aluno e método; mudanças de política revogam sessões afetadas. Professor pode encerrar sessões remotamente. Não reutilizar sessão de professor no computador do aluno. Não armazenar senhas em texto claro. Nenhum dado de aluno em cache PWA.
Estado: especificação e esquema inicial; integração, migração do instalador e testes pendentes.

## Padrão de autorização remota (aprovado)
QR Code é a interface principal de autorização remota. O computador apresenta um QR contendo exclusivamente o código opaco temporário do desafio, sem dados pessoais nem segredo do navegador. O professor autenticado escaneia pelo PWA, seleciona turma e aluno e confirma a autorização. O código manual permanece como alternativa. A câmera deve operar via HTTPS, com decodificação local e alternativa manual quando indisponível. O professor continua escolhendo os métodos permitidos por turma. A preferência pelo QR não altera automaticamente as políticas de turmas existentes. A implementação das interfaces e os testes continuam pendentes.
