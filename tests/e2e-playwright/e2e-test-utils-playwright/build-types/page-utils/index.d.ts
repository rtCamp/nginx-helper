/**
 * External dependencies
 */
import type { Browser, Page, BrowserContext } from '@playwright/test';
import { pressKeyTimes } from './press-key-times';
import { setBrowserViewport } from './set-browser-viewport';
declare type PageUtilConstructorParams = {
    page: Page;
};
declare class PageUtils {
    browser: Browser;
    page: Page;
    context: BrowserContext;
    constructor({ page }: PageUtilConstructorParams);
    isCurrentURL: (path: string) => boolean;
    pressKeyTimes: typeof pressKeyTimes;
    pressKeyWithModifier: (modifier: import("@wordpress/keycodes/build-types").WPKeycodeModifier, key: string) => Promise<void>;
    setBrowserViewport: typeof setBrowserViewport;
    setClipboardData: (args_0: {
        plainText: string;
        html: string;
    }) => void;
}
export { PageUtils };
//# sourceMappingURL=index.d.ts.map