# Upgrade

## Unreleased (2.3.x)

### Flexible content field: JSON list, positions, prototypes, allowed blocks

- **Saved value**: the collection is now always saved as a list, sorted by position, with positions renumbered `0`,
  `1`, `2`… (integers). It used to be saved with the form names as keys (`{"3": …, "a1b2-c3d": …}`, a JSON object)
  as soon as a block was moved, removed or added, with the positions written by the admin script (`3`, `4`, …) and a
  block at position `0` moved to the end (`999`). Templates iterating over the content (`for block in content`) are
  not affected; code reading entries by key or by absolute `position` value is. Existing rows are rewritten on their
  next save only; no migration is needed.
- **Stored lists**: a content stored as a JSON list (or an object with numeric keys) no longer raises a `TypeError`
  when its edit form is built.
- **Prototypes**: new `complete_prototypes` form option (`FlexibleContentField::completePrototypes()`), default
  `false` (unchanged behaviour).
- **Allowed blocks**: `BlockCollection::getAllowedBlocks()` no longer asserts that the resource implements
  `ContentEditableInterface`. For other resources the list given to `allowedBlocks()` decides and
  `BlockTypeInterface::supports()` is not called (as for shared blocks).
