"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.PageUtils = void 0;
/**
 * Internal dependencies
 */
const is_current_url_1 = require("./is-current-url");
const press_key_with_modifier_1 = require("./press-key-with-modifier");
const press_key_times_1 = require("./press-key-times");
const set_browser_viewport_1 = require("./set-browser-viewport");
class PageUtils {
    browser;
    page;
    context;
    constructor({ page }) {
        this.page = page;
        this.context = page.context();
        this.browser = this.context.browser();
    }
    isCurrentURL = is_current_url_1.isCurrentURL.bind(this);
    pressKeyTimes = press_key_times_1.pressKeyTimes.bind(this);
    pressKeyWithModifier = press_key_with_modifier_1.pressKeyWithModifier.bind(this);
    setBrowserViewport = set_browser_viewport_1.setBrowserViewport.bind(this);
    setClipboardData = press_key_with_modifier_1.setClipboardData.bind(this);
}
exports.PageUtils = PageUtils;
//# sourceMappingURL=index.js.map