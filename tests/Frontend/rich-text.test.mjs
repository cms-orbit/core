import assert from 'node:assert/strict';
import { test } from 'node:test';
import { chromium } from 'playwright';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react';

test('Rich text loads saved HTML and emits edited HTML and Markdown', async () => {
    const server = await createServer({ configFile: false, plugins: [react()], server: { host: '127.0.0.1', port: 0 } });
    await server.listen();
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage({ permissions: ['clipboard-read', 'clipboard-write'] });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`http://127.0.0.1:${server.httpServer.address().port}/tests/Frontend/rich-text.html`);
        const editor = page.locator('[contenteditable=true]');
        await editor.waitFor();
        assert.equal(await editor.textContent(), 'Saved content');
        assert.equal(await editor.locator('strong').textContent(), 'content');

        await editor.press('End');
        await editor.pressSequentially(' updated');
        await page.waitForFunction(() => document.querySelector('output')?.textContent.includes('updated'));
        const html = await page.locator('output').textContent();
        assert.equal(html, '<p>Saved <strong>content updated</strong></p>');
        await page.getByRole('button', { name: 'Copy Markdown', exact: true }).click();
        const markdown = await page.evaluate(() => navigator.clipboard.readText());
        assert.equal(markdown.trim(), 'Saved **content updated**');
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
        await server.close();
    }
});
