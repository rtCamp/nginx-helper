"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.saveSiteEditorEntities = void 0;
/**
 * Save entities in the site editor. Assumes the editor is in a dirty state.
 *
 * @param {Editor} this
 */
async function saveSiteEditorEntities() {
    await this.page.click('role=region[name="Editor top bar"i] >> role=button[name="Save"i]');
    // Second Save button in the entities panel.
    await this.page.click('role=region[name="Editor publish"i] >> role=button[name="Save"i]');
    await this.page.waitForSelector('role=region[name="Editor top bar"i] >> role=button[name="Save"i][disabled]');
}
exports.saveSiteEditorEntities = saveSiteEditorEntities;
//# sourceMappingURL=site-editor.js.map