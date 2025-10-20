<?php
// auth.php - funções simples de autenticação/autorização
// Inclua este arquivo em páginas que precisem verificar login/role.
// Uso: require 'auth.php'; then has_role(['admin']) or require_role('admin').

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se o usuário está logado
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) || !empty($_SESSION['username']);
}

/**
 * Retorna a role do usuário da sessão (se existir), caso contrário 'user'
 */
function user_role(): string {
    return $_SESSION['role'] ?? 'user';
}

/**
 * Verifica se o usuário possui uma das roles fornecidas
 */
function has_role(array $roles): bool {
    $r = user_role();
    return in_array($r, $roles, true);
}

/**
 * Bloqueia acesso caso usuário não tenha a role requerida
 */
function require_role(string $role) {
    if (!is_logged_in() || user_role() !== $role) {
        http_response_code(403);
        // mensagem amigável (pode ser substituída por redirecionamento)
        echo '<div style="color:red;text-align:center;">Acesso negado. Você não tem permissão para acessar este recurso.</div>';
        exit;
    }
}
