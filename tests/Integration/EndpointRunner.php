<?php
declare(strict_types=1);

namespace ClientFlow\Tests\Integration;

use RuntimeException;

/**
 * Executa os endpoints reais de api/ em um processo PHP isolado e devolve o JSON da resposta.
 * Isolamento necessário porque os endpoints abrem conexão, chamam session_start() e exit().
 */
final class EndpointRunner
{
    /** Banco exclusivo dos testes. Nunca é o banco "clientflow" do sistema. */
    public const BANCO_TESTE = 'clientflow_test';

    public static function executar(string $endpoint, array $post = [], array $sessao = []): array
    {
        $sessionId = 'cftest' . bin2hex(random_bytes(8));

        $env = array_merge(getenv(), [
            'CF_DB_NAME' => self::BANCO_TESTE,
            'CF_HARNESS' => json_encode([
                'endpoint'   => $endpoint,
                'post'       => $post,
                'session'    => $sessao,
                'session_id' => $sessionId,
            ]),
        ]);

        $processo = proc_open(
            [PHP_BINARY, __DIR__ . DIRECTORY_SEPARATOR . 'endpoint_child.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $env
        );

        if (!is_resource($processo)) {
            throw new RuntimeException('Não foi possível iniciar o processo PHP do endpoint.');
        }

        $saida = stream_get_contents($pipes[1]);
        $erros = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($processo);

        $json = json_decode((string) $saida, true);
        if (!is_array($json)) {
            throw new RuntimeException(
                "Resposta inválida de {$endpoint}.\nSaída: {$saida}\nErros: {$erros}"
            );
        }

        return $json;
    }
}
