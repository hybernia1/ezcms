<?php
/** @var string $appName */
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($appName) ?> – Přehled</title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        body {
            margin: 0;
            padding: 3rem 1.5rem 4rem;
            display: flex;
            justify-content: center;
        }

        .container {
            width: min(960px, 100%);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        h1 {
            margin: 0;
            font-size: 2rem;
        }

        .welcome {
            padding: 2rem;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(14, 116, 144, 0.08));
            border: 1px solid rgba(59, 130, 246, 0.2);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
        }

        .welcome h2 {
            margin-top: 0;
            font-size: 1.5rem;
        }

        .quick-links {
            margin-top: 2rem;
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .quick-link {
            padding: 1.25rem;
            border-radius: 16px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .quick-link h3 {
            margin: 0 0 0.5rem;
            font-size: 1.05rem;
        }

        .quick-link p {
            margin: 0;
            color: #475569;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1><?= htmlspecialchars($appName) ?></h1>
        <span>verze 0.1</span>
    </header>
    <section class="welcome">
        <h2>Vítejte v administraci</h2>
        <p>Odsud budete spravovat obsah webu, uživatele, nastavení a vzhled. Tato úvodní obrazovka slouží jako rychlý přehled o tom, co vás v systému čeká.</p>
    </section>
    <section class="quick-links">
        <article class="quick-link">
            <h3>Obsah</h3>
            <p>Spravujte stránky, články a další obsah webu.</p>
        </article>
        <article class="quick-link">
            <h3>Uživatelé</h3>
            <p>Přidávejte nové správce, upravujte oprávnění a auditujte akce.</p>
        </article>
        <article class="quick-link">
            <h3>Nastavení</h3>
            <p>Nakonfigurujte základní chování systému, integrace a moduly.</p>
        </article>
    </section>
</div>
</body>
</html>
