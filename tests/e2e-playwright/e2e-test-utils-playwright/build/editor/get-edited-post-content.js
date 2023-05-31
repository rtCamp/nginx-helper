"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getEditedPostContent = void 0;
/**
 * Returns a promise which resolves with the edited post content (HTML string).
 *
 * @param {Editor} this
 *
 * @return {Promise} Promise resolving with post content markup.
 */
async function getEditedPostContent() {
    return await this.page.evaluate(() => 
    // @ts-ignore (Reason: wp isn't typed)
    window.wp.data.select('core/editor').getEditedPostContent());
}
exports.getEditedPostContent = getEditedPostContent;
//# sourceMappingURL=get-edited-post-content.js.map