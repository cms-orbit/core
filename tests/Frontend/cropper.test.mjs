import assert from 'node:assert/strict';
import { test } from 'node:test';
import { chromium } from 'playwright';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react';

test('Cropper 2 exports the requested dimensions and cleans up when reopened', async () => {
    const server = await createServer({ configFile: false, plugins: [react()], server: { host: '127.0.0.1', port: 0 } });
    await server.listen();
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`http://127.0.0.1:${server.httpServer.address().port}/tests/Frontend/cropper.html`);
        const dataUrl = await page.evaluate(() => {
            const canvas = document.createElement('canvas');
            canvas.width = 400;
            canvas.height = 200;
            canvas.getContext('2d').fillRect(0, 0, 400, 200);
            return canvas.toDataURL();
        });
        const file = { name: 'source.png', mimeType: 'image/png', buffer: Buffer.from(dataUrl.split(',')[1], 'base64') };
        for (let attempt = 0; attempt < 2; attempt++) {
            await page.locator('input[type=file]').first().setInputFiles(file);
            await page.locator('cropper-selection').waitFor();
            await page.waitForFunction(() => document.querySelector('cropper-selection')?.width > 0);
            assert.equal(await page.locator('cropper-canvas').count(), 1);
            if (attempt === 0) {
                await page.getByRole('button', { name: 'Cancel', exact: true }).click();
                assert.equal(await page.locator('cropper-canvas').count(), 0);
            }
        }
        await page.getByRole('button', { name: 'Crop & save' }).click();
        await page.waitForFunction(() => document.querySelector('output')?.textContent.startsWith('data:image/png'));
        const dimensions = await page.locator('output').evaluate(async output => {
            const image = new Image();
            image.src = output.textContent;
            await image.decode();
            const canvas = document.createElement('canvas');
            canvas.width = image.naturalWidth;
            canvas.height = image.naturalHeight;
            canvas.getContext('2d').drawImage(image, 0, 0);
            return [image.naturalWidth, image.naturalHeight, canvas.getContext('2d').getImageData(64, 32, 1, 1).data[3]];
        });
        assert.deepEqual(dimensions, [128, 64, 255]);
        assert.equal(await page.locator('cropper-canvas').count(), 0);
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
        await server.close();
    }
});
