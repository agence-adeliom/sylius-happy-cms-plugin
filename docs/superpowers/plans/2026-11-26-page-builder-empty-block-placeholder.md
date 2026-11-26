# Encart de bloc vide et hauteur minimale dans l'apercu - plan d'implementation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dans l'apercu du constructeur de pages, chaque bloc reste identifiable et editable : encart a son nom quand il
ne rend rien, bouton d'edition jamais coupe, libelles dans la langue de l'admin.

**Architecture:** L'URL signee de l'apercu porte la langue de l'admin. Un fournisseur de libelles traduit les textes de
l'apercu et le nom du type de bloc dans cette langue, `Helper` les passe a `PreviewOverlayRenderer::wrap()`, qui rend
l'encart masque. L'agent d'apercu (iframe) mesure le contenu de chaque bloc, pose `is-empty` et renvoie `geometry`.

**Tech Stack:** PHP 8.2, Symfony (Translator, UriSigner, RequestStack), PHPUnit, JS vanilla (ES modules), `node --test`,
Webpack Encore.

**Spec:** `docs/superpowers/specs/2026-11-26-page-builder-empty-block-placeholder-design.md`

## Global Constraints

- Base : branche `feat/page-builder-cross-host-preview` (PR #180). Version cible 2.3.5, sans rupture en 2.x.
- `PreviewOverlayRenderer::wrap()` garde ses 5 parametres actuels, dans le meme ordre ; les nouveaux sont optionnels et
  places apres.
- Le constructeur de `Helper` garde ses 4 arguments ; le nouveau est optionnel et place apres.
- `SignedPreviewContext`, `SignedPreviewParameters`, `PreviewUrlSigner` sont non publies : modification libre.
- Styles structurels en ligne dans le renderer ; couleurs `var(--hcms-*, repli)`.
- Pas de detection du vide en PHP (`strip_tags()` interdit pour cet usage).
- Aucun nom de client dans le code, les commits ou la PR ("dans un projet").
- Les agents ne commitent pas : chaque tache se termine par un message de commit propose, l'utilisateur valide et
  commite.
- Tests PHP : `vendor/bin/phpunit <chemin>` dans `make php-shell`. Tests JS : `node --test assets/page-builder/tests/`.

## Review Focus

1. Langue d'admin absente de l'URL (apercu historique meme hote, URL signee avant la mise a jour) : l'apercu s'affiche,
   libelles dans la langue de la requete. Test : Task 1 (validator), Task 2 (provider).
2. Langue d'admin mal formee ou injectee (`fr<script>`, `../`) : jamais rendue, jamais utilisee pour traduire. Test :
   Task 1.
3. Nom de bloc avec `<`, `"` ou `&` : echappe dans l'attribut et dans l'encart. Test : Task 3.
4. Bloc dont le contenu est un simple noeud texte sans element : pas marque vide. Test : Task 5 (`contentNodesOf`).
5. Bloc vide qui recoit son contenu plus tard (point de montage JS) : `is-empty` retire. Verification manuelle Task 6.

---

### Task 1: Langue de l'admin dans l'URL signee

**Files:**
- Modify: `src/Security/Preview/SignedPreviewParameters.php`
- Modify: `src/Security/Preview/PreviewUrlSigner.php`
- Modify: `src/Security/Preview/SignedPreviewRequestValidator.php`
- Modify: `src/Security/Preview/SignedPreviewContext.php`
- Test: `tests/Unit/Security/Preview/PreviewUrlSignerTest.php`, `tests/Unit/Security/Preview/SignedPreviewRequestValidatorTest.php`

**Interfaces:**
- Produces: `SignedPreviewParameters::ADMIN_LOCALE = '_hcms_admin_locale'` ;
  `SignedPreviewContext::__construct(string $resource, int $id, string $locale, string $adminOrigin, ?string $adminLocale = null)`
  avec `public ?string $adminLocale`.

- [x] **Step 1: Ecrire les tests qui echouent**
  - `PreviewUrlSignerTest::testItAddsTheAdminLocale` : la requete d'admin a la langue `fr_FR`, l'URL signee contient
    `_hcms_admin_locale=fr_FR`.
  - `SignedPreviewRequestValidatorTest::testItReadsTheAdminLocale` : URL signee avec `_hcms_admin_locale=fr_FR`,
    `$context->adminLocale === 'fr_FR'`.
  - `testItAcceptsARequestWithoutAdminLocale` : URL signee sans le parametre, contexte non nul, `adminLocale === null`.
  - `testItIgnoresAMalformedAdminLocale` (data provider : `fr<script>`, `../fr`, `FR`, `fr_fr`, chaine vide) : URL
    signee avec la valeur, contexte non nul, `adminLocale === null`.
  - Ajouter `_hcms_admin_locale=fr_FR` -> `_hcms_admin_locale=en_US` aux `alterations()` : la requete est refusee.
- [x] **Step 2: Lancer les tests, verifier qu'ils echouent**
  Run: `vendor/bin/phpunit tests/Unit/Security/Preview` - Expected: FAIL sur les nouveaux tests.
- [x] **Step 3: Implementer**
  - `PreviewUrlSigner::sign()` : `$query[SignedPreviewParameters::ADMIN_LOCALE] = $request->getLocale();`.
  - Le validator ne met pas `ADMIN_LOCALE` dans la liste des parametres obligatoires. Il le lit apres la verification
    de signature et le garde seulement s'il respecte `/^[a-z]{2,3}(_[A-Z]{2})?$/`, sinon `null`.
- [x] **Step 4: Lancer les tests, verifier qu'ils passent**
  Run: `vendor/bin/phpunit tests/Unit/Security tests/Functional/Controller/PageBuilder` - Expected: PASS.
- [x] **Step 5: Proposer le commit** `feat(page-builder): carry the admin locale in the signed preview URL`

### Task 2: Libelles de l'apercu traduits dans la langue de l'admin

**Files:**
- Create: `src/Factory/ContentBlock/PreviewLabels.php`
- Create: `src/Factory/ContentBlock/PreviewLabelsProviderInterface.php`
- Create: `src/Factory/ContentBlock/PreviewLabelsProvider.php`
- Modify: `translations/messages.en.yaml`, `translations/messages.fr.yaml` (bloc `sylius_happy_cms.page_builder`)
- Modify: `config/services.yaml` (ou `config/services/block.yaml`, a cote de `Helper`)
- Test: `tests/Unit/Factory/ContentBlock/PreviewLabelsProviderTest.php`

**Interfaces:**
- Consumes: `SignedPreviewRequestValidatorInterface::validate(Request): ?SignedPreviewContext` (Task 1).
- Produces:
  - `final readonly class PreviewLabels` : `__construct(string $editBlock = 'Edit Block', string $unpublishedBlock = 'Unpublished Block', string $deletedBlock = 'Deleted Block', string $emptyBlock = 'No visible output in the preview')`, proprietes publiques du meme nom.
  - `interface PreviewLabelsProviderInterface` : `labels(): PreviewLabels` ; `blockName(BlockTypeInterface $block): string`.
  - `final class PreviewLabelsProvider` : `__construct(TranslatorInterface $translator, RequestStack $requestStack, SignedPreviewRequestValidatorInterface $validator)`. Service aliase sur l'interface.

- [x] **Step 1: Ajouter les cles de traduction** sous `sylius_happy_cms.page_builder` (pas sous `page_builder.preview`,
  qui est deja une valeur) :
  - en : `preview_overlay.unpublished_block: 'Unpublished block'`, `preview_overlay.deleted_block: 'Deleted block'`,
    `preview_overlay.empty_block: 'No visible output in the preview'`.
  - fr : `preview_overlay.unpublished_block: 'Bloc non publié'`, `preview_overlay.deleted_block: 'Bloc supprimé'`,
    `preview_overlay.empty_block: 'Aucun rendu visible dans l''aperçu'`.
  - Bouton : reutiliser `sylius_happy_cms.page_builder.edit_block` (existe deja en fr et en).
- [x] **Step 2: Ecrire les tests qui echouent** (`Translator` reel avec `ArrayLoader`, cles en `fr_FR` et `en_US`)
  - `testItTranslatesInTheAdminLocaleOfTheSignedPreview` : requete en `en_US`, contexte signe `adminLocale = 'fr_FR'`,
    `labels()->editBlock === 'Éditer le bloc'`, `labels()->emptyBlock === "Aucun rendu visible dans l'aperçu"`.
  - `testItFallsBackOnTheRequestLocale` : validator renvoie `null`, requete en `en_US`, `editBlock === 'Edit Block'`.
  - `testItTranslatesTheBlockName` : bloc dont `getName()` renvoie une cle traduite en fr, `blockName()` renvoie la
    traduction fr ; un nom sans traduction (`'Shared block'`) revient tel quel.
  - `testItResolvesTheLocaleOncePerRequest` : `validate()` appele une seule fois pour 2 appels a `labels()` et 1 a
    `blockName()`.
- [x] **Step 3: Lancer les tests, verifier qu'ils echouent**
  Run: `vendor/bin/phpunit tests/Unit/Factory/ContentBlock/PreviewLabelsProviderTest.php` - Expected: FAIL (classe absente).
- [x] **Step 4: Implementer** `PreviewLabels`, l'interface et `PreviewLabelsProvider`. Langue = `adminLocale` du contexte
  signe de la requete principale, sinon `$request->getLocale()`, sinon `null` (langue par defaut du traducteur). Les
  traductions passent la langue en 4e argument de `trans()`, sans changer la langue de la requete. `labels()` est
  memorise par requete.
- [x] **Step 5: Lancer les tests, verifier qu'ils passent**
  Run: `vendor/bin/phpunit tests/Unit/Factory/ContentBlock` - Expected: PASS.
- [x] **Step 6: Proposer le commit** `feat(page-builder): translate the preview labels in the admin locale`

### Task 3: Encart de bloc vide et hauteurs fixes dans le renderer

**Files:**
- Modify: `src/Factory/ContentBlock/PreviewOverlayRenderer.php`
- Test: `tests/Unit/Factory/ContentBlock/PreviewOverlayRendererTest.php`

**Interfaces:**
- Consumes: `PreviewLabels` (Task 2).
- Produces: `wrap(string $content, int $blockId, ?string $layer, bool $isPreviewPublished, bool $isDeleted, ?string $blockName = null, string|array|null $blockIcon = null, ?PreviewLabels $labels = null): string`.
  Markup lu par l'agent (Task 5) : wrapper `.content-block-wrapper[data-block-id]` avec `data-block-name` optionnel ;
  enfants dans l'ordre `contenu`, `.content-block-empty-placeholder`, surcouche d'etat eventuelle,
  `.content-block-hover-overlay`.

- [ ] **Step 1: Ecrire les tests qui echouent**
  - `it_sets_the_block_name_attribute` : `wrap('', 1, null, true, false, 'Hero')` contient `data-block-name="Hero"`.
  - `it_escapes_the_block_name` : nom `'A "B" <C> & D'` -> attribut et encart contiennent
    `A &quot;B&quot; &lt;C&gt; &amp; D`, jamais `<C>`.
  - `it_omits_the_block_name_attribute_without_name` : appel a 5 arguments, pas de `data-block-name`.
  - `it_renders_a_hidden_empty_placeholder` : une seule occurrence de `class="content-block-empty-placeholder"`, avec
    `display: none` dans son style, placee apres le contenu et avant `content-block-hover-overlay`.
  - `it_renders_the_icon_in_the_placeholder` : icone `'<img src="x.webp">'` reprise telle quelle ; icone tableau
    `['<img src="a.webp">', '<img src="b.webp">']` -> seule `a.webp` presente ; icone `null` ou `''` -> pas de conteneur
    d'icone.
  - `it_uses_the_given_labels` : `new PreviewLabels('Éditer le bloc', 'Bloc non publié', 'Bloc supprimé', 'Aucun rendu')`
    sur un bloc non publie -> les 4 textes sont presents (bouton, badge, encart), aucun libelle anglais.
  - `it_keeps_the_english_labels_by_default` : appel a 5 arguments -> `Edit Block` present (tests existants verts).
  - `it_gives_the_edit_button_a_fixed_height` : le style du bouton contient `height: 44px` et `box-sizing: border-box`.
- [ ] **Step 2: Lancer les tests, verifier qu'ils echouent**
  Run: `vendor/bin/phpunit tests/Unit/Factory/ContentBlock/PreviewOverlayRendererTest.php` - Expected: FAIL.
- [ ] **Step 3: Implementer**
  - Bouton : `height: 44px; box-sizing: border-box; line-height: 20px; padding: 0 24px; white-space: nowrap;`.
  - Badge d'etat : `height: 40px; box-sizing: border-box; line-height: 20px; padding: 0 20px; white-space: nowrap;`.
  - Encart : `position: absolute; inset: 0; z-index: 5; display: none; align-items: center; gap: 12px;
    padding: 0 var(--hcms-block-gap, 16px); box-sizing: border-box; overflow: hidden;
    border: 2px dashed var(--hcms-border, #dadfe5); background: var(--hcms-surface-muted, #f6f8fb);
    color: var(--hcms-text-muted, #667382); font-size: 14px; line-height: 20px; pointer-events: none;`.
    Icone dans un conteneur `flex: none; display: flex; align-items: center; height: 40px; max-width: 64px;
    overflow: hidden;` avec `img, svg { max-height: 40px; width: auto; object-fit: contain }` pose par l'agent (Task 5).
    Nom en `<strong>` couleur `var(--hcms-text, #182433)`, mention dessous.
  - Libelles : `$labels ?? new PreviewLabels()`, chaque texte echappe avec `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- [ ] **Step 4: Lancer les tests, verifier qu'ils passent**
  Run: `vendor/bin/phpunit tests/Unit/Factory/ContentBlock` - Expected: PASS.
- [ ] **Step 5: Proposer le commit** `feat(page-builder): render an empty block placeholder in the preview wrapper`

### Task 4: Brancher Helper sur les libelles

**Files:**
- Modify: `src/Factory/ContentBlock/Helper.php`
- Modify: `config/services/block.yaml` (argument du service `Helper`)
- Test: `tests/Functional/Controller/PageBuilder/SignedPreviewTest.php`

**Interfaces:**
- Consumes: `PreviewLabelsProviderInterface` (Task 2), nouveau `wrap()` (Task 3).
- Produces: `Helper::__construct(Environment $twig, EventDispatcherInterface $eventDispatcher, BlockCollection $collection, AssetRenderer $assetRenderer, ?PreviewLabelsProviderInterface $previewLabelsProvider = null)`.

- [ ] **Step 1: Ecrire le test qui echoue** `testTheOverlayIsInTheAdminLocale` : page `en_US` avec un bloc, URL signee
  dont la requete d'admin est en `fr_FR`. La reponse contient `Éditer le bloc`, `data-block-name="` et
  `content-block-empty-placeholder`. Si `PreviewPageFactory` ne cree pas de bloc, lui ajouter une methode
  `pageWithBlock(string $slug, string $locale, string $blockType)`.
- [ ] **Step 2: Lancer le test, verifier qu'il echoue**
  Run: `vendor/bin/phpunit tests/Functional/Controller/PageBuilder/SignedPreviewTest.php` - Expected: FAIL.
- [ ] **Step 3: Implementer** : en mode preview, `Helper::renderContentBlock()` passe a `wrap()`
  `$this->previewLabelsProvider?->blockName($block) ?? $block->getName()`, `$block->getIcon()` et
  `$this->previewLabelsProvider?->labels()`. Ajouter l'argument `'@...PreviewLabelsProviderInterface'` au service.
- [ ] **Step 4: Lancer les tests, verifier qu'ils passent**
  Run: `vendor/bin/phpunit tests/Functional tests/Unit` - Expected: PASS.
- [ ] **Step 5: Proposer le commit** `feat(page-builder): pass the block name, icon and labels to the preview wrapper`

### Task 5: Hauteur minimale et detection du vide dans l'agent

**Files:**
- Create: `assets/page-builder/preview-empty-block.js`
- Modify: `assets/page-builder/preview-agent.js`
- Test: `assets/page-builder/tests/preview-empty-block.test.js`

**Interfaces:**
- Consumes: markup de la Task 3.
- Produces (module pur, sans acces global au DOM) :
  - `export const EMPTY_HEIGHT_THRESHOLD = 4;`
  - `export const RESERVED_CLASSES = ['content-block-empty-placeholder', 'content-block-state-overlay', 'content-block-hover-overlay'];`
  - `export function contentNodesOf(wrapper): Node[]` : les `childNodes` du wrapper hors elements portant une classe de
    `RESERVED_CLASSES`, hors commentaires et hors noeuds texte vides (espaces seuls).
  - `export function isEmptyHeight(height: number): boolean` : `!(height >= EMPTY_HEIGHT_THRESHOLD)` (NaN compte vide).

- [ ] **Step 1: Ecrire les tests qui echouent** (faux noeuds `{ nodeType, textContent, classList: { contains } }`)
  - `contentNodesOf` exclut encart, surcouche d'etat et surcouche de survol.
  - `contentNodesOf` garde un noeud texte non vide (`nodeType 3`, `'Bonjour'`) et ecarte `'\n  '` et un commentaire
    (`nodeType 8`).
  - `isEmptyHeight(0)`, `isEmptyHeight(3.9)`, `isEmptyHeight(NaN)` -> `true` ; `isEmptyHeight(4)`, `isEmptyHeight(20)`
    -> `false`.
- [ ] **Step 2: Lancer les tests, verifier qu'ils echouent**
  Run: `node --test assets/page-builder/tests/` - Expected: FAIL (module absent).
- [ ] **Step 3: Implementer le module**, puis l'agent :
  - `STYLES` : retirer `min-height: 150px`. Ajouter sur `.content-block-wrapper` les variables `--hcms-block-gap: 16px`,
    `--hcms-edit-button-height: 44px`, `--hcms-state-badge-height: 40px`,
    `--hcms-block-min-height: calc(var(--hcms-edit-button-height) + 2 * var(--hcms-block-gap))`,
    `--hcms-block-state-min-height: calc(var(--hcms-state-badge-height) + 12px + var(--hcms-edit-button-height) + 2 * var(--hcms-block-gap))`
    et `min-height: var(--hcms-block-min-height)`. Les wrappers `[data-published="false"]` et `[data-deleted="true"]`
    prennent `min-height: var(--hcms-block-state-min-height)`.
  - `.content-block-wrapper.is-empty .content-block-empty-placeholder { display: flex !important; }` et regle `img, svg`
    de l'icone (Task 3).
  - Avec `is-empty` : `.content-block-hover-overlay` passe en `justify-content: flex-end; padding: 0 var(--hcms-block-gap)`
    et `.content-block-state-overlay` en `flex-direction: row; justify-content: flex-end; padding: 0 var(--hcms-block-gap)`.
  - Mesure d'un wrapper : `Range` de `setStartBefore(premier)` a `setEndAfter(dernier)` de `contentNodesOf(wrapper)`,
    `getBoundingClientRect().height` ; aucun noeud -> hauteur 0. `wrapper.classList.toggle('is-empty', isEmptyHeight(h))`.
  - Remesure groupee par `requestAnimationFrame` : au demarrage, sur `resize`, via un `ResizeObserver` qui observe les
    elements de `contentNodesOf(wrapper)`, et via un `MutationObserver` (`childList`, `subtree`, `characterData`) sur
    chaque wrapper qui ignore les mutations dans les elements reserves. Apres une mutation, reobserver les nouveaux
    elements de contenu.
  - Quand `is-empty` change sur au moins un bloc, appeler `scheduleGeometry()`.
- [ ] **Step 4: Lancer les tests, verifier qu'ils passent**
  Run: `node --test assets/page-builder/tests/` - Expected: PASS (tests existants compris).
- [ ] **Step 5: Proposer le commit** `feat(page-builder): size preview blocks from the edit button and flag empty ones`

### Task 6: Documentation, assets et verification manuelle

**Files:**
- Modify: `UPGRADE.md` (section `## 2.3.5`), `CHANGELOG.md` (section `# v2.3.5`), `docs/usage/preview.md`
- Modify: `Resources/public/*` (rebuild)

- [ ] **Step 1: Documenter**
  - `UPGRADE.md` 2.3.5 : `min-height: 150px` remplace par `--hcms-block-min-height` et
    `--hcms-block-state-min-height` ; nouvel encart `.content-block-empty-placeholder`, classe `is-empty`, attribut
    `data-block-name` ; libelles traduits (cles `sylius_happy_cms.page_builder.preview_overlay.*` et `edit_block`) dans
    la langue de l'admin portee par `_hcms_admin_locale`. Note pour un projet qui a surcharge les styles du wrapper ou
    decore `PreviewOverlayRenderer::wrap()`.
  - `CHANGELOG.md` : une ligne sous v2.3.5.
  - `docs/usage/preview.md` : section courte sur les variables CSS et l'encart.
- [ ] **Step 2: Reconstruire les assets** dans `make node-shell` : `npm run build`. Expected : `Resources/public/`
  contient un nouveau `page-builder-preview.*.js`, `entrypoints.json` et `manifest.json` a jour.
- [ ] **Step 3: Verification manuelle** (admin en francais, page anglaise, `http://localhost:8099`) avec 3 blocs :
  - un bloc vide : encart avec icone, nom et "Aucun rendu visible dans l'aperçu" ; au survol, bouton a droite, nom lisible ;
  - un bloc de 20px : bouton entier, rien ne deborde ;
  - un bloc normal : rendu inchange.
  - Un bloc vide non publie : badge et bouton a droite, nom lisible.
  - Un bloc rempli apres coup par JS (ou image lente) : l'encart disparait, les poignees du constructeur suivent.
- [ ] **Step 4: Lancer la suite** : `make phpunit`, `make phpstan`, `make ecs`, `node --test assets/page-builder/tests/`.
  Expected : tout vert.
- [ ] **Step 5: Proposer le commit** `docs(page-builder): document the empty block placeholder and rebuild assets`
