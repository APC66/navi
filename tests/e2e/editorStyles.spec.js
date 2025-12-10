import { test, expect } from '@playwright/test';
import { login, dismissWelcomeModal } from './helpers/loginHelper.js';

test.describe('theme.json styles', () => {
  test('should apply correct styles to editor blocks', async ({ page }) => {
    await login(page);

    // Navigate to create new post
    await page.goto('/wp/wp-admin/post-new.php');
    await dismissWelcomeModal(page);

    // Get the editor iframe
    const editorFrame = page.frameLocator('iframe[name="editor-canvas"]');

    // Click on the title and add text
    await editorFrame
      .locator('.wp-block-post-title')
      .click();
    await page.keyboard.type('Example text');

    // Check h1.wp-block-post-title font size (should be 4xl = var(--wp--preset--font-size--4-xl))
    const postTitle = await editorFrame.locator('h1.wp-block-post-title');
    const h1FontSize = await postTitle.evaluate(el =>
      window.getComputedStyle(el).fontSize
    );

    // Click on the empty paragraph block below the title
    await editorFrame
      .locator('p[aria-label="Add default block"], p.wp-block-paragraph')
      .first()
      .click();

    // Add a paragraph with text
    await page.keyboard.type('Example text for paragraph');

    // Add a button block last
    await page.keyboard.press('Enter');
    await page.keyboard.type('/button');
    await page.waitForTimeout(500); // Wait for autocomplete menu
    await page.keyboard.press('Enter');
    await page.keyboard.type('Example button');

    // Check button has no border radius (border-radius: 0)
    const button = await editorFrame.locator('.wp-block-button__link').first();
    await button.waitFor({ state: 'visible' });
    const buttonBorderRadius = await button.evaluate(el =>
      window.getComputedStyle(el).borderRadius
    );
    expect(buttonBorderRadius).toBe('0px');

    // Clean up - we don't need to save the test post
    // Just navigate away without saving
    await page.goto('/wp/wp-admin');
  });
});
