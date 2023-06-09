"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.clickBlockToolbarButton = void 0;
/**
 * Clicks a block toolbar button.
 *
 * @param {Editor} this
 * @param {string} label The text string of the button label.
 */
async function clickBlockToolbarButton(label) {
    await this.showBlockToolbar();
    const blockToolbar = this.page.locator('role=toolbar[name="Block tools"i]');
    const button = blockToolbar.locator(`role=button[name="${label}"]`);
    await button.click();
}
exports.clickBlockToolbarButton = clickBlockToolbarButton;
//# sourceMappingURL=click-block-toolbar-button.js.map