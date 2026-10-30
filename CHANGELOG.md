# Changelog

## Icons arrive rendered, and a user's results stay their own

**Valet's icons show for icon libraries that render inline SVG.** Valet drew
each result's icon from its selector, as `<span class="icon icon-…">`, which
only drew while the icon's library was a font with its stylesheet on the page.
Where neo_icon is installed, `/api/valet` now answers `{items, icons}`: items
keep their `icon` selector, and `icons` holds each icon's rendered markup once,
however many results use it. Script puts that markup in the page, falling back
to the class for an icon the map lacks — an exo_icon site, or a selector an
alter hook names that no library holds — which draws as it always did.

- The data carries the icon libraries' cache tags, and saving a library, as
  switching its render mode or re-uploading its package does, flushes Valet's
  caches and moves the cache id, so browsers fetch the results again.
- The Valet element attaches `neo_icon/icon-inline`, the sizing rules an inline
  SVG icon needs.
- Results an alter hook removed no longer turn the data into an object keyed by
  position; `items` is always a list.
- Browsers holding results in the old shape (a bare list) still read them, and
  fetch new ones since the cache id changes.

**A user is no longer handed another user's kept results.** The Valet element
is render cached per set of permissions, but it carried the id of the user who
rendered it first in the cache id browsers keep results under, so every user
sharing those permissions got that id, and in a shared browser the first
user's links. The element now carries only the site's cache id; script adds the
user's id and permissions hash from `drupalSettings`, so the kept results are
also fetched again when a user's roles change. Recent links show only from
results kept for the current user, and are fetched when there are none.

## 2.1.4 — 2026-08-17

- `valet.info.yml` carries the right version again.

## 2.1.3 — 2026-07-08

- The admin form's optional typed config manager argument is declared
  nullable, for PHP 8.4.

## 2.1.2 — 2026-03-12

- Drupal 11: the admin form hands `ConfigFormBase` the typed config manager
  when the container has one.

## 2.1.1 — 2026-03-11

- A **Recent** list shows the five links the user has chosen most, before
  anything is typed.
- Valet's colour follows the theme's `--color-primary-500` when it is set.

## 2.1.0 — 2026-03-11

- Results whose label matches rank above tag-only matches, then by how often
  they have been chosen in this browser.
- The last eXo names are gone: browser storage keys are now `valetData` and
  `valetCacheId`, so browsers fetch their results once more.
- The build moved from gulp to esbuild and sass.

## Releases before 2.1.0 are not recorded

This file was started in October 2026. The releases from `2.1.0` on were
recorded afterwards from their commits; earlier ones are in the git history
only.
