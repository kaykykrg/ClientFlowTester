<?php
declare(strict_types=1);

namespace ClientFlow\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Bloco F — permissões da sessão (T41 a T44).
 *
 * Exercita a função REAL montar_permissoes_sessao(), carregada de
 * api/permissoes_sessao.php (o mesmo arquivo usado por api/usuario_login.php).
 */
final class BlockFTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../api/permissoes_sessao.php';
    }

    /** Monta uma linha de usuarios_agencia com as seis chaves, todas 0, e altera só uma. */
    private function vinculo(string $chave, $valor): array
    {
        $ua = [
            'perm_ver_clientes'      => 0,
            'perm_criar_clientes'    => 0,
            'perm_ver_projetos'      => 0,
            'perm_criar_projetos'    => 0,
            'perm_designar_projetos' => 0,
            'perm_gerenciar_membros' => 0,
        ];
        $ua[$chave] = $valor;

        return $ua;
    }

    /** 0 e "0" viram false; 1 e "1" viram true — sempre com tipo booleano estrito. */
    private function verificarConversaoBooleana(string $chave): void
    {
        foreach ([0, '0'] as $valor) {
            $resultado = \montar_permissoes_sessao($this->vinculo($chave, $valor));

            $this->assertArrayHasKey($chave, $resultado);
            $this->assertSame(false, $resultado[$chave], "Entrada " . var_export($valor, true) . " deveria virar false");
        }

        foreach ([1, '1'] as $valor) {
            $resultado = \montar_permissoes_sessao($this->vinculo($chave, $valor));

            $this->assertArrayHasKey($chave, $resultado);
            $this->assertSame(true, $resultado[$chave], "Entrada " . var_export($valor, true) . " deveria virar true");
        }
    }

    /** T41 — perm_ver_clientes vira booleano. */
    public function testT41PermVerClientesViraBooleano(): void
    {
        $this->verificarConversaoBooleana('perm_ver_clientes');
    }

    /** T42 — perm_criar_clientes vira booleano. */
    public function testT42PermCriarClientesViraBooleano(): void
    {
        $this->verificarConversaoBooleana('perm_criar_clientes');
    }

    /** T43 — perm_ver_projetos vira booleano. */
    public function testT43PermVerProjetosViraBooleano(): void
    {
        $this->verificarConversaoBooleana('perm_ver_projetos');
    }

    /** T44 — perm_criar_projetos vira booleano. */
    public function testT44PermCriarProjetosViraBooleano(): void
    {
        $this->verificarConversaoBooleana('perm_criar_projetos');
    }
}
