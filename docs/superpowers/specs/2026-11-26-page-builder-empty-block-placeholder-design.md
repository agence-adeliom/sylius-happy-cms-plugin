# Apercu du constructeur de pages : un bloc reste identifiable et editable quel que soit son rendu

Date : 2026-11-26
Base : branche `feat/page-builder-cross-host-preview` (PR #180, qui remplace la #179 fermee). Sans rupture de
compatibilite en 2.x (section `## 2.3.5` de `UPGRADE.md`).

## Intention

Dans l'apercu du constructeur de pages, `PreviewOverlayRenderer::wrap()` enveloppe chaque bloc dans un
`.content-block-wrapper`. L'agent d'apercu (`assets/page-builder/preview-agent.js`, injecte dans l'iframe) lui donne
`min-height: 150px`.

Dans un projet, deux defauts gênent l'edition :

- Un bloc qui ne rend rien de visible (gabarit vide, donnees manquantes, point de montage JS vide, element masque a ce
  format d'ecran, popin, script de suivi) laisse un rectangle blanc de 150px. Au survol, on voit le cadre et le bouton
  d'edition, sans savoir de quel bloc il s'agit.
- Un bloc moins haut que le bouton d'edition coupe ce bouton. Les surcouches d'etat (non publie, supprime) empilent un
  badge et un bouton, et debordent elles aussi.

Le but : un bloc present reste toujours identifiable et editable dans l'apercu. Un bloc vide affiche un encart a son
nom. Un bloc bas garde un bouton entier. Un bloc normal ne change pas.

## Objectifs

- La hauteur minimale d'un bloc suit la hauteur du bouton d'edition (et du badge d'etat), plus une marge en haut et en
  bas. Aucun bouton ni surcouche ne deborde du wrapper.
- Un bloc sans rendu visible affiche un encart avec l'icone et le nom de son type, et la mention "Aucun rendu visible
  dans l'apercu".
- La detection du vide se fait dans le navigateur, sur la hauteur reellement affichee. L'encart disparait si le contenu
  apparait plus tard.
- Les libelles de l'apercu ("Modifier le bloc", "Bloc non publie", "Bloc supprime", la mention de l'encart, le nom du
  type de bloc) s'affichent dans la langue de l'admin de la personne qui edite, des le premier rendu.

## Hors perimetre

- La detection cote PHP (par exemple `strip_tags()`) : elle rate les contenus presents mais invisibles.
- Un contenu present mais invisible par `visibility: hidden` ou `opacity: 0` : il garde sa hauteur, l'encart ne
  s'affiche pas. Limite acceptee.
- Le rendu hors apercu (front public) : rien ne change.
- Le nom du bloc partage reference : l'encart d'un bloc partage affiche le nom de son type ("Shared block"), comme
  tout autre bloc. Decision de l'utilisateur, pour ne pas charger l'entite referencee dans `Helper`.

## Contraintes

- Pas de rupture en 2.x. `wrap()` garde ses 5 parametres actuels dans le meme ordre, les nouveaux sont optionnels et
  places apres. Le constructeur de `Helper` (classe non finale, service public) garde ses 4 arguments, les nouveaux sont
  optionnels et places apres.
- `SignedPreviewContext`, `SignedPreviewParameters` et `PreviewUrlSigner` sont nouveaux dans la #180, non publies :
  on peut les modifier librement.
- Styles structurels en ligne dans le renderer, comme aujourd'hui (l'apercu s'affiche correctement avant l'injection
  des styles de l'agent). Couleurs par les variables `--hcms-*` avec repli.
- Aucun nom de client dans le code, les commits ou la PR ("dans un projet").

## Approches etudiees pour la langue des libelles

L'apercu est rendu dans la langue de la page, parfois sur un autre hote, sans la session d'admin. Il fallait choisir la
langue des libelles, puis la facon de la transmettre.

1. **Langue de la page, traduite en PHP.** Simple, mais une personne qui edite en francais une page anglaise lit
   "Edit block". Ecartee par l'utilisateur.
2. **Langue de l'admin, envoyee par message apres le chargement.** L'admin envoie les libelles et le nom de chaque type
   de bloc a l'agent, qui remplace les textes. Les textes changent apres l'affichage et l'agent porte plus de code.
   Ecartee.
3. **Langue de l'admin dans l'URL signee (retenue).** `PreviewUrlSigner` ajoute la langue de la requete d'admin a l'URL
   signee. Le serveur de l'apercu traduit les libelles dans cette langue. Tout est juste des le premier rendu, la
   signature protege le parametre, l'agent n'a rien a traduire.

## Conception

### 1. Langue de l'admin dans l'URL signee

- Nouvelle constante `SignedPreviewParameters::ADMIN_LOCALE = '_hcms_admin_locale'`.
- `PreviewUrlSigner::sign()` ajoute `$request->getLocale()` de la requete d'admin principale.
- `SignedPreviewRequestValidator` lit ce parametre de facon facultative : absent ou invalide, la validation reste
  valide et la langue d'admin vaut `null`. Format accepte : `/^[a-z]{2,3}(_[A-Z]{2})?$/`. Le parametre est couvert par
  la signature comme les autres.
- `SignedPreviewContext` recoit une propriete `?string $adminLocale` (dernier argument, `null` par defaut).
- Repli quand la langue d'admin est inconnue (apercu historique sur le meme hote, URL sans le parametre) : la langue de
  la requete courante, donc celle de la page.

### 2. Traduction des libelles et du nom du bloc

- Domaine `messages`, la ou vivent deja les cles `sylius_happy_cms.page_builder.*`. Cles en fr et en :
  - `sylius_happy_cms.page_builder.edit_block` existe deja ("Edit Block" / "Éditer le bloc") : on la reutilise.
  - Nouvelles cles `sylius_happy_cms.page_builder.preview_overlay.unpublished_block`,
    `...preview_overlay.deleted_block`, `...preview_overlay.empty_block` ("Aucun rendu visible dans l'apercu" /
    "No visible output in the preview"). Pas de sous-cles sous `page_builder.preview`, qui porte deja une valeur
    ("Preview"). Ces cles deviennent surchargeables par les projets : les citer dans `UPGRADE.md`.
- Le nom du type de bloc (`BlockTypeInterface::getName()`) est une cle de traduction : on le traduit avec le meme
  traducteur et la meme langue.
- `Helper` recoit en arguments optionnels un `?TranslatorInterface` et un moyen d'obtenir la langue d'admin (le
  `SignedPreviewRequestValidatorInterface` et le `RequestStack`, ou un petit service dedie : au choix du builder). Il
  traduit tout et passe des chaines deja traduites a `wrap()`. Sans traducteur, les libelles anglais actuels restent.
- `wrap()` ne depend pas du traducteur : il recoit les textes. Nouveaux parametres optionnels apres les 5 actuels :
  `?string $blockName = null`, `string|array|null $blockIcon = null`, et les libelles traduits (un objet valeur
  `PreviewLabels` en lecture seule, avec des valeurs par defaut en anglais, est le choix propose).

### 3. Hauteur minimale

- Le bouton d'edition prend une hauteur fixe et previsible en ligne (`height`, `box-sizing: border-box`,
  `line-height`), pour ne plus dependre de la typographie du site. Meme chose pour le badge d'etat.
- Variables CSS posees par l'agent sur `.content-block-wrapper` :
  - `--hcms-block-gap` : 16px, marge en haut et en bas.
  - `--hcms-block-min-height` : hauteur du bouton + 2 x marge (environ 76px).
  - `--hcms-block-state-min-height` : hauteur du badge + espace + bouton + 2 x marge (environ 130px), appliquee aux
    wrappers `[data-published="false"]` et `[data-deleted="true"]`.
- Le `min-height: 150px` disparait. Un projet peut surcharger les variables.

### 4. Encart de bloc vide

- `wrap()` pose `data-block-name` (nom traduit, echappe) sur le wrapper quand le nom est fourni.
- `wrap()` ajoute `.content-block-empty-placeholder` apres le contenu et avant les surcouches, masque par defaut
  (`display: none` en ligne). Il contient l'icone, le nom et la mention.
- Placement : `position: absolute; inset: 0`, sous les surcouches (z-index inferieur a 10). Il ne pese pas sur la
  hauteur du bloc, ce qui evite une boucle entre la mesure et l'affichage.
- Mise en page : une ligne, icone a gauche (premiere image si `getIcon()` renvoie un tableau, contrainte a 40px de haut
  maximum, `object-fit: contain`), puis le nom en gras et la mention dessous. Fond neutre clair, bordure en pointilles,
  couleurs `var(--hcms-*, repli)`.
- L'icone est du HTML fourni par le developpeur du type de bloc (comme dans la liste des blocs de l'admin) : il est
  insere tel quel. Le nom et les libelles sont echappes.
- Lisibilite au survol : quand le wrapper a `is-empty`, la surcouche de survol aligne le bouton a droite (au lieu du
  centre) avec la marge `--hcms-block-gap`. Le nom reste visible a gauche.
- Meme regle pour la surcouche d'etat d'un bloc vide non publie ou supprime (choix du redacteur) : avec `is-empty`,
  elle passe en ligne, badge et bouton alignes a droite. Le nom de l'encart reste visible a gauche.
- L'agent affiche l'encart par `.content-block-wrapper.is-empty .content-block-empty-placeholder { display: flex }`.

### 5. Detection du vide dans l'agent

- Contenu du bloc = les noeuds enfants du wrapper places avant l'encart (texte compris). Les surcouches et l'encart
  sont exclus.
- Mesure : un `Range` qui couvre ces noeuds, puis `getBoundingClientRect().height`. Les elements en `display: none`
  comptent pour zero. Sous 4px (seuil en constante), le wrapper recoit `is-empty`. Sinon la classe est retiree.
- Nouvelle mesure quand :
  - un `ResizeObserver` voit changer la taille d'un element enfant du contenu (images chargees, rendu JS) ;
  - un `MutationObserver` (`childList`, `subtree`) voit des noeuds ajoutes ou retires dans le contenu (point de montage
    rempli plus tard). Il ignore les mutations dans l'encart et les surcouches.
  - la fenetre change de taille (element masque selon le format d'ecran).
- Les mesures sont groupees a une par image (`requestAnimationFrame`), comme `scheduleGeometry()`.

### 6. Prevenir le constructeur

- Le message existant s'appelle `geometry` (`MessageType.GEOMETRY`), pas `hcms:blocks-layout`. Il part deja quand la
  taille du `body` change. Apres chaque changement de `is-empty`, l'agent appelle `scheduleGeometry()` pour le cas ou la
  hauteur du body ne bouge pas. Aucun nouveau type de message.

### 7. Documentation

- `UPGRADE.md`, section 2.3.5 : le `min-height: 150px` devient des variables CSS ; nouvel encart et nouvel attribut
  `data-block-name` ; libelles traduits dans la langue de l'admin. Note pour un projet qui a surcharge les styles du
  wrapper, le gabarit ou `wrap()` par decoration.
- `CHANGELOG.md`, section v2.3.5 : une ligne.
- `docs/usage/preview.md` : les variables CSS et l'encart.

## Verification

- PHPUnit `PreviewOverlayRendererTest` : `data-block-name` present et echappe, encart present et masque par defaut,
  nom echappe dans l'encart, icone en tableau reduite a la premiere, appel a 5 arguments inchange, libelles fournis
  repris dans le bouton et les badges.
- PHPUnit sur la signature et la validation : le parametre de langue d'admin est signe, lu, facultatif, et rejete s'il
  est mal forme (la requete reste valide, la langue vaut `null`).
- PHPUnit `Helper` : la langue d'admin du contexte signe est utilisee, repli sur la langue de la requete.
- `node --test` dans `assets/page-builder/tests/` si la mesure est extraite en fonction pure (decision de vide a partir
  d'une hauteur), sinon verification manuelle.
- Verification manuelle avec 3 blocs : un bloc vide affiche l'encart, un bloc de 20px garde un bouton entier, un bloc
  normal ne change pas. Avec l'admin en francais sur une page anglaise, les libelles sont en francais.

## Laisse au builder

- Le decoupage exact entre `Helper` et un eventuel service qui resout la langue d'admin.
- Les valeurs exactes des hauteurs (bouton, badge), tant que les variables restent coherentes avec elles.
- L'icone par defaut quand le type de bloc n'en fournit pas (proposition : l'icone crayon deja presente, ou aucune).
