# 📋 Documentação Comprobatória de Testes Automatizados — ClientFlow
**Atividade:** RA2 — Automação de Casos de Teste com Robot Framework  
**Projeto:** ClientFlow (Sistema Web de Gestão de Clientes, Checklists, Templates e Permissões)  
**Ferramenta de Automação:** Robot Framework 7.x (Python / Process Library / Collections / OperatingSystem)  
**Total de Testes:** 30 Casos de Teste (5 casos por integrante × 6 integrantes)  
**Resultado Geral:** **30 Aprovados (100% PASS) | 0 Falhas | 0 Avisos**

---

## 👥 1. Equipe e Distribuição de Responsabilidades

| Bloco | Responsável | Módulo / Domínio Avaliado | Casos de Teste | Status |
|:---:|:---|:---|:---:|:---:|
| **Bloco A** | **Kayky Quesada Kruger** | Validação de CPF/CNPJ, Autenticação e Montagem de Sessão | T01 – T05 | ✅ 5/5 PASS |
| **Bloco B** | **Vantuil Júnior Plaster** | Política de Senhas Fortes e Validação de Checklists | T06 – T10 | ✅ 5/5 PASS |
| **Bloco C** | **Arthur Kenji** | Sanitização, Formatação (CPF/CNPJ/Fone) e Vínculo de Checklists | T11 – T15 | ✅ 5/5 PASS |
| **Bloco D** | **Matheus Pires** | Normalização de Uploads, Extensões e Regras de Reenvio | T16 – T20 | ✅ 5/5 PASS |
| **Bloco E** | **Matheus Fonteles Pires** | Normalização de Itens de Template e Autorização de Projetos | T21 – T25 | ✅ 5/5 PASS |
| **Bloco F** | **Yuri Allegreti** | Cast de Permissões de Sessão (MySQL/0/1) e Controle de Acesso | T26 – T30 | ✅ 5/5 PASS |

---

## 🎓 2. Fundamentação Teórica e Conceitos Aplicados

Em consonância com as diretrizes discutidas em sala de aula, foram empregadas as seguintes técnicas de Engenharia de Testes de Software:

1. **Teste de Caixa-Preta (Black-Box Testing):** Os testes foram elaborados focando nas especificações de entrada e saída das regras de negócio, sem acoplamento a implementações internas voláteis.
2. **Particionamento em Classes de Equivalência (Equivalence Partitioning):**
   - *Classes Válidas:* Dados de entrada aceitos pelo sistema que devem resultar em processamento bem-sucedido (ex.: CPF com dígitos verificadores matematicamente válidos, senha com todos os critérios).
   - *Classes Inválidas:* Conjunto de entradas que devem acionar bloqueios, mensagens de erro e sanitizações (ex.: CPFs com dígitos repetidos, tipos de itens desconhecidos, senhas sem numerais).
3. **Análise de Valores Limite (Boundary Value Analysis - BVA):**
   - Teste no limiar exato de tamanho de senha (comprimento < 8 caracteres).
   - Inversão de limites mínimo e máximo em campos numéricos (`min > max`).
4. **Análise de Causa-Efeito e Tabelas de Decisão:**
   - Mapeamento de combinações condicionais de autorização (ex.: papel de usuário `dev` vs. permissão booleana `perm_criar_projetos`).
   - Bloqueio de reenvio de arquivos quando `status == 'approved'`.
5. **Automação Multi-camada (Keywords e Process Runner):**
   - Utilização de Keywords do Robot Framework (`Run Process`, `Should Be Equal`, `Set Variable`, etc.) que acionam com fidelidade determinística o motor de regras da aplicação.

---

## 📊 3. Resumo Executivo dos 30 Casos de Teste

| ID | Bloco | Integrante | Descrição do Caso de Teste | Técnica | Entrada | Esperado | Obtido | Status |
|:---:|:---:|:---|:---|:---:|:---|:---:|:---:|:---:|
| **T01** | A | Kayky Quesada Kruger | CPF válido deve ser aceito | Partição Válida | `529.982.247-25` | `true` | `true` | ✅ PASS |
| **T02** | A | Kayky Quesada Kruger | CPF repetido deve ser rejeitado | Partição Inválida | `111.111.111-11` | `false` | `false` | ✅ PASS |
| **T03** | A | Kayky Quesada Kruger | CNPJ válido deve ser aceito | Partição Válida | `04.252.011/0001-10` | `true` | `true` | ✅ PASS |
| **T04** | A | Kayky Quesada Kruger | CNPJ com zeros deve ser rejeitado | Partição Inválida | `00.000.000/0000-00` | `false` | `false` | ✅ PASS |
| **T05** | A | Kayky Quesada Kruger | Login válido monta sessão com dados íntegros | Integração / Causa-Efeito | `id=1, ag=5, perm=1` | `sessao válida` | `sessao válida` | ✅ PASS |
| **T06** | B | Vantuil Júnior Plaster | Senha < 8 caracteres rejeitada | Valor Limite | `Ab1!` (4 chars) | `curta` | `curta` | ✅ PASS |
| **T07** | B | Vantuil Júnior Plaster | Senha sem maiúscula rejeitada | Partição Inválida | `abc12345!` | `sem_maiuscula` | `sem_maiuscula` | ✅ PASS |
| **T08** | B | Vantuil Júnior Plaster | Senha sem minúscula rejeitada | Partição Inválida | `ABC12345!` | `sem_minuscula` | `sem_minuscula` | ✅ PASS |
| **T09** | B | Vantuil Júnior Plaster | Senha sem número rejeitada | Partição Inválida | `Abcdefg!` | `sem_numero` | `sem_numero` | ✅ PASS |
| **T10** | B | Vantuil Júnior Plaster | Checklist sem itens retorna mensagem | Causa-Efeito / API | `itens=[]` | `Adicione pelo menos um item...` | `Adicione pelo menos um item...` | ✅ PASS |
| **T11** | C | Arthur Kenji | Remover caracteres não numéricos | Caixa-Preta | `(11) 91234-5678` | `11912345678` | `11912345678` | ✅ PASS |
| **T12** | C | Arthur Kenji | Formatar CPF com 11 dígitos | Partição Válida | `52998224725` | `529.982.247-25` | `529.982.247-25` | ✅ PASS |
| **T13** | C | Arthur Kenji | Formatar CNPJ com 14 dígitos | Partição Válida | `11222333000181` | `11.222.333/0001-81` | `11.222.333/0001-81` | ✅ PASS |
| **T14** | C | Arthur Kenji | Formatar telefone celular 11 dígitos | Valor Limite | `11912345678` | `(11) 91234-5678` | `(11) 91234-5678` | ✅ PASS |
| **T15** | C | Arthur Kenji | Bloquear checklist já vinculado a outro cliente | Regra de Negócio | `cliente_id=10, novo=42` | `Este checklist ja esta vinculado...` | `Este checklist ja esta vinculado...` | ✅ PASS |
| **T16** | D | Matheus Pires | Extensões convertidas para minúsculas | Equivalência | `JPG,PNG,GIF` | `jpg,png,gif` | `jpg,png,gif` | ✅ PASS |
| **T17** | D | Matheus Pires | Extensões duplicadas removidas | Partição Inválida | `jpg,png,jpg,gif,png` | `jpg,png,gif` | `jpg,png,gif` | ✅ PASS |
| **T18** | D | Matheus Pires | Caracteres inválidos em extensões removidos | Sanitização | `.jp@g, .p!ng, g*if` | `jpg,png,gif` | `jpg,png,gif` | ✅ PASS |
| **T19** | D | Matheus Pires | Tipo image retorna extensões padrão | Mapeamento | `image` | `jpg,jpeg,png,gif,webp` | `jpg,jpeg,png,gif,webp` | ✅ PASS |
| **T20** | D | Matheus Pires | Item com status approved bloqueia reenvio | Segurança / Negócio | `status=approved` | `bloqueado` | `bloqueado` | ✅ PASS |
| **T21** | E | Matheus Fonteles Pires | Item de template sem nome rejeitado | Campo Obrigatório | `nome="   "` | `null` | `null` | ✅ PASS |
| **T22** | E | Matheus Fonteles Pires | Tipo inválido normalizado para text | Causa-Efeito / Fallback | `tipo=banana` | `text` | `text` | ✅ PASS |
| **T23** | E | Matheus Fonteles Pires | Tipo de item válido mantido | Partição Válida | `tipo=image` | `image` | `image` | ✅ PASS |
| **T24** | E | Matheus Fonteles Pires | Limites min e max invertidos corrigidos | Valor Limite (Swap) | `min=100, max=10` | `10\|100` | `10\|100` | ✅ PASS |
| **T25** | E | Matheus Fonteles Pires | Usuário sem permissão não salva template | Tabela de Decisão | `dev, perm_criar=false` | `bloqueado` | `bloqueado` | ✅ PASS |
| **T26** | F | Yuri Allegreti | `perm_ver_clientes=1` convertido para bool true | Cast Estrito | `1` (int) | `true` | `true` | ✅ PASS |
| **T27** | F | Yuri Allegreti | `perm_criar_clientes=0` convertido para bool false | Cast Estrito | `0` (int) | `false` | `false` | ✅ PASS |
| **T28** | F | Yuri Allegreti | `perm_ver_projetos='1'` (MySQL string) vira bool true | Compatibilidade BD | `'1'` (string) | `true` | `true` | ✅ PASS |
| **T29** | F | Yuri Allegreti | `perm_criar_projetos='0'` (MySQL string) vira bool false | Compatibilidade BD | `'0'` (string) | `false` | `false` | ✅ PASS |
| **T30** | F | Yuri Allegreti | Não-admin não pode alterar status de outra conta | Controle de Acesso (RBAC) | `usuario_tipo=agency` | `Perfil sem permissao...` | `Perfil sem permissao...` | ✅ PASS |

---

## 🔍 4. Detalhamento e Passo a Passo de Cada Caso de Teste

---

### 🟪 BLOCO A — Login e Cadastro
**Responsável:** Kayky Quesada Kruger  
**Arquivo Robot:** [`tests/robot/bloco_a_login_cadastro.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_a_login_cadastro.robot)

#### 🔹 T01: CPF Válido Deve Ser Aceito
* **Objetivo:** Verificar se um CPF com dígitos verificadores matematicamente válidos é aceito pela rotina `isValidCPF()`.
* **Técnica:** Particionamento de Equivalência (Classe Válida).
* **Pré-condições:** Módulo de validação disponível.
* **Passo a Passo:**
  1. Definir o valor de entrada `${cpf} = '529.982.247-25'`.
  2. Executar a keyword `Validar CPF Retorna True ${cpf}`.
  3. A keyword executa o algoritmo de verificação módulo 11 via interpretador PHP (`Run Process`).
  4. Obter a saída do stdout e comparar com `'true'`.
* **Entrada:** `529.982.247-25`
* **Resultado Esperado:** `true`
* **Resultado Obtido:** `true`
* **Status:** ✅ PASS

#### 🔹 T02: CPF Com Dígitos Repetidos Deve Ser Rejeitado
* **Objetivo:** Garantir a rejeição de CPFs formados por 11 dígitos idênticos (falso-positivo comum se calculados apenas pelos dígitos verificadores).
* **Técnica:** Particionamento de Equivalência (Classe Inválida — Dígitos Repetidos).
* **Pré-condições:** Módulo de validação disponível.
* **Passo a Passo:**
  1. Definir o valor de entrada `${cpf} = '111.111.111-11'`.
  2. Invocar a keyword `Validar CPF Retorna False ${cpf}`.
  3. O teste aciona o regex `/^(.)\1+$/` dentro da função de validação.
  4. Comparar a saída com `'false'`.
* **Entrada:** `111.111.111-11`
* **Resultado Esperado:** `false`
* **Resultado Obtido:** `false`
* **Status:** ✅ PASS

#### 🔹 T03: CNPJ Válido Deve Ser Aceito
* **Objetivo:** Validar que uma inscrição no CNPJ válida seja aceita pelo cálculo de 14 dígitos.
* **Técnica:** Particionamento de Equivalência (Classe Válida).
* **Passo a Passo:**
  1. Definir a entrada `${cnpj} = '04.252.011/0001-10'`.
  2. Executar keyword de cálculo dos 2 dígitos verificadores com pesos 5,4,3,2,9...
  3. Validar se o stdout é `'true'`.
* **Entrada:** `04.252.011/0001-10`
* **Resultado Esperado:** `true`
* **Resultado Obtido:** `true`
* **Status:** ✅ PASS

#### 🔹 T04: CNPJ Inválido Deve Ser Rejeitado
* **Objetivo:** Bloquear CNPJ inválido ou zerado.
* **Técnica:** Particionamento de Equivalência (Classe Inválida).
* **Passo a Passo:**
  1. Definir `${cnpj} = '00.000.000/0000-00'`.
  2. Executar verificação no validador.
  3. Validar que o retorno é `'false'`.
* **Entrada:** `00.000.000/0000-00`
* **Resultado Esperado:** `false`
* **Resultado Obtido:** `false`
* **Status:** ✅ PASS

#### 🔹 T05: Login Válido Monta Sessão Com Dados Corretos
* **Objetivo:** Garantir que após login bem-sucedido, o array de sessão seja preenchido com `usuario_id`, `agencia_id` e permissões booleanas.
* **Técnica:** Teste de Integração / Análise de Causa-Efeito.
* **Passo a Passo:**
  1. Simular registro de usuário com `usuario_id = 1`, `agencia_id = 5` e `perm_ver_clientes = 1`.
  2. Disparar a rotina de montagem de sessão da aplicação.
  3. Conferir se a sessão contém os valores exatos e a flag booleana `perm_ver_clientes === true`.
* **Entrada:** `['id' => 1, 'agencia_id' => 5, 'perm_ver_clientes' => 1]`
* **Resultado Esperado:** `ok`
* **Resultado Obtido:** `ok`
* **Status:** ✅ PASS

---

### 🟨 BLOCO B — Senha e Checklist
**Responsável:** Vantuil Júnior Plaster  
**Arquivo Robot:** [`tests/robot/bloco_b_senha_checklist.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_b_senha_checklist.robot)

#### 🔹 T06: Senha Com Menos De 8 Caracteres Deve Ser Rejeitada
* **Objetivo:** Validar a restrição de tamanho mínimo de 8 caracteres exigida pela política de segurança.
* **Técnica:** Análise de Valores Limite (Abaixo do Limiar Mínimo).
* **Passo a Passo:**
  1. Fornecer a senha `'Ab1!'` (4 caracteres).
  2. Executar `validatePasswordStrength('Ab1!')`.
  3. Verificar se o erro retornado indica tamanho insuficiente (`curta`).
* **Entrada:** `Ab1!` (4 caracteres)
* **Resultado Esperado:** `curta`
* **Resultado Obtido:** `curta`
* **Status:** ✅ PASS

#### 🔹 T07: Senha Sem Letra Maiúscula Deve Ser Rejeitada
* **Objetivo:** Garantir a exigência de ao menos um caractere maiúsculo (A-Z).
* **Técnica:** Particionamento de Equivalência (Partição Inválida).
* **Passo a Passo:**
  1. Enviar senha `'abc12345!'` (sem letras maiúsculas).
  2. Executar `validatePasswordStrength`.
  3. Checar se a mensagem de erro retornada é `'sem_maiuscula'`.
* **Entrada:** `abc12345!`
* **Resultado Esperado:** `sem_maiuscula`
* **Resultado Obtido:** `sem_maiuscula`
* **Status:** ✅ PASS

#### 🔹 T08: Senha Sem Letra Minúscula Deve Ser Rejeitada
* **Objetivo:** Garantir a exigência de ao menos um caractere minúsculo (a-z).
* **Técnica:** Particionamento de Equivalência (Partição Inválida).
* **Passo a Passo:**
  1. Enviar senha `'ABC12345!'` (apenas maiúsculas e números).
  2. Executar `validatePasswordStrength`.
  3. Verificar retorno `'sem_minuscula'`.
* **Entrada:** `ABC12345!`
* **Resultado Esperado:** `sem_minuscula`
* **Resultado Obtido:** `sem_minuscula`
* **Status:** ✅ PASS

#### 🔹 T09: Senha Sem Número Deve Ser Rejeitada
* **Objetivo:** Exigir a presença de pelo menos um algarismo numérico (0-9).
* **Técnica:** Particionamento de Equivalência (Partição Inválida).
* **Passo a Passo:**
  1. Enviar senha `'Abcdefg!'` (sem números).
  2. Executar `validatePasswordStrength`.
  3. Verificar se o resultado retornado é `'sem_numero'`.
* **Entrada:** `Abcdefg!`
* **Resultado Esperado:** `sem_numero`
* **Resultado Obtido:** `sem_numero`
* **Status:** ✅ PASS

#### 🔹 T10: Checklist Sem Itens Deve Retornar Mensagem De Erro
* **Objetivo:** Impedir a criação de um checklist vazio no banco de dados.
* **Técnica:** Análise de Causa-Efeito / Teste Funcional de API.
* **Passo a Passo:**
  1. Simular payload de criação com `itens = []`.
  2. Processar a regra de negócio do endpoint `checklist_criar.php`.
  3. Validar se a mensagem retornada orienta a inclusão de ao menos um item.
* **Entrada:** `itens = []`
* **Resultado Esperado:** `Adicione pelo menos um item no formulario.`
* **Resultado Obtido:** `Adicione pelo menos um item no formulario.`
* **Status:** ✅ PASS

---

### 🟩 BLOCO C — Formatação e Vínculo de Checklist
**Responsável:** Arthur Kenji  
**Arquivo Robot:** [`tests/robot/bloco_c_formatacao_vinculo.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_c_formatacao_vinculo.robot)

#### 🔹 T11: Remover Caracteres Não Numéricos De Uma Entrada
* **Objetivo:** Testar a função de sanitização `onlyDigits()` utilizada em formulários e buscas.
* **Técnica:** Teste de Caixa-Preta (Partição Mista).
* **Passo a Passo:**
  1. Passar string com máscara: `'(11) 91234-5678'`.
  2. Executar `onlyDigits('(11) 91234-5678')`.
  3. Comparar o retorno com a sequência pura de dígitos `'11912345678'`.
* **Entrada:** `(11) 91234-5678`
* **Resultado Esperado:** `11912345678`
* **Resultado Obtido:** `11912345678`
* **Status:** ✅ PASS

#### 🔹 T12: Formatar CPF Com 11 Dígitos
* **Objetivo:** Verificar se a função de exibição `formatCPF()` aplica a máscara padrão `000.000.000-00`.
* **Técnica:** Particionamento de Equivalência (Partição Válida).
* **Passo a Passo:**
  1. Informar sequência sem formatação `'52998224725'`.
  2. Chamar `formatCPF('52998224725')`.
  3. Confirmar que a saída é `'529.982.247-25'`.
* **Entrada:** `52998224725`
* **Resultado Esperado:** `529.982.247-25`
* **Resultado Obtido:** `529.982.247-25`
* **Status:** ✅ PASS

#### 🔹 T13: Formatar CNPJ Com 14 Dígitos
* **Objetivo:** Verificar a máscara oficial de CNPJ `00.000.000/0000-00`.
* **Técnica:** Particionamento de Equivalência (Partição Válida).
* **Passo a Passo:**
  1. Passar os dígitos `'11222333000181'`.
  2. Executar `formatCNPJ('11222333000181')`.
  3. Confirmar a formatação `'11.222.333/0001-81'`.
* **Entrada:** `11222333000181`
* **Resultado Esperado:** `11.222.333/0001-81`
* **Resultado Obtido:** `11.222.333/0001-81`
* **Status:** ✅ PASS

#### 🔹 T14: Formatar Telefone Celular Com 11 Dígitos
* **Objetivo:** Garantir que números com 11 dígitos sejam formatados como celular com nono dígito `(XX) 9XXXX-XXXX`.
* **Técnica:** Análise de Valores Limite (11 dígitos).
* **Passo a Passo:**
  1. Passar `'11912345678'`.
  2. Invocar `formatPhone('11912345678')`.
  3. Comparar a string retornada com `'(11) 91234-5678'`.
* **Entrada:** `11912345678`
* **Resultado Esperado:** `(11) 91234-5678`
* **Resultado Obtido:** `(11) 91234-5678`
* **Status:** ✅ PASS

#### 🔹 T15: Checklist Já Associado A Outro Cliente Deve Ser Bloqueado
* **Objetivo:** Proteger a integridade de checklist compartilhado impedindo que outro cliente sobrescreva o vínculo existente.
* **Técnica:** Análise de Causa-Efeito / Integridade Referencial.
* **Passo a Passo:**
  1. Configurar um checklist existente com `cliente_id = 10`.
  2. Tentar vincular o checklist a um novo cliente (`cliente_id = 42`).
  3. Avaliar a resposta do interceptador de segurança.
* **Entrada:** `checklist.cliente_id = 10`, `solicitante = 42`
* **Resultado Esperado:** `Este checklist ja esta vinculado a outro cliente.`
* **Resultado Obtido:** `Este checklist ja esta vinculado a outro cliente.`
* **Status:** ✅ PASS

---

### 🟦 BLOCO D — Upload de Arquivos
**Responsável:** Matheus Pires  
**Arquivo Robot:** [`tests/robot/bloco_d_upload_arquivos.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_d_upload_arquivos.robot)

#### 🔹 T16: Extensões Devem Ser Convertidas Para Minúsculas
* **Objetivo:** Evitar falhas de correspondência de case em sistemas operacionais Unix/Linux (`.JPG` vs `.jpg`).
* **Técnica:** Particionamento de Equivalência.
* **Passo a Passo:**
  1. Passar string `'JPG,PNG,GIF'`.
  2. Executar `normalize_extensions('JPG,PNG,GIF')`.
  3. Confirmar que a saída gerada é toda minúscula `'jpg,png,gif'`.
* **Entrada:** `JPG,PNG,GIF`
* **Resultado Esperado:** `jpg,png,gif`
* **Resultado Obtido:** `jpg,png,gif`
* **Status:** ✅ PASS

#### 🔹 T17: Extensões Duplicadas Devem Ser Removidas
* **Objetivo:** Eliminar itens redundantes informados nas configurações do formulário de upload.
* **Técnica:** Particionamento de Equivalência (Entrada com Duplicatas).
* **Passo a Passo:**
  1. Passar lista com itens repetidos: `'jpg,png,jpg,gif,png'`.
  2. Executar `normalize_extensions`.
  3. Verificar se as duplicatas foram expurgadas, restando `'jpg,png,gif'`.
* **Entrada:** `jpg,png,jpg,gif,png`
* **Resultado Esperado:** `jpg,png,gif`
* **Resultado Obtido:** `jpg,png,gif`
* **Status:** ✅ PASS

#### 🔹 T18: Caracteres Inválidos Das Extensões Devem Ser Removidos
* **Objetivo:** Sanitizar caracteres perigosos ou acidentais (`@`, `!`, `*`, `.`).
* **Técnica:** Causa-Efeito / Sanitização de Input.
* **Passo a Passo:**
  1. Passar a string contaminada `'.jp@g, .p!ng, g*if'`.
  2. Submeter ao filtro `normalize_extensions`.
  3. Checar se apenas os caracteres alfanuméricos foram preservados: `'jpg,png,gif'`.
* **Entrada:** `.jp@g, .p!ng, g*if`
* **Resultado Esperado:** `jpg,png,gif`
* **Resultado Obtido:** `jpg,png,gif`
* **Status:** ✅ PASS

#### 🔹 T19: Tipo Image Deve Retornar Extensões Padrão
* **Objetivo:** Testar catálogo de extensões pré-definidas para o tipo `image`.
* **Técnica:** Particionamento de Equivalência (Tipo Reconhecido).
* **Passo a Passo:**
  1. Solicitar configuração padrão para o tipo `'image'`.
  2. Executar `default_extensions_for_type('image')`.
  3. Validar se a lista padrão devolvida é `'jpg,jpeg,png,gif,webp'`.
* **Entrada:** `image`
* **Resultado Esperado:** `jpg,jpeg,png,gif,webp`
* **Resultado Obtido:** `jpg,jpeg,png,gif,webp`
* **Status:** ✅ PASS

#### 🔹 T20: Item Com Status Approved Não Deve Aceitar Reenvio
* **Objetivo:** Garantir a imutabilidade de itens já aprovados pelo revisor da agência.
* **Técnica:** Teste de Segurança e Regra de Negócio.
* **Passo a Passo:**
  1. Definir item com `status = 'approved'`.
  2. Avaliar a permissão booleana `$pode_reenviar = ($item_status !== 'approved')`.
  3. Confirmar que a tentativa de reenvio é classificada como `'bloqueado'`.
* **Entrada:** `item_status = 'approved'`
* **Resultado Esperado:** `bloqueado`
* **Resultado Obtido:** `bloqueado`
* **Status:** ✅ PASS

---

### 🟫 BLOCO E — Templates
**Responsável:** Matheus Fonteles Pires  
**Arquivo Robot:** [`tests/robot/bloco_e_templates.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_e_templates.robot)

#### 🔹 T21: Item De Template Sem Nome Deve Ser Rejeitado
* **Objetivo:** Validar a obrigatoriedade do título do item na montagem do template.
* **Técnica:** Análise de Valores Limite (Campo Vazio / Espaços em Branco).
* **Passo a Passo:**
  1. Enviar estrutura de item com `nome = '   '` (somente espaços).
  2. Chamar a função `normalizar_item_template()`.
  3. Checar se a função descarta o item retornando `null`.
* **Entrada:** `nome = '   '`
* **Resultado Esperado:** `null`
* **Resultado Obtido:** `null`
* **Status:** ✅ PASS

#### 🔹 T22: Tipo De Item Inválido Deve Ser Normalizado Para Text
* **Objetivo:** Aplicar fallback seguro caso o usuário submeta um tipo de campo desconhecido.
* **Técnica:** Particionamento de Equivalência (Classe Inválida -> Fallback).
* **Passo a Passo:**
  1. Submeter item com `tipo = 'banana'` (tipo não reconhecido).
  2. Executar a normalização do item.
  3. Confirmar que o sistema substitui automaticamente pelo tipo base `'text'`.
* **Entrada:** `tipo = 'banana'`
* **Resultado Esperado:** `text`
* **Resultado Obtido:** `text`
* **Status:** ✅ PASS

#### 🔹 T23: Tipo De Item Válido Deve Ser Mantido
* **Objetivo:** Garantir a preservação dos tipos aceitos (`image`, `file`, etc.).
* **Técnica:** Particionamento de Equivalência (Classe Válida).
* **Passo a Passo:**
  1. Submeter item com `tipo = 'image'`.
  2. Executar `normalizar_item_template()`.
  3. Verificar que o tipo retornado permanece `'image'`.
* **Entrada:** `tipo = 'image'`
* **Resultado Esperado:** `image`
* **Resultado Obtido:** `image`
* **Status:** ✅ PASS

#### 🔹 T24: Limites Mínimo E Máximo Invertidos Devem Ser Trocados
* **Objetivo:** Prevenir inconsistências em que o limite inferior seja maior que o limite superior informado pelo usuário (`min > max`).
* **Técnica:** Análise de Valores Limite e Tratamento de Inversão.
* **Passo a Passo:**
  1. Configurar `min_chars = 100` e `max_chars = 10`.
  2. Executar a rotina de ajuste do template.
  3. Constatar que a rotina efetuou o swap dos limites para `min = 10` e `max = 100`.
* **Entrada:** `min = 100, max = 10`
* **Resultado Esperado:** `10|100`
* **Resultado Obtido:** `10|100`
* **Status:** ✅ PASS

#### 🔹 T25: Usuário Sem Permissão Não Deve Salvar Template
* **Objetivo:** Proteger a rota de persistência de templates contra usuários sem o privilégio `perm_criar_projetos`.
* **Técnica:** Tabela de Decisão / Controle de Acesso (RBAC).
* **Passo a Passo:**
  1. Definir usuário com perfil `dev` e `perm_criar_projetos = false`.
  2. Avaliar a expressão de autorização: `($papel === 'admin_agencia' || !empty($permissoes['perm_criar_projetos']))`.
  3. Constatar o bloqueio da operação (`'bloqueado'`).
* **Entrada:** `papel = 'dev', perm_criar_projetos = false`
* **Resultado Esperado:** `bloqueado`
* **Resultado Obtido:** `bloqueado`
* **Status:** ✅ PASS

---

### 🟥 BLOCO F — Permissões e Membros
**Responsável:** Yuri Allegreti  
**Arquivo Robot:** [`tests/robot/bloco_f_permissoes_membros.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_f_permissoes_membros.robot)

#### 🔹 T26: Permissão perm_ver_clientes Com Valor 1 Deve Virar Booleano True
* **Objetivo:** Garantir que o valor numérico `1` retornado do banco seja convertido para o tipo booleano estrito `true`.
* **Técnica:** Particionamento de Equivalência (Inteiro 1 -> `true`).
* **Passo a Passo:**
  1. Enviar payload de permissão `['perm_ver_clientes' => 1]`.
  2. Executar `montar_permissoes_sessao()`.
  3. Testar com `is_bool()` e valor booleano estrito, esperando string `'true'`.
* **Entrada:** `1` (inteiro)
* **Resultado Esperado:** `true`
* **Resultado Obtido:** `true`
* **Status:** ✅ PASS

#### 🔹 T27: Permissão perm_criar_clientes Com Valor 0 Deve Virar Booleano False
* **Objetivo:** Garantir que o valor numérico `0` seja convertido para `false`.
* **Técnica:** Particionamento de Equivalência (Inteiro 0 -> `false`).
* **Passo a Passo:**
  1. Enviar payload de permissão `['perm_criar_clientes' => 0]`.
  2. Executar `montar_permissoes_sessao()`.
  3. Testar com `is_bool()` e checar se retornou `'false'`.
* **Entrada:** `0` (inteiro)
* **Resultado Esperado:** `false`
* **Resultado Obtido:** `false`
* **Status:** ✅ PASS

#### 🔹 T28: Permissão perm_ver_projetos Com String 1 Deve Virar Booleano True
* **Objetivo:** Tratar especificidade do driver PDO/MySQL onde valores TINYINT(1) podem ser recuperados como string `'1'`.
* **Técnica:** Causa-Efeito / Conversão de Tipos de Dados.
* **Passo a Passo:**
  1. Passar `['perm_ver_projetos' => '1']` (tipo string).
  2. Executar `montar_permissoes_sessao()`.
  3. Verificar que o cast `(bool)` converteu corretamente para `true`.
* **Entrada:** `'1'` (string MySQL)
* **Resultado Esperado:** `true`
* **Resultado Obtido:** `true`
* **Status:** ✅ PASS

#### 🔹 T29: Permissão perm_criar_projetos Com String 0 Deve Virar Booleano False
* **Objetivo:** Garantir que a string `'0'` do MySQL não seja avaliada como truthy indevido.
* **Técnica:** Causa-Efeito / Segurança de Cast de Tipos.
* **Passo a Passo:**
  1. Passar `['perm_criar_projetos' => '0']` (string).
  2. Executar a normalização de permissões de sessão.
  3. Confirmar que a chave resultante é estritamente `false`.
* **Entrada:** `'0'` (string MySQL)
* **Resultado Esperado:** `false`
* **Resultado Obtido:** `false`
* **Status:** ✅ PASS

#### 🔹 T30: Não Admin Não Deve Conseguir Alterar Status De Outra Conta
* **Objetivo:** Bloquear a chamada ao endpoint `admin_usuario_atualizar_status.php` por usuários comuns (RBAC).
* **Técnica:** Tabela de Decisão / Autorização de Rota Administrativa.
* **Passo a Passo:**
  1. Simular requisição com perfil `usuario_tipo = 'agency'` (não admin).
  2. Avaliar a validação do controlador: `if ($sessao['usuario_tipo'] !== 'admin')`.
  3. Confirmar a mensagem de bloqueio `'Perfil sem permissao para acessar esta area.'`.
* **Entrada:** `usuario_tipo = 'agency'`
* **Resultado Esperado:** `Perfil sem permissao para acessar esta area.`
* **Resultado Obtido:** `Perfil sem permissao para acessar esta area.`
* **Status:** ✅ PASS

---

## 💻 5. Instruções de Reprodução e Execução

### Pré-requisitos
* **Python 3.10+**
* **Robot Framework 7.x** (`pip install robotframework`)
* **PHP 8.x** instalado e acessível no `PATH` do sistema

### Comandos de Execução

```powershell
# 1. Executar a suíte completa com todos os 30 testes:
robot --outputdir tests/robot/results tests/robot/

# 2. Executar testes filtrados por integrante:
robot --include kayky tests/robot/
robot --include vantuil tests/robot/
robot --include arthur tests/robot/
robot --include matheus_pires tests/robot/
robot --include matheus_fonteles tests/robot/
robot --include yuri tests/robot/

# 3. Executar testes filtrados por bloco funcional:
robot --include bloco_a tests/robot/
robot --include bloco_b tests/robot/
robot --include bloco_c tests/robot/
robot --include bloco_d tests/robot/
robot --include bloco_e tests/robot/
robot --include bloco_f tests/robot/

# 4. Executar script de validação de evidências automatizado (saída JSON estruturada):
php tests/robot/evidence_runner.php
```

---

## 📁 6. Arquivos e Evidências Comprobatórias Geradas

1. **Relatório Visual Completo em HTML:**  
   [`tests/robot/results/report.html`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/results/report.html)
2. **Log Passo a Passo em HTML:**  
   [`tests/robot/results/log.html`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/results/log.html)
3. **Log Estruturado em XML:**  
   [`tests/robot/results/output.xml`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/results/output.xml)
4. **Runner de Evidências Automatizado:**  
   [`tests/robot/evidence_runner.php`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/evidence_runner.php)
5. **Arquivos de Teste Robot Framework:**
   - [`tests/robot/bloco_a_login_cadastro.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_a_login_cadastro.robot)
   - [`tests/robot/bloco_b_senha_checklist.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_b_senha_checklist.robot)
   - [`tests/robot/bloco_c_formatacao_vinculo.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_c_formatacao_vinculo.robot)
   - [`tests/robot/bloco_d_upload_arquivos.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_d_upload_arquivos.robot)
   - [`tests/robot/bloco_e_templates.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_e_templates.robot)
   - [`tests/robot/bloco_f_permissoes_membros.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/bloco_f_permissoes_membros.robot)
   - [`tests/robot/__init__.robot`](file:///c:/Users/kayky/Downloads/ClientFlow%20(1)/ClientFlow/tests/robot/__init__.robot)
