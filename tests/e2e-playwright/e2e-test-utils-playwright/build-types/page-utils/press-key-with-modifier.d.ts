import type { WPKeycodeModifier } from '@wordpress/keycodes';
/**
 * Internal dependencies
 */
import type { PageUtils } from './index';
declare let clipboardDataHolder: {
    plainText: string;
    html: string;
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
export declare function setClipboardData(this: PageUtils, { plainText, html }: typeof clipboardDataHolder): void;
/**
 * Performs a key press with modifier (Shift, Control, Meta, Alt), where each modifier
 * is normalized to platform-specific modifier.
 *
 * @param  this
 * @param  modifier
 * @param  key
 */
export declare function pressKeyWithModifier(this: PageUtils, modifier: WPKeycodeModifier, key: string): Promise<void>;
export {};
//# sourceMappingURL=press-key-with-modifier.d.ts.map