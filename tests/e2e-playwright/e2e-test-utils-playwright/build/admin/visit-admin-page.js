"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.visitAdminPage = void 0;
/**
 * External dependencies
 */
const path_1 = require("path");
/**
 * Visits admin page and handle errors.
 *
 * @param {Admin}  this
 * @param {string} adminPath String to be serialized as pathname.
 * @param {string} query     String to be serialized as query portion of URL.
 */
async function visitAdminPage(adminPath, query) {
    await this.page.goto((0, path_1.join)('wp-admin', adminPath) + (query ? `?${query}` : ''));
    // Handle upgrade required screen
    if (this.pageUtils.isCurrentURL('wp-admin/upgrade.php')) {
        // Click update
        await this.page.click('.button.button-large.button-primary');
        // Click continue
        await this.page.click('.button.button-large');
    }
    if (this.pageUtils.isCurrentURL('wp-login.php')) {
        throw new Error('Not logged in');
    }
    const error = await this.getPageError();
    if (error) {
        throw new Error('Unexpected error in page content: ' + error);
    }
}
exports.visitAdminPage = visitAdminPage;
//# sourceMappingURL=visit-admin-page.js.map