<?php
/** @var string $appName */
/** @var string|null $error */
/** @var string|null $lastUsername */
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($appName) ?> – Přihlášení</title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f6f7fb;
            color: #1f2933;
        }

        body {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .card {
            width: min(420px, 100%);
            background: #ffffff;
            border-radius: 18px;
            padding: 2.5rem 2rem;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.12);
        }

        h1 {
            margin: 0 0 1.5rem;
            font-size: 1.75rem;
            text-align: center;
            color: #0f172a;
        }

        .alert {
            margin-bottom: 1.5rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid rgba(220, 38, 38, 0.25);
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 0.9rem;
            border-radius: 10px;
            border: 1px solid #cbd5f5;
            font-size: 1rem;
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            outline: none;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        button[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.85rem 1rem;
            border-radius: 999px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
        }

        .footnote {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
<main class="card" aria-labelledby="admin-login-title">
    <h1 id="admin-login-title"><?= htmlspecialchars($appName) ?></h1>
    <?php if (!empty($error)) : ?>
        <div class="alert" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <form method="post" action="/admin/login" autocomplete="off">
        <div class="form-group">
            <label for="admin-login-username">Uživatelské jméno</label>
            <input type="text" name="username" id="admin-login-username" value="<?= htmlspecialchars((string) ($lastUsername ?? '')) ?>" required>
        </div>
        <div class="form-group">
            <label for="admin-login-password">Heslo</label>
            <input type="password" name="password" id="admin-login-password" required>
        </div>
        <button type="submit">Přihlásit se</button>
    </form>
    <p class="footnote">Výchozí údaje: <strong>admin / admin</strong></p>
</main>
</body>
</html>
