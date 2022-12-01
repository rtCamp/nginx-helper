/**
 * WordPress dependencies
 */
const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

test.describe("Enable the purge entire cache", () => {
  test("Should able to validate the purge entire cache", async ({
    admin,
    page,
  }) => {
    await admin.visitAdminPage("/");

    await page.hover('role=link[name="Settings"i]');

    await page.click('role=link[name="Nginx Helper"i]');

    expect(page.locator(".rt_option_title")).toHaveText("Nginx Settings");

    await page.click("a[class='button-primary']");

    page.on("dialog", (dialog) => dialog.accept());
    
  });
});
