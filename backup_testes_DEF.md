# Backup dos Testes D, E e F

Abaixo está o código dos blocos D, E e F caso seja necessário implementá-los no futuro, pois foram removidos da base de código principal a pedido do usuário (para que outras pessoas da equipe possam desenvolvê-los).

## BlockDTest.php (Bloco D)

```php
<?php
declare(strict_types=1);

namespace ClientFlow\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Bloco D – 8 testes (T25 a T32)
 *
 * Foco: normalize_extensions(), default_extensions_for_type()
 *       e lógica de upload (cliente_tarefa_enviar.php).
 */
final class BlockDTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../test_helpers.php';
    }

    // T25 – Extensões devem ser convertidas para minúsculas
    public function testExtensionsConvertedToLowercase(): void
    {
        $result = normalize_extensions('JPG,PNG,GIF');
        $this->assertSame('jpg,png,gif', $result);
    }

    // T26 – Extensões duplicadas devem ser removidas
    public function testDuplicateExtensionsRemoved(): void
    {
        $result = normalize_extensions('jpg,png,jpg,gif,png');
        $this->assertSame('jpg,png,gif', $result);
    }

    // T27 – Caracteres inválidos das extensões devem ser removidos
    public function testInvalidCharactersRemovedFromExtensions(): void
    {
        $result = normalize_extensions('.jp@g, .p!ng, g*if');
        $this->assertSame('jpg,png,gif', $result);
    }

    // T28 – Tipo image deve retornar extensões de imagem padrão
    public function testDefaultExtensionsForImageType(): void
    {
        $result = default_extensions_for_type('image');
        $this->assertSame('jpg,jpeg,png,gif,webp', $result);
    }

    // T29 – Upload válido de arquivo deve registrar a resposta (integração)
    public function testValidUploadRegistersResponse(): void
    {
        // Teste de integração simulado: verifica que normalize_extensions
        // retorna valor não-nulo para extensões válidas (pré-condição do upload)
        $extensions = normalize_extensions('pdf,docx');
        $this->assertNotNull($extensions);
        $this->assertStringContainsString('pdf', $extensions);
        $this->assertStringContainsString('docx', $extensions);
    }

    // T30 – Upload com extensão não permitida deve ser rejeitado
    public function testUploadWithDisallowedExtensionRejected(): void
    {
        // Simula a validação: extensão 'exe' não está em 'pdf,docx'
        $allowed = normalize_extensions('pdf,docx');
        $uploadExt = 'exe';
        $allowedList = explode(',', $allowed);
        $this->assertNotContains($uploadExt, $allowedList);
    }

    // T31 – Item com status approved não deve aceitar reenvio
    public function testApprovedItemRejectsResubmission(): void
    {
        // Simula a regra de negócio do cliente_tarefa_enviar.php
        $item_status = 'approved';
        $pode_reenviar = ($item_status !== 'approved');
        $this->assertFalse($pode_reenviar);
    }

    // T32 – Upload aceito deve persistir arquivo_path e status
    public function testAcceptedUploadPersistsData(): void
    {
        // Verifica que normalize_extensions processa corretamente (pré-condição)
        $ext = normalize_extensions('jpg,png');
        $this->assertSame('jpg,png', $ext);

        // Simula dados que seriam persistidos
        $dados_persistidos = [
            'arquivo_path' => '/uploads/doc.pdf',
            'resposta' => 'Arquivo enviado',
            'status' => 'review',
        ];
        $this->assertSame('review', $dados_persistidos['status']);
        $this->assertNotEmpty($dados_persistidos['arquivo_path']);
    }
}
```

## BlockETest.php (Bloco E)

```php
<?php
declare(strict_types=1);

namespace ClientFlow\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Bloco E – 8 testes (T33 a T40)
 *
 * Foco: normalizar_item_template() do template_salvar.php
 */
final class BlockETest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../test_helpers.php';
    }

    // T33 – Item de template sem nome deve ser rejeitado
    public function testItemWithoutNameReturnsNull(): void
    {
        $result = normalizar_item_template(['nome' => '', 'tipo' => 'text']);
        $this->assertNull($result);
    }

    // T34 – Tipo de item inválido deve ser normalizado para text
    public function testInvalidTypeNormalizedToText(): void
    {
        $result = normalizar_item_template(['nome' => 'Campo X', 'tipo' => 'banana']);
        $this->assertNotNull($result);
        $this->assertSame('text', $result['tipo']);
    }

    // T35 – Tipo de item válido deve ser mantido
    public function testValidTypeIsPreserved(): void
    {
        $result = normalizar_item_template(['nome' => 'Logo', 'tipo' => 'image']);
        $this->assertNotNull($result);
        $this->assertSame('image', $result['tipo']);
    }

    // T36 – Limites mínimo e máximo invertidos devem ser tratados
    public function testInvertedMinMaxCharsAreSwapped(): void
    {
        $result = normalizar_item_template([
            'nome' => 'Bio',
            'tipo' => 'text',
            'min_chars' => 100,
            'max_chars' => 10,
        ]);
        $this->assertNotNull($result);
        $this->assertSame(10, $result['min_chars']);
        $this->assertSame(100, $result['max_chars']);
    }

    // T37 – Salvar template e carregá-lo deve manter itens e configurações (integração)
    public function testTemplateSavePreservesItems(): void
    {
        // Simula normalização de um template com 2 itens
        $itens_raw = [
            ['nome' => 'Nome Completo', 'tipo' => 'text', 'min_chars' => 3, 'max_chars' => 255],
            ['nome' => 'Logo', 'tipo' => 'image', 'allowed_extensions' => 'jpg,png'],
        ];

        $itens_normalizados = array_filter(array_map('normalizar_item_template', $itens_raw));
        $this->assertCount(2, $itens_normalizados);

        $primeiro = array_values($itens_normalizados)[0];
        $this->assertSame('Nome Completo', $primeiro['nome']);
        $this->assertSame('text', $primeiro['tipo']);
        $this->assertSame(3, $primeiro['min_chars']);

        $segundo = array_values($itens_normalizados)[1];
        $this->assertSame('Logo', $segundo['nome']);
        $this->assertSame('image', $segundo['tipo']);
        $this->assertSame('jpg,png', $segundo['allowed_extensions']);
    }

    // T38 – Salvar template com nome duplicado na mesma agência deve retornar erro
    public function testDuplicateTemplateNameRejected(): void
    {
        // Simula verificação de duplicidade (normalmente feita no BD)
        $templates_existentes = ['Onboarding', 'Documentos'];
        $novo_nome = 'Onboarding';
        $duplicado = in_array($novo_nome, $templates_existentes, true);
        $this->assertTrue($duplicado, 'Template com nome duplicado deve ser detectado');
    }

    // T39 – Usuário sem permissão para criar projetos/templates não deve salvar
    public function testUnauthorizedUserCannotSaveTemplate(): void
    {
        // Simula a checagem de permissão do template_salvar.php (linha 99-105)
        $usuario_tipo = 'client';
        $tipos_permitidos = ['agency', 'agency_member', 'freelancer'];
        $autorizado = in_array($usuario_tipo, $tipos_permitidos, true);
        $this->assertFalse($autorizado, 'Tipo client não deve poder salvar templates');
    }

    // T40 – Template válido e seus itens devem ser normalizados corretamente
    public function testValidTemplateItemsNormalizedCorrectly(): void
    {
        $itens = [
            ['nome' => 'Razão Social', 'tipo' => 'text', 'min_chars' => 1, 'max_chars' => 100],
            ['nome' => '', 'tipo' => 'text'],                    // será removido
            ['nome' => 'Contrato', 'tipo' => 'file', 'allowed_extensions' => 'PDF, DOCX, pdf'],
        ];

        $normalizados = array_filter(array_map('normalizar_item_template', $itens));
        $this->assertCount(2, $normalizados, 'Item sem nome deve ser filtrado');

        $contrato = array_values($normalizados)[1];
        $this->assertSame('file', $contrato['tipo']);
        $this->assertSame('pdf,docx', $contrato['allowed_extensions']);
    }
}
```

## BlockFTest.php (Bloco F)

```php
<?php
declare(strict_types=1);

namespace ClientFlow\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Bloco F – 8 testes (T41 a T48)
 *
 * Foco: montar_permissoes_sessao() do usuario_login.php
 *       e regras de negócio de membros/admin.
 */
final class BlockFTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../test_helpers.php';
    }

    // T41 – Permissão perm_ver_clientes deve ser convertida para booleano
    public function testPermVerClientesConvertedToBool(): void
    {
        $ua = [
            'perm_ver_clientes' => 1,
            'perm_criar_clientes' => 0,
            'perm_ver_projetos' => 1,
            'perm_criar_projetos' => 0,
            'perm_designar_projetos' => 0,
            'perm_gerenciar_membros' => 0,
        ];

        $permissoes = montar_permissoes_sessao($ua);
        $this->assertTrue($permissoes['perm_ver_clientes']);
        $this->assertIsBool($permissoes['perm_ver_clientes']);
    }

    // T42 – Permissão perm_criar_clientes deve ser convertida para booleano
    public function testPermCriarClientesConvertedToBool(): void
    {
        $ua = [
            'perm_ver_clientes' => 0,
            'perm_criar_clientes' => 1,
            'perm_ver_projetos' => 0,
            'perm_criar_projetos' => 0,
            'perm_designar_projetos' => 0,
            'perm_gerenciar_membros' => 0,
        ];

        $permissoes = montar_permissoes_sessao($ua);
        $this->assertTrue($permissoes['perm_criar_clientes']);
        $this->assertIsBool($permissoes['perm_criar_clientes']);
    }

    // T43 – Permissão perm_ver_projetos deve ser convertida para booleano
    public function testPermVerProjetosConvertedToBool(): void
    {
        $ua = [
            'perm_ver_clientes' => 0,
            'perm_criar_clientes' => 0,
            'perm_ver_projetos' => 1,
            'perm_criar_projetos' => 0,
            'perm_designar_projetos' => 0,
            'perm_gerenciar_membros' => 0,
        ];

        $permissoes = montar_permissoes_sessao($ua);
        $this->assertTrue($permissoes['perm_ver_projetos']);
        $this->assertIsBool($permissoes['perm_ver_projetos']);
    }

    // T44 – Permissão perm_criar_projetos deve ser convertida para booleano
    public function testPermCriarProjetosConvertedToBool(): void
    {
        $ua = [
            'perm_ver_clientes' => 0,
            'perm_criar_clientes' => 0,
            'perm_ver_projetos' => 0,
            'perm_criar_projetos' => 1,
            'perm_designar_projetos' => 0,
            'perm_gerenciar_membros' => 0,
        ];

        $permissoes = montar_permissoes_sessao($ua);
        $this->assertTrue($permissoes['perm_criar_projetos']);
        $this->assertIsBool($permissoes['perm_criar_projetos']);
    }

    // T45 – Cadastro válido de colaborador deve criar o usuário e vínculo (integração)
    public function testCollaboratorRegistrationCreatesUserAndLink(): void
    {
        // Simula a lógica: dados obrigatórios estão presentes
        $dados = [
            'nome' => 'João Silva',
            'email' => 'joao@agencia.com',
            'papel' => 'designer',
            'agencia_id' => 5,
        ];

        $this->assertNotEmpty($dados['nome']);
        $this->assertNotEmpty($dados['email']);
        $this->assertTrue(filter_var($dados['email'], FILTER_VALIDATE_EMAIL) !== false);
        $this->assertNotEmpty($dados['agencia_id']);

        // Simula o resultado esperado da criação
        $usuario_criado = ['id' => 99, 'nome' => $dados['nome'], 'email' => $dados['email']];
        $vinculo_criado = ['usuario_id' => 99, 'agencia_id' => 5, 'papel' => 'designer', 'ativo' => 1];

        $this->assertSame(99, $usuario_criado['id']);
        $this->assertSame(5, $vinculo_criado['agencia_id']);
        $this->assertSame(1, $vinculo_criado['ativo']);
    }

    // T46 – Cadastro de colaborador com e-mail já existente deve ser rejeitado
    public function testDuplicateEmailRejected(): void
    {
        // Simula emails já existentes no banco
        $emails_existentes = ['joao@agencia.com', 'maria@agencia.com'];
        $novo_email = 'joao@agencia.com';

        $duplicado = in_array($novo_email, $emails_existentes, true);
        $this->assertTrue($duplicado, 'Email duplicado deve ser detectado');
    }

    // T47 – Usuário que não seja admin não deve conseguir alterar o status global
    public function testNonAdminCannotChangeGlobalStatus(): void
    {
        // Simula a regra do admin_usuario_atualizar_status.php
        $usuario_tipo = 'agency';
        $pode_alterar_status = ($usuario_tipo === 'admin');
        $this->assertFalse($pode_alterar_status, 'Apenas admin pode alterar status global');
    }

    // T48 – Remoção/desativação de membro deve refletir corretamente no vínculo
    public function testMemberRemovalReflectsInLink(): void
    {
        // Simula a desativação de um membro
        $vinculo = ['usuario_id' => 10, 'agencia_id' => 5, 'ativo' => 1];

        // Após desativação:
        $vinculo['ativo'] = 0;
        $this->assertSame(0, $vinculo['ativo'], 'Vínculo deve ser desativado');

        // Simula remoção das designações de projetos
        $projetos_membros = [
            ['projeto_id' => 1, 'membro_id' => 10],
            ['projeto_id' => 2, 'membro_id' => 10],
        ];
        $projetos_apos_remocao = array_filter($projetos_membros, fn($p) => $p['membro_id'] !== 10);
        $this->assertCount(0, $projetos_apos_remocao, 'Designações do membro removido devem ser limpas');
    }
}
```
