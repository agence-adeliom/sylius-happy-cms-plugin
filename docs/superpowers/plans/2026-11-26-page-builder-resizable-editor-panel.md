# Largeur réglable du panneau d'édition - plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** permettre de régler à la souris (et au clavier) la largeur du panneau d'édition du constructeur de pages, et la conserver dans le `localStorage`.

**Architecture:** une poignée rendue côté Twig sur le bord gauche du panneau. Un module JS dédié pilote la variable CSS `--hcms-editor-width` posée sur le panneau, borne la valeur et la persiste. `page-builder.js` se contente d'appeler le module.

**Tech Stack:** Twig, CSS natif, JavaScript ES modules compilé par Webpack Encore, Behat (non JS) pour le balisage.

**Spec:** `docs/superpowers/specs/2026-11-26-page-builder-resizable-editor-panel-design.md`

## Global Constraints

- Largeur par défaut : `820` px. Minimum : `360` px. Maximum : `window.innerWidth - 120` px.
- Clé `localStorage` : `happy-cms.page-builder.editor-width`, valeur entière en pixels.
- Pas clavier : `20` px. Flèche gauche agrandit, flèche droite réduit, `Home` = minimum, `End` = maximum, `Entrée` = défaut.
- Variable CSS : `--hcms-editor-width`, posée en ligne sur `.page-builder__editor-panel`.
- Aucun accès au `localStorage` hors `try/catch`.
- Aucune nouvelle dépendance npm ou Composer.
- Base `2.3.x`, sans rupture de compatibilité : un projet qui surcharge `content.html.twig` sans la poignée garde un panneau de 820 px fonctionnel.

## Review Focus

1. Souris relâchée au-dessus d'une iframe ou hors de la fenêtre : le glissement doit se terminer proprement (`setPointerCapture`, `pointerup` et `pointercancel` gérés), les iframes doivent retrouver leurs `pointer-events`.
2. Valeur stockée corrompue (`"abc"`, `"-5"`, `"99999"`) : le panneau s'ouvre à une largeur bornée, sans erreur console.
3. Fenêtre réduite sous la largeur stockée : la largeur affichée est rebornée sans réécrire la valeur enregistrée.
4. `localStorage` qui lève une exception (navigation privée, stockage bloqué) : le panneau fonctionne à 820 px.
5. Gabarit surchargé sans poignée (projet en 2.3.x) : `initEditorResize` ne plante pas si la poignée est absente.

Pas de lanceur de tests JS dans le projet : ces cas se vérifient à la main (Task 2, Step 4), dans le navigateur, sur l'application de test.

---

### Task 1: Poignée dans le balisage, traductions et largeur pilotée par variable CSS

**Files:**
- Modify: `templates/admin/page_builder/content.html.twig:166` (début de `.page-builder__editor-panel`)
- Modify: `assets/page-builder/page-builder.css:278-297` (bloc `.page-builder__editor-panel`)
- Modify: `translations/messages.fr.yaml:350`, `translations/messages.en.yaml:345` (sous `sylius_happy_cms.page_builder`)
- Modify: `features/admin/page_builder.feature`
- Modify: `tests/Behat/Context/PageBuilderContext.php`

**Interfaces:**
- Produces : élément `data-page-builder-target="editorResizeHandle"`, classe `page-builder__editor-resize-handle`, attributs `role="separator"`, `aria-orientation="vertical"`, `tabindex="0"`, `aria-label` et `title` = clé `sylius_happy_cms.page_builder.resize_panel`. Les attributs `aria-valuenow`, `aria-valuemin`, `aria-valuemax` sont posés par le JS (Task 2).
- Produces : classe `is-resizing` posée par le JS sur l'élément `.page-builder` racine pendant un glissement.

- [x] **Step 1: Écrire le scénario Behat qui échoue**

Dans `features/admin/page_builder.feature`, ajouter au scénario existant (ou à un nouveau scénario `@no-api @ui` avec le même `Given`/`When`) :

```gherkin
Then the editor panel should have a resize handle
```

Dans `PageBuilderContext`, ajouter `#[Then('the editor panel should have a resize handle')] public function theEditorPanelShouldHaveAResizeHandle(): void`. Assertions :

```php
$handle = $this->session->getPage()->find('css', '.page-builder__editor-panel [data-page-builder-target="editorResizeHandle"]');
Assert::notNull($handle, 'The editor panel resize handle is missing');
Assert::same($handle->getAttribute('role'), 'separator');
Assert::same($handle->getAttribute('aria-orientation'), 'vertical');
Assert::same($handle->getAttribute('tabindex'), '0');
Assert::notEmpty($handle->getAttribute('aria-label'));
```

- [x] **Step 2: Lancer le scénario et constater l'échec**

Run: `make behat` (ou `vendor/bin/behat --strict --tags="@admin_page_builder&&~@javascript"`)
Expected: FAIL avec « The editor panel resize handle is missing ».

- [x] **Step 3: Ajouter la poignée, les traductions et le CSS**

- Twig : la poignée est le premier enfant de `.page-builder__editor-panel`, avant `.page-builder__editor-header`, avec les attributs listés dans Interfaces.
- Traductions : `resize_panel: Redimensionner le panneau` (fr) et `resize_panel: Resize panel` (en).
- CSS du panneau : `width: var(--hcms-editor-width, 820px);`, `min-width: 360px;`, `max-width` inchangé.
- CSS de la poignée : `position: absolute; top: 0; bottom: 0; left: -4px; width: 8px; cursor: col-resize; z-index: 1;` ; trait visible de 2 px centré, en `--hcms-accent` au survol, au `:focus-visible` et pendant `.page-builder.is-resizing`.
- CSS du glissement : sous `.page-builder.is-resizing`, `iframe { pointer-events: none; }`, `user-select: none; cursor: col-resize;` et `.page-builder__editor-panel { transition: none; }`.

- [x] **Step 4: Relancer le scénario**

Run: `make behat`
Expected: PASS, aucun autre scénario cassé.

Run: `make ecs` et `yarn lint-style` (ou `npx stylelint "./assets/**/*.css"` sans `--fix` pour vérifier)
Expected: aucune erreur.

- [x] **Step 5: Proposer le commit**

`feat(page-builder): add a resize handle on the editor panel`

---

### Task 2: Module de redimensionnement et persistance

**Files:**
- Create: `assets/page-builder/editor-resize.js`
- Modify: `assets/page-builder/page-builder.js` (bloc d'initialisation du panneau, vers la ligne 568)

**Interfaces:**
- Consumes : la poignée et la classe `is-resizing` de la Task 1.
- Produces, dans `editor-resize.js` :
  - `export const DEFAULT_WIDTH = 820`, `MIN_WIDTH = 360`, `VIEWPORT_GUTTER = 120`, `KEYBOARD_STEP = 20`, `STORAGE_KEY = 'happy-cms.page-builder.editor-width'`.
  - `export function clampWidth(width: number, viewportWidth: number): number` : borne entre `MIN_WIDTH` et `viewportWidth - VIEWPORT_GUTTER` ; si ce maximum est inférieur à `MIN_WIDTH`, retourne `MIN_WIDTH`.
  - `export function readStoredWidth(): number | null` : entier positif lu sous `STORAGE_KEY`, sinon `null` (valeur absente, non numérique, négative, ou exception).
  - `export function storeWidth(width: number): void` : écrit l'entier arrondi ; ignore silencieusement une exception.
  - `export function initEditorResize(root: HTMLElement, panel: HTMLElement, handle: HTMLElement | null): void` : ne fait rien si `handle` est `null`.

- [ ] **Step 1: Écrire `editor-resize.js`**

`initEditorResize` :
- Applique `clampWidth(readStoredWidth() ?? DEFAULT_WIDTH, window.innerWidth)` via `panel.style.setProperty('--hcms-editor-width', `${w}px`)` et met à jour `aria-valuenow`, `aria-valuemin`, `aria-valuemax` de la poignée.
- `pointerdown` (bouton principal) : `handle.setPointerCapture(e.pointerId)`, ajoute `is-resizing` sur `root`. `pointermove` : largeur = `clampWidth(window.innerWidth - e.clientX, window.innerWidth)`. `pointerup` et `pointercancel` : retire `is-resizing`, `storeWidth`.
- `dblclick` : `DEFAULT_WIDTH` bornée, puis `storeWidth`.
- `keydown` : touches de Global Constraints, `preventDefault()`, puis `storeWidth`.
- `resize` de la fenêtre : réapplique `clampWidth(readStoredWidth() ?? DEFAULT_WIDTH, window.innerWidth)`, sans `storeWidth`.

- [ ] **Step 2: Brancher le module**

Dans `page-builder.js`, importer `initEditorResize` et l'appeler une fois, dans le `DOMContentLoaded`, avec `document.querySelector('.page-builder')`, `editorPanel` et `document.querySelector('[data-page-builder-target="editorResizeHandle"]')`, à l'intérieur du `if (editorPanel)` existant.

- [ ] **Step 3: Compiler**

Run: `(cd vendor/sylius/test-application && yarn build)` puis `vendor/bin/console assets:install` (dans Docker, depuis `make node-shell` et `make php-shell`).
Expected: build sans erreur.

- [ ] **Step 4: Vérifier dans le navigateur (http://localhost:8099, constructeur d'une page)**

Cocher chaque point :
- Tirer la poignée vers la gauche puis la droite, en passant au-dessus des deux iframes : le panneau suit, sans décrocher.
- Relâcher au-dessus de l'aperçu, puis survoler un bloc : le survol de l'aperçu fonctionne de nouveau.
- Recharger la page et rouvrir un bloc : même largeur.
- Double-clic et `Entrée` : 820 px. Flèches, `Home`, `End` : comportement de Global Constraints, `aria-valuenow` à jour.
- Dans la console : `localStorage.setItem('happy-cms.page-builder.editor-width', 'abc')`, recharger : 820 px, aucune erreur. Même chose avec `'99999'` : largeur maximale.
- Réduire la fenêtre sous la largeur stockée : le panneau se réduit. Agrandir de nouveau : il revient à la largeur stockée, qui n'a pas changé.
- Ouvrir le navigateur de blocs et le générateur IA : ils suivent la largeur.
- Thème sombre : la poignée reste visible au survol et au focus.

- [ ] **Step 5: Relancer la QA et proposer le commit**

Run: `make behat`, `make phpstan`, `make ecs`
Expected: verts.

`feat(page-builder): resize the editor panel and remember its width`
