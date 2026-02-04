Fonctionnement actuel :
===

- Les entités qui étendent la classe 'CmsRoutableInterface' ont des entités Translation associées.
- Dans les entités Translation, il y a un champ 'content' qui stock la liste des blocs de contenu au format JSON.
- Cette liste contient les données nécessaires pour reconstruire les blocs de contenu via un formulaire.
- Chaque bloc de contenu est identifiable car il s'agit d'un form type qui implémente l'interface 'Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface'.
- Lorsqu'une une entité est persistée ou mise à jour, un event listener intercepte l'événement et créer les routes associées à l'entité en question.
- Le système de routing est basé sur CmfRouting, un plugin symfony.
- Le page builder actuel est une page "edit" des resources sylius, où le formulaire n'affiche que le champ content.
- Une vue spéciale permet d'ajouter, de déplacer et de supprimer des blocs de contenu via une interface drag & drop.
- Le formulare est en partie alimenté en js par des prototypes (contenu html mis dans des attributs data-*) pour permettre l'ajout dynamique de blocs.

Fonctionnement souhaité :
===

- Créer une interface spéciale, via un controller dédié, pour éditer le contenu des pages.
- Ce controller doit fonctionner pour toues les entités (resources sylius) qui étendent 'CmsRoutableInterface'.
- Au lieu d'avoir un champ content dans les entités, l'idée serait d'avoir une entité ContentBlock liée à l'entité principale via une relation OneToMany.
- Chaque ContentBlock aurait un type (correspondant aux form types implémentant BlockTypeInterface) et des données sérialisées (json). Il faudrait des propriétés à l'entités pour connaître la locale, la position, si le contenu est publié. Chaque ContentBlock contient des données JSON, à savoir 2 versions, une version publiée utilisée en front, et une version draft utilisée en backoffice pour une mode preview. Je pense aussi à ajouter une information qui permettrait de dire dans quel layer d'un template un block est positionné. Par défault on peut le mettre avec la valeur null ou 'default'.

Les étapes pour y parvenir seraient les suivantes :

A. Gestion de la nouvelle entité et migration des données existantes

1. [x] Créer l'entité ContentBlock avec les propriétés nécessaires (type, data, locale, position, published, etc.).
2. [x] Mettre à jour les entités existantes pour ajouter une relation OneToMany vers ContentBlock. Etant donné que ContentBlock contient la locale, ce champ serait à ajouter plutôt dans l'entité qui n'est pas la translation. Penser à la rétrocompatibilité et garder le champ actuel.
3. [x] Prévoir une commande de migration pour transférer les données du champ content vers des ContentBlocks liés.
   - [x] Pour chaque entité CmsRoutableInterface, récupérer le contenu JSON du champ content.
   - [x] Parser ce JSON pour extraire les blocs de contenu.
   - [x] Pour chaque bloc, créer une instance de ContentBlock avec les données extraites, en définissant le type, les données, la locale, la position, etc.
   - [x] Lier chaque ContentBlock à l'entité principale correspondante.
   - [x] Persister les ContentBlocks en base de données.
4. [x] Tester la commande de migration sur une base de données de test pour s'assurer que toutes les données sont correctement migrées.
5. [x] Ecrire un readme expliquant la migration et les changements à prévoir pour les utilisateurs du plugin.

B. Création du controller et de l'interface d'édition

Pour cette interface, voici comment je l'imagine :
- Un container sur la toute la largeur qui est une zone de toolbar avec une première zone pour changer la résolution (desktop, tablette, mobile), une zone pour publier / sauvegarder le contenu, et un bouton qui permet d'activer/désactiver les interactions avec les blocs qui seront dans l'iframe, lien _blank vers la page publiée côté front.
- Le container principal se divise en deux partie de gauche à droite.
  - La partie de gauche est une sidebar très fine.
  - La partie de droite est une iframe qui charge l'url publique de la page en question. Cette iframe occupe toute la hauteur disponible et la largeur restante. Elle permet de visualiser le rendu final de la page avec les blocks ajoutés.
- L'iframe et la sidebar occupent toute la hauteur de la fenêtre et sont synchronisées en scroll. Il faudra prévoir un script pour gérer cette synchronisation.
- Dans l'idéal, et dans l'iframe, les blocs sont identifiable via un code unique. Ce code unique permet de connaître la hauteur et la position de chaque bloc dans l'iframe. Cela permettra de positionner des "handles" dans la sidebar pour chaque bloc, afin de pouvoir les déplacer, éditer ou supprimer.
- La sidebar est minimaliste, elle affiche un simple bouton qui s'active lorsqu'on survole un bloc dans l'iframe. En cliquant sur ce bouton, on ouvre un tooltip qui permet plusieurs actions :
  - Supprimer le bloc.
  - Editer le bloc (ouvrir un formulaire modal avec les champs du block type correspondant).
  - Déplacer le bloc (via du drag & drop dans la sidebar).
- En haut de la sidebar, il y a un bouton "Ajouter un bloc" qui ouvre un modal avec la liste des blocks types disponibles. En sélectionnant un block type, on l'ajoute à la fin de la liste des blocks. Il faudra ensuite rafraîchir l'iframe pour afficher le nouveau bloc.
- La toolbar en haut permet de sauvegarder les modifications. L'iframe se recharge pour afficher les changements et idéalement, on reste positionné au même endroit (scroll) dans l'iframe.
Le tout est un composant livewire ou symfony UX pour gérer les interactions en ajax.
Le point de départ est l'entity resource sylius. La classe étend de 'ResourceInterface', 'CmsRoutableInterface' et de 'ContentEditableInterface', on peut donc créer une route dédiée pour éditer le contenu via le page builder.

Voici les étapes pour cette partie B :

1. [x] Créer le controller dédié pour l'édition du contenu des entités qui étendent de 'ResourceInterface', 'CmsRoutableInterface' et de 'ContentEditableInterface'. Il reste à reflechir à la meilleure façon de faire cela (qui soit compatible avec la déclartion des routes sylius pour les resources). Soit de faire un controller global à qui on passe la resource et l'id en paramètre, soit de faire un controller par resource (plus verbeux mais plus simple à gérer).
2. [x] Créer le composant livewire ou symfony UX pour gérer l'interface d'édition décrite plus haut. On commence par la structure de base (toolbar, sidebar (sans bloc pour commencer), iframe) en front statique. L'utilisation de bootstrap est possible pour le layout pour les différentes composants.
3. [x] Réussir à charger l'url publique de la page dans l'iframe en fonction de la resource et de l'id passés en paramètre au controller. Utilisation de la route preview.
4. [x] Dans l'interface du builder, j'aimerais ajouter une 3ème colonne à droite. Cette colonne s'affiche comme un volet venant de la droite quand on a une résolution d'écran inférieure à 1980px. A l'inverse pour les grandes résulutions, la colonne prendrait 1 tier de l'écran à droite. On pourra prévoir dans ce layer 3 choses : 
     - 1 première ligne avec deux boutons alignés à droite (plier/déplier le volet/colonne), un autre qui accueillera un menu contextuel (on peut mettre une icône avec 3 points verticaux).
     - 1 container qui prend la hauteur disponible et qui accueilera à terme le formulaire d'édition d'un bloc. Par défault on peut mettre une information qui indique à l'utilisateur de sélectionner un bloc pour l'éditer. Avec un skeleton ressemblant à un formulaire.
     - 1 ligne de bouton en bas à hauteur fixe, qui contient un bouton à droite qui permettra de sauvegarder le formulaire d'édition du block. Peut être à gauche un bouton annuler pour revenir à l'état initial (cf. message ci-dessus)
5. [x] Modifier le rendu des blocs pour ajouter un identifiant unique dans le HTML de chaque bloc (data-block-id ou id html). Cela permettra de les identifier dans l'iframe. Pour le rendu on peut créer des nouveaux helper twig spécifiques au nouveau système de ContentBlock.
6. [x] Afficher dans la sidebar, la liste des blocs présents dans la page en question (cf. layer blockHandles). Via la méthode getContentBlocks() de l'entité principale. En utilisant getPreviewContentBlock(). Prévoir d'ajouter dans l'interface, dans la barre supérieure un select pour modifier la locale en cours. Au changement de valeur de la locale afficher une confirmation avant de recharger la page. Utiliser un get pour la locale souhaitée. La prendre en compte dans le controller. La liste des locales doit être celles définies dans l'entités locales de sylius.
7. [x] Ajuster les liste des blocks dans la page + sidebar. Il ne faudrait plus se baser sur getPreviewContentBlock(), en réalité il faut qu'on affiche tous les blocs, et en mode preview, nous devons ajouter un attribut autour du bloc, pour indiquer qu'un block est inactif. On pourra alors appliquer un style différent dans la sidebar pour indiquer ceux qui sont disabled. Et même aller plus loin si possible, ajouter un layer par dessus le block qui montre son état inactif, directement dans l'iframe (pour cela il faut mettre des styles en html car on ne pourra pas avoir de feuille de style, donc  à prévoir dans le heplper twig 'happy_cms_content_block_render')
8. [x] Maintenant nous avons 3 blocs dans la page, essayons de synchroniser le scroll entre l'iframe et la sidebar. Lorsqu'on scroll dans l'iframe, la sidebar doit aussi scroller pour rester alignée avec les blocs visibles.
   Pour information les blocs, en front, en mode preview sont wrappé un par un layer spécial
```html
    <div data-block-id="42" data-block-layer="default" class="content-block-wrapper">
      ...
    </div>
```
8. [x] Pour le formulaire d'édition d'un block je pensais faire un composant symfony live basé sur le form type du bloc. Il faudrait, par un jeu d'event, que le composant live se charge
   via la fonction edit bloc. Pour le composant live, il est soit lié à un block existant alors on charge le formulaire dans sa zone centrale et dans le bouton on a le bouton submit.
   A l'inverse, dans le cas où aucun block type est lié, cela signifie qu'on vient de cliquer sur le bouton d'ajout d'un block. Dans ce cas on affiche un bouton centré en hauteur pour
   permet de "parcourir les blocks existant" (on verra plus tard pour l'interface de choix des blocs existants).
9. [x] Pour terminer les actions entre les boutons d'édition ou ajout des blocks sur le composant bloc "BlockEditor" éditor, il faudra :
   1. Gérer le bouton "Add Block" pour appeler editBlock(null), Et editBlock(id) pour l'édition.
   2. Le composant live affichera dans un premier temps en fonction de si on est en mode ajout au modification : Ajout => Bouton browse déjà existant ; Modification => Zone de formulaire vide pour l'instant + bouton save dans le footer.
10. [x] Dans le pannel d'édition du bloc. J'aimerai ajuster l'UI.
    - Quand l'éditeur est en mode édition, ajouter un layer de toolbar liée au bloc, dans le layer page-builder__editor-content. Y ajouter quelques éléments statiques pour l'instant :
      - A gauche le nom du bloc (fin du formType)
      - au milieu 2 dropdowns :
        - 1 pour gérer la visilité du bloc, publié / non publié
        - 1 autre pour gérer le déplacement. Ex: "Position 1/X". Et dans les valeurs on pourrait avoir "Déplacer en premier", "Déplacer en dernier", "Déplacer en position 1/X", etc.. en allant de 1 à X.
      - A droite un bouton supprimer avec une confirmation.
    Pour rappel nous sommes dans le context du component Live BlockEditor.
    - Le layer page-builder__editor-footer devrait être aligné tout en bas pour que la zone page-builder__editor-content prennent toute la hauteur disponible.
    - Dans le layer page-builder__editor-content, quand le form est actif, avant le formulaire nous allons ajouter un titre pour indiquer qu'il faut renseigner le formulaire pour éditer le bloc.
    - Penser aux traductions.
11. [x] Chaque block peut avoir des form theme, il faudrait les charger dynamiquement. Cf la méthode configureAdminFormThemes d'AbstractBlock. Dans templates/admin/page_builder/_block_editor.html.twig il faut ensuite charger tous les form theme. On peut prendre exemple sur la method getViewVars du fichier vendor/agence-adeliom/sylius-easy-crud-plugin/src/CrudFactory/CrudAdminFactory.php
12. [x] Chaque block peut avoir des assets spécifiques, il faudrait les charger dynamiquement. Comme pour les form theme, il faudrait les charger dynamiquement. Voir manageFieldAssets du fichier vendor/agence-adeliom/sylius-easy-crud-plugin/src/CrudFactory/CrudAdminFactory.php, puis getViewVars pour l'injection dans les vues.
13. [x] L'event live:render:finished du BlockEditor.js n'est jamais appelé, il faudrait aussi l'appeler au premier chargement.
14. [x] Dans le BlockEditor, en mode ajout. J'aimerais ajuster le contenu et ajouter une option en plus de browser de bloc. Qui serait que si aucun bloc n'existe dans la page pou la locale en cours, de proposer d'aller récupérer les blocs d'une autre locale. Et à validation, via le composant live, aller vérifier la configuration de la locale, récupérer les blocs et les injecter à l'identique. Ensuite le block s'ajoute automatique, l'interface s'actualise et l'utilisateur voit le mode édition directement chargé.
15. [x] Renommer l'event happy-cms:block-editor:form-loaded, pour le rendre plus générique 'sylius-crud:dynamic:reload', renommer les différents fichiers
16. [x] Le Field TinyMCEField, charge le tinymce en js, hors cela ne fonctionne pas lorsqu'on a le rendu des blocs en ajax; Il faudrait voir pour déporter les scripts et la configuration dynamique dans un fichier d'asset à part. Le template du form theme est ici field/tinymce/form.html.twig. J'ai commencé à créé un fichier assets/tinymce/field.js. Le bundle utilisé est emileperron/tinymce-bundle.
17. [x] Désormais, nous allons travailler sur le remplissage du formulaire avec les données d'un contentBlock à injecter. Puis gérer, la validation et l'enregistrement du formulaire.
18. [x] Le formulaire ne persist pas et j'ai compris pourquoi. C'est par qu'on change son formType selon qu'on soit en mode édition ou en mode ajout. Le premier form type EmptyBlockType::class reste en mémoire. Pour solution on va devoir découper le BlockEditor en 2 composants différents. Le parent qui gère uniquement la partie ajout sans formulaire, le fait qu'on puisse passer d'un mode ajout au mode édit avec le blockId. Et dans le deuxième composant on va uniquement gérer la partie formulaire avec le formType du block en cours d'édition. Les 2 blocks pourront communiquer ensemble. On peut par exemple créer le composant BlockEditorForm.

19. [x] Dans le fichier templates/admin/page_builder/index.html.twig, nous avons à partir de la ligne 992, plusieurs scripts qui ferment le panneau, il faudrait appeler la fonction editBlock(null) quand il se ferme.

20. [x] On va tester une nouvelle approche pour le formulaire d'édition d'un bloc :
    - Au lieu de passer par un composant live BlockEditorForm
    - On va remplacer la layer par une iframe qui va occuper la hauteur disponible.
    - On va créer une nouvelle route comme fait pour 'sylius_happy_cms_admin_page_builder' mais dédiée uniquement au formulaire.
    - Cette route va passer via le controller PageBuilderController. On va reprendre la logique qu'on avait dans le BlockEditorForm.
    - Sauf qu'on va faire un formulaire standard, et non live.
    - Au post, on va effectuer les mêmes actions sur l'entité ContentBlock, celles qu'on avait dans la méthode save().
    - Une fois le form validé et enregistré, on pourra ajouter un event pour recharger l'autre iframe de preview.
21. [x] Dans le fichier templates/admin/page_builder/_block_editor.html.twig, dans le cas où on propose de copier des contenu venant d'une autre languue, cf. availableLocalesWithBlocks. J'aimerais ajouter un checkbox en dessous des choix. Une checkbox qui permettrait de traduire automatiquement via un agent IA (non configuré encore pour l'instant) dans la langue en cours. L'option IA sera à terme activable dans ce bundle avec le plugin symfony ia. Peut être trouver le moyen de donner à l'utilisateur d'en savoir plus en redirigeant vers la doc. https://github.com/agence-adeliom/sylius-happy-cms-plugin/blob/2.x/docs/CONFIGURE_IA_AGENT.md. Cette fonctionnalité sera implémentée plus tard.

22. [x] Dans le fichier templates/admin/page_builder/content.html.twig, j'ai ajouté un lien pour publier la page. L'idée est de recharger la page en passant des GET :

23. [x] Dans le dossier src/Block/, j'ai plusieurs Blocks. Chaque bloc a un fichier template twig équivalent. On peut retrouver le fichier avec la méthode 'getFrontEndTemplatePath'. J'aimerai retravailler les templates front avec bootstrap avec une UI propre et pertinente par rapport au bloc. Tu peux t'aider du formulaire construit via la méthode buildBlock pour connaitre les propriétés disponibles. L'idée est d'ajuster le template front pour l'améliorer avec l'ui bootstrap.

24. [x] Alimenter l'interface browse package, depuis le bouton présent ici templates/admin/page_builder/_block_editor.html.twig :
    - Au clic sur le bouton browse packages, ouvrir une modal qui va lister les blocs.
    - Dans cette modal, reprendre les fonctionnalités qu'on trouve dans le block 'flexible_content_collection_widget' du fichier templates/field/flexible_content/form.html.twig, il s'agit de l'ancienne version.
    - Il y avait une liste des blocs ainsi qu'un moteur de filtre
    - Je le te laisse libre pour l'UI dans la modal bootstrap.
    - On verra plus tard pour les actions d'ajout et comment les gérer.
25. [x] La fonction supprimer un bloc est manquante dans le fichier templates/admin/page_builder/_block_editor.html.twig, on peut passer par une action du live component BlockEditor
27. [x] Bouton retour vers la page d'édition, trouver un moyen de dynamiser le retour vers la route edit. La resource Sylius a des routes pour son CRUD, il faudrait voir si on peut récupérer le nom de la route 'edit' ou 'update.'
28. [x] Dans contentBlock, il faudrait ajouter un boolean pour une fonctionnalité de soft delete. Lorsqu'on supprime un bloc, cocher le boolean. Lors de la publication du contenu, supprimer le bloc à ce moment là. En mode preview, le bloc doit rester visible. Mais à la manière des blocs non publiés, il faudrait ajouter un layer au dessus du bloc pour indiquer qu'il est supprimé, en rouge. Un bloc supprimé peut être restauré dans le panneau d'édition. L'îcone de suppression sera remplacée par une icône de restauration.
29. [x] Soucis pour lister blocs partagés
30. [x] Dans easy crud, ajouter les contextes de la resource dans les actions. Afin de metDans PageAdmin, voir pour remplacer l'action "manage content" par le lien vers le nouveau page builder.
31. [x] Voir pour le bundle IA, pour l'intégrer et l'invoquer optionnement si le bundle est installé.
32. [x] Déplacer les styles et scripts dans des assets :
    - assets/admin/page-builder/page-builder.css (12KB - styles du page builder)
    - assets/admin/page-builder/page-builder.js (35KB - logique modulaire)
    - assets/admin/page-builder/entrypoint.js (point d'entrée Webpack)
33. [ ] Erreurs php stan
34. [x] Sylius 2.2+ => Attendre le test application version 2.2
35. [x] Ajouter un paramètre au bundle qui permet de forcer le template de page utilisé pour le preview dans l'iframe. Par défaut on utilise le template de la page. Mais dans certains cas, on peut vouloir utiliser un template spécifique pour le preview (sans header/footer par exemple).
36. [x] Renommer la variable de preview. ?preview=1 => ?happy_cms_preview=1.
37. [x] Ajouter un bouton à côté des actions de redimension (desktop, tablette, mobile) pour modifier l'alignement de l'iframe. Par défault elle est centrée. Le bouton agit comme un toggle. Si on clique dessus, l'iframe s'aligne à gauche. Un second clic l'aligne à droite. Un troisième clic la remet au centre.
38. [ ] En publiant une page, appeler le service de vidage de cache.
39. [ ] Déplacer l'action de vidage de cache dans l'interface du page builder. Ajouter un bouton dans la toolbar pour vider le cache manuellement. Et l'enlever de l'interface des pages (grid)
40. [ ] Ajouter des dépréciations pour les anciennes méthodes liées à l'ancien système de page builder (champ content JSON). Mais aussi le field src/Admin/Field/FlexibleContentField.php
        Ce système sera supprimé dans la version 3.x du plugin.
41. [ ] Documentation

C. Fonctionnalité de génération de contenu via IA

Basé sur le bundle Symfony IA je souhaite :

1. [x] Création d’un outil IA (ou service symfony) pour fournir la liste des blocs de contenus existants du CMS.
- Les blocs héritent de l’interface Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface. Il est possible de récupérer la collection via le service Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection
- Chaque bloc est en réalité un form Symfony, on peut récupérer la liste des champs possibles via la méthod buildBlock. Qui aliment en un FormBuilderInterface passé en paramètre.
- Cet outil IA, doit retourner la liste des blocs et leurs champs + type.
- L’idée est de founir un json.
- L’objectif est de serialiser les propriétés des blocs de contenu. Cela peut aussi se faire via un service. L’outil IA (Tool), pourrait être optionnel à ce stade.

2. [x] Ajout dans le page builder d’un bouton de génération de contenu via IA.
- Le bundle IA n’est pas forcément intégré dans les dépendances du projet, il faudrait ajouter un test  qui va conditionner l’affichage du bouton. Si le bundle n'est pas installé on peut tout de même afficher la fonctionnalité mais avec un message indiquant qu'il est possible de profitier de cette fonctionnalité en installant le bundle Symfony IA et en branchant un agent IA (via ChatGPT, etc..)
- Lorsqu’on ouvre le panneau d’édition. Ajouter dans l’interface un mini formulaire qui demande 2 informations :
  a. Prompt du contenu de la page et du contenu qu’on souhaite générer. Par exemple : Je souhaite générer du contenu pour une page service, qui explique notre prestation de développement e-commerce.
  b. Nombre de blocs à générer.
- Utiliser le composant Live BlockEditor pour implémenter ça.
- Lorsqu’on valide, poster ces éléments dans une action.
- Créer un  service Symfony qui va récupérer ces données. Ce service va permettre ensuite d’appeler un agent IA via les fonctionnalités Symfony IA.

3. [x] Communication avec les agents IA.
- Ajouter un paramètre au bundle Happy CMS pour définir l’agent IA à utiliser. Ou alors se baser sur l’agent par défaut configuré dans Symfony IA. Sans doute plus efficace.
- Via les composants Symfony IA, créer un chat qui injecte la liste des blocs (A.) et fait une demande de création de contenu. Basé sur le prompt donné par l’utilisateur (B), et le nombre de bloc souhaités.
- C’est l’agent IA qui doit à partir des blocs fourni prendre les plus pertinents selon le contexte et le contenu souhaité.
- La réponse de l’agent doit être retournée via le systèmème de OutputProcessor et passer par un objet.
- Cet objet renvoi la liste des blocs générés. A l’image de liste des blocs founis en entrés (meme propriétés, namespace, etc…)
- Ce contenu doit ensuite être enregistré	 dans la page. Voici un exemple de contenu à enregistrer :
```php
[

  'hp-flex-9' => [
      'title' => 'Ready to Transform Your Content Management?',
      'wysiwyg' => '<p>Join hundreds of Sylius stores already using Happy CMS to create exceptional content experiences. Get started today and see the difference a purpose-built CMS can make.</p>',
      'cta_one' => [
          'label' => 'Get Started Now',
          'link' => 'https://github.com/agence-adeliom/sylius-happy-cms-plugin',
      ],
      'position' => '9',
      'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\CtaBlockType',
      'block_published' => '1',
  ],

    'hp-flex-10' => [
        'wysiwyg' => '<p><strong>Happy CMS for Sylius</strong> is the complete content management solution designed specifically for Sylius e-commerce platforms. With its revolutionary visual page builder, advanced media management, multi-language support, and SEO optimization tools, Happy CMS empowers merchants to create stunning content experiences that drive conversions. Built by <strong>Agence Adeliom</strong>, Happy CMS seamlessly integrates with Sylius\'s architecture while providing an intuitive interface for content creators. Whether you\'re building product landing pages, managing blog content, or creating marketing campaigns, Happy CMS delivers the flexibility and power you need to succeed in e-commerce.</p>',
        'position' => '10',
        'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\SeoBlockType',
        'block_published' => '1',
    ],
]
```

4. [x] Améliorer la configuration du bundle pour gérer les options IA :
    - Dans le fichier src/Services/AI/BlockContentGenerator.php (method buildSystemPrompt) on utilise un prompt système par défaut. Il faudrait permettre à l'utilisateur de le personnaliser via la configuration du bundle.

Todo :
- Déporter la doc USAGE IN BLOCK-SPECIFIC SCRIPTS dans la doc de création d'un bloc
