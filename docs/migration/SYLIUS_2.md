# V1 > V2 Migration Guide

- Removed jms/serializer dependency, replaced with symfony/serializer

| Annotation JMS  | Équivalent Symfony                                                 |
| :- |:-|
| #[Serializer\ExclusionPolicy('ALL')]  | à supprimer, Symfony ignore par défaut les propriétés non annotées |
| #[Serializer\Expose]    | #[Groups(['default'])                                              |
| #[Serializer\VirtualProperty]  | Supprimé (automatique)                                             |
| #[Serializer\SerializedName('name')]  | #[SerializedName('name')]                                          |
| #[Serializer\Groups(['Group'])] | #[Groups(['Group'])                                                |

- Removed support for PHP 7.4, minimum PHP version is now 8.1

