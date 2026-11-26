# Apercu du constructeur sur un hote de front distinct - plan d'implementation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Le constructeur de pages fonctionne quand l'admin et le front sont sur des hotes differents, grace a une URL
d'apercu signee et a un dialogue par `postMessage`.

**Architecture:** Cote serveur, un signataire produit l'URL signee, un validateur la controle, un voter accorde
`PAGE_BUILDER` et un ecouteur de reponse pose les en-tetes et injecte le script d'apercu. Cote navigateur, un module de
protocole partage filtre les messages ; l'agent d'apercu (front) et le pont (admin) remplacent tout acces direct au DOM
de l'iframe. Chaque rechargement passe par une route d'admin qui signe une URL fraiche.

**Tech Stack:** PHP 8.4, Symfony 7.4/8 (`UriSigner`, Security voters, `kernel.response`, `MockClock`), Sylius 2.3,
Twig, Webpack Encore, JS natif, `node --test`.

**Spec:** `docs/superpowers/specs/2026-11-26-page-builder-cross-host-preview-design.md`

## Global Constraints

- Base `2.3.x`, aucune rupture : aucune signature publique existante ne change ; changements notes dans `UPGRADE.md`, section `## 2.3.5`.
- Chaque nouveau service est declare dans `config/services.yaml` avec un alias par interface, surchargeable par un projet.
- Parametres signes : `_hcms_resource`, `_hcms_id`, `_hcms_locale`, `_hcms_admin_host` ; plus `happy_cms_preview=1`, `_expiration`, `_hash` (noms par defaut du `UriSigner`).
- `_hcms_admin_host` contient une origine complete `scheme://host[:port]`, jamais un chemin.
- Option `sylius_happy_cms.page_builder.preview_url_ttl` : entier en secondes, minimum 1, defaut `300`.
- `UriSigner::sign()` recoit une date (`DateTimeImmutable`) ou un `DateInterval`, jamais un entier (un entier est un horodatage).
- `postMessage` : jamais `'*'` en cible ; chaque cote verifie `event.origin` et `event.source`.
- Enveloppe des messages : `{ source: 'happy-cms', type, payload }`.
- Delai d'attente du message `ready` : 10 s (constante JS).
- Aucun nom de client dans le code, la doc ou les commits : ecrire « dans un projet ».
- Les agents ne commitent pas : chaque tache se termine par un message de commit propose, l'utilisateur valide et commite.
- Commandes PHP lancees dans Docker : prefixe `docker compose run --rm php` (voir `Makefile`), `ENV=test` pour PHPUnit.

## Review Focus

- Reponse d'apercu en redirection (3xx, `RedirectionManager`) : ni script ni en-tete de cadre. Test dans la tache 5.
- HTML sans `</body>` : reponse inchangee, sans erreur. Test dans la tache 5.
- `</body>` present deux fois (chaine dans un script inline) : insertion avant le dernier. Test dans la tache 5.
- Origine d'admin avec port (`http://admin.localhost:8099`) : acceptee par le validateur. Test dans la tache 2.
- Slug accentue ou encode dans le chemin : la signature reste valide de bout en bout. Test dans la tache 8.

---

## Carte des fichiers

| Fichier | Role |
|---|---|
| `src/Security/Preview/SignedPreviewParameters.php` | Constantes des noms de parametres |
| `src/Security/Preview/PreviewSigningDeniedException.php` | Refus de signer (etend `AccessDeniedException`) |
| `src/Security/Preview/PreviewUrlSignerInterface.php`, `PreviewUrlSigner.php` | Complete et signe une URL |
| `src/Security/Preview/SignedPreviewContext.php` | Contexte lu dans une requete signee valide |
| `src/Security/Preview/SignedPreviewRequestValidatorInterface.php`, `SignedPreviewRequestValidator.php` | Valide une requete d'apercu signee |
| `src/Security/SignedPreviewVoter.php` | Accorde `PAGE_BUILDER` sur signature |
| `src/Security/Preview/PreviewUrlGeneratorInterface.php`, `PreviewUrlGenerator.php` | Genere l'URL depuis la route de l'entite puis signe |
| `src/Twig/PreviewUrlExtension.php` | Fonction `happy_cms_signed_preview_url` |
| `src/EventListener/PreviewResponseListener.php` | En-tetes et injection du script |
| `src/Services/Cmf/RouteRenderService.php` | Refus en 403 |
| `src/Controller/PageBuilder/PageBuilderController.php` | Action `previewUrlAction` |
| `src/DependencyInjection/Configuration.php` | Option `preview_url_ttl` |
| `config/services.yaml`, `config/routes/admin.yaml` | Services, alias, route |
| `templates/admin/page_builder/index.html.twig` | URL signee et URL de renouvellement |
| `assets/page-builder/preview-protocol.js` | Enveloppe, filtre, envoi (fonctions pures) |
| `assets/page-builder/preview-agent.js` | Script d'apercu (front) |
| `assets/page-builder/preview-bridge.js` | Pont cote admin |
| `assets/page-builder/page-builder.js`, `assets/controllers/BlockEditor.js` | Passage aux messages |
| `assets/page-builder/tests/preview-protocol.test.js` | Tests `node --test` |
| `webpack.config.js` | Entree `page-builder-preview` |
| `UPGRADE.md`, `docs/usage/preview.md`, `CHANGELOG.md` | Documentation |

---

### Task 1: Signataire d'URL et option de duree

**Files:**
- Create: `src/Security/Preview/SignedPreviewParameters.php`, `PreviewSigningDeniedException.php`, `PreviewUrlSignerInterface.php`, `PreviewUrlSigner.php`
- Modify: `src/DependencyInjection/Configuration.php:115-127` (noeud `page_builder`), `config/services.yaml`
- Test: `tests/Unit/Security/Preview/PreviewUrlSignerTest.php`, `tests/Unit/DependencyInjection/` (test de config existant ou nouveau `PageBuilderConfigurationTest.php`)

**Interfaces:**
- Produces:
  - `final class SignedPreviewParameters { PREVIEW = 'happy_cms_preview'; RESOURCE = '_hcms_resource'; ID = '_hcms_id'; LOCALE = '_hcms_locale'; ADMIN_HOST = '_hcms_admin_host'; }` (constantes publiques `string`)
  - `class PreviewSigningDeniedException extends Symfony\Component\Security\Core\Exception\AccessDeniedException`
  - `PreviewUrlSignerInterface::sign(string $url, CmsRoutableInterface $subject, string $resource, string $locale): string` (leve `PreviewSigningDeniedException`)
  - `PreviewUrlSigner::__construct(AuthorizationCheckerInterface $authorizationChecker, UriSigner $uriSigner, RequestStack $requestStack, Psr\Clock\ClockInterface $clock, int $ttl)`
  - Parametre conteneur `sylius_happy_cms.page_builder.preview_url_ttl` (pose par `processPageBuilderConfiguration`)

- [ ] **Step 1: Ecrire les tests qui echouent**

Dans `PreviewUrlSignerTest` (requete d'admin courante `http://admin.localhost:8099/admin/...` poussee dans un `RequestStack`, `UriSigner('secret', clock: $clock)`, `MockClock('2026-11-26 10:00:00')`, TTL 300) :

```php
testItRefusesToSignWithoutPageBuilderRight()      // checker -> false ; expectException(PreviewSigningDeniedException::class) ; aucun appel a UriSigner
testItCompletesASchemeRelativeUrlWithTheAdminScheme() // '//localhost:8099/ma-page?happy_cms_preview=1' -> commence par 'http://localhost:8099/ma-page?'
testItKeepsHttpsWhenTheAdminIsServedOverHttps()   // requete https://admin.example.test -> '//front.example.test/p' devient 'https://front.example.test/p'
testItCompletesARelativePathWithTheAdminHost()    // '/ma-page' -> 'http://admin.localhost:8099/ma-page?...'
testItAddsThePreviewAndSignedParameters()         // query : happy_cms_preview=1, _hcms_resource=sylius_happy_cms.page, _hcms_id=42, _hcms_locale=fr_FR, _hcms_admin_host=http://admin.localhost:8099
testItExpiresAfterTheConfiguredTtl()              // MockClock('2026-11-26 10:00:00', 'UTC') : _expiration == 1795687500 (10:05:00 UTC) ; $uriSigner->check($url) true ; $clock->sleep(301) -> false
```

Config : `preview_url_ttl` vaut `300` par defaut ; `0` leve `InvalidConfigurationException`.

- [ ] **Step 2: Lancer les tests, verifier l'echec**

Run: `docker compose run --rm php vendor/bin/phpunit tests/Unit/Security/Preview/PreviewUrlSignerTest.php`
Expected: FAIL (classe introuvable)

- [ ] **Step 3: Implementer**

- `Configuration` : `->integerNode('preview_url_ttl')->defaultValue(300)->min(1)->info(...)` dans `page_builder`.
- `PreviewUrlSigner::sign()` dans l'ordre de la spec (section 1) : droit `ContentDocumentVoter::PAGE_BUILDER` sur `$subject`, completion (schema et hote depuis `$requestStack->getMainRequest()`), ajout des parametres (fusion avec la query existante, sans doublon de `happy_cms_preview`), puis `$this->uriSigner->sign($url, $this->clock->now()->modify(sprintf('+%d seconds', $this->ttl)))`.
- Services : `Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlSigner` (arguments `@security.authorization_checker`, `@uri_signer`, `@request_stack`, `@clock`, `%sylius_happy_cms.page_builder.preview_url_ttl%`) et alias `PreviewUrlSignerInterface`.

- [ ] **Step 4: Lancer les tests, verifier le succes**

Run: `docker compose run --rm php vendor/bin/phpunit tests/Unit/Security/Preview tests/Unit/DependencyInjection`
Expected: PASS

- [ ] **Step 5: Verifier PHPStan et ECS**

Run: `make phpstan && make ecs`
Expected: `[OK] No errors` et aucune erreur ECS

- [ ] **Step 6: Proposer le commit**

`feat(page-builder): sign preview URLs with an expiration`

---

### Task 2: Validateur de requete d'apercu signee

**Files:**
- Create: `src/Security/Preview/SignedPreviewContext.php`, `SignedPreviewRequestValidatorInterface.php`, `SignedPreviewRequestValidator.php`
- Modify: `config/services.yaml`
- Test: `tests/Unit/Security/Preview/SignedPreviewRequestValidatorTest.php`

**Interfaces:**
- Consumes: `SignedPreviewParameters`, `PreviewUrlSigner` (pour fabriquer des URL valides dans les tests), `PreviewRequestMatcher`
- Produces:
  - `final readonly class SignedPreviewContext { __construct(public string $resource, public int $id, public string $locale, public string $adminOrigin) }`
  - `SignedPreviewRequestValidatorInterface::validate(Request $request): ?SignedPreviewContext`
  - `SignedPreviewRequestValidator::__construct(RequestMatcherInterface $previewRequestMatcher, UriSigner $uriSigner)`
  - Attribut de requete `_hcms_signed_preview` : cache du resultat (`SignedPreviewContext` ou `false`)

- [ ] **Step 1: Ecrire les tests qui echouent**

Une URL valide est produite par un `PreviewUrlSigner` reel (meme `UriSigner`, `MockClock`), puis transformee en `Request::create($url)`.

```php
testItReturnsTheContextOfAValidSignedRequest()     // resource, id 42, locale fr_FR, adminOrigin 'http://admin.localhost:8099'
testItAcceptsAnAdminOriginWithAPort()              // adminOrigin 'http://admin.localhost:8099' conserve le port
testItRejectsAnExpiredUrl()                        // $clock->sleep(301) -> null
testItRejectsAnAlteredParameter(string $param)     // DataProvider : _hcms_resource, _hcms_id, _hcms_locale, _hcms_admin_host, _expiration, chemin -> null
testItRejectsAnUnsignedPreviewRequest()            // '/ma-page?happy_cms_preview=1' -> null
testItRejectsAnInvalidAdminOrigin(string $origin)  // DataProvider : 'javascript:alert(1)', 'http://admin.test/path', 'ftp://admin.test', '' (URL signee avec cette valeur) -> null
testItCachesTheResultOnTheRequest()                // second appel : UriSigner non rappele (mock ou compteur)
```

- [ ] **Step 2: Lancer les tests, verifier l'echec**

Run: `docker compose run --rm php vendor/bin/phpunit tests/Unit/Security/Preview/SignedPreviewRequestValidatorTest.php`
Expected: FAIL

- [ ] **Step 3: Implementer** selon la spec, section 2 (matcher, presence des parametres, `checkRequest()`, forme de l'origine). Origine valide : `parse_url` donne `scheme` dans `http|https`, `host` non vide, ni `path`, ni `query`, ni `user`. Service et alias par interface ; le matcher injecte est `@sylius.happy_cms.plugin.security.preview_request_matcher`.

- [ ] **Step 4: Lancer les tests, verifier le succes**

Run: `docker compose run --rm php vendor/bin/phpunit tests/Unit/Security/Preview`
Expected: PASS

- [ ] **Step 5: Verifier PHPStan et ECS** - `make phpstan && make ecs`, sans erreur.

- [ ] **Step 6: Proposer le commit**

`feat(page-builder): validate signed preview requests`

---

### Task 3: Voter de signature et refus en 403

**Files:**
- Create: `src/Security/SignedPreviewVoter.php`
- Modify: `src/Services/Cmf/RouteRenderService.php:83-89`, `config/services.yaml`
- Test: `tests/Unit/Security/SignedPreviewVoterTest.php`, `tests/Unit/Services/Cmf/RouteRenderServicePreviewAccessTest.php` (ou test existant du service s'il y en a un)

**Interfaces:**
- Consumes: `SignedPreviewRequestValidatorInterface::validate()`, `SignedPreviewContext`, `ContentDocumentVoter::PAGE_BUILDER`
- Produces: `SignedPreviewVoter::__construct(RequestStack $requestStack, SignedPreviewRequestValidatorInterface $validator, array $resources)` ; `$resources` = `%sylius.resources%`, tague `security.voter`

- [ ] **Step 1: Ecrire les tests qui echouent**

Sujet : une `Page` de test (`Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page`, id 42 force par reflexion), `$resources['sylius_happy_cms.page']['classes']['model'] = Page::class`, requete principale de locale `fr_FR`.

```php
testItGrantsPageBuilderForAMatchingSignedRequest()   // ACCESS_GRANTED
testItAbstainsForAnotherId()                         // contexte id 43 -> ACCESS_ABSTAIN
testItAbstainsForAnotherResource()                   // resource inconnue ou modele d'une autre classe -> ACCESS_ABSTAIN
testItAbstainsForAnotherLocale()                     // requete en en_US -> ACCESS_ABSTAIN
testItAbstainsWithoutSignature()                     // validate() -> null -> ACCESS_ABSTAIN
testItAbstainsForAnotherAttribute()                  // 'EDIT' -> ACCESS_ABSTAIN
testItNeverDenies()                                  // sur tous les cas ci-dessus : jamais ACCESS_DENIED
```

`RouteRenderService` : un apercu refuse leve `Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException` avec le message `Access Denied to preview content document` (statut 403).

- [ ] **Step 2: Lancer les tests, verifier l'echec**

Run: `docker compose run --rm php vendor/bin/phpunit tests/Unit/Security/SignedPreviewVoterTest.php`
Expected: FAIL

- [ ] **Step 3: Implementer** le voter (spec, section 2) et remplacer `throw new \Exception(...)` par `throw new AccessDeniedHttpException('Access Denied to preview content document', $e)`.

- [ ] **Step 4: Lancer les tests, verifier le succes**

Run: `docker compose run --rm php vendor/bin/phpunit tests/Unit/Security tests/Unit/Services`
Expected: PASS

- [ ] **Step 5: Verifier PHPStan et ECS** - `make phpstan && make ecs`, sans erreur.

- [ ] **Step 6: Proposer le commit**

`feat(page-builder): grant the page builder right to signed preview requests`

---

### Task 4: Generateur d'URL, fonction Twig et route de renouvellement

**Files:**
- Create: `src/Security/Preview/PreviewUrlGeneratorInterface.php`, `PreviewUrlGenerator.php`, `src/Twig/PreviewUrlExtension.php`
- Modify: `src/Controller/PageBuilder/PageBuilderController.php` (nouvelle action), `config/routes/admin.yaml`, `config/services.yaml`, `templates/admin/page_builder/index.html.twig:18-22`
- Test: `tests/Unit/Security/Preview/PreviewUrlGeneratorTest.php`, `tests/Functional/Controller/PageBuilder/PreviewUrlActionTest.php`

**Interfaces:**
- Consumes: `PreviewUrlSignerInterface::sign()`
- Produces:
  - `PreviewUrlGeneratorInterface::generate(CmsRoutableInterface $subject, string $resource, string $locale): string` (chaine vide si l'entite n'a pas de route en ligne pour la locale, comme `happy_cms_path_by_id` ; leve `PreviewSigningDeniedException`)
  - `PreviewUrlGenerator::__construct(RouterInterface $router, PreviewUrlSignerInterface $signer)`
  - Twig : `happy_cms_signed_preview_url(CmsRoutableInterface entity, string resource, string locale): string`
  - Route `sylius_happy_cms_admin_page_builder_preview_url` : `GET /happy-cms-builder/{resource}/{id}/preview-url`, query `locale`, reponse `{"url": "..."}` ou 403 `{"error": "..."}`
  - Attribut HTML `data-preview-url-endpoint` sur `[data-builder-configuration]` (chemin de cette route, avec `locale`)

- [ ] **Step 1: Ecrire les tests qui echouent**

```php
// PreviewUrlGeneratorTest (routeur mocke)
testItGeneratesFromTheEntityOnlineRoute()   // router->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [ROUTE_OBJECT => $entity->getOnlineRoute('fr_FR')]) puis signer->sign(...)
testItDoesNotUseTheChannelContext()         // aucun ChannelContextInterface injecte (verifie par le constructeur)
testItReturnsAnEmptyStringWithoutOnlineRoute() // getOnlineRoute() -> null : '' et signer non appele

// PreviewUrlActionTest (KernelBrowser, admin connecte via loginUser)
testItReturnsASignedUrlForAnAuthorizedAdmin() // 200, JSON, url contient _hash et _hcms_id
testItReturns403WithoutPageBuilderRight()     // admin sans ROLE_HAPPY_CMS_CONTENT_BUILDER -> 403 JSON
```

Le test fonctionnel cree une page publiee avec une traduction (`DatabaseSchema::reset()`, persist, flush : `EntityRouteIndexer` cree la route). Si aucun utilitaire n'existe, creer `tests/Support/PreviewPageFactory.php` reutilise par la tache 8.

- [ ] **Step 2: Lancer les tests, verifier l'echec**

Run: `docker compose run --rm php vendor/bin/phpunit tests/Unit/Security/Preview/PreviewUrlGeneratorTest.php`
Expected: FAIL

- [ ] **Step 3: Implementer**

- Generateur, extension Twig, alias par interface.
- `previewUrlAction(Request $request, string $resource, int $id): JsonResponse` : resolution et chargement comme `builderAction`, locale validee de la meme facon, `PreviewSigningDeniedException` -> `JsonResponse(['error' => ...], 403)`.
- Route dans `config/routes/admin.yaml`, sur le modele de `sylius_happy_cms_admin_page_builder` (`methods: [GET]`, `permission: false`, `id: '\d+'`).
- Gabarit : `data-iframe-url="{{ happy_cms_signed_preview_url(entity, resource, locale) }}"` et `data-preview-url-endpoint="{{ path('sylius_happy_cms_admin_page_builder_preview_url', {resource: resource, id: entity.id, locale: locale}) }}"`.

- [ ] **Step 4: Lancer les tests, verifier le succes**

Run: `make phpunit`
Expected: PASS

- [ ] **Step 5: Verifier PHPStan et ECS** - `make phpstan && make ecs`, sans erreur.

- [ ] **Step 6: Proposer le commit**

`feat(page-builder): sign the preview iframe URL and add a route to renew it`

---

### Task 5: Ecouteur de reponse d'apercu

**Files:**
- Create: `src/EventListener/PreviewResponseListener.php`
- Modify: `config/services.yaml`
- Test: `tests/Unit/EventListener/PreviewResponseListenerTest.php`

**Interfaces:**
- Consumes: `SignedPreviewRequestValidatorInterface::validate()`, `SignedPreviewContext::$adminOrigin`, le matcher d'apercu
- Produces: `PreviewResponseListener::__construct(RequestMatcherInterface $previewRequestMatcher, SignedPreviewRequestValidatorInterface $validator, Packages $packages)` ; `onKernelResponse(ResponseEvent $event): void`, tague `kernel.event_listener` sur `kernel.response`. Asset : `$packages->getUrl('page-builder-preview.js', 'sylius.happy.cms.assets.package')`. Balise : `<script src="..." data-admin-origin="..." defer></script>` (valeurs echappees `htmlspecialchars`, `ENT_QUOTES`).

- [ ] **Step 1: Ecrire les tests qui echouent**

```php
testItInjectsTheScriptAndFrameHeadersOnASignedPreview()  // script avant </body>, data-admin-origin="http://admin.localhost:8099" ; Cache-Control contient 'private' ; X-Robots-Tag 'noindex' ; CSP 'frame-ancestors http://admin.localhost:8099'
testItRemovesXFrameOptions()                             // en-tete present en entree -> absent
testItAppendsFrameAncestorsToAnExistingCsp()             // "default-src 'self'" -> "default-src 'self'; frame-ancestors http://admin.localhost:8099"
testItKeepsAnExistingFrameAncestors()                    // "frame-ancestors 'self'" inchange
testItInsertsBeforeTheLastClosingBody()                  // contenu avec '</body>' dans un script inline puis '</body>' final : script insere avant le dernier
testItLeavesHtmlWithoutClosingBodyUnchanged()            // contenu inchange, en-tetes poses
testItDoesNothingOnARedirect()                           // 302 signe : ni script, ni CSP, X-Frame-Options conserve
testItDoesNothingOnJsonOrErrors()                        // application/json ; 403 ; 500
testItOnlySetsPrivateAndNoindexOnAnUnsignedPreview()     // happy_cms_preview=1 sans signature, 200 HTML : private + noindex, pas de script ni CSP
testItIgnoresSubRequests()                               // HttpKernelInterface::SUB_REQUEST -> rien
```

- [ ] **Step 2: Lancer les tests, verifier l'echec**

Run: `docker compose run --rm php vendor/bin/phpunit tests/Unit/EventListener/PreviewResponseListenerTest.php`
Expected: FAIL

- [ ] **Step 3: Implementer** selon la spec, section 3. `Cache-Control` : `$response->setPrivate()`.

- [ ] **Step 4: Lancer les tests, verifier le succes** - meme commande, PASS.

- [ ] **Step 5: Verifier PHPStan et ECS** - `make phpstan && make ecs`, sans erreur.

- [ ] **Step 6: Proposer le commit**

`feat(page-builder): mark preview responses private and inject the preview script`

---

### Task 6: Protocole de messages et agent d'apercu

**Files:**
- Create: `assets/page-builder/preview-protocol.js`, `assets/page-builder/preview-agent.js`, `assets/page-builder/tests/preview-protocol.test.js`
- Modify: `webpack.config.js` (entree `page-builder-preview` -> `./assets/page-builder/preview-agent.js`)

**Interfaces:**
- Produces (`preview-protocol.js`, modules ES) :
  - `export const SOURCE = 'happy-cms'`
  - `export const MessageType = { READY: 'ready', GEOMETRY: 'geometry', HOVER: 'hover', EDIT: 'edit', THEME: 'theme', HIGHLIGHT: 'highlight', SCROLL_TO_BLOCK: 'scroll-to-block', RESTORE_SCROLL: 'restore-scroll' }`
  - `export function isAcceptedMessage(event, { origin, source }): boolean` : vrai seulement si `event.origin === origin`, `event.source === source`, `event.data?.source === SOURCE` et `type` connu
  - `export function postToWindow(targetWindow, targetOrigin, type, payload = {}): void` : leve une `Error` si `targetOrigin` est vide ou `'*'`
  - Payloads (spec, section 5) : `ready`/`geometry` = `{ path, blocks: [{ id, top, height }], viewportHeight, scrollY }` ; `hover` = `{ blockId: string|null }` ; `edit` = `{ blockId }` ; `theme` = `{ variables: { '--hcms-accent': ..., ... } }` ; `highlight` = `{ blockId, on }` ; `scroll-to-block` = `{ blockId }` ; `restore-scroll` = `{ y }`
- Agent : lit `document.currentScript.dataset.adminOrigin` des le debut du script (avant tout code asynchrone).

- [ ] **Step 1: Ecrire les tests qui echouent** (`node:test`, `node:assert/strict`)

```js
test('ignores a message from an unknown origin')        // origin 'https://evil.test' -> false
test('ignores a message from another window')           // bonne origine, source differente -> false
test('ignores a message without the happy-cms source')  // data.source 'other' -> false
test('ignores an unknown type')                         // type 'eval' -> false
test('accepts a well-formed message')                   // -> true
test('posts to the exact target origin')                // faux window : postMessage appele avec ('https://admin.test') et l'enveloppe
test('refuses a wildcard or empty target origin')       // '*' et '' -> throws
```

- [ ] **Step 2: Lancer les tests, verifier l'echec**

Run: `node --test assets/page-builder/tests/`
Expected: FAIL (module introuvable)

- [ ] **Step 3: Implementer** `preview-protocol.js`, puis `preview-agent.js` :
  - si `window.parent === window` ou origine absente : ne rien faire ;
  - bloquer clics sur liens et soumissions de formulaires (capture), retirer `.sf-toolbar`, injecter les styles de survol (deplaces depuis `page-builder.js`, bloc `#page-builder-styles`) ;
  - envoyer `ready` au `load`, `geometry` sur `scroll`/`resize` (une fois par `requestAnimationFrame`), `hover` sur `mouseenter`/`mouseleave` des `.content-block-wrapper[data-block-id]`, `edit` au clic sur `.content-block-edit-button` ;
  - traiter `theme` (variables sur `document.documentElement.style`), `highlight` (classe `is-hovered`), `scroll-to-block` (`scrollIntoView({ block: 'start' })`), `restore-scroll` (`window.scrollTo(0, y)`).
  - Ajouter l'entree Encore.

- [ ] **Step 4: Lancer les tests et le build**

Run: `node --test assets/page-builder/tests/` puis `make node-shell` et `yarn build` (ou la commande Encore du projet)
Expected: tests PASS ; build sans erreur, `page-builder-preview.js` present dans la sortie Encore

- [ ] **Step 5: Proposer le commit**

`feat(page-builder): add the preview agent and its message protocol`

---

### Task 7: Pont cote admin et passage du constructeur aux messages

**Files:**
- Create: `assets/page-builder/preview-bridge.js`
- Modify: `assets/page-builder/page-builder.js` (blocs `isCurrentIframeUrlValid`, `syncTheme`, ecouteur `load`, `isIframeUrlValid`, `updateBlockHandlesPositions`, intervalle de 500 ms, ecouteur `message`), `assets/controllers/BlockEditor.js:78,93`

**Interfaces:**
- Consumes: `preview-protocol.js` (tache 6), `data-iframe-url` et `data-preview-url-endpoint` (tache 4)
- Produces:
  - `export class PreviewBridge { constructor(iframe, { refreshEndpoint, onMessage, onTimeout, onSessionExpired }); load(url): void; reload(): Promise<void>; send(type, payload): void; get origin(): string }`
  - Evenement DOM `happy-cms:preview-reload` sur `document` : `BlockEditor.js` l'emet au lieu d'appeler `contentWindow.location.reload()` ; `page-builder.js` l'ecoute et appelle `bridge.reload()`

- [ ] **Step 1: Implementer `PreviewBridge`**
  - `load(url)` : memorise l'origine et le chemin de `url`, affecte `iframe.src`, arme le delai de 10 s (annule a la reception de `ready`) ; a l'expiration, `onTimeout()`.
  - Ecoute `message` avec `isAcceptedMessage(event, { origin: this.origin, source: iframe.contentWindow })` ; un `ready` dont `path` differe du chemin attendu desactive les messages jusqu'au prochain `load` et declenche l'alerte existante (« The page has changed... »).
  - `reload()` : memorise le dernier `scrollY` recu, `fetch(refreshEndpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })` ; si `response.redirected` ou type non JSON ou statut non 200 : `onSessionExpired()` ; sinon `load(json.url)` puis, au `ready` suivant, `send('restore-scroll', { y })`.

- [ ] **Step 2: Brancher `page-builder.js`**
  - Supprimer tout acces a `iframe.contentDocument` / `contentWindow.location` et l'intervalle de 500 ms.
  - Poignees : positions depuis `ready`/`geometry` (meme calcul de decalage qu'aujourd'hui).
  - Survol des poignees -> `send('highlight', ...)` ; `hover` recu -> classe `is-hovered` sur la poignee ; `edit` recu -> `editBlock(blockId)`.
  - `syncTheme()` -> `send('theme', { variables })` pour l'apercu ; les iframes de formulaire (meme origine) restent traitees comme aujourd'hui.
  - Ecouteur `block:saved` : ignorer si `event.origin !== window.location.origin`, puis `bridge.reload()`.
  - `onTimeout` : afficher un message traduisible (cle `sylius_happy_cms.page_builder.preview_unreachable`, en et fr) qui demande de renseigner le `hostname` du canal et renvoie a `docs/usage/preview.md`.
  - `onSessionExpired` : message traduisible `sylius_happy_cms.page_builder.preview_session_expired` qui demande de recharger la page.
  - Clic sur une poignee : `send('scroll-to-block', { blockId })` en plus de `editBlock`.

- [ ] **Step 3: Brancher `BlockEditor.js`** : remplacer les 2 `contentWindow.location.reload()` par `document.dispatchEvent(new CustomEvent('happy-cms:preview-reload'))`.

- [ ] **Step 4: Verifier**

Run: `node --test assets/page-builder/tests/` puis le build Encore ; `grep -n "contentDocument\|contentWindow.location" assets/page-builder/page-builder.js assets/controllers/BlockEditor.js`
Expected: tests PASS, build OK, le grep ne renvoie que des acces aux iframes de formulaire (`.block-editor-form-iframe`)

- [ ] **Step 5: Recette manuelle, meme hote** (`make up`, http://localhost:8099/admin) : survol croise poignees/blocs, edition depuis l'apercu et depuis la barre laterale, enregistrement d'un bloc (rechargement, position de defilement gardee), deplacement, publication, changement de theme clair/sombre, liens bloques dans l'apercu.

- [ ] **Step 6: Proposer le commit**

`feat(page-builder): drive the preview through postMessage instead of its DOM`

---

### Task 8: Tests fonctionnels de bout en bout

**Files:**
- Create: `tests/Functional/Controller/PageBuilder/SignedPreviewTest.php`
- Reuse: `tests/Support/PreviewPageFactory.php` (tache 4)

**Interfaces:**
- Consumes: `PreviewUrlGeneratorInterface::generate()` pour fabriquer l'URL valide (jeton d'admin pose dans le `security.token_storage` du conteneur), puis `KernelBrowser` sans session pour la requete d'apercu. URL expiree : chemin de la page et 4 parametres `_hcms_*` signes directement avec le service `uri_signer` et une expiration `new \DateTimeImmutable('-1 minute')`.

- [ ] **Step 1: Ecrire les tests**

```php
testASignedPreviewIsServedWithoutAdminSession()   // 200 ; corps contient 'page-builder-preview.js' et data-admin-origin ; Cache-Control 'private' ; X-Robots-Tag 'noindex'
testAnExpiredSignedPreviewIsRefused()             // 403
testAnAlteredSignedPreviewIsRefused()             // _hcms_id change -> 403
testASignatureForAnotherPageIsRefused()           // parametres signes d'une page A sur le chemin d'une page B -> 403
testASlugWithAccentsKeepsAValidSignature()        // page fr_FR de slug 'été-à-paris' -> 200
```

- [ ] **Step 2: Lancer les tests**

Run: `make phpunit`
Expected: PASS. Un echec de `testASlugWithAccentsKeepsAValidSignature` revele un ecart d'encodage entre `UrlGenerator` et `Request::getUri()` : le corriger dans le signataire (normaliser l'URL avant signature), pas dans le test.

- [ ] **Step 3: Proposer le commit**

`test(page-builder): cover signed previews end to end`

---

### Task 9: Documentation

**Files:**
- Modify: `UPGRADE.md` (section `## 2.3.5`), `docs/usage/preview.md`, `CHANGELOG.md`

- [ ] **Step 1: Ecrire** les points listes dans la spec, section « Documentation » : apercu sur un autre hote, `preview_url_ttl`, `X-Frame-Options` retire et `frame-ancestors` ajoute, 403 au lieu de 500, `hostname` du canal obligatoire quand l'admin a son propre hote, `trusted_proxies` derriere un proxy TLS, `window.top`/`window.parent` a remplacer par `window.location`, pare-feu `admin_happy_cms_content_builder` devenu facultatif pour le constructeur. Depannage dans `preview.md` : CSP avec `frame-ancestors` deja defini, session d'admin expiree, canal sans `hostname`, proxy sans `trusted_proxies`. Aucun nom de client.

- [ ] **Step 2: Proposer le commit**

`docs(page-builder): document the cross-host preview`

---

### Task 10: Verification finale

- [ ] **Step 1:** `make phpstan` -> `[OK] No errors`
- [ ] **Step 2:** `make ecs` -> aucune erreur
- [ ] **Step 3:** `make phpunit` -> tout passe
- [ ] **Step 4:** `node --test assets/page-builder/tests/` -> tout passe ; build Encore sans erreur
- [ ] **Step 5: Recette multi-hote** : `ADMIN_HOST=admin.localhost` dans `.env`, `make down && make up`, canal de test avec `hostname` `localhost`. Refaire la recette de la tache 7 depuis http://admin.localhost:8099/admin. Verifier aussi : console sans erreur cross-origin ; URL de l'iframe en `http://localhost:8099/...` ; ouvrir l'URL signee dans une fenetre privee (200), puis apres 5 min (403) ; vider le `hostname` du canal -> message « apercu injoignable » apres 10 s.
- [ ] **Step 6:** retirer `ADMIN_HOST`, `make down && make up`, refaire la recette meme hote.
