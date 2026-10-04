<?php
session_start();

$appName = getenv('APP_NAME') ?: 'Company Management';
$dbError = null;

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $url = trim((string)getenv('DATABASE_URL'));
    if ($url === '') {
        throw new Exception('DATABASE_URL is not configured. In Render, open Web Service → Environment and set DATABASE_URL to the PostgreSQL Internal Database URL from company-management-db, or sync the Blueprint.');
    }

    // Allow a URL copied with surrounding quotes from an environment-variable editor.
    if (strlen($url) >= 2) {
        $first = $url[0];
        $last = $url[strlen($url) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $url = trim(substr($url, 1, -1));
        }
    }

    // Render Postgres supports both schemes; normalize postgres:// for PDO/libpq.
    if (stripos($url, 'postgres://') === 0) {
        $url = 'postgresql://' . substr($url, strlen('postgres://'));
    }

    $parts = @parse_url($url);
    if ($parts === false) {
        throw new Exception('DATABASE_URL cannot be parsed. Copy the complete Internal Database URL from Render Postgres → Connect.');
    }

    $scheme = strtolower($parts['scheme'] ?? '');
    if (!in_array($scheme, ['postgresql', 'postgres'], true)) {
        // Give a more useful message when the Render Dashboard label itself was pasted.
        if (stripos($url, 'internal database url') !== false || stripos($url, 'database url') !== false) {
            throw new Exception('DATABASE_URL contains a label instead of a PostgreSQL URL. In Render Postgres → Connect, copy the actual Internal Database URL beginning with postgresql:// and paste it into Web Service → Environment → DATABASE_URL.');
        }
        throw new Exception('DATABASE_URL must start with postgresql:// or postgres://. Use the actual Internal Database URL from Render Postgres → Connect.');
    }

    $host = $parts['host'] ?? '';
    $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
    $pass = array_key_exists('pass', $parts) ? rawurldecode($parts['pass']) : '';
    $port = isset($parts['port']) ? (int)$parts['port'] : 5432;
    $dbname = isset($parts['path']) ? ltrim(rawurldecode($parts['path']), '/') : '';

    if ($host === '' || $user === '' || $dbname === '') {
        throw new Exception('DATABASE_URL is incomplete. It must contain host, username, password, and database name. Use the complete Internal Database URL from Render.');
    }
    if ($port < 1 || $port > 65535) {
        throw new Exception('DATABASE_URL contains an invalid PostgreSQL port.');
    }

    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;connect_timeout=10', $host, $port, $dbname);
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
        if (!empty($query['sslmode'])) {
            $sslmode = preg_replace('/[^a-z0-9_-]/i', '', (string)$query['sslmode']);
            if ($sslmode !== '') $dsn .= ';sslmode=' . $sslmode;
        }
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Never expose the password or full DATABASE_URL.
        throw new Exception('PostgreSQL connection failed: ' . $e->getMessage());
    }

    return $pdo;
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function require_login(){ if(empty($_SESSION['user'])) { header('Location: /login.php'); exit; } }
function require_admin(){ require_login(); if($_SESSION['user']['role'] !== 'admin'){ http_response_code(403); exit('Forbidden'); } }
function flash($type,$msg){ $_SESSION['flash']=[$type,$msg]; }
function get_flash(){ $x=$_SESSION['flash']??null; unset($_SESSION['flash']); return $x; }
function csrf(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(){ if(!hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')){ http_response_code(419); exit('Invalid CSRF token'); } }

function setup_database(){
    $pdo = db();
    $schemaPath = '/var/www/sql/schema.sql';
    if (!is_file($schemaPath)) {
        throw new Exception('Database schema file is missing.');
    }

    // PostgreSQL supports executing this schema as a single command string.
    $schema = file_get_contents($schemaPath);
    if ($schema === false) {
        throw new Exception('Unable to read database schema.');
    }
    $pdo->exec($schema);

    $pdo->exec("CREATE TABLE IF NOT EXISTS warehouse_reset_logs (
        id BIGSERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        product_count_before INTEGER NOT NULL DEFAULT 0,
        movement_count_before INTEGER NOT NULL DEFAULT 0,
        stock_total_before INTEGER NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_reset_logs_created ON warehouse_reset_logs(created_at)');

    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $s = $pdo->prepare('INSERT INTO users(username,password_hash,full_name,role,active) VALUES(?,?,?,?,TRUE)');
        $s->execute([
            'admin',
            password_hash('Admin@123', PASSWORD_DEFAULT),
            'System Administrator',
            'admin'
        ]);
    }
}

function current_user_id(){ return (int)($_SESSION['user']['id'] ?? 0); }
function is_admin(){ return ($_SESSION['user']['role'] ?? '') === 'admin'; }

try {
    setup_database();
} catch(Throwable $e) {
    $dbError = $e->getMessage();
}
