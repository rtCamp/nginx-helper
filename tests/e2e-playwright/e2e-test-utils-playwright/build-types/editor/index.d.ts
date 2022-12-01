/**
 * External dependencies
 */
import type { Browser, Page, BrowserContext, Frame } from '@playwright/test';
declare type EditorConstructorProps = {
    page: Page;
    hasIframe?: boolean;
};
export declare class Editor {
    #private;
    browser: Browser;
    page: Page;
    context: BrowserContext;
    constructor({ page, hasIframe }: EditorConstructorProps);
    get canvas(): Frame | Page;
    clickBlockOptionsMenuItem: (label: string) => Promise<void>;
    clickBlockToolbarButton: (label: string) => Promise<void>;
    getEditedPostContent: () => Promise<any>;
    insertBlock: (blockRepresentation: import("./insert-block").BlockRepresentation) => Promise<void>;
    openDocumentSettingsSidebar: () => Promise<void>;
    openPreviewPage: () => Promise<Page>;
    publishPost: () => Promise<number | null>;
    saveSiteEditorEntities: () => Promise<void>;
    selectBlocks: (startSelectorOrLocator: string | import("playwright-core").Locator, endSelectorOrLocator?: string | import("playwright-core").Locator | undefined) => Promise<void>;
    showBlockToolbar: () => Promise<void>;
    transformBlockTo: (name: string) => Promise<void>;
}
export {};
//# sourceMappingURL=index.d.ts.map