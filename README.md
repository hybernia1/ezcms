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