// easy-media-manager (@agence-adeliom/easy-media-manager, installed by the project), configured once per page from
// the JSON `#happy-cms-easy-media-config` (templates/media/easy_media/config.html.twig). Loaded on demand by the
// Stimulus controllers `media-manager` (media manager page) and `media-field` (media field).
import { EasyMedia, enTranslations, frTranslations } from '@agence-adeliom/easy-media-manager';
// Stylesheet without cascade layers: the rules of the Sylius admin (Tabler, outside layers) do not win over the
// rules of the library.
import '@agence-adeliom/easy-media-manager/style.unlayered.css';
import './easy-media.css';

export const CONFIG_ELEMENT_ID = 'happy-cms-easy-media-config';

const translations = { en: enTranslations, fr: frTranslations };

let configured = false;

/**
 * The library, configured with the routes, options and CSRF token of the page on the first call.
 */
export function getEasyMedia() {
  if (configured) {
    return EasyMedia;
  }

  const element = document.getElementById(CONFIG_ELEMENT_ID);
  if (!element) {
    throw new Error(`easy-media-manager: #${CONFIG_ELEMENT_ID} is missing, enable sylius_happy_cms.media.easy_media_manager.`);
  }

  const settings = JSON.parse(element.textContent);
  const locale = String(settings.locale ?? 'en').split(/[-_]/)[0];
  // Without a mode in the theme option, the manager follows the light/dark theme of the admin.
  const followsAdminTheme = !settings.config?.theme?.mode;
  const theme = followsAdminTheme ? { ...settings.config?.theme, mode: adminThemeMode() } : settings.config?.theme;
  EasyMedia.configure({
    locale,
    translations: translations[locale] ?? enTranslations,
    // CSRF token of the write routes, read by the CsrfProtection of the media controllers.
    config: { ...settings.config, theme, headers: { 'X-CSRF-Token': settings.csrfToken } },
    features: settings.features,
    routes: settings.routes,
  });
  if (followsAdminTheme) {
    followAdminTheme();
  }
  configured = true;

  return EasyMedia;
}

/**
 * The theme of the Sylius admin, set on <html> as data-bs-theme by its theme switcher.
 */
function adminThemeMode() {
  return document.documentElement.dataset.bsTheme === 'dark' ? 'dark' : 'light';
}

/**
 * Passes the theme switches of the admin to the open manager (easy-media-manager 0.3+, which has setTheme()). With an
 * older version, the mode of the page load stays until the next page.
 */
function followAdminTheme() {
  if (typeof EasyMedia.setTheme !== 'function') {
    return;
  }

  new MutationObserver(() => EasyMedia.setTheme({ mode: adminThemeMode() })).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-bs-theme'],
  });
}
