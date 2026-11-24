# Arquitetura
## Decisões
Monólito modular PHP 8.3, MySQL 8/InnoDB, HTML5, Bootstrap e JavaScript. Document root public/. Configuração e armazenamento fora da raiz pública. Sem dependência de processos persistentes. PWA posterior, sem cache de dados pessoais por padrão.
## Autorização
Administrador global sem tenant; professor e aluno pertencem a um tenant. Toda operação privada deriva tenant e identidade da sessão autenticada e verifica papel, estado da conta e do tenant. Chaves estrangeiras compostas impedem vínculos cruzados. Suspensão revoga sessões; política de bloqueio de alunos ainda sujeita à confirmação. Sem cadastro público.
## Módulos
Identidade, administração, turmas, matrículas, currículo, catálogo, guias, planejamento, atividades, evidências, avaliações, gamificação, laboratórios, duelos, relatórios, auditoria.
## Pedagogia
Educação Infantil (objetivos EI03; atividades mediadas para crianças menores) ao 9º ano. Três eixos oficiais. Importar códigos e descrições oficiais após conferência. Atividades versionadas, com revisão pedagógica, objetivos, fundamentação, roteiro temporizado, materiais, alternativa desplugada ou justificativa, acessibilidade, rubrica e evidências. Professor personaliza cópia privada. XP não equivale a domínio curricular.
## Segurança
PDO preparado, CSRF, escape HTML, hash de senha Argon2id quando disponível, cookies seguros, renovação de sessão, limitação de tentativas, auditoria sem segredos, minimização de dados de menores, política de retenção e backups. Código estudantil em ambiente isolado sem acesso à sessão ou execução PHP. Testes negativos de isolamento obrigatórios.
