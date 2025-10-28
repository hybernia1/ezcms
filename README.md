# ezcms

## Core utility modules

### Asset manager

`Core\Assets\AssetManager` poskytuje registraci a vykreslování statických
assetů (CSS/JS) s podporou závislostí, verzování a oddělením do sekcí (head a
footer). Umožňuje také nastavit vlastní resolver URL pro napojení na CDN nebo
jinou logiku generování cest.

Základní použití:

```php
$assets = new Core\Assets\AssetManager();
$assets->registerStyle('app', '/assets/app.css');
$assets->registerScript('app', '/assets/app.js', dependencies: ['vendor']);
$assets->registerScript('vendor', '/assets/vendor.js');

$assets->enqueueStyle('app');
$assets->enqueueScript('app');

echo $assets->renderStyles();  // <link rel="stylesheet" ...>
echo $assets->renderScripts(); // <script src="...">
```

### Router

`Core\Routing\Router` je jednoduchý směrovač, který umožňuje registraci cest,
včetně pojmenovaných rout, vlastních regulárních omezení parametrů a generování
URL. Podporuje prioritizaci rout a základní dispatch, kdy handler obdrží instanci
`RouteMatch` s rozparsovanými parametry.

```php
$router = new Core\Routing\Router(basePath: '/admin');

$router->get(
    handler: fn (Core\Routing\RouteMatch $match) => 'Dashboard',
    pattern: '/',
    name: 'dashboard'
);

$router->get(
    handler: fn (Core\Routing\RouteMatch $match) => 'Detail ' . $match->getParameters()['slug'],
    pattern: '/posts/{slug}',
    name: 'post.detail',
    requirements: ['slug' => '[a-z0-9\-]+']
);

$match = $router->match('GET', '/admin/posts/hello-world');
$url = $router->generate('post.detail', ['slug' => 'hello-world']);
```

### Překlady

`Core\Translation\Translator` poskytuje jednoduchou práci s překlady ve stylu
WordPressu. Překladové soubory jsou klasické PHP soubory vracející asociativní
pole klíč => hodnota a nachází se ve složce `inc/languages`. V základu se tak
očekávají soubory pojmenované podle locale, například `EN.php`, `CS.php`,
`PL.php`.

```php
use Core\Translation\Translator;

$translator = new Translator(defaultLocale: 'CS');
$translator->setFallbackLocale('EN');

echo $translator->translate('greeting');
echo $translator->translate('greeting.named', ['name' => 'Eva']);

// Globální helpery fungující obdobně jako ve WordPressu
Translator::setGlobal($translator);
echo __('greeting');
_e('greeting.named', ['name' => 'Karel']);
```

### CSRF tokeny

`Core\Security\CsrfTokenManager` řeší generování a validaci CSRF tokenů nad
session. Token lze vygenerovat pro libovolný formulářový identifikátor a při
úspěšné validaci je (volitelně) zneplatněn. Tokeny lze snadno vkládat do
formulářů pomocí helperu `Core\Utils\Forms::csrfField()`.

```php
use Core\Security\CsrfTokenManager;
use Core\Utils\Forms;

$csrf = new CsrfTokenManager();
$token = $csrf->getToken('contact-form');

echo Forms::csrfField($token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validateToken($_POST['_token'] ?? '', 'contact-form')) {
        throw new RuntimeException('Neplatný CSRF token');
    }
}
```