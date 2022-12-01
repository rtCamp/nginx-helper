import { expect } from '@playwright/test';
/**
 * Internal dependencies
 */
import { Admin, Editor, PageUtils, RequestUtils } from './index';
declare const test: import("@playwright/test").TestType<import("@playwright/test").PlaywrightTestArgs & import("@playwright/test").PlaywrightTestOptions & {
    admin: Admin;
    editor: Editor;
    pageUtils: PageUtils;
    snapshotConfig: void;
}, import("@playwright/test").PlaywrightWorkerArgs & import("@playwright/test").PlaywrightWorkerOptions & {
    requestUtils: RequestUtils;
}>;
export { test, expect };
//# sourceMappingURL=test.d.ts.map