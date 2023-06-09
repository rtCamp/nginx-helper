"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.openDocumentSettingsSidebar = void 0;
const { expect } = require('../test');
/**
 * Clicks on the button in the header which opens Document Settings sidebar when it is closed.
 *
 * @param {Editor} this
 */
async function openDocumentSettingsSidebar() {
    const editorSettings = this.page.locator('role=region[name="Editor settings"i]');
    if (!(await editorSettings.isVisible())) {
        await this.page.click('role=region[name="Editor top bar"i] >> role=button[name="Settings"i]');
        await expect(editorSettings).toBeVisible();
    }
}
exports.openDocumentSettingsSidebar = openDocumentSettingsSidebar;
//# sourceMappingURL=open-document-settings-sidebar.js.map