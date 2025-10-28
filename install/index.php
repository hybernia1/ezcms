<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/load.php';
require_once dirname(__DIR__, 2) . '/inc/Class/Core/Translation/functions.php';

use Core\Database\Connect;
use Core\Database\Init;
use Core\Mail\Mailer;
use Core\Translation\Translator;
use Core\Utils\Forms;

session_start();

const INSTALLER_SESSION_KEY = 'ezcms_installer';

$configPath = dirname(__DIR__, 2) . '/config.php';

if (is_file($configPath)) {
    http_response_code(409);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Installer</title>';
    echo '<style>body{font-family:system-ui,sans-serif;background:#f8fafc;color:#0f172a;padding:4rem;text-align:center;}a{color:#2563eb;text-decoration:none;}a:hover{text-decoration:underline;}</style>';
    echo '</head><body><h1>Application already installed</h1>';
    echo '<p>The configuration file already exists. Remove <code>config.php</code> if you wish to reinstall.</p>';
    echo '<p><a href="../">Go back</a></p>';
    echo '</body></html>';
    return;
}

$availableLanguages = [
    'CZ' => 'Čeština',
    'EN' => 'English',
];

$installer = $_SESSION[INSTALLER_SESSION_KEY] ?? [
    'language' => 'CZ',
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'database' => '',
        'user' => '',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'db_tested' => false,
];

$step = (int)($_GET['step'] ?? 1);
$step = max(1, min(4, $step));

$language = $installer['language'] ?? 'EN';
$translator = new Translator(defaultLocale: $language, fallbackLocale: 'EN');
Translator::setGlobal($translator);

$errors = [];
$success = null;
$adminForm = [
    'email' => '',
    'name' => '',
];

$goToStep = static function (int $targetStep): void {
    header('Location: ?step=' . $targetStep);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            $lang = strtoupper(trim((string)($_POST['language'] ?? '')));
            if (!isset($availableLanguages[$lang])) {
                $errors[] = __('installer.language.error');
            } else {
                $installer['language'] = $lang;
                $_SESSION[INSTALLER_SESSION_KEY] = $installer;
                $goToStep(2);
            }
            break;

        case 2:
            $host     = trim((string)($_POST['host'] ?? ''));
            $port     = trim((string)($_POST['port'] ?? '3306'));
            $database = trim((string)($_POST['database'] ?? ''));
            $user     = trim((string)($_POST['user'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $charset  = trim((string)($_POST['charset'] ?? 'utf8mb4'));

            if ($host === '') {
                $errors[] = __('installer.db.error.host');
            }
            if ($database === '') {
                $errors[] = __('installer.db.error.database');
            }
            if ($user === '') {
                $errors[] = __('installer.db.error.user');
            }

            if ($errors === []) {
                $installer['db'] = [
                    'driver'   => 'mysql',
                    'host'     => $host,
                    'port'     => $port !== '' ? $port : '3306',
                    'database' => $database,
                    'user'     => $user,
                    'password' => $password,
                    'charset'  => $charset !== '' ? $charset : 'utf8mb4',
                ];
                $installer['db_tested'] = false;
                $_SESSION[INSTALLER_SESSION_KEY] = $installer;
                $goToStep(3);
            }
            break;

        case 3:
            try {
                $connect = new Connect(['db' => $installer['db']]);
                $pdo = $connect->pdo();
                $pdo->query('SELECT 1');
                $installer['db_tested'] = true;
                $_SESSION[INSTALLER_SESSION_KEY] = $installer;
                $success = __('installer.db.test.success');
            } catch (\Throwable $e) {
                $errors[] = __('installer.db.test.failure', ['message' => $e->getMessage()]);
            }
            break;

        case 4:
            if (($installer['db_tested'] ?? false) !== true) {
                $errors[] = __('installer.final.error.notTested');
                break;
            }

            $adminEmail    = trim((string)($_POST['admin_email'] ?? ''));
            $adminName     = trim((string)($_POST['admin_name'] ?? ''));
            $adminPassword = (string)($_POST['admin_password'] ?? '');
            $adminConfirm  = (string)($_POST['admin_password_confirm'] ?? '');

            $adminForm['email'] = $adminEmail;
            $adminForm['name'] = $adminName;

            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = __('installer.admin.error.email');
            }
            if ($adminName === '') {
                $errors[] = __('installer.admin.error.name');
            }
            if (strlen($adminPassword) < 8) {
                $errors[] = __('installer.admin.error.passwordLength');
            }
            if ($adminPassword !== $adminConfirm) {
                $errors[] = __('installer.admin.error.passwordMatch');
            }

            if ($errors !== []) {
                break;
            }

            try {
                $dbConfig = ['db' => $installer['db']];
                Init::boot($dbConfig);
                $pdo = Init::pdo();
                $tables = require __DIR__ . '/tables.php';
                foreach ($tables as $sql) {
                    $pdo->exec($sql);
                }

                $hash = password_hash($adminPassword, PASSWORD_DEFAULT);

                $query = Init::query();
                $query->table('users')->insert([
                    'email' => $adminEmail,
                    'password' => $hash,
                    'name' => $adminName,
                    'role' => 'admin',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ])->execute();

                $config = [
                    'app' => [
                        'locale' => $installer['language'],
                        'installed_at' => date('c'),
                    ],
                    'db' => $installer['db'],
                ];

                $configContents = "<?php\n";
                $configContents .= "declare(strict_types=1);\n\n";
                $configContents .= 'return ' . var_export($config, true) . ';' . "\n";

                if (@file_put_contents($configPath, $configContents, LOCK_EX) === false) {
                    throw new \RuntimeException('Unable to write config.php');
                }

                $mailer = new Mailer();
                $loginBody = '<p>' . __('installer.mail.body.intro') . '</p>';
                $loginBody .= '<p>' . __('installer.mail.body.credentials') . '</p>';
                $loginBody .= '<p><strong>' . __('installer.admin.email') . ':</strong> ' . htmlspecialchars($adminEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br />';
                $loginBody .= '<strong>' . __('installer.admin.password') . ':</strong> ' . htmlspecialchars($adminPassword, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';

                $mailer->send($adminEmail, __('installer.mail.subject'), $loginBody);

                unset($_SESSION[INSTALLER_SESSION_KEY]);
                session_regenerate_id(true);

                header('Location: ../');
                exit;
            } catch (\Throwable $e) {
                $errors[] = __('installer.final.error.generic', ['message' => $e->getMessage()]);
            }
            break;
    }
}

// Guard steps if user tries to skip ahead
if ($step > 1 && !isset($installer['language'])) {
    $goToStep(1);
}
if ($step > 2 && ($installer['db']['database'] ?? '') === '') {
    $goToStep(2);
}
if ($step > 3 && ($installer['db_tested'] ?? false) !== true) {
    $goToStep(3);
}

$language = $installer['language'] ?? 'EN';
$translator->setLocale($language);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="<?= strtolower(h($translator->getLocale())) ?>">
<head>
    <meta charset="utf-8">
    <title><?= h(__('installer.title')) ?></title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #0f172a;
            --fg: #f8fafc;
            --card-bg: rgba(15, 23, 42, 0.85);
            --card-fg: #e2e8f0;
            --accent: #38bdf8;
            --error: #f87171;
            --success: #34d399;
        }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: var(--fg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: var(--card-bg);
            color: var(--card-fg);
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: 0 40px 80px rgba(15, 23, 42, 0.45);
            width: min(620px, 100%);
            backdrop-filter: blur(18px);
        }
        h1 {
            margin-top: 0;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        p.lead {
            color: rgba(226, 232, 240, 0.8);
            margin-top: 0;
        }
        .steps {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 0.65rem 0.5rem;
            border-radius: 0.75rem;
            background: rgba(148, 163, 184, 0.2);
            font-size: 0.85rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .step.active {
            background: var(--accent);
            color: #0f172a;
            font-weight: 600;
        }
        form {
            display: grid;
            gap: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
            letter-spacing: 0.03em;
        }
        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: rgba(15, 23, 42, 0.6);
            color: inherit;
            font-size: 1rem;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25);
        }
        button, .button {
            background: var(--accent);
            color: #0f172a;
            font-weight: 700;
            border: none;
            padding: 0.85rem 1.25rem;
            border-radius: 0.85rem;
            cursor: pointer;
            font-size: 1rem;
            letter-spacing: 0.02em;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        button:hover, .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 30px rgba(56, 189, 248, 0.35);
        }
        button.secondary {
            background: transparent;
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: inherit;
        }
        ul.messages {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem;
        }
        ul.messages li {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
        }
        ul.messages li.error {
            background: rgba(248, 113, 113, 0.15);
            border: 1px solid rgba(248, 113, 113, 0.45);
            color: #fee2e2;
        }
        ul.messages li.success {
            background: rgba(52, 211, 153, 0.15);
            border: 1px solid rgba(52, 211, 153, 0.4);
            color: #dcfce7;
        }
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .actions .left {
            margin-right: auto;
        }
        .info-box {
            background: rgba(148, 163, 184, 0.12);
            padding: 1rem;
            border-radius: 0.85rem;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        code {
            font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1><?= h(__('installer.title')) ?></h1>
        <p class="lead"><?= h(__('installer.subtitle')) ?></p>

        <div class="steps">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="step<?= $step === $i ? ' active' : '' ?>"><?= h(__('installer.step.' . $i)) ?></div>
            <?php endfor; ?>
        </div>

        <?php if ($errors !== []): ?>
            <ul class="messages">
                <?php foreach ($errors as $error): ?>
                    <li class="error"><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($success !== null): ?>
            <ul class="messages">
                <li class="success"><?= h($success) ?></li>
            </ul>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <?= Forms::open('?step=1', 'post') ?>
                <div>
                    <?= Forms::label('language', __('installer.language.label')) ?>
                    <?= Forms::select('language', $availableLanguages, $installer['language'] ?? 'CZ', ['id' => 'language']) ?>
                </div>
                <div class="actions">
                    <span class="left"></span>
                    <button type="submit"><?= h(__('common.continue')) ?></button>
                </div>
            <?= Forms::close() ?>
        <?php elseif ($step === 2): ?>
            <?= Forms::open('?step=2', 'post') ?>
                <div>
                    <?= Forms::label('host', __('installer.db.host')) ?>
                    <?= Forms::input('host', $installer['db']['host'] ?? '', 'text', ['id' => 'host', 'required' => true]) ?>
                </div>
                <div>
                    <?= Forms::label('port', __('installer.db.port')) ?>
                    <?= Forms::input('port', $installer['db']['port'] ?? '3306', 'number', ['id' => 'port', 'min' => '1', 'max' => '65535']) ?>
                </div>
                <div>
                    <?= Forms::label('database', __('installer.db.database')) ?>
                    <?= Forms::input('database', $installer['db']['database'] ?? '', 'text', ['id' => 'database', 'required' => true]) ?>
                </div>
                <div>
                    <?= Forms::label('user', __('installer.db.user')) ?>
                    <?= Forms::input('user', $installer['db']['user'] ?? '', 'text', ['id' => 'user', 'required' => true]) ?>
                </div>
                <div>
                    <?= Forms::label('password', __('common.password')) ?>
                    <?= Forms::input('password', $installer['db']['password'] ?? '', 'password', ['id' => 'password']) ?>
                </div>
                <div>
                    <?= Forms::label('charset', __('installer.db.charset')) ?>
                    <?= Forms::input('charset', $installer['db']['charset'] ?? 'utf8mb4', 'text', ['id' => 'charset']) ?>
                </div>
                <div class="actions">
                    <a class="button secondary left" href="?step=1"><?= h(__('common.back')) ?></a>
                    <button type="submit"><?= h(__('common.continue')) ?></button>
                </div>
            <?= Forms::close() ?>
        <?php elseif ($step === 3): ?>
            <div class="info-box">
                <?= h(__('installer.db.test.info')) ?>
            </div>
            <?= Forms::open('?step=3', 'post') ?>
                <div class="actions">
                    <a class="button secondary left" href="?step=2"><?= h(__('common.back')) ?></a>
                    <button type="submit"><?= h(__('installer.db.test.button')) ?></button>
                </div>
            <?= Forms::close() ?>
            <?php if (($installer['db_tested'] ?? false) === true): ?>
                <div class="actions" style="margin-top:1.5rem;">
                    <a class="button" href="?step=4"><?= h(__('common.continue')) ?></a>
                </div>
            <?php endif; ?>
        <?php elseif ($step === 4): ?>
            <div class="info-box">
                <?= h(__('installer.final.info')) ?>
            </div>
            <?= Forms::open('?step=4', 'post') ?>
                <div>
                    <?= Forms::label('admin_email', __('installer.admin.email')) ?>
                    <?= Forms::input('admin_email', $adminForm['email'], 'email', ['id' => 'admin_email', 'required' => true]) ?>
                </div>
                <div>
                    <?= Forms::label('admin_name', __('installer.admin.name')) ?>
                    <?= Forms::input('admin_name', $adminForm['name'], 'text', ['id' => 'admin_name', 'required' => true]) ?>
                </div>
                <div>
                    <?= Forms::label('admin_password', __('installer.admin.password')) ?>
                    <?= Forms::input('admin_password', '', 'password', ['id' => 'admin_password', 'required' => true]) ?>
                </div>
                <div>
                    <?= Forms::label('admin_password_confirm', __('common.password.confirm')) ?>
                    <?= Forms::input('admin_password_confirm', '', 'password', ['id' => 'admin_password_confirm', 'required' => true]) ?>
                </div>
                <div class="actions">
                    <a class="button secondary left" href="?step=3"><?= h(__('common.back')) ?></a>
                    <button type="submit"><?= h(__('installer.install')) ?></button>
                </div>
            <?= Forms::close() ?>
        <?php endif; ?>
    </div>
</body>
</html>
