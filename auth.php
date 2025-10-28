<?php
// auth.php - autenticação/autorizaçao mais flexível
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Helpers de debug */
function _auth_log($msg) {
    // registra no log do servidor (XAMPP: apache\logs\error.log)
    error_log('auth.php: ' . $msg);
}

/** Checa se existe alguma indicação de usuário logado em várias chaves possíveis */
function is_logged_in(): bool {
    // chaves comuns (pt/en)
    $cands = [
        'user_id', 'id', 'usuario_id', 'usuarioId', 'userid',
        'username', 'usuario', 'user', 'nome'
    ];
    foreach ($cands as $k) {
        if (!empty($_SESSION[$k])) return true;
    }
    return false;
}

/** Retorna role/nível conhecido na sessão (mais de uma opção de chave) */
function user_role(): string {
    // tenta chaves que costumam existir no seu projeto
    $keys = ['role', 'role_name', 'tipo', 'nivel', 'nivel_acesso', 'permission', 'permissao'];
    foreach ($keys as $k) {
        if (isset($_SESSION[$k]) && $_SESSION[$k] !== '') {
            // se 'nivel' for numérico, converte para string 'admin' se alto
            if ($k === 'nivel' || $k === 'nivel_acesso') {
                if (is_numeric($_SESSION[$k])) {
                    $n = intval($_SESSION[$k]);
                    if ($n >= 10) return 'admin';
                    if ($n >= 5) return 'editor';
                    return 'user';
                }
            }
            return (string) $_SESSION[$k];
        }
    }
    // fallback: se existir um usuário com nome 'admin' (raro) - não considerar
    return 'user';
}

/** Normaliza role para comparação (lowercase, trim) */
function _normalize_role($r): string {
    return strtolower(trim((string)$r));
}

/** Verifica se o usuário tem uma (ou várias) roles.
 *  $roles pode ser array ou string
 */
function has_role($roles): bool {
    if (!is_logged_in()) return false;
    $current = _normalize_role(user_role());
    if (is_string($roles)) {
        $roles = [$roles];
    }
    foreach ($roles as $role) {
        if (_normalize_role($role) === $current) return true;
    }
    return false;
}

/** Exige que o usuário tenha determinada role (string ou array).
 *  Se não, retorna 403 e encerra. */
function require_role($role_or_array) {
    // aceitar string ou array
    if (is_string($role_or_array)) {
        $roles = [$role_or_array];
    } else {
        $roles = (array)$role_or_array;
    }

    // Se sessão indica que está logado e tem role conhecida -> validar
    if (is_logged_in()) {
        foreach ($roles as $r) {
            if (has_role($r)) {
                return; // autorizado
            }
        }
    }

    // Tentativa extra: se existirem chaves de id na sessão e houver conexão $conn,
    // podemos tentar buscar a role no DB (apenas se $conn existir)
    if (!empty($_SESSION['user_id']) || !empty($_SESSION['usuario_id']) || !empty($_SESSION['id'])) {
        $uid = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;
        if ($uid && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            $stmt = $GLOBALS['conn']->prepare("SELECT role, role_name, tipo, nivel FROM usuarios WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $uid);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        // tenta várias colunas
                        $dbRole = $row['role'] ?? $row['role_name'] ?? $row['tipo'] ?? null;
                        if ($dbRole) {
                            foreach ($roles as $r) {
                                if (_normalize_role($dbRole) === _normalize_role($r)) {
                                    // opcional: sincronizar para sessão
                                    $_SESSION['role'] = $dbRole;
                                    return;
                                }
                            }
                        }
                        if (isset($row['nivel']) && is_numeric($row['nivel'])) {
                            $nivel = intval($row['nivel']);
                            if ($nivel >= 10 && in_array('admin', array_map('_normalize_role', $roles))) {
                                $_SESSION['role'] = 'admin';
                                return;
                            }
                        }
                    }
                }
                $stmt->close();
            } else {
                _auth_log('require_role: falha prepare para buscar role no DB: ' . $GLOBALS['conn']->error);
            }
        }
    }

    // Não autorizado -> 403
    http_response_code(403);
    echo '<div style="color:red;text-align:center;">Acesso negado. Você não tem permissão para acessar este recurso.</div>';
    _auth_log('Acesso negado. Sessao: ' . var_export($_SESSION, true));
    exit;
}
