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

### Skladové položky a modal s pohyby

`Core\Inventory\InventoryModalRenderer` umožňuje jednoduše vykreslit tlačítko
`[sklad]`, které otevře modal se všemi pohyby skladové položky. Historie
zahrnuje jak naskladnění, tak vyskladnění včetně informace o důvodu (například
inventura nebo expedice objednávky) a výsledného stavu po každém pohybu.

```php
use Core\Inventory\InventoryItem;
use Core\Inventory\InventoryModalRenderer;
use Core\Inventory\InventoryMovement;
use Core\Inventory\InventoryMovementReason;

$item = new InventoryItem(sku: 'SKU-123', name: 'Zimní bunda', startingStock: 12);
$item->addMovement(new InventoryMovement(
    occurredAt: new DateTimeImmutable('2024-02-01 09:15'),
    type: InventoryMovement::TYPE_OUT,
    quantity: 2,
    reason: InventoryMovementReason::ORDER_DISPATCH,
    reference: 'OBJ-548',
));
$item->addMovement(new InventoryMovement(
    occurredAt: new DateTimeImmutable('2024-02-05 14:30'),
    type: InventoryMovement::TYPE_OUT,
    quantity: 1,
    reason: InventoryMovementReason::INVENTORY_CHECK,
));

$renderer = new InventoryModalRenderer();
echo $renderer->render($item);
```

Vykreslený modal je plně responsivní, obsahuje běžné ovládací prvky a
zobrazí aktuální skladovou zásobu i přehled všech pohybů s důvody vyskladnění.