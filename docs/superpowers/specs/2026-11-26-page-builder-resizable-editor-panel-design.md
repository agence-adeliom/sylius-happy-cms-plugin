# Largeur réglable du panneau d'édition du constructeur de pages

Date : 2026-11-26
Base : branche `2.3.x`, sans rupture de compatibilité.
Taille : petit changement (spec courte).

## Intention

Quand un administrateur édite un bloc, le panneau de droite recouvre une partie de l'aperçu. Il veut voir le rendu du
bloc pendant qu'il réfléchit au contenu à saisir. Il veut donc régler la largeur du panneau à la souris, en tirant son
bord gauche, pour le réduire ou l'agrandir. La largeur choisie doit être conservée après un rechargement de la page.

## Existant

- `templates/admin/page_builder/content.html.twig` : le panneau `.page-builder__editor-panel`
  (`data-page-builder-target="editorPanel"`), ouvert par la classe `is-open`.
- `assets/page-builder/page-builder.css` : le panneau est en `position: fixed` à droite, `width: 820px`,
  `max-width: calc(100vw - 120px)`. Il recouvre l'aperçu sans le pousser.
- `assets/page-builder/page-builder.js` : ouverture et fermeture du panneau (`editBlock`, `closeBlock`, bouton
  `editorToggle`, touche Echap).
- Le panneau contient l'iframe du formulaire du bloc ; l'aperçu de la page est une autre iframe.

## Objectifs

- Une poignée sur le bord gauche du panneau permet d'en régler la largeur à la souris (curseur `col-resize`).
- La largeur est enregistrée dans le `localStorage` et réappliquée à chaque ouverture, y compris après un rechargement.
- La largeur reste comprise entre 360 px et `100vw - 120px`.
- Un double-clic sur la poignée remet la largeur par défaut (820 px).
- La poignée est utilisable au clavier.

## Hors périmètre

- Une largeur par type de bloc ou par page : une seule valeur pour tout le navigateur.
- Une largeur stockée côté serveur ou par utilisateur d'admin.
- Le redimensionnement des couches internes (navigateur de blocs, génération IA) : elles occupent déjà tout le panneau
  (`.page-builder__layer`, `inset: 0`) et suivent donc sa largeur.

## Approches

1. **Poignée en JS sur le bord gauche, largeur portée par une variable CSS (retenue).** Elle répond exactement au geste
   demandé et ne modifie pas la largeur de l'aperçu.
2. Propriété CSS `resize` native : écartée, elle n'agit que sur le coin bas droit et pas sur le bord gauche.
3. Panneau qui pousse l'aperçu au lieu de le recouvrir : écartée, la largeur de l'aperçu changerait et le rendu du bloc
   (points de rupture responsive) ne serait plus fidèle.

## Comportement

### Largeur

- Le CSS du panneau passe à `width: var(--hcms-editor-width, 820px)` et garde `max-width: calc(100vw - 120px)`.
- Un `min-width: 360px` garantit un formulaire utilisable.
- Le JS pose `--hcms-editor-width` en ligne sur le panneau.

### Poignée

- Un élément poignée est ajouté au balisage du panneau, positionné sur toute la hauteur de son bord gauche, avec une
  zone de saisie d'environ 8 px et un retour visuel au survol et au focus (couleur `--hcms-accent`).
- Attributs : `role="separator"`, `aria-orientation="vertical"`, `aria-valuenow`, `aria-valuemin`, `aria-valuemax`
  (en pixels), `tabindex="0"`, `aria-label` et `title` traduits (nouvelles clés dans `translations/messages.fr.yaml` et
  `messages.en.yaml`, sous `sylius_happy_cms.page_builder`).
- Glissement : nouvelle largeur = `window.innerWidth - clientX`, bornée par les limites. Les Pointer Events avec
  `setPointerCapture` sont utilisés.
- Pendant le glissement, les deux iframes reçoivent `pointer-events: none` (par une classe posée sur le conteneur du
  constructeur), la sélection de texte est bloquée et la transition du panneau est coupée. Tout est rétabli au
  relâchement.
- Clavier : flèche gauche agrandit de 20 px, flèche droite réduit de 20 px ; `Home` et `End` vont aux bornes ; `Entrée`
  ou double-clic remettent 820 px.

### Persistance

- Clé `localStorage` : `happy-cms.page-builder.editor-width`, valeur entière en pixels.
- Écriture au relâchement de la souris et après chaque action clavier, pas à chaque mouvement.
- Lecture au chargement du constructeur. Une valeur absente, non numérique ou illisible donne 820 px.
- Accès au `localStorage` entouré d'un `try/catch` : en navigation privée ou stockage bloqué, le panneau fonctionne avec
  la largeur par défaut.
- Au redimensionnement de la fenêtre, la largeur affichée est rebornée ; la valeur enregistrée n'est pas réécrite.

## Laissé au constructeur

- L'emplacement du code : dans `page-builder.js` ou dans un petit module importé par lui.
- Le style exact de la poignée, dans le respect des jetons de thème clair et sombre existants (`--hcms-*`).
- Le comportement sur écran étroit, du moment que les bornes s'appliquent et qu'il n'y a pas de défilement horizontal.

## Vérification attendue

- Tirer la poignée réduit et agrandit le panneau, y compris quand la souris passe au-dessus des iframes.
- Après un rechargement, le panneau s'ouvre à la largeur laissée.
- Double-clic et `Entrée` remettent 820 px.
- Les flèches, `Home` et `End` agissent quand la poignée a le focus.
- Le navigateur de blocs et le générateur IA suivent la largeur du panneau.
