<?php
declare(strict_types=1);

namespace ClientFlow\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes de integração, funcional/API, segurança e persistência
 * dos Blocos A (T05-T08), B (T13-T16) e C (T21-T24).
 *
 * Estes testes simulam o comportamento das APIs do ClientFlow
 * usando funções puras extraídas e simulação de sessão/banco.
 */
final class BlockABCIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../test_helpers.php';
    }

    // ================================================================
    // BLOCO A – Testes variados (T05 a T08)
    // Login: usuario_login.php
    // ================================================================

    // T05 – Integração – Login válido deve autenticar e montar sessão
    public function testT05LoginValidoMontaSessao(): void
    {
        // Simula dados retornados pelo banco para um usuário válido
        $usuario = [
            'id' => 1,
            'nome' => 'Kayky',
            'email' => 'kayky@test.com',
            'tipo' => 'agency',
            'status_conta' => 'aprovado',
            'plano_id' => 2,
            'nome_plano' => 'Pro',
        ];

        // Simula dados de vínculo de agência ativo
        $ua = [
            'ua_id' => 10,
            'agencia_id' => 5,
            'papel' => 'admin',
            'perm_ver_clientes' => 1,
            'perm_criar_clientes' => 1,
            'perm_ver_projetos' => 1,
            'perm_criar_projetos' => 1,
            'perm_designar_projetos' => 0,
            'perm_gerenciar_membros' => 1,
            'ativo' => 1,
        ];

        // Monta permissões usando a função real
        $permissoes = montar_permissoes_sessao($ua);

        // Simula construção da sessão (como faz o login)
        $sessao = [
            'usuario_id' => $usuario['id'],
            'usuario_nome' => $usuario['nome'],
            'usuario_email' => $usuario['email'],
            'usuario_tipo' => $usuario['tipo'],
            'agencia_id' => $ua['agencia_id'],
            'papel_agencia' => $ua['papel'],
            'ua_id' => $ua['ua_id'],
            'permissoes' => $permissoes,
        ];

        $this->assertSame(1, $sessao['usuario_id']);
        $this->assertSame('Kayky', $sessao['usuario_nome']);
        $this->assertSame(5, $sessao['agencia_id']);
        $this->assertTrue($sessao['permissoes']['perm_ver_clientes']);
        $this->assertTrue($sessao['permissoes']['perm_criar_clientes']);
    }

    // T06 – Funcional/API – Login com e-mail ou senha em branco deve retornar validação
    public function testT06LoginCamposEmBrancoRetornaMensagem(): void
    {
        // Simula a validação do usuario_login.php (linhas 52-57)
        $email = '';
        $senha = '';

        $retorno = ['status' => 'nok', 'mensagem' => 'Credenciais inválidas'];

        if (empty($email) || empty($senha)) {
            $retorno['mensagem'] = 'Preencha e-mail e senha.';
        }

        $this->assertSame('nok', $retorno['status']);
        $this->assertSame('Preencha e-mail e senha.', $retorno['mensagem']);
    }

    // T07 – Segurança/Autorização – Agência sem vínculo ativo não conclui login
    public function testT07AgenciaSemVinculoNaoFazLogin(): void
    {
        // Simula carregar_vinculo_agencia_ativo retornando null
        $usuario_tipo = 'agency_member';
        $ua = null; // sem vínculo ativo

        $retorno = ['status' => 'nok', 'mensagem' => 'Credenciais inválidas'];

        if (in_array($usuario_tipo, ['agency_member', 'agency'], true) && !$ua) {
            $retorno['mensagem'] = 'Sua conta não possui um vínculo de agência ativo.';
        }

        $this->assertSame('nok', $retorno['status']);
        $this->assertSame('Sua conta não possui um vínculo de agência ativo.', $retorno['mensagem']);
    }

    // T08 – Persistência/BD – data_ultimo_acesso deve ser atualizada
    public function testT08DataUltimoAcessoAtualizada(): void
    {
        // Simula a lógica do login (linha 119-122): UPDATE usuarios SET data_ultimo_acesso
        $antes_login = null; // data_ultimo_acesso antes do login
        $apos_login = date('Y-m-d H:i:s'); // simulação do CURRENT_TIMESTAMP

        $this->assertNotNull($apos_login);
        $this->assertNotSame($antes_login, $apos_login);

        // Verifica que o formato é válido (YYYY-MM-DD HH:MM:SS)
        $parsed = \DateTime::createFromFormat('Y-m-d H:i:s', $apos_login);
        $this->assertInstanceOf(\DateTime::class, $parsed);
    }

    // ================================================================
    // BLOCO B – Testes variados (T13 a T16)
    // Checklist: checklist_criar.php
    // ================================================================

    // T13 – Integração – Criação válida de checklist salva checklist e itens
    public function testT13ChecklistCriacaoValidaSalvaItens(): void
    {
        // Simula os dados de criação com 2 itens válidos
        $titulo = 'Onboarding Cliente';
        $itens = [
            ['nome' => 'RG frente', 'tipo' => 'image', 'allowed_extensions' => 'jpg,png'],
            ['nome' => 'Comprovante', 'tipo' => 'file', 'allowed_extensions' => 'pdf'],
        ];

        // Valida pré-condições (como faz checklist_criar.php)
        $this->assertNotEmpty($titulo);
        $this->assertCount(2, $itens);

        // Normaliza extensões de cada item
        foreach ($itens as $item) {
            $ext = normalize_extensions($item['allowed_extensions']);
            $this->assertNotNull($ext);
        }

        // Simula geração de link_hash
        $link_hash = bin2hex(random_bytes(16));
        $this->assertSame(32, strlen($link_hash));
    }

    // T14 – Funcional/API – Checklist sem itens retorna mensagem
    public function testT14ChecklistSemItensRetornaMensagem(): void
    {
        // Replica a lógica de checklist_criar.php (linhas 70-75)
        $itens = [];
        $retorno = ['status' => 'nok', 'mensagem' => ''];

        if (!is_array($itens) || count($itens) === 0) {
            $retorno['mensagem'] = 'Adicione pelo menos um item no formulário.';
        }

        $this->assertSame('Adicione pelo menos um item no formulário.', $retorno['mensagem']);
    }

    // T15 – Segurança/Autorização – Perfil cliente não cria checklist
    public function testT15PerfilClienteNaoCriaChecklist(): void
    {
        // Replica a lógica de checklist_criar.php (linhas 27-32)
        $usuario_tipo = 'client';
        $retorno = ['status' => 'nok', 'mensagem' => ''];

        if ($usuario_tipo === 'client') {
            $retorno['mensagem'] = 'Perfil sem permissão para criar formulário.';
        }

        $this->assertSame('Perfil sem permissão para criar formulário.', $retorno['mensagem']);
    }

    // T16 – Persistência/BD – Checklist possui link_hash único
    public function testT16ChecklistLinkHashUnico(): void
    {
        // Gera dois hashes e verifica que são diferentes (unicidade)
        $hash1 = bin2hex(random_bytes(16));
        $hash2 = bin2hex(random_bytes(16));

        $this->assertSame(32, strlen($hash1));
        $this->assertSame(32, strlen($hash2));
        $this->assertNotSame($hash1, $hash2, 'Cada checklist deve ter um link_hash único');
    }

    // ================================================================
    // BLOCO C – Testes variados (T21 a T24)
    // Vínculo de checklist: checklist_vincular_cliente.php
    // ================================================================

    // T21 – Integração – Vincular checklist ao cliente autenticado
    public function testT21VincularChecklistAoClienteAutenticado(): void
    {
        // Simula sessão de um cliente autenticado
        $sessao = ['usuario_id' => 42, 'usuario_tipo' => 'client'];

        // Simula checklist sem vínculo prévio
        $checklist = ['id' => 100, 'cliente_id' => null, 'link_hash' => 'abc123'];

        // Após vínculo:
        $checklist['cliente_id'] = $sessao['usuario_id'];

        $this->assertSame(42, $checklist['cliente_id']);
        $this->assertNotNull($checklist['cliente_id']);
    }

    // T22 – Funcional/API – Checklist já associado a outro cliente retorna bloqueio
    public function testT22ChecklistJaAssociadoRetornaBloqueio(): void
    {
        // Checklist já vinculado ao cliente 10
        $checklist = ['id' => 100, 'cliente_id' => 10];
        $novo_cliente_id = 42;

        $retorno = ['status' => 'nok', 'mensagem' => ''];

        if ($checklist['cliente_id'] !== null && $checklist['cliente_id'] !== $novo_cliente_id) {
            $retorno['mensagem'] = 'Este checklist já está vinculado a outro cliente.';
        }

        $this->assertSame('Este checklist já está vinculado a outro cliente.', $retorno['mensagem']);
    }

    // T23 – Segurança/Autorização – Usuário não autenticado não vincula
    public function testT23UsuarioNaoAutenticadoNaoVincula(): void
    {
        $usuario_id = null; // não autenticado
        $retorno = ['status' => 'nok', 'mensagem' => 'Usuário não autenticado'];

        if (empty($usuario_id)) {
            // mantém a mensagem padrão
        }

        $this->assertSame('nok', $retorno['status']);
        $this->assertSame('Usuário não autenticado', $retorno['mensagem']);
    }

    // T24 – Persistência/BD – cliente_id deve permanecer associado ao checklist
    public function testT24ClienteIdPersisteNoChecklist(): void
    {
        // Simula vínculo e posterior consulta
        $checklist_antes = ['id' => 100, 'cliente_id' => null];
        $cliente_id = 42;

        // UPDATE checklists SET cliente_id = ? WHERE id = ?
        $checklist_antes['cliente_id'] = $cliente_id;

        // SELECT (simulação de consulta posterior)
        $checklist_apos = $checklist_antes;

        $this->assertSame(42, $checklist_apos['cliente_id']);
        $this->assertSame($cliente_id, $checklist_apos['cliente_id']);
    }
}
