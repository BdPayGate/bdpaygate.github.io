# bdpaygate.github.io

Website for BDPayGate: every Bangladeshi payment gateway behind one API.

- Laravel package: https://github.com/byteminus/laravel-bdpaygate

`landing.html` is the page; `build.php` wraps it with the SEO head and writes `public/` (index, robots.txt, sitemap.xml). The `pages` workflow builds and deploys on every push to `main`.

```bash
php build.php   # preview build into public/
```
