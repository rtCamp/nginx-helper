"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.activateTheme = void 0;
const config_1 = require("../config");
const THEMES_URL = new URL('/wp-admin/themes.php', config_1.WP_BASE_URL).href;
async function activateTheme(themeSlug) {
    let response = await this.request.get(THEMES_URL);
    const html = await response.text();
    const matchGroup = html.match(`action=activate&amp;stylesheet=${encodeURIComponent(themeSlug)}&amp;_wpnonce=[a-z0-9]+`);
    if (!matchGroup) {
        if (html.includes(`data-slug="${themeSlug}"`)) {
            // The theme is already activated.
            return;
        }
        throw new Error(`The theme "${themeSlug}" is not installed`);
    }
    const [activateQuery] = matchGroup;
    const activateLink = THEMES_URL + `?${activateQuery}`.replace(/&amp;/g, '&');
    response = await this.request.get(activateLink);
    await response.dispose();
}
exports.activateTheme = activateTheme;
//# sourceMappingURL=themes.js.map