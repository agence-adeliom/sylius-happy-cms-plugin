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
10. [ ] Dans le pannel d'édition du bloc. J'aimerai ajuster l'UI.
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
11. [ ] Revoir le système ContentBlock pour ajouter une entité séparée pour le contenu + locale. Cela permettra d'avoir une liste de block commune entre toutes les langues. Et de gérer un contenu de fallback avec la langue par défaut de sylius.
    - On va créer une entité src/Entity/ContentBlock/ContentBlockTranslation.php + son inte
12. [ ] Modifier la calcul des routes, en enlevant la génération des routes de preview, désormais ça reprend les routes online auxquelles on ajoute ?preview=1. Donc plus besoin d'enregistrer en base de donnée.
