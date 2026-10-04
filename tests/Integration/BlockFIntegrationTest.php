<?php
declare(strict_types=1);

namespace ClientFlow\Tests\Integration;

use mysqli;
use PHPUnit\Framework\TestCase;

/**
 * Bloco F — colaboradores e permissões (T45 a T48).
 *
 * Executa os endpoints REAIS de api/ (em processo PHP isolado) contra um MySQL de teste
 * ("clientflow_test", criado a partir de database/init.sql). O banco do sistema
 * ("clientflow") nunca é tocado.
 *
 * Requer MySQL ativo. Variáveis opcionais: CF_DB_HOST (127.0.0.1), CF_DB_PORT (3306),
 * CF_DB_USER (root), CF_DB_PASS (vazio).
 */
final class BlockFIntegrationTest extends TestCase
{
    private static mysqli $db;

    public static function setUpBeforeClass(): void
    {
        $host  = getenv('CF_DB_HOST') ?: '127.0.0.1';
        $porta = (int) (getenv('CF_DB_PORT') ?: 3306);
        $user  = getenv('CF_DB_USER') ?: 'root';
        $senha = getenv('CF_DB_PASS') !== false ? (string) getenv('CF_DB_PASS') : '';

        try {
            self::$db = new mysqli($host, $user, $senha, '', $porta);
        } catch (\mysqli_sql_exception $e) {
            self::markTestSkipped('MySQL indisponível, testes T45–T48 não executados: ' . $e->getMessage());
        }
        self::$db->set_charset('utf8mb4');

        $banco = EndpointRunner::BANCO_TESTE;
        $sql = file_get_contents(dirname(__DIR__, 2) . '/database/init.sql');
        $sql = preg_replace('/^CREATE DATABASE IF NOT EXISTS clientflow\b/m', "CREATE DATABASE IF NOT EXISTS {$banco}", $sql, 1);
        $sql = preg_replace('/^USE clientflow;/m', "USE {$banco};", $sql, 1);

        self::$db->query("DROP DATABASE IF EXISTS {$banco}");
        self::$db->multi_query($sql);
        do {
            if ($resultado = self::$db->store_result()) {
                $resultado->free();
            }
        } while (self::$db->more_results() && self::$db->next_result());

        self::$db->select_db($banco);
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$db)) {
            self::$db->query('DROP DATABASE IF EXISTS ' . EndpointRunner::BANCO_TESTE);
            self::$db->close();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$db->query('DELETE FROM agencias');
        self::$db->query('DELETE FROM usuarios');
    }

    // ------------------------------------------------------------------
    // Preparação de dados
    // ------------------------------------------------------------------

    private function executar(string $sql, array $params = []): int
    {
        $stmt = self::$db->prepare($sql);
        if ($params) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $id = (int) self::$db->insert_id;
        $stmt->close();

        return $id;
    }

    private function escalar(string $sql, array $params = []): int
    {
        $stmt = self::$db->prepare($sql);
        if ($params) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $linha = $stmt->get_result()->fetch_row();
        $stmt->close();

        return (int) ($linha[0] ?? 0);
    }

    private function texto(string $sql, array $params = []): ?string
    {
        $stmt = self::$db->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $linha = $stmt->get_result()->fetch_row();
        $stmt->close();

        return $linha[0] ?? null;
    }

    /** Agência com assinatura ativa do plano "profissional" (limite de 10 colaboradores). */
    private function criarAgencia(): int
    {
        $agencia = $this->executar('INSERT INTO agencias (nome_empresa) VALUES (?)', ['Agencia Teste']);
        $this->executar(
            "INSERT INTO assinaturas_planos (agencia_id, tipo_plano_id, data_inicio, status)
             SELECT ?, id, CURDATE(), 'ativa' FROM tipos_planos WHERE nome = 'profissional'",
            [$agencia]
        );

        return $agencia;
    }

    private function criarUsuario(string $email, string $tipo = 'agency_member'): int
    {
        return $this->executar(
            "INSERT INTO usuarios (nome, email, senha_hash, tipo, status_conta) VALUES (?, ?, ?, ?, 'aprovado')",
            ['Usuario Teste', $email, password_hash('Senha@123', PASSWORD_DEFAULT), $tipo]
        );
    }

    private function vincular(int $agencia, int $usuario, string $papel, int $gerenciarMembros = 0): int
    {
        return $this->executar(
            'INSERT INTO usuarios_agencia (agencia_id, usuario_id, papel, perm_gerenciar_membros) VALUES (?, ?, ?, ?)',
            [$agencia, $usuario, $papel, $gerenciarMembros]
        );
    }

    private function criarChecklist(int $agencia): int
    {
        return $this->executar(
            'INSERT INTO checklists (agencia_id, titulo, link_hash) VALUES (?, ?, ?)',
            [$agencia, 'Checklist Teste', bin2hex(random_bytes(16))]
        );
    }

    private function designar(int $checklist, int $ua): void
    {
        $this->executar('INSERT INTO projetos_membros (checklist_id, usuario_agencia_id) VALUES (?, ?)', [$checklist, $ua]);
    }

    /** Sessão de um usuário logado como membro de uma agência. */
    private function sessaoAgencia(int $usuario, int $agencia, int $ua, string $papel, bool $gerenciarMembros): array
    {
        return [
            'usuario_id'     => $usuario,
            'usuario_tipo'   => 'agency',
            'agencia_id'     => $agencia,
            'papel_agencia'  => $papel,
            'ua_id'          => $ua,
            'permissoes'     => [
                'perm_gerenciar_membros' => $gerenciarMembros,
                'perm_designar_projetos' => false,
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Testes
    // ------------------------------------------------------------------

    /** T45 — cadastrar colaborador cria o usuário e o vínculo com a agência. */
    public function testT45CadastrarColaboradorCriaUsuarioEVinculo(): void
    {
        $agencia = $this->criarAgencia();
        $admin   = $this->criarUsuario('admin45@teste.com', 'agency');
        $adminUa = $this->vincular($agencia, $admin, 'admin_agencia', 1);

        $resposta = EndpointRunner::executar(
            'membro_cadastrar.php',
            ['nome' => 'Colaborador Novo', 'email' => 'novo45@teste.com', 'senha' => 'Senha@123', 'papel' => 'dev'],
            $this->sessaoAgencia($admin, $agencia, $adminUa, 'admin_agencia', true)
        );

        $this->assertSame('ok', $resposta['status']);
        $this->assertSame('Membro adicionado com sucesso!', $resposta['mensagem']);

        $this->assertSame(1, $this->escalar("SELECT COUNT(*) FROM usuarios WHERE email = ? AND tipo = 'agency_member'", ['novo45@teste.com']));
        $this->assertSame(1, $this->escalar(
            "SELECT COUNT(*) FROM usuarios_agencia ua JOIN usuarios u ON u.id = ua.usuario_id
             WHERE u.email = ? AND ua.agencia_id = ? AND ua.papel = 'dev' AND ua.ativo = 1",
            ['novo45@teste.com', $agencia]
        ));
    }

    /** T46 — e-mail já cadastrado é rejeitado e nenhum registro novo é criado. */
    public function testT46EmailJaCadastradoERejeitado(): void
    {
        $agencia = $this->criarAgencia();
        $admin   = $this->criarUsuario('admin46@teste.com', 'agency');
        $adminUa = $this->vincular($agencia, $admin, 'admin_agencia', 1);
        $this->criarUsuario('duplicado46@teste.com');

        $usuariosAntes  = $this->escalar('SELECT COUNT(*) FROM usuarios');
        $vinculosAntes  = $this->escalar('SELECT COUNT(*) FROM usuarios_agencia WHERE agencia_id = ?', [$agencia]);

        $resposta = EndpointRunner::executar(
            'membro_cadastrar.php',
            ['nome' => 'Outro Nome', 'email' => 'duplicado46@teste.com', 'senha' => 'Senha@123', 'papel' => 'dev'],
            $this->sessaoAgencia($admin, $agencia, $adminUa, 'admin_agencia', true)
        );

        $this->assertSame('nok', $resposta['status']);
        $this->assertSame(
            'Este e-mail já está cadastrado no sistema. Utilize outro e-mail para este colaborador.',
            $resposta['mensagem']
        );
        $this->assertSame($usuariosAntes, $this->escalar('SELECT COUNT(*) FROM usuarios'));
        $this->assertSame($vinculosAntes, $this->escalar('SELECT COUNT(*) FROM usuarios_agencia WHERE agencia_id = ?', [$agencia]));
        $this->assertSame(1, $this->escalar('SELECT COUNT(*) FROM usuarios WHERE email = ?', ['duplicado46@teste.com']));
    }

    /** T47 — quem não é admin do sistema não consegue alterar o status de outra conta. */
    public function testT47NaoAdminNaoAlteraStatusDeOutraConta(): void
    {
        $alvo = $this->criarUsuario('alvo47@teste.com', 'client');

        $resposta = EndpointRunner::executar(
            'admin_usuario_atualizar_status.php',
            ['usuario_id' => $alvo, 'status' => 'banido'],
            ['usuario_id' => 999, 'usuario_tipo' => 'agency']
        );

        $this->assertSame('nok', $resposta['status']);
        $this->assertSame('Perfil sem permissão para acessar esta área.', $resposta['mensagem']);
        $this->assertSame('aprovado', $this->texto('SELECT status_conta FROM usuarios WHERE id = ?', [$alvo]));

        // Controle: o mesmo pedido feito por um admin funciona (prova que o teste não é vazio).
        $respostaAdmin = EndpointRunner::executar(
            'admin_usuario_atualizar_status.php',
            ['usuario_id' => $alvo, 'status' => 'banido'],
            ['usuario_id' => 1, 'usuario_tipo' => 'admin']
        );
        $this->assertSame('ok', $respostaAdmin['status']);
        $this->assertSame('banido', $this->texto('SELECT status_conta FROM usuarios WHERE id = ?', [$alvo]));
    }

    /** T48 — desativar membro marca o vínculo como inativo e remove só as designações dele. */
    public function testT48DesativarMembroAtualizaVinculoEDesignacoes(): void
    {
        $agencia   = $this->criarAgencia();
        $admin     = $this->criarUsuario('admin48@teste.com', 'agency');
        $adminUa   = $this->vincular($agencia, $admin, 'admin_agencia', 1);
        $alvo      = $this->criarUsuario('alvo48@teste.com');
        $alvoUa    = $this->vincular($agencia, $alvo, 'dev');
        $outro     = $this->criarUsuario('outro48@teste.com');
        $outroUa   = $this->vincular($agencia, $outro, 'dev');
        $checklist = $this->criarChecklist($agencia);
        $this->designar($checklist, $alvoUa);
        $this->designar($checklist, $outroUa);

        $resposta = EndpointRunner::executar(
            'membro_excluir.php',
            ['ua_id' => $alvoUa],
            $this->sessaoAgencia($admin, $agencia, $adminUa, 'admin_agencia', true)
        );

        $this->assertSame('ok', $resposta['status']);
        $this->assertSame('Membro desativado com sucesso.', $resposta['mensagem']);

        $this->assertSame(0, $this->escalar('SELECT ativo FROM usuarios_agencia WHERE id = ?', [$alvoUa]));
        $this->assertSame(0, $this->escalar('SELECT COUNT(*) FROM projetos_membros WHERE usuario_agencia_id = ?', [$alvoUa]));

        // O usuário não é excluído; admin e outro membro continuam intactos.
        $this->assertSame(1, $this->escalar('SELECT COUNT(*) FROM usuarios WHERE id = ?', [$alvo]));
        $this->assertSame(1, $this->escalar('SELECT ativo FROM usuarios_agencia WHERE id = ?', [$adminUa]));
        $this->assertSame(1, $this->escalar('SELECT ativo FROM usuarios_agencia WHERE id = ?', [$outroUa]));
        $this->assertSame(1, $this->escalar('SELECT COUNT(*) FROM projetos_membros WHERE usuario_agencia_id = ?', [$outroUa]));
    }
}
