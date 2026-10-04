<?php
/**
 * test_helpers.php
 *
 * Extrai e redefine funções puras dos scripts da API do ClientFlow
 * para que possam ser testadas isoladamente pelo PHPUnit,
 * sem disparar session_start(), include de db_conexao.php, etc.
 */

// ========================
// Funções de checklist_criar.php / template_salvar.php
// ========================

if (!function_exists('parse_int_or_null')) {
    function parse_int_or_null($value) {
        if ($value === null || $value === '') return null;
        if (!is_numeric($value)) return null;
        $parsed = intval($value);
        return $parsed > 0 ? $parsed : null;
    }
}

if (!function_exists('normalize_extensions')) {
    function normalize_extensions($value) {
        if (is_array($value)) {
            return null;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $parts = explode(',', strtolower($value));
        $normalized = [];

        foreach ($parts as $part) {
            $clean = preg_replace('/[^a-z0-9]/', '', trim($part));
            if ($clean !== '' && !in_array($clean, $normalized, true)) {
                $normalized[] = $clean;
            }
        }

        return count($normalized) ? implode(',', $normalized) : null;
    }
}

if (!function_exists('normalizar_item_template')) {
    function normalizar_item_template($item) {
        $nome = trim((string)($item['nome'] ?? ''));
        if ($nome === '') {
            return null;
        }

        $tipo = strtolower(trim((string)($item['tipo'] ?? 'text')));
        $tipos_validos = ['text', 'long_text', 'url', 'file', 'image', 'color'];
        if (!in_array($tipo, $tipos_validos, true)) {
            $tipo = 'text';
        }

        $min_chars = parse_int_or_null($item['min_chars'] ?? null);
        $max_chars = parse_int_or_null($item['max_chars'] ?? null);
        if ($min_chars !== null && $max_chars !== null && $min_chars > $max_chars) {
            $tmp = $min_chars;
            $min_chars = $max_chars;
            $max_chars = $tmp;
        }

        return [
            "nome" => $nome,
            "tipo" => $tipo,
            "descricao" => trim((string)($item['descricao'] ?? '')),
            "min_chars" => $min_chars,
            "max_chars" => $max_chars,
            "allowed_extensions" => normalize_extensions($item['allowed_extensions'] ?? null),
            "max_file_size_kb" => parse_int_or_null($item['max_file_size_kb'] ?? null),
            "min_width" => parse_int_or_null($item['min_width'] ?? null),
            "max_width" => parse_int_or_null($item['max_width'] ?? null),
            "min_height" => parse_int_or_null($item['min_height'] ?? null),
            "max_height" => parse_int_or_null($item['max_height'] ?? null)
        ];
    }
}

// ========================
// Funções de usuario_login.php
// ========================

// Função real, carregada de api/permissoes_sessao.php (a mesma usada por usuario_login.php)
require_once __DIR__ . '/../api/permissoes_sessao.php';

// ========================
// Função auxiliar para default_extensions_for_type (Bloco D - T28)
// ========================

if (!function_exists('default_extensions_for_type')) {
    function default_extensions_for_type($tipo) {
        $mapa = [
            'image' => 'jpg,jpeg,png,gif,webp',
            'file'  => 'pdf,doc,docx,xls,xlsx,csv,txt',
        ];
        return $mapa[$tipo] ?? null;
    }
}
