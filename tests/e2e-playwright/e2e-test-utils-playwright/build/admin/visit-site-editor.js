"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.visitSiteEditor = void 0;
/**
 * WordPress dependencies
 */
const url_1 = require("@wordpress/url");
const CANVAS_SELECTOR = 'iframe[title="Editor canvas"i]';
/**
 * Visits the Site Editor main page
 *
 * By default, it also skips the welcome guide. The option can be disabled if need be.
 *
 * @param {Admin}                 this
 * @param {SiteEditorQueryParams} query            Query params to be serialized as query portion of URL.
 * @param {boolean}               skipWelcomeGuide Whether to skip the welcome guide as part of the navigation.
 */
async function visitSiteEditor(query, skipWelcomeGuide = true) {
    const path = (0, url_1.addQueryArgs)('', {
        ...query,
    }).slice(1);
    await this.visitAdminPage('site-editor.php', path);
    await this.page.waitForSelector(CANVAS_SELECTOR);
    if (skipWelcomeGuide) {
        await this.page.evaluate(() => {
            // TODO, type `window.wp`.
            // @ts-ignore
            window.wp.data
                .dispatch('core/preferences')
                .set('core/edit-site', 'welcomeGuide', false);
            // @ts-ignore
            window.wp.data
                .dispatch('core/preferences')
                .toggle('core/edit-site', 'welcomeGuideStyles', false);
        });
    }
}
exports.visitSiteEditor = visitSiteEditor;
//# sourceMappingURL=visit-site-editor.js.map