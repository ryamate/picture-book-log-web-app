import { test, expect } from '@playwright/test';

test.describe('画面表示確認', () => {
  test('ログインページが正しく表示される', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('[data-slot="card-title"]', { hasText: 'ログイン' })).toBeVisible();
    await expect(page.getByLabel('メールアドレス')).toBeVisible();
    await expect(page.getByLabel('パスワード')).toBeVisible();
    await expect(page.getByRole('button', { name: 'ログイン' })).toBeVisible();
    await expect(page.getByRole('link', { name: '新規登録' })).toBeVisible();
  });

  test('新規登録ページが正しく表示される', async ({ page }) => {
    await page.goto('/register');
    await expect(page.locator('[data-slot="card-title"]', { hasText: '新規登録' })).toBeVisible();
    await expect(page.getByLabel('名前')).toBeVisible();
    await expect(page.getByLabel('メールアドレス')).toBeVisible();
    await expect(page.getByLabel('パスワード', { exact: true })).toBeVisible();
    await expect(page.getByLabel('パスワード（確認）')).toBeVisible();
    await expect(page.getByRole('button', { name: '登録' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'ログイン' })).toBeVisible();
  });

  test('未認証時にログインページへリダイレクトされる', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL(/\/login/);
  });

  test('ログインページにレイアウト崩れがない', async ({ page }) => {
    await page.goto('/login');
    await page.waitForLoadState('networkidle');

    // Card が画面内に収まっている
    const card = page.locator('[class*="card"]').first();
    await expect(card).toBeVisible();
    const box = await card.boundingBox();
    expect(box).not.toBeNull();
    if (box) {
      const viewport = page.viewportSize();
      expect(box.x).toBeGreaterThanOrEqual(0);
      expect(box.x + box.width).toBeLessThanOrEqual(viewport!.width);
    }
  });

  test('新規登録ページにレイアウト崩れがない', async ({ page }) => {
    await page.goto('/register');
    await page.waitForLoadState('networkidle');

    const card = page.locator('[class*="card"]').first();
    await expect(card).toBeVisible();
    const box = await card.boundingBox();
    expect(box).not.toBeNull();
    if (box) {
      const viewport = page.viewportSize();
      expect(box.x).toBeGreaterThanOrEqual(0);
      expect(box.x + box.width).toBeLessThanOrEqual(viewport!.width);
    }
  });

  test('ログインフォームのバリデーションが動作する', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('button', { name: 'ログイン' }).click();
    await expect(page.getByText('メールアドレスを入力してください')).toBeVisible();
    await expect(page.getByText('パスワードを入力してください')).toBeVisible();
  });

  test('新規登録フォームのバリデーションが動作する', async ({ page }) => {
    await page.goto('/register');
    await page.getByRole('button', { name: '登録' }).click();
    await expect(page.getByText('名前を入力してください')).toBeVisible();
    await expect(page.getByText('メールアドレスを入力してください')).toBeVisible();
    await expect(page.getByText('パスワードを入力してください')).toBeVisible();
    await expect(page.getByText('パスワード（確認）を入力してください')).toBeVisible();
  });
});
