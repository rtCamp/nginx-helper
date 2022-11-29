"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.isCurrentURL = void 0;
/**
 * Internal dependencies
 */
const config_1 = require("../config");
/**
 * Checks if current path of the URL matches the provided path.
 *
 * @param {PageUtils} this
 * @param {string}    path String to be serialized as pathname.
 *
 * @return {boolean} Boolean represents whether current URL is or not a WordPress path.
 */
function isCurrentURL(path) {
    const currentURL = new URL(this.page.url());
    const expectedURL = new URL(path, config_1.WP_BASE_URL);
    return expectedURL.pathname === currentURL.pathname;
}
exports.isCurrentURL = isCurrentURL;
//# sourceMappingURL=is-current-url.js.map