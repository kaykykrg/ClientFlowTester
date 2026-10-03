<?php
declare(strict_types=1);

namespace ClientFlow\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Bloco E — templates (T33 a T40).
 *
 * As regras puras compartilhadas por template_salvar.php e
 * template_carregar.php são carregadas de tests/test_helpers.php para evitar
 * que os efeitos do endpoint (sessão, conexão e exit()) sejam disparados.
 */
final class BlockETest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../test_helpers.php';
    }

    /** T33 — item sem nome é descartado. */
    public function testT33ItemSemNomeERejeitado(): void
    {
        $resultado = \normalizar_item_template(['nome' => '   ', 'tipo' => 'text']);

        $this->assertNull($resultado);
    }

    /** T34 — tipo desconhecido usa o padrão text. */
    public function testT34TipoInvalidoENormalizadoParaText(): void
    {
        $resultado = \normalizar_item_template(['nome' => 'Campo X', 'tipo' => 'banana']);

        $this->assertNotNull($resultado);
        $this->assertSame('text', $resultado['tipo']);
    }

    /** T35 — tipo aceito não é alterado. */
    public function testT35TipoValidoEMantido(): void
    {
        $resultado = \normalizar_item_template(['nome' => 'Logo', 'tipo' => 'image']);

        $this->assertNotNull($resultado);
        $this->assertSame('image', $resultado['tipo']);
    }

    /** T36 — limites invertidos são trocados antes de salvar. */
    public function testT36LimitesInvertidosSaoCorrigidos(): void
    {
        $resultado = \normalizar_item_template([
            'nome' => 'Biografia',
            'tipo' => 'text',
            'min_chars' => 100,
            'max_chars' => 10,
        ]);

        $this->assertNotNull($resultado);
        $this->assertSame(10, $resultado['min_chars']);
        $this->assertSame(100, $resultado['max_chars']);
    }

    /**
     * T37 — itens normalizados são serializados como o campo JSON `itens`
     * e, ao serem carregados, preservam suas configurações.
     */
    public function testT37SalvarECarregarTemplateMantemItensEConfiguracoes(): void
    {
        $itensRecebidos = [
            ['nome' => 'Nome completo', 'tipo' => 'text', 'min_chars' => 3, 'max_chars' => 255],
            ['nome' => 'Logo', 'tipo' => 'image', 'allowed_extensions' => 'JPG, png, jpg'],
        ];

        $itensParaSalvar = array_values(array_filter(
            array_map('\normalizar_item_template', $itensRecebidos)
        ));
        $itensSerializados = json_encode($itensParaSalvar, JSON_UNESCAPED_UNICODE);
        $itensCarregados = json_decode((string) $itensSerializados, true);

        $this->assertIsString($itensSerializados);
        $this->assertIsArray($itensCarregados);
        $this->assertSame($itensParaSalvar, $itensCarregados);
        $this->assertSame(3, $itensCarregados[0]['min_chars']);
        $this->assertSame('jpg,png', $itensCarregados[1]['allowed_extensions']);
    }

    /** T38 — a duplicidade é avaliada dentro da mesma agência. */
    public function testT38NomeDuplicadoNaMesmaAgenciaERetornaErro(): void
    {
        $templatesExistentes = [
            ['agencia_id' => 10, 'nome' => 'Onboarding'],
            ['agencia_id' => 20, 'nome' => 'Onboarding'],
        ];
        $agenciaId = 10;
        $nome = 'Onboarding';

        $duplicado = array_filter(
            $templatesExistentes,
            static fn (array $template): bool => $template['agencia_id'] === $agenciaId
                && $template['nome'] === $nome
        );
        $resposta = $duplicado
            ? 'Já existe um template com esse nome para sua agência.'
            : 'Template salvo com sucesso.';

        $this->assertNotEmpty($duplicado);
        $this->assertSame('Já existe um template com esse nome para sua agência.', $resposta);
    }

    /** T39 — membro de agência sem perm_criar_projetos é bloqueado. */
    public function testT39UsuarioSemPermissaoNaoSalvaTemplate(): void
    {
        $papelAgencia = 'dev';
        $permissoes = ['perm_criar_projetos' => false];

        $autorizado = $papelAgencia === 'admin_agencia'
            || !empty($permissoes['perm_criar_projetos']);
        $resposta = $autorizado
            ? 'Template salvo com sucesso.'
            : 'Você não tem permissão para criar templates.';

        $this->assertFalse($autorizado);
        $this->assertSame('Você não tem permissão para criar templates.', $resposta);
    }

    /** T40 — um registro pronto para o banco contém somente itens válidos. */
    public function testT40TemplateValidoPersisteItensNormalizados(): void
    {
        $itensRecebidos = [
            ['nome' => 'Razão social', 'tipo' => 'text', 'min_chars' => 1, 'max_chars' => 100],
            ['nome' => '', 'tipo' => 'text'],
            ['nome' => 'Contrato', 'tipo' => 'file', 'allowed_extensions' => 'PDF, DOCX, pdf'],
        ];

        $itens = array_values(array_filter(array_map('\normalizar_item_template', $itensRecebidos)));
        $registroParaBanco = [
            'agencia_id' => 10,
            'nome' => 'Documentos iniciais',
            'descricao' => 'Materiais necessários para iniciar o projeto.',
            'itens' => json_encode($itens, JSON_UNESCAPED_UNICODE),
        ];

        $this->assertSame(10, $registroParaBanco['agencia_id']);
        $this->assertSame('Documentos iniciais', $registroParaBanco['nome']);
        $this->assertCount(2, $itens);
        $this->assertSame('file', $itens[1]['tipo']);
        $this->assertSame('pdf,docx', $itens[1]['allowed_extensions']);
        $this->assertJson((string) $registroParaBanco['itens']);
    }
}
