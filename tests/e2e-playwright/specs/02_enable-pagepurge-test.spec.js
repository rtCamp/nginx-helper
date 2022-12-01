/**
 * WordPress dependencies
 */
const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

test.describe("Enable the page purge", () => {
  test("Should able to validate the page purge", async ({ admin, page }) => {
    await admin.visitAdminPage("/");

    await page.hover('role=link[name="Settings"i]');

    await page.click('role=link[name="Nginx Helper"i]');

    await page.waitForTimeout(2000);

    expect(page.locator(".rt_option_title")).toHaveText("Nginx Settings");


    await page.click("#enable_purge");

    await page.click("#smart_http_expire_save");

    await page.waitForTimeout(1000);
    expect(page.locator("div[class='updated'] p")).toHaveText(
      "Settings saved."
    );

    expect(page.locator("a[title='Purge Cache']")).not.toBe(null);
  });
});
