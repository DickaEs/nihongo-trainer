# Nihongo Trainer

Vue/Vite app untuk latihan kotoba N5, hiragana, dan katakana.

## Local Development

```bash
npm install
npm run dev
```

Jika memakai PowerShell dan `npm` diblokir execution policy, gunakan:

```powershell
npm.cmd run dev
```

## Build

```bash
npm run build
```

Atau di PowerShell:

```powershell
npm.cmd run build
```

## Deploy ke Vercel

Import repository ini ke Vercel dengan setting:

```text
Framework Preset: Vite
Build Command: npm run build
Output Directory: dist
Install Command: npm install
```

Data kotoba tambahan dan skor terakhir disimpan di browser melalui `localStorage`.
