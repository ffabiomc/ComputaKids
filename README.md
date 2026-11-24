# ComputaKids

Plataforma educacional gratuita e gamificada de Computação, da Educação Infantil ao 9º ano do Ensino Fundamental, baseada no complemento à BNCC.

## Escopo
- Administrador geral cadastra, ativa e suspende professores; não há cadastro público, assinaturas, cobranças ou publicidade.
- Professores administram suas próprias turmas, alunos, planejamento e avaliações.
- Alunos realizam atividades, jogos e projetos e acompanham seu progresso.
- Catálogo central de habilidades, jogos, laboratórios, guias docentes, planos de aula, roteiros e materiais desplugados.
- Cada atividade publicada exige revisão técnica e pedagógica, objetivos, habilidades oficiais verificadas, roteiro, acessibilidade e critérios de avaliação.

## Arquitetura
PHP 8.3+, MySQL 8, HTML5, JavaScript e Bootstrap; monólito modular, PDO, hospedagem compartilhada e PWA. Banco compartilhado com isolamento por tenant_id derivado da sessão. Catálogo curricular global, dados escolares privados. Sem execução de código estudantil no servidor.

## Desenvolvimento
O projeto está em fase inicial. Consulte [arquitetura](docs/architecture.md) e [roteiro](docs/roadmap.md) quando disponíveis. Não utilizar dados reais de estudantes antes da validação de segurança e privacidade.

## Referência curricular
Resolução CNE/CEB nº 1, de 4 de outubro de 2022, e complemento à BNCC Computação. A matriz oficial será conferida integralmente antes da publicação de atividades.
