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
4. [ ] Tester la commande de migration sur une base de données de test pour s'assurer que toutes les données sont correctement migrées.
5. [x] Ecrire un readme expliquant la migration et les changements à prévoir pour les utilisateurs du plugin.

B. Création du controller et de l'interface d'édition

1. [ ] Créer un controller dédié pour gérer l'édition des pages avec le page builder.
2. [ ] Ce controller peut être une route unique, mais il faut trouver le moyen de déterminer quelle entité est éditée (via des paramètres dans l'url par exemple), ainsi que permettre de vérifier les droits d'accès.
3. [ ] En première version, cet écran devra s'intégrer dans le template sylius (garder le menu à gauche, header, footer, etc.).
4. [ ] Et afficher une iframe qui charge l'url publique de la page en question, pour permettre une édition en contexte.

