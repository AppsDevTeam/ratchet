# Nette Ratchet extension

Implementace websocketového serveru Ratchet http://socketo.me, do Nette.


## Vlastnosti

1. Tvorba `Component` stejně jako v Ratchetu, ale s využitím všech pohodlných věcí z Nette
2. Oddělení Vaších aplikací pomocí routování
3. Běh více aplikací na jednom serveru
4. Vytvoření více oddělených instancí stejné aplikace


## Instalace rozšíření

1. Stažení přes composer: **adt/ratchet**
2. Připojení DI rozšíření **ADT\Ratchet\DI\RatchetExtension**

## Jak to celé funguje



## Serverová část

Implementace serverové části v `server.php` se velmi podobá souboru `index.php`:
```
$container = require __DIR__ . '/../private/app/bootstrap.php';
$container->getByType('ADT\Ratchet\Server')->run();
```


Spuštění serveru:
```
php web/server.php
```


## Controller

Každá naše aplikace, kterou chceme vytvořit, se nazývá `Controller`. Každý `Controller` implementuje některé z rozhraní `Ratchet\ComponentInterface` (stejně jako kterákoliv Ratchet `Component`a).

## Router

K oddělení jednotlivých aplikací slouží `ADT\Ratchet\Components\Router`. Routy lze zadávat pohodlně v neonu:

```
ratchet:
    routes:
    	'/kittenSubscriber': @\App\RatchetModule\Controllers\IKittenControllerFactory
        
        # nebo podrobně
    	'/kittenSubscriber':
    		controller: @\App\RatchetModule\Controllers\IKittenControllerFactory
    		httpHost: NULL
    		instantionResolver: @\App\RatchetModule\Controllers\InstantionResolver
```

Základ routy tvoří dvojice `path`-`controller`. `Controller` lze zadat buď přímo jako servisu a nebo jako továrničku implementující funkci `create()`.

Zadáním `httpHost` specifikujeme konkrétní doménu, pro kterou bude routa fungovat.

V některých případech chceme pro každou ze skupin uživatelů vytvořit vlastní instanci `Controller`u. Příkladem může být běh stejné aplikace pro více subdomén. Pohodlnějším způsobem, než vše v každém `Controller`u ošetřovat, je využití parametru `instantionResolver`. Parametrem je třída implementující rozhraní `ADT\Ratchet\Controllers\IInstantionResolver`. Nejlepší bude ukázat příklad. Následující implementace `InstantionResolver`u provede to, že se pro každou ze subdomén vytvoří (samozřejmě lazy) vlastní instance `Controller`u:

```
<?php

namespace App\RatchetModule\Controllers;

use \Ratchet\ConnectionInterface;
use \Guzzle\Http\Message\RequestInterface;

class InstantionResolver implements \ADT\Ratchet\Controllers\IInstantionResolver {
    
	public function getInstantionIdentifier(RequestInterface $request, ConnectionInterface $conn) {
		$origin = new \Nette\Http\Url($request->getHeader('origin')->toArray()[0]);
		return $origin->host;
	}
	
}
```

## Integrace do existující aplikace

TODO: ZMQ model

## Debugování

TODO: ADT\Dumper, vytvořit Debug Componentu, ke které by se přihlásil jeden uživatel a na ten by se odesílal všechen výstup (subscribe k _debug)?

## TODOs

1. Přidat do configu možnost `autoDestroy`: pokud je Controller dynamicky vytvořen a všichni uživatelé se odpojili, smaž imstanci Controlleru.
2. Benchmark:
Porovnat NodeJS a Ratchet.
Rychlost odezvy na jeden jediný request (pokud oboje je 1ms, tak neřešit, ale pokud je jedno z nich 200ms a druhý 1ms, tak je to špaténka).
Odezva je v 99% 1-2ms (s tím, že se tam započítává nejspíš i nějaký JS balast).
3. Chci naše controllery odstínit od toho, aby si museli rozdělovat všechno na subdomény. Pokud se někdo připojí ke konkrétní subdoméně, nemůže komunikovat s nikým jiným. Každý prohlížeč se však může připojit ke všem controllerům své subdomény a také ke všem controllerům domény. Controller nebude řešit subdomény a bude počítat s tím, že se k němu request z jiné subodmény nedostane => toto je třeba řešit v nižších vrstvách.
4. Router by neměl dostávat přímo Controller, ale továrnu, která se na základě parametrů (v mém případě subdomény) rozhodna zda vytvořit nový Controller a nebo zda vrátit existující, pokud pro subdoménuexistuje.

5. Routa:
	- Path
	- Controller class name
	- Callback, který z parametrů routy vrátí jednoznačný identifikátor

6. odstínit RatchetModel (resp. ZMQ\Context v LockControlleru) od kontroly subdomény


## Poděkování

Projekt volně navazuje na nedokončený frosty22/ratchet. Děkujeme za inspiraci.

