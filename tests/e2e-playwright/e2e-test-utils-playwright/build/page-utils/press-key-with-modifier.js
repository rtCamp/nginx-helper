"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.pressKeyWithModifier = exports.setClipboardData = void 0;
/**
 * External dependencies
 */
const lodash_1 = require("lodash");
/**
 * WordPress dependencies
 */
const keycodes_1 = require("@wordpress/keycodes");
let clipboardDataHolder = {
    plainText: '',
    html: '',
};
/**
 * Sets the clipboard data that can be pasted with
 * `pressKeyWithModifier( 'primary', 'v' )`.
 *
 * @param  this
 * @param  clipboardData
 * @param  clipboardData.plainText
 * @param  clipboardData.html
 */
function setClipboardData({ plainText = '', html = '' }) {
    clipboardDataHolder = {
        plainText,
        html,
    };
}
exports.setClipboardData = setClipboardData;
async function emulateClipboard(page, type) {
    clipboardDataHolder = await page.evaluate(([_type, _clipboardData]) => {
        const clipboardDataTransfer = new DataTransfer();
        if (_type === 'paste') {
            clipboardDataTransfer.setData('text/plain', _clipboardData.plainText);
            clipboardDataTransfer.setData('text/html', _clipboardData.html);
        }
        else {
            const selection = window.getSelection();
            const plainText = selection.toString();
            let html = plainText;
            if (selection.rangeCount) {
                const range = selection.getRangeAt(0);
                const fragment = range.cloneContents();
                html = Array.from(fragment.childNodes)
                    .map((node) => node.outerHTML ?? node.nodeValue)
                    .join('');
            }
            clipboardDataTransfer.setData('text/plain', plainText);
            clipboardDataTransfer.setData('text/html', html);
        }
        document.activeElement?.dispatchEvent(new ClipboardEvent(_type, {
            bubbles: true,
            cancelable: true,
            clipboardData: clipboardDataTransfer,
        }));
        return {
            plainText: clipboardDataTransfer.getData('text/plain'),
            html: clipboardDataTransfer.getData('text/html'),
        };
    }, [type, clipboardDataHolder]);
}
/**
 * Performs a key press with modifier (Shift, Control, Meta, Alt), where each modifier
 * is normalized to platform-specific modifier.
 *
 * @param  this
 * @param  modifier
 * @param  key
 */
async function pressKeyWithModifier(modifier, key) {
    if (modifier.toLowerCase() === 'primary' && key.toLowerCase() === 'c') {
        return await emulateClipboard(this.page, 'copy');
    }
    if (modifier.toLowerCase() === 'primary' && key.toLowerCase() === 'x') {
        return await emulateClipboard(this.page, 'cut');
    }
    if (modifier.toLowerCase() === 'primary' && key.toLowerCase() === 'v') {
        return await emulateClipboard(this.page, 'paste');
    }
    const isAppleOS = () => process.platform === 'darwin';
    const overWrittenModifiers = {
        ...keycodes_1.modifiers,
        shiftAlt: (_isApple) => _isApple() ? [keycodes_1.SHIFT, keycodes_1.ALT] : [keycodes_1.SHIFT, keycodes_1.CTRL],
    };
    const mappedModifiers = overWrittenModifiers[modifier](isAppleOS).map((keycode) => (keycode === keycodes_1.CTRL ? 'Control' : (0, lodash_1.capitalize)(keycode)));
    await this.page.keyboard.press(`${mappedModifiers.join('+')}+${key}`);
}
exports.pressKeyWithModifier = pressKeyWithModifier;
//# sourceMappingURL=press-key-with-modifier.js.map