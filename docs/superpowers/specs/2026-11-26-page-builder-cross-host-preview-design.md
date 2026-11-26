# Apercu du constructeur de pages sur un hote de front distinct de l'admin

Date : 2026-11-26
Base : branche `2.3.x`, sans rupture de compatibilite (section `## 2.3.5` de `UPGRADE.md`).

## Intention

Le constructeur de pages s'ouvre sur l'hote d'admin et affiche la page dans une iframe pointee sur le front. Il ne
fonctionne aujourd'hui que si l'admin et le front partagent le meme nom de domaine. Dans un projet, l'admin peut tourner
sur son propre hote. Le constructeur doit alors fonctionner sans partager le cookie de session d'admin avec le front.

Deux mecanismes dependent aujourd'hui du meme hote :

1. L'autorisation. `RouteRenderService::renderAction()` exige `ContentDocumentVoter::PAGE_BUILDER` quand la requete porte
   `happy_cms_preview=1`. Ce droit vient de la session d'admin, partagee avec le front par le pare-feu
   `admin_happy_cms_content_builder` (contexte `admin`, `request_matcher` = `PreviewRequestMatcher`). Le navigateur
   n'envoie pas cette session a un autre hote.
2. L'edition. `assets/page-builder/page-builder.js` lit et modifie le DOM de l'iframe (survol, poignees, styles
   injectes, blocage des liens, theme). `assets/controllers/BlockEditor.js` et `page-builder.js` rechargent l'apercu par
   `contentWindow.location.reload()`. Le navigateur interdit ces acces entre deux origines.

## Objectifs

- L'apercu s'autorise par une URL signee et datee, sans session d'admin sur le front.
- Le constructeur dialogue avec l'apercu par `postMessage` et ne touche plus au DOM de l'iframe.
- Le comportement visible reste identique quand l'admin et le front partagent le meme hote.
- Les projets surchargent chaque service par son interface.

## Hors perimetre

- Mettre a jour un bloc ou l'ordre des blocs sans recharger l'apercu. Chaque enregistrement recharge toujours l'apercu,
  comme aujourd'hui.
- L'attribut `ContentPreview` cite par `docs/usage/preview.md` et absent de `src/`.
- L'apercu par session (`happy_cms_path(page, true)` sans signature) reste tel quel pour les liens d'apercu existants.

## Pistes ecartees (anti-objectifs)

- Servir l'apercu depuis l'hote d'admin : collisions entre routes d'admin et routes CMS.
- Prefixe `/_preview`, jeton dans un en-tete HTTP, `document.domain`.
- `cookie_domain` sur le domaine parent : la session d'admin serait exposee au front.
- Cookie d'apercu pose sur l'hote du front : les navigateurs bloquent les cookies tiers dans une iframe d'un autre site.
- Un authenticator sur le pare-feu d'apercu : chaque projet devrait modifier son `security.yaml`.
- Une fonction Twig a placer dans le gabarit de page pour charger le script d'apercu : un oubli casserait le
  constructeur sans erreur visible.
- Un double chemin (DOM si meme hote, messages sinon) : deux codes a maintenir, et le chemin par messages ne serait pas
  teste en local.

## Approches retenues

Trois choix ont ete valides avec le demandeur.

1. **Autorisation par un voter dedie.** `SignedPreviewVoter` accorde `PAGE_BUILDER` a une requete d'apercu signee.
   `RouteRenderService` appelle deja `denyAccessUnlessGranted` ; seul le statut du refus change (voir section 3).
2. **Injection du script d'apercu par un ecouteur de reponse.** Il insere le script avant `</body>`, comme la barre de
   debogage de Symfony, et pose les en-tetes de l'apercu.
3. **Un seul chemin par messages**, que les hotes soient identiques ou non.

Renouvellement de la signature (choix du demandeur) : avant chaque rechargement, le constructeur demande une URL
fraichement signee a une route d'admin, puis l'affecte a `iframe.src`. Un message `reload` ne suffirait pas : une fois la
signature expiree, la page de refus ne contient plus le script d'apercu.

## Conception

### Vue d'ensemble

```
Admin (hote A)                                    Front (hote B)
index.html.twig
  happy_cms_signed_preview_url(...) --URL signee--> GET /ma-page?happy_cms_preview=1&_hcms_...&_expiration&_hash
page-builder.js                                     SignedPreviewVoter -> PAGE_BUILDER
  iframe.src = URL signee                           PreviewResponseListener -> script + en-tetes
  <-------------- postMessage (origine B -> A) ---- page-builder-preview.js
  --------------- postMessage (origine A -> B) --->
  rechargement : GET /admin/happy-cms-builder/{resource}/{id}/preview-url (session) -> nouvelle URL signee
```

### 1. Signature de l'URL

**`PreviewUrlSignerInterface` / `PreviewUrlSigner`** (`src/Security/Preview/`).

```php
public function sign(string $url, CmsRoutableInterface $subject, string $resource, string $locale): string;
```

Etapes, dans cet ordre :

1. Verifier `isGranted(ContentDocumentVoter::PAGE_BUILDER, $subject)` sur l'utilisateur courant. Sinon, lever
   `PreviewSigningDeniedException` (etend `AccessDeniedException`) et ne produire aucune URL.
2. Completer l'URL :
   - `//hote[:port]/chemin` (rendu par le routeur quand l'hote de la route differe de celui de l'admin) : prefixer le
     schema de la requete d'admin courante (`https` en production, `http` dans la stack Docker locale). Le port rendu par
     le routeur est garde. Choix du demandeur : une admin en HTTPS avec un front en HTTP n'est pas un cas realiste ;
   - `/chemin` (canal sans `hostname`, ou meme hote) : prefixer le schema et l'hote de la requete d'admin courante.
3. Ajouter `happy_cms_preview=1` s'il manque, puis les parametres signes :
   - `_hcms_resource` : nom de la ressource Sylius (ex. `sylius_happy_cms.page`) ;
   - `_hcms_id` : identifiant du contenu ;
   - `_hcms_locale` : locale previsualisee ;
   - `_hcms_admin_host` : origine de l'admin (`scheme://host[:port]` de la requete courante).
4. Signer avec le `UriSigner` de Symfony (service `uri_signer`, secret du noyau) et une expiration
   `new \DateInterval('PT' . $ttl . 'S')`. Attention : un entier passe a `sign()` est un horodatage, pas une duree.

**Configuration** : `sylius_happy_cms.page_builder.preview_url_ttl`, entier en secondes, minimum 1, defaut `300`.
La signature ne sert qu'au chargement du document principal, d'ou une valeur courte.

**Hote du front (choix du demandeur).** L'URL se calcule depuis l'entite elle-meme, jamais depuis le canal du contexte
courant : `router->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [ROUTE_OBJECT => $entity->getOnlineRoute($locale)])`.
La route stockee porte l'hote du canal de l'entite (`getRouteHost()`, issu de `Channel::getHostname()`). Sur un hote
d'admin distinct, le canal du contexte peut etre faux, et `happy_cms_path_by_id` donnerait une URL vide pour une page
d'un autre canal. Aucune option `front_base_url` n'est ajoutee.

Quand l'admin a son propre hote, le canal de la page doit donc avoir un `hostname`. Le serveur ne peut pas savoir que
les hotes different quand ce champ est vide : il complete alors avec l'hote d'admin, ce qui convient au cas « meme
hote ». Pour l'autre cas, le constructeur affiche une erreur claire si l'apercu n'envoie pas `ready` dans un delai
(10 s, en constante JS) : le message demande de renseigner le `hostname` du canal et renvoie a `docs/usage/preview.md`
(choix de l'implementation).

**Twig** : fonction `happy_cms_signed_preview_url(entity, resource, locale)` dans une nouvelle extension
(`src/Twig/PreviewUrlExtension.php`). Elle genere l'URL comme ci-dessus puis appelle le signataire.
`templates/admin/page_builder/index.html.twig` l'utilise pour `data-iframe-url`. La route de renouvellement passe par le
meme code (service `PreviewUrlGeneratorInterface` / `PreviewUrlGenerator` dans `src/Security/Preview/`, qui genere puis
signe ; la fonction Twig et la route l'appellent tous deux).

### 2. Validation de la requete d'apercu

**`SignedPreviewRequestValidatorInterface` / `SignedPreviewRequestValidator`** (`src/Security/Preview/`).

```php
public function validate(Request $request): ?SignedPreviewContext;
```

Renvoie `null` si l'une des conditions echoue :

- `PreviewRequestMatcher::matches()` est vrai (le matcher reste l'entree unique pour reconnaitre une requete d'apercu) ;
- les 4 parametres `_hcms_*`, `_hash` et `_expiration` sont presents ;
- `UriSigner::checkRequest()` est vrai (signature intacte et non expiree) ;
- `_hcms_admin_host` est une origine bien formee (`http` ou `https`, hote, port facultatif, sans chemin).

`SignedPreviewContext` est un objet en lecture seule : `resource`, `id`, `locale`, `adminOrigin`. Le validateur garde
le resultat dans les attributs de la requete pour eviter un second calcul (voter puis ecouteur).

**`SignedPreviewVoter`** (`src/Security/`), tague `security.voter` :

- s'abstient si l'attribut n'est pas `PAGE_BUILDER`, si le sujet n'est pas un `CmsRoutableInterface`, ou s'il n'y a pas
  de requete principale ;
- accorde si `validate()` renvoie un contexte et que le sujet correspond : classe du sujet compatible avec le modele de
  `resource` (configuration des ressources Sylius, comme `PageBuilderController::resolveEntityClass()`), `id` egal,
  `locale` egale a `$request->getLocale()` ;
- s'abstient dans tous les autres cas. Il ne refuse jamais : `ContentDocumentVoter` continue d'accorder le droit aux
  admins connectes.

Une URL signee pour une page ne donne donc acces ni a une autre page, ni a une autre locale, meme si une route change.

### 3. Reponse d'apercu

**Refus (choix de l'implementation).** Aujourd'hui, `RouteRenderService::renderAction()` transforme l'`AccessDeniedException`
en `\Exception('Access Denied to preview content document')`, soit une erreur 500. Une URL signee expiree remplirait les
journaux de production. Le service leve desormais une `AccessDeniedHttpException` avec le meme message : une 403 nette,
sans redirection vers la connexion de la boutique (l'exception HTTP ne passe pas par le point d'entree du pare-feu).
L'apercu par session suit le meme changement, note dans `UPGRADE.md`.

**`PreviewResponseListener`** (`src/EventListener/`), sur `kernel.response`, requete principale uniquement. Il agit
seulement si `validate()` renvoie un contexte et que la reponse est en HTML avec un statut 2xx :

- `Cache-Control: private` et `X-Robots-Tag: noindex` ;
- autorise l'affichage dans l'admin : retire `X-Frame-Options` ; si la reponse n'a pas de `Content-Security-Policy`,
  ajoute `frame-ancestors <adminOrigin>` ; si elle en a une sans `frame-ancestors`, y ajoute la directive ; si elle en
  a une avec `frame-ancestors`, n'y touche pas (cas documente) ;
- insere avant le dernier `</body>` une balise `<script>` vers l'asset `page-builder-preview.js` (paquet
  `sylius.happy.cms.assets.package`), avec `data-admin-origin="<adminOrigin>"` echappe.

L'ecouteur pose aussi `Cache-Control: private` et `X-Robots-Tag: noindex` sur une requete d'apercu non signee mais
autorisee par session, sans injecter le script. Le script n'est injecte que pour une requete signee valide.

### 4. Route d'admin de renouvellement

`sylius_happy_cms_admin_page_builder_preview_url`, `GET /happy-cms-builder/{resource}/{id}/preview-url?locale=xx`,
dans `config/routes/admin.yaml`, action dans `PageBuilderController` :

- charge l'entite comme `builderAction` ;
- renvoie `{"url": "<URL signee>"}` ;
- renvoie 403 en JSON si le signataire leve `PreviewSigningDeniedException`.

Le pare-feu d'admin protege la route. Une session expiree donne une redirection vers la connexion : le constructeur la
detecte (`response.redirected` ou reponse non JSON) et affiche un message qui demande de recharger la page.

### 5. Dialogue par messages

Deux modules partagent une meme definition des messages :

- `assets/page-builder/preview-bridge.js` (admin), utilise par `page-builder.js` et `BlockEditor.js` ;
- `assets/page-builder/preview-agent.js` (front), nouvelle entree Encore `page-builder-preview` dans
  `webpack.config.js`.

Enveloppe : `{ source: 'happy-cms', type: '<type>', payload: {...} }`. Tout message sans `source: 'happy-cms'` est ignore.

Securite des deux cotes :

- l'admin accepte un message si `event.origin` est l'origine de l'URL signee courante et si
  `event.source === iframe.contentWindow` ; il envoie avec `targetOrigin` = cette origine ;
- l'apercu accepte un message si `event.origin === data-admin-origin` et si `event.source === window.parent` ; il envoie
  avec `targetOrigin` = `data-admin-origin` ;
- jamais `'*'` en envoi ;
- l'ecouteur existant du message `block:saved` (iframe du formulaire de bloc, meme origine que l'admin) verifie
  desormais `event.origin === window.location.origin`.

Messages de l'apercu vers l'admin :

| Type | Contenu | Remplace |
|---|---|---|
| `ready` | chemin courant, liste des blocs `{id, top, height}`, hauteur de fenetre, `scrollY` | lecture du DOM au `load` |
| `geometry` | memes donnees, au defilement et au redimensionnement (limite a une frame) | ecouteurs `scroll`/`resize` sur `iframeWindow` |
| `hover` | `blockId` ou `null` | `mouseenter`/`mouseleave` sur les blocs |
| `edit` | `blockId` | clic sur `.content-block-edit-button` |

Messages de l'admin vers l'apercu :

| Type | Contenu | Remplace |
|---|---|---|
| `theme` | variables `--hcms-*` | `applyPreviewTheme(iframeDocument)` |
| `highlight` | `blockId`, `on` | ajout de `.is-hovered` depuis les poignees |
| `scroll-to-block` | `blockId` | (nouveau) defilement vers un bloc |
| `restore-scroll` | `y` | (nouveau) garde la position apres rechargement |

L'agent d'apercu fait seul, au chargement : blocage des liens et formulaires, styles de survol (deplaces depuis
`page-builder.js`), retrait de `.sf-toolbar`.

Cote admin :

- le controle d'URL par lecture de `contentWindow.location` (`isCurrentIframeUrlValid`, `isIframeUrlValid`, intervalle
  de 500 ms) disparait. L'agent bloque la navigation, et le chemin recu dans `ready` doit egaler celui de l'URL signee ;
  sinon le constructeur ignore les messages et affiche l'alerte existante ;
- les poignees se placent d'apres `ready` et `geometry` ;
- chaque `contentWindow.location.reload()` est remplace par `previewBridge.reload()` : memorise le dernier `scrollY`,
  appelle la route de renouvellement, affecte `iframe.src`, puis envoie `restore-scroll` a la reception de `ready`.

### 6. Compatibilite

- Meme hote : meme chemin de code. La signature et la session accordent toutes deux le droit.
- Le pare-feu `admin_happy_cms_content_builder` n'est plus necessaire au constructeur. Il reste documente et utile pour
  les liens d'apercu par session.
- Aucune signature publique existante ne change. Les nouveaux services sont declares dans `config/services.yaml`, avec
  un alias par interface.
- Prerequis documente (choix de l'implementation) : derriere un proxy qui termine le TLS, le projet doit configurer
  `trusted_proxies`. `UriSigner::checkRequest()` signe le schema et l'hote, et `_hcms_admin_host` se lit sur la requete
  d'admin. Sans ce reglage, toutes les signatures seraient refusees, ou `frame-ancestors` et `targetOrigin` viseraient la
  mauvaise origine. Symfony exige deja ce reglage pour generer des URL correctes. Pas de signature maison.

## Documentation

- `UPGRADE.md`, section `## 2.3.5` : apercu sur un autre hote, option `preview_url_ttl`, retrait de
  `X-Frame-Options` et ajout de `frame-ancestors` sur les reponses d'apercu signees, apercu refuse en 403 au lieu de 500,
  `hostname` du canal obligatoire quand l'admin a son propre hote, `trusted_proxies` derriere un proxy TLS, et point de
  vigilance : un script de front qui lit `window.top` ou `window.parent` leve une erreur entre origines et doit lire
  `window.location`.
- `docs/usage/preview.md` : apercu signe, configuration, depannage (CSP avec `frame-ancestors` deja defini, session
  d'admin expiree, canal sans `hostname`, proxy sans `trusted_proxies`).
- `CHANGELOG.md`.

## Tests

PHPUnit (unitaire, avec `MockClock` injecte dans le `UriSigner`) :

- signataire : exception et aucune URL sans droit d'edition ; `//hote:port/chemin` complete avec le schema de la
  requete d'admin (`https` et `http`), port garde ; `/chemin` complete avec l'hote d'admin ; parametres `_hcms_*`
  presents ; expiration = maintenant + TTL ;
- generateur : URL calculee depuis la route de l'entite, independante du canal du contexte ;
- validateur : `null` pour une URL expiree, alteree (chaque parametre), sans `_hash`, avec une origine parente invalide ;
- voter : accorde avec un contexte valide et le bon sujet ; s'abstient pour un autre identifiant, une autre ressource,
  une autre locale, une requete sans signature ;
- ecouteur : en-tetes et script sur une reponse signee valide ; rien sur une reponse JSON, une erreur, une requete non
  signee ; regles CSP et `X-Frame-Options`.

PHPUnit fonctionnel (`tests/Functional/`) :

- apercu accepte avec une signature valide, sans session d'admin : 200, script injecte, en-tetes poses ;
- apercu refuse en 403 avec une URL expiree, alteree ou utilisee sur une autre page ;
- route de renouvellement : JSON signe pour un admin autorise, 403 sans droit.

JS : `node --test` (aucune dependance ajoutee), sur les fonctions pures de filtrage des messages :

- un message d'une origine inconnue est ignore ;
- un message d'une source autre que l'iframe (ou que le parent) est ignore ;
- un message sans `source: 'happy-cms'` est ignore ;
- l'envoi utilise l'origine cible exacte, jamais `'*'`.

Recette manuelle : environnement Docker avec `ADMIN_HOST=admin.localhost` (voir `docs/contribution.md`), puis sans.

## Laisse au constructeur du plan

- Le decoupage exact des fichiers JS et le nommage interne des fonctions.
- La limitation de frequence des messages `geometry`.
- La methode de resolution ressource vers classe, a factoriser avec `PageBuilderController` si c'est simple.
