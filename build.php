<?php

/*
 * Builds the public landing page from landing.html.
 *
 *     SITE_URL=https://bdpaygate.example php build.php
 *
 * Writes public/: index.html plus the AI layer (llms.txt, llms-full.txt, Markdown docs), robots.txt, sitemap.xml.
 * to any static host (GitHub Pages, Vercel, Netlify, Cloudflare Pages).
 */

$siteUrl = rtrim(getenv('SITE_URL') ?: 'https://bdpaygate.github.io', '/').'/';
$repo = 'https://github.com/byteminus/laravel-bdpaygate';
$today = date('Y-m-d');

$title = 'BDPayGate: Laravel Payment Gateway for bKash, Nagad, SSLCommerz';
$description = 'Laravel package for every Bangladeshi payment gateway: bKash, Nagad, SSLCommerz, Upay, ShurjoPay, aamarPay and more. Each payment verified server-side.';
$keywords = 'laravel payment gateway bangladesh, laravel bkash, laravel nagad, laravel sslcommerz, bkash laravel package, nagad laravel, sslcommerz laravel, shurjopay laravel, aamarpay laravel, upay laravel, portpos laravel, bangladesh payment gateway php';

$source = file_get_contents(__DIR__.'/landing.html');

// The landing file is written for the artifact viewer: drop its title and viewer-only meta.
$body = preg_replace([
    '#<title>.*?</title>\s*#s',
    '#<meta name="claude:[^"]+"[^>]*>\s*#',
], '', $source);

// Lift the font links and the stylesheet into <head>; the rest is the body.
preg_match_all('#<link[^>]+>\s*#', $body, $links);
preg_match('#<style>.*?</style>#s', $body, $style);
$body = trim(str_replace(array_merge($links[0], [$style[0] ?? '']), '', $body));

// FAQ structured data, read from the page itself so the two never drift apart.
preg_match_all('#<details[^>]*><summary>(.*?)</summary><p>(.*?)</p></details>#s', $body, $faqs, PREG_SET_ORDER);
$faqEntities = array_map(fn ($m) => [
    '@type' => 'Question',
    'name' => html_entity_decode(strip_tags($m[1])),
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => html_entity_decode(strip_tags($m[2]))],
], $faqs);

$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'SoftwareSourceCode',
            '@id' => $siteUrl.'#software',
            'name' => 'BDPayGate',
            'alternateName' => 'laravel-bdpaygate',
            'description' => $description,
            'url' => $siteUrl,
            'codeRepository' => $repo,
            'programmingLanguage' => 'PHP',
            'runtimePlatform' => 'Laravel 11, 12, 13',
            'license' => 'https://opensource.org/licenses/MIT',
            'keywords' => $keywords,
            'author' => ['@id' => $siteUrl.'#org'],
        ],
        [
            '@type' => 'SoftwareSourceCode',
            '@id' => $siteUrl.'node/#software',
            'name' => 'BDPayGate for Node.js',
            'alternateName' => 'bdpaygate',
            'description' => 'npm package for every Bangladeshi payment gateway (bKash, Nagad, SSLCommerz, Upay, ShurjoPay, aamarPay and more) with adapters for Express, Next.js, NestJS, Fastify, Hono and more.',
            'url' => $siteUrl.'node/',
            'codeRepository' => 'https://github.com/byteminus/bdpaygate-js',
            'programmingLanguage' => 'TypeScript',
            'runtimePlatform' => 'Node.js 20.19+, Bun, Deno, Cloudflare Workers',
            'license' => 'https://opensource.org/licenses/MIT',
            'author' => ['@id' => $siteUrl.'#org'],
        ],
        [
            '@type' => 'Organization',
            '@id' => $siteUrl.'#org',
            'name' => 'ByteMinus',
            'url' => 'https://github.com/byteminus',
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => $faqEntities,
        ],
    ],
];

$e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES);

$head = <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{$e($title)}</title>
<meta name="description" content="{$e($description)}">
<meta name="keywords" content="{$e($keywords)}">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="google-site-verification" content="Ji49dQQzAI964-8NWbmh_egmo0Lp2u1vE-drxqyKoYY">
<link rel="canonical" href="{$e($siteUrl)}">
<meta name="theme-color" content="#00694d">
<meta name="color-scheme" content="light dark">

<meta property="og:type" content="website">
<meta property="og:site_name" content="BDPayGate">
<meta property="og:title" content="{$e($title)}">
<meta property="og:description" content="{$e($description)}">
<meta property="og:url" content="{$e($siteUrl)}">
<meta property="og:locale" content="en_US">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{$e($title)}">
<meta name="twitter:description" content="{$e($description)}">

<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%2300694d'/%3E%3Ctext x='32' y='46' font-size='40' text-anchor='middle' fill='%23fff' font-family='sans-serif' font-weight='700'%3E%E0%A7%B3%3C/text%3E%3C/svg%3E">
HTML;

$out = $head."\n".implode('', $links[0])
    .'<script type="application/ld+json">'.json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."</script>\n"
    ."<style>*,*::before,*::after{box-sizing:border-box}body{margin:0}img{max-width:100%}[hidden]{display:none!important}</style>\n"
    .($style[0] ?? '')."\n</head>\n<body>\n".$body."\n</body>\n</html>\n";

// ---------------------------------------------------------------------------
// AI-readable layer: llms.txt (https://llmstxt.org), Markdown copies of every
// page and doc, llms-full.txt with everything in one file, and a robots.txt
// that names the AI crawlers explicitly. Docs come from the package repo so
// the site and the package never disagree.
// ---------------------------------------------------------------------------

$packageDir = rtrim(getenv('PACKAGE_DIR') ?: __DIR__.'/../laravel-taka', '/');

if (! is_file($packageDir.'/README.md')) {
    fwrite(STDERR, "Package docs not found in {$packageDir}. Set PACKAGE_DIR.\n");
    exit(1);
}

$md = fn (string $path) => trim((string) file_get_contents($packageDir.'/'.$path));

// The npm package (bdpaygate) lives in its own repo; its docs become /node/.
$nodeDir = rtrim(getenv('NODE_PACKAGE_DIR') ?: __DIR__.'/../bdpaygate-js', '/');
$nodeRepo = 'https://github.com/byteminus/bdpaygate-js';

if (! is_file($nodeDir.'/README.md')) {
    fwrite(STDERR, "Node package docs not found in {$nodeDir}. Set NODE_PACKAGE_DIR.\n");
    exit(1);
}

$nodeMd = fn (string $path) => trim((string) file_get_contents($nodeDir.'/'.$path));

// Repo-relative links become absolute site links.
$absolute = fn (string $text) => preg_replace(
    ['#\]\(docs/gateways/([a-z0-9-]+)\.md\)#', '#\]\(CONTRIBUTING\.md\)#'],
    ['](' . $siteUrl . 'docs/$1.md)', '](' . $siteUrl . 'docs/contributing.md)'],
    $text,
);

$readme = $absolute($md('README.md'));
$skill = preg_replace('#^---.*?---\s*#s', '', $md('resources/boost/skills/bdpaygate-integration/SKILL.md'));
$contributing = $absolute($md('CONTRIBUTING.md'));
$changelog = $md('CHANGELOG.md');

$gatewayOrder = ['bkash', 'nagad', 'sslcommerz', 'upay', 'shurjopay', 'aamarpay', 'portpos', 'paystation', 'uddoktapay', 'moneybag', 'eps', 'dgepay'];
$gateways = [];

foreach ($gatewayOrder as $slug) {
    $file = "docs/gateways/{$slug}.md";

    if (is_file($packageDir.'/'.$file)) {
        $text = $absolute($md($file));
        preg_match('/^# (.+)$/m', $text, $h1);
        preg_match('/\*\*Status:\*\* ([A-Za-z-]+)/', $text, $status);
        $gateways[$slug] = ['title' => trim($h1[1] ?? $slug), 'status' => trim($status[1] ?? ''), 'text' => $text];
    }
}

$nodeAbsolute = fn (string $text) => preg_replace(
    ['#\]\(docs/gateways/([a-z0-9-]+)\.md\)#', '#\]\(CONTRIBUTING\.md\)#', '#\]\((SECURITY\.md|LICENSE)\)#'],
    ['](' . $siteUrl . 'node/docs/$1.md)', '](' . $siteUrl . 'node/docs/contributing.md)', ']('.$nodeRepo.'/blob/main/$1)'],
    $text,
);

// Badges belong on GitHub and npm, not on the docs site.
$nodeReadme = preg_replace('/^\[!\[.*\n/m', '', $nodeAbsolute($nodeMd('README.md')));
$nodeContributing = $nodeAbsolute($nodeMd('CONTRIBUTING.md'));
$nodeChangelog = $nodeMd('CHANGELOG.md');
$nodeGateways = [];

foreach ($gatewayOrder as $slug) {
    $file = "docs/gateways/{$slug}.md";

    if (is_file($nodeDir.'/'.$file)) {
        $text = $nodeAbsolute($nodeMd($file));
        preg_match('/^# (.+)$/m', $text, $h1);
        preg_match('/\*\*Status:\*\* ([A-Za-z-]+)/', $text, $status);
        $nodeGateways[$slug] = ['title' => trim($h1[1] ?? $slug), 'status' => trim($status[1] ?? ''), 'text' => $text];
    }
}

$docs = [
    'node/index.md' => $nodeReadme,
    'node/docs/contributing.md' => $nodeContributing,
    'node/docs/changelog.md' => $nodeChangelog,
    'index.md' => $readme,
    'docs/integration-guide.md' => $skill,
    'docs/contributing.md' => $contributing,
    'docs/changelog.md' => $changelog,
];

foreach ($gateways as $slug => $g) {
    $docs["docs/{$slug}.md"] = $g['text'];
}

foreach ($nodeGateways as $slug => $g) {
    $docs["node/docs/{$slug}.md"] = $g['text'];
}

$nodeGatewayList = implode("\n", array_map(
    fn ($slug, $g) => "- [{$g['title']} for Node.js]({$siteUrl}node/docs/{$slug}.md): config, env vars, verification and quirks ({$g['status']})",
    array_keys($nodeGateways),
    $nodeGateways,
));

$gatewayList = implode("\n", array_map(
    fn ($slug, $g) => "- [{$g['title']}]({$siteUrl}docs/{$slug}.md): setup, .env keys, verification and quirks ({$g['status']})",
    array_keys($gateways),
    $gateways,
));

$llms = <<<TXT
# BDPayGate

> BDPayGate is open-source (MIT) and comes in two packages with the same gateways, safety rules and database table: the Laravel package `byteminus/laravel-bdpaygate` and the npm package `bdpaygate` for Node.js, Bun, Deno and edge runtimes. The Laravel package, `byteminus/laravel-bdpaygate`, puts every Bangladeshi payment gateway behind one API: bKash, Nagad, SSLCommerz, Upay, ShurjoPay, aamarPay, PortPos (formerly PortWallet), PayStation, UddoktaPay, Moneybag, EPS and DGePay. Rocket, OK Wallet and CellFin are accepted through SSLCommerz, ShurjoPay or aamarPay. It supports Laravel 11 to 13 on PHP 8.2 to 8.5.

Install with `composer require byteminus/laravel-bdpaygate`. Every gateway callback is confirmed with the gateway's own API before a payment changes state; orders should be fulfilled in a `PaymentCompleted` listener, never on the redirect page. Status changes are atomic, timeouts leave payments pending for `php artisan bdpaygate:sync`, and `BdPayGate::fake()` gives network-free tests. Seven gateways (bKash, SSLCommerz, ShurjoPay, aamarPay, PayStation, UddoktaPay, Moneybag) have been run end to end on the gateways' real sandboxes.

## Docs

- [Overview and quick start]({$siteUrl}index.md): install, configure, take a payment, events, refunds, testing
- [Integration guide]({$siteUrl}docs/integration-guide.md): .env keys for every gateway, refund and sync support, lifecycle, troubleshooting
- [Full documentation in one file]({$siteUrl}llms-full.txt): everything below concatenated

## Gateways

{$gatewayList}

## Node.js (npm: bdpaygate)

Install with `npm install bdpaygate`. TypeScript, zero runtime dependencies, ESM and CommonJS. Adapters for Express, Next.js, NestJS, Fastify, Hono, Koa, Nuxt, SvelteKit, React Router / Remix, Astro and AdonisJS; runs on Node.js 20.19+, Bun, Deno and Cloudflare Workers. Fulfil orders in a `pay.on('payment.completed')` listener; mount the callback handler before CSRF/session middleware; run `pay.syncPending()` on a schedule; use `pay.fake()` in tests. The same seven gateways were paid end to end in their sandboxes through the npm package.

- [Node.js overview and quick start]({$siteUrl}node/index.md): install, configure, framework adapters, events, refunds, storage, testing
- [Full Node.js documentation in one file]({$siteUrl}node/llms-full.txt)
{$nodeGatewayList}
- [Node.js contributing guide]({$siteUrl}node/docs/contributing.md)
- [npm package](https://www.npmjs.com/package/bdpaygate) · [GitHub]({$nodeRepo})

## Source

- [GitHub repository]({$repo}): code, issues, releases
- [Packagist](https://packagist.org/packages/byteminus/laravel-bdpaygate): `byteminus/laravel-bdpaygate`

## Optional

- [Contributing and writing a gateway driver]({$siteUrl}docs/contributing.md)
- [Changelog]({$siteUrl}docs/changelog.md)

TXT;

$nodeFull = "# BDPayGate for Node.js: full documentation\n\nSource: {$siteUrl}node/ · Package: bdpaygate (npm) · Repository: {$nodeRepo}\n\n"
    .implode("\n\n---\n\n", array_merge([$nodeReadme], array_column($nodeGateways, 'text'), [$nodeContributing]))
    ."\n";

$full = "# BDPayGate: full documentation\n\nSource: {$siteUrl} · Package: byteminus/laravel-bdpaygate · Repository: {$repo}\n\n"
    .implode("\n\n---\n\n", array_merge([$readme, $skill], array_column($gateways, 'text'), [$contributing]))
    ."\n";

// Explicit welcome for search and AI crawlers. Everything is allowed for
// everyone anyway; naming them avoids any doubt in their own policies.
$aiAgents = [
    'GPTBot', 'OAI-SearchBot', 'ChatGPT-User',
    'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'anthropic-ai',
    'PerplexityBot', 'Perplexity-User',
    'Google-Extended', 'GoogleOther', 'Applebot-Extended', 'Applebot',
    'Bingbot', 'DuckAssistBot', 'Amazonbot', 'meta-externalagent', 'cohere-ai', 'MistralAI-User', 'CCBot',
];
$robots = "User-agent: *\nAllow: /\n\n"
    .implode('', array_map(fn ($a) => "User-agent: {$a}\nAllow: /\n\n", $aiAgents))
    ."Sitemap: {$siteUrl}sitemap.xml\n";

// ---------------------------------------------------------------------------
// HTML docs for search engines: /docs/ and one page per doc at /docs/{slug}/,
// rendered from the same Markdown the AI layer serves.
// ---------------------------------------------------------------------------

require __DIR__.'/vendor/autoload.php';

$converter = new League\CommonMark\GithubFlavoredMarkdownConverter([
    'html_input' => 'escape',
    'allow_unsafe_links' => false,
]);

$sourceBase = $repo.'/blob/main/';
$docPages = [
    'getting-started' => ['nav' => 'Getting started', 'text' => $readme, 'md' => 'index.md', 'source' => 'README.md',
        'seo' => 'Getting Started: Bangladesh Payment Gateways in Laravel', 'group' => 'Guides',
        'description' => 'Install BDPayGate, add your gateway keys and take your first bKash, Nagad or SSLCommerz payment in Laravel. Events, refunds and testing included.'],
    'integration-guide' => ['nav' => 'Integration guide', 'text' => $skill, 'md' => 'docs/integration-guide.md',
        'source' => 'resources/boost/skills/bdpaygate-integration/SKILL.md',
        'seo' => 'Laravel Payment Gateway Integration Guide for Bangladesh', 'group' => 'Guides',
        'description' => '.env keys for all 12 Bangladeshi gateways, which support refunds and status sync, the payment lifecycle, and fixes for common problems.'],
];

foreach ($gateways as $slug => $g) {
    $docPages[$slug] = ['nav' => $g['title'], 'text' => $g['text'], 'md' => "docs/{$slug}.md", 'source' => "docs/gateways/{$slug}.md",
        'seo' => preg_replace('/\s*\(.*\)$/', '', $g['title']).' Laravel Integration', 'group' => 'Gateways', 'status' => $g['status'],
        'description' => 'Accept '.preg_replace('/\s*\(.*\)$/', '', $g['title']).' payments in Laravel with BDPayGate: setup, .env keys, server-side verification, refunds and known quirks.'];
}

$docPages['contributing'] = ['nav' => 'Contributing', 'text' => $contributing, 'md' => 'docs/contributing.md', 'source' => 'CONTRIBUTING.md',
    'seo' => 'Write a Payment Gateway Driver', 'group' => 'Project'];
$docPages['changelog'] = ['nav' => 'Changelog', 'text' => $changelog, 'md' => 'docs/changelog.md', 'source' => 'CHANGELOG.md',
    'seo' => 'Changelog', 'group' => 'Project'];

// Absolute Markdown links become links between the HTML pages.
$htmlLinks = fn (string $html) => preg_replace(
    ['#'.preg_quote($siteUrl, '#').'docs/([a-z0-9-]+)\.md#', '#'.preg_quote($siteUrl, '#').'index\.md#'],
    ['/docs/$1/', '/docs/getting-started/'],
    $html,
);

// The first real paragraph, as plain text, for meta descriptions.
$summary = function (string $markdown) use ($description): string {
    foreach (preg_split("/\n{2,}/", $markdown) as $block) {
        $block = trim($block);

        if ($block === '' || preg_match('/^(#|\||-|\*\*Status|```|>|\d+\.)/', $block)) {
            continue;
        }

        $text = trim(preg_replace(['/\[([^\]]+)\]\([^)]+\)/', '/[*_`]/', '/\s+/'], ['$1', '', ' '], $block));

        return mb_strlen($text) > 155 ? rtrim(mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, 152), ' '))).'…' : $text;
    }

    return $description;
};

$docsCss = <<<'CSS'
<style>
.docs-main .wrap { display: grid; grid-template-columns: 230px minmax(0, 1fr); gap: clamp(24px, 4vw, 56px); padding-block: 36px 88px; }
@media (max-width: 860px) { .docs-main .wrap { grid-template-columns: minmax(0, 1fr); } }
.docnav { font-size: 0.92rem; position: sticky; top: calc(env(safe-area-inset-top, 0px) + 76px); align-self: start; max-height: calc(100vh - 100px); overflow-y: auto; }
@media (max-width: 860px) { .docnav { position: static; max-height: none; border-bottom: 1px solid var(--line); padding-bottom: 16px; } }
.docnav p { margin: 18px 0 6px; font: 600 0.72rem var(--font-mono); letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); }
.docnav p:first-child { margin-top: 0; }
.docnav ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 2px; }
@media (max-width: 860px) { .docnav ul { display: flex; flex-wrap: wrap; gap: 4px 14px; } }
.docnav a { display: block; color: var(--ink); text-decoration: none; padding: 4px 10px; border-radius: 6px; }
.docnav a:hover { background: var(--green-soft); }
.docnav a[aria-current="page"] { background: var(--green-soft); color: var(--green); font-weight: 600; }
.crumbs { font-size: var(--step--1); color: var(--muted); margin-bottom: 14px; }
.crumbs a { color: var(--muted); }
.prose { min-width: 0; max-width: 76ch; }
.prose h1 { font-size: var(--step-3); font-weight: 800; margin-bottom: 18px; }
.prose h2 { font-size: var(--step-2); font-weight: 700; margin: 2.2em 0 0.6em; }
.prose h3 { font-size: var(--step-1); font-weight: 700; margin: 1.8em 0 0.5em; }
.prose p, .prose ul, .prose ol { margin: 0 0 1em; }
.prose li { margin: 0.25em 0; }
.prose code { font-size: 0.88em; background: var(--green-soft); padding: 1px 6px; border-radius: 5px; overflow-wrap: anywhere; }
.prose pre { background: var(--code-bg); color: var(--code-ink); padding: 18px 20px; border-radius: var(--radius); overflow-x: auto; margin: 0 0 1.4em; font-size: 0.86rem; line-height: 1.6; }
.prose pre code { background: none; padding: 0; color: inherit; overflow-wrap: normal; }
.prose table { display: block; overflow-x: auto; min-width: 0; margin: 0 0 1.4em; border: 1px solid var(--line); border-radius: var(--radius); font-size: 0.92rem; }
.prose th, .prose td { padding: 9px 14px; }
.prose blockquote { margin: 0 0 1em; padding: 4px 16px; border-left: 3px solid var(--green); color: var(--muted); }
.prose hr { border: 0; border-top: 1px solid var(--line); margin: 2em 0; }
.docfoot { margin-top: 48px; padding-top: 18px; border-top: 1px solid var(--line); display: flex; flex-wrap: wrap; gap: 8px 20px; font-size: var(--step--1); color: var(--muted); }
.cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin: 0 0 2em; }
.cards a { display: block; border: 1px solid var(--line); border-radius: var(--radius); padding: 14px 16px; text-decoration: none; color: var(--ink); background: var(--panel); }
.cards a:hover { border-color: var(--green); }
.cards b { display: block; font-family: var(--font-display); font-size: 1.1rem; }
.cards span { color: var(--muted); font-size: 0.86rem; }
</style>
CSS;

$docHeader = fn (string $brand, string $github) => <<<HTML
<header class="top">
  <div class="wrap">
    <a class="brand" href="/"><b>BDPay<span class="taka">৳</span>Gate</b><small>{$brand}</small></a>
    <nav aria-label="Site">
      <a href="/">Home</a>
      <a href="/docs/">Laravel docs</a>
      <a href="/node/">Node.js</a>
      <a href="/#gateways">Gateways</a>
      <a href="{$github}">GitHub</a>
    </nav>
  </div>
</header>
HTML;

$docFooter = '<footer class="foot"><div class="wrap"><span>BDPayGate · Bangladesh payment gateways for Laravel and Node.js · MIT license</span>'
    .'<span>For AI assistants: <a href="/llms.txt">llms.txt</a> · <a href="/llms-full.txt">Laravel docs</a> · <a href="/node/llms-full.txt">Node.js docs</a> · By <a href="https://github.com/byteminus">ByteMinus</a></span></div></footer>';

$statusLabel = fn (?string $s) => $s ? '<span class="pill '.match (strtolower($s)) { 'stable' => 'verified', 'experimental' => 'exp', default => 'beta' }.'">'.ucfirst($s).'</span>' : '';

$pageHead = function (string $title, string $desc, string $url, string $mdUrl, array $ld) use ($e, $links, $style, $docsCss, $siteUrl): string {
    return "<!doctype html>\n<html lang=\"en\">\n<head>\n<meta charset=\"utf-8\">\n"
        ."<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, viewport-fit=cover\">\n"
        ."<title>{$e($title)}</title>\n<meta name=\"description\" content=\"{$e($desc)}\">\n"
        ."<meta name=\"robots\" content=\"index, follow, max-image-preview:large\">\n"
        ."<meta name=\"google-site-verification\" content=\"Ji49dQQzAI964-8NWbmh_egmo0Lp2u1vE-drxqyKoYY\">\n"
        ."<link rel=\"canonical\" href=\"{$e($url)}\">\n"
        ."<link rel=\"alternate\" type=\"text/markdown\" href=\"{$e($mdUrl)}\">\n"
        ."<link rel=\"describedby\" href=\"{$siteUrl}llms.txt\" type=\"text/plain\">\n"
        ."<meta name=\"theme-color\" content=\"#00694d\">\n<meta name=\"color-scheme\" content=\"light dark\">\n"
        ."<meta property=\"og:type\" content=\"article\">\n<meta property=\"og:site_name\" content=\"BDPayGate\">\n"
        ."<meta property=\"og:title\" content=\"{$e($title)}\">\n<meta property=\"og:description\" content=\"{$e($desc)}\">\n"
        ."<meta property=\"og:url\" content=\"{$e($url)}\">\n<meta name=\"twitter:card\" content=\"summary\">\n"
        ."<link rel=\"icon\" href=\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%2300694d'/%3E%3Ctext x='32' y='46' font-size='40' text-anchor='middle' fill='%23fff' font-family='sans-serif' font-weight='700'%3E%E0%A7%B3%3C/text%3E%3C/svg%3E\">\n"
        .implode('', $links[0])
        .'<script type="application/ld+json">'.json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."</script>\n"
        ."<style>*,*::before,*::after{box-sizing:border-box}body{margin:0}img{max-width:100%}[hidden]{display:none!important}</style>\n"
        .($style[0] ?? '')."\n".$docsCss."\n</head>\n<body>\n";
};

$breadcrumbs = fn (array $items) => [
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_map(fn ($i, $item) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1]], array_keys($items), $items),
];

$htmlDocUrls = [];

/*
 * One docs section: a home page at /{base}/ and a page per doc at /{base}/{slug}/.
 * $home is either a list of card groups (Laravel) or a page to render as the home (Node.js).
 */
$renderSection = function (string $base, string $label, string $brand, string $repoUrl, array $pages, array $home, callable $links, string $softwareId)
    use ($siteUrl, $today, $converter, $summary, $statusLabel, $pageHead, $breadcrumbs, $docHeader, $docFooter, &$htmlDocUrls): void {
    $sourceBase = $repoUrl.'/blob/main/';
    $header = $docHeader($brand, $repoUrl);

    $nav = function (string $current) use ($pages, $base, $label, $home): string {
        $html = '<nav class="docnav" aria-label="'.$label.' documentation">';
        $group = null;

        if (isset($home['page'])) {
            $html .= '<p>'.$label.'</p><ul><li><a href="/'.$base.'/"'.($current === '' ? ' aria-current="page"' : '').'>Overview</a></li></ul>';
        }

        foreach ($pages as $slug => $page) {
            if ($page['group'] !== $group) {
                $html .= ($group === null ? '' : '</ul>').'<p>'.$page['group'].'</p><ul>';
                $group = $page['group'];
            }

            $here = $slug === $current ? ' aria-current="page"' : '';
            $html .= "<li><a href=\"/{$base}/{$slug}/\"{$here}>".htmlspecialchars($page['nav']).'</a></li>';
        }

        return $html.'</ul></nav>';
    };

    $write = function (string $slug, string $title, string $desc, string $mdPath, string $source, string $crumb, string $bodyHtml, ?string $status, string $type) use ($siteUrl, $base, $label, $today, $pageHead, $breadcrumbs, $header, $nav, $docFooter, $statusLabel, $sourceBase, $softwareId, &$htmlDocUrls): void {
        $path = $base.'/'.($slug === '' ? '' : $slug.'/');
        $url = $siteUrl.$path;
        $trail = [['Home', $siteUrl], [$label, $siteUrl.$base.'/']];

        if ($slug !== '') {
            $trail[] = [$crumb, $url];
        }

        $ld = ['@context' => 'https://schema.org', '@graph' => [
            ['@type' => $type, 'headline' => $title, 'name' => $title, 'description' => $desc, 'url' => $url, 'inLanguage' => 'en',
                'dateModified' => $today, 'about' => ['@id' => $siteUrl.$softwareId], 'publisher' => ['@type' => 'Organization', 'name' => 'ByteMinus', 'url' => 'https://github.com/byteminus']],
            $breadcrumbs($trail),
        ]];

        $crumbs = '<p class="crumbs"><a href="/">Home</a> / '.($slug === '' ? $label : '<a href="/'.$base.'/">'.$label.'</a> / '.htmlspecialchars($crumb))
            .($status ? ' '.$statusLabel($status) : '').'</p>';

        $html = $pageHead($title.' | BDPayGate', $desc, $url, $siteUrl.$mdPath, $ld)
            .$header."\n<main class=\"docs-main\"><div class=\"wrap\">".$nav($slug)
            .'<article class="prose">'.$crumbs.$bodyHtml
            .($source !== '' ? '<p class="docfoot"><a href="'.$sourceBase.$source.'">Edit this page on GitHub</a><a href="/'.$mdPath.'">View as Markdown</a></p>' : '')
            ."</article></div></main>\n".$docFooter."\n</body>\n</html>\n";

        @mkdir(__DIR__.'/public/'.$path, 0755, true);
        file_put_contents(__DIR__.'/public/'.$path.'index.html', $html);
        $htmlDocUrls[] = $path;
    };

    foreach ($pages as $slug => $page) {
        $write($slug, $page['seo'], $page['description'] ?? $summary($page['text']), $page['md'], $page['source'], $page['nav'],
            $links($converter->convert($page['text'])->getContent()), $page['status'] ?? null, 'TechArticle');
    }

    if (isset($home['page'])) {
        $page = $home['page'];
        $write('', $page['seo'], $page['description'], $page['md'], $page['source'], $label,
            $links($converter->convert($page['text'])->getContent()), null, 'TechArticle');

        return;
    }

    $cards = fn (array $slugs) => '<div class="cards">'.implode('', array_map(
        fn ($s) => '<a href="/'.$base.'/'.$s.'/"><b>'.htmlspecialchars($pages[$s]['nav']).'</b><span>'
            .(isset($pages[$s]['status']) ? $statusLabel($pages[$s]['status']) : htmlspecialchars($summary($pages[$s]['text']))).'</span></a>',
        $slugs,
    )).'</div>';

    $bodyHtml = '<h1>'.htmlspecialchars($home['h1']).'</h1><p>'.$home['intro'].'</p>';

    foreach ($home['groups'] as $heading => $slugs) {
        $bodyHtml .= '<h2>'.$heading.'</h2>'.$cards($slugs);
    }

    $write('', $home['title'], $home['description'], 'llms.txt', '', $label, $bodyHtml, null, 'CollectionPage');
};

$renderSection('docs', 'Docs', 'for Laravel', $repo, $docPages, [
    'title' => 'Laravel Docs: Bangladesh Payment Gateways',
    'h1' => 'BDPayGate documentation',
    'description' => 'Documentation for BDPayGate: set up bKash, Nagad, SSLCommerz, Upay, ShurjoPay, aamarPay and more in Laravel, with server-side verification and refunds.',
    'intro' => 'Install the package, then pick your gateway. Every gateway uses the same <code>charge</code>, callback, <code>sync</code> and <code>refund</code> API, and every payment is confirmed with the gateway before your order is marked paid. Using Node.js? See <a href="/node/">BDPayGate for Node.js</a>.',
    'groups' => ['Guides' => ['getting-started', 'integration-guide'], 'Gateways' => array_keys($gateways), 'Project' => ['contributing', 'changelog']],
], $htmlLinks, '#software');

// Node.js section, from the npm package's repo.
$nodePages = [];

foreach ($nodeGateways as $slug => $g) {
    $name = preg_replace('/\s*\(.*\)$/', '', $g['title']);
    $nodePages[$slug] = ['nav' => $name, 'text' => $g['text'], 'md' => "node/docs/{$slug}.md", 'source' => "docs/gateways/{$slug}.md",
        'seo' => $name.' Node.js Integration', 'group' => 'Gateways', 'status' => $g['status'],
        'description' => 'Accept '.$name.' payments in Node.js, Next.js, Express or NestJS with BDPayGate: setup, env vars, server-side verification, refunds and quirks.'];
}

$nodePages['contributing'] = ['nav' => 'Contributing', 'text' => $nodeContributing, 'md' => 'node/docs/contributing.md', 'source' => 'CONTRIBUTING.md',
    'seo' => 'Contribute to BDPayGate for Node.js', 'group' => 'Project'];
$nodePages['changelog'] = ['nav' => 'Changelog', 'text' => $nodeChangelog, 'md' => 'node/docs/changelog.md', 'source' => 'CHANGELOG.md',
    'seo' => 'BDPayGate for Node.js Changelog', 'group' => 'Project'];

$nodeLinks = fn (string $html) => preg_replace(
    ['#'.preg_quote($siteUrl, '#').'node/docs/([a-z0-9-]+)\.md#', '#'.preg_quote($siteUrl, '#').'node/index\.md#', '#href="https://bdpaygate\.github\.io/node/"#'],
    ['/node/$1/', '/node/', 'href="/node/"'],
    $html,
);

$renderSection('node', 'Node.js', 'for Node.js', $nodeRepo, $nodePages, ['page' => [
    'text' => $nodeReadme, 'md' => 'node/index.md', 'source' => 'README.md',
    'seo' => 'bKash, Nagad & SSLCommerz for Node.js and Next.js',
    'description' => 'npm package for every Bangladeshi payment gateway in Node.js: bKash, Nagad, SSLCommerz, Upay and more, for Express, Next.js, NestJS, Bun and Deno.',
]], $nodeLinks, 'node/#software');

$urls = array_merge([''], $htmlDocUrls); // HTML pages only; AI files are found via llms.txt
$priority = fn (string $u) => match (true) { $u === '' => '1.0', str_ends_with($u, '/') => '0.8', default => '0.4' };
$sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
    .implode('', array_map(fn ($u) => "  <url><loc>{$siteUrl}{$u}</loc><lastmod>{$today}</lastmod><changefreq>weekly</changefreq><priority>{$priority($u)}</priority></url>\n", $urls))
    ."</urlset>\n";

// Point HTML readers and agents at the Markdown and llms.txt versions.
$out = str_replace(
    '<meta name="theme-color"',
    "<link rel=\"alternate\" type=\"text/markdown\" href=\"{$siteUrl}index.md\" title=\"BDPayGate docs in Markdown\">\n"
    ."<link rel=\"describedby\" href=\"{$siteUrl}llms.txt\" type=\"text/plain\">\n<meta name=\"theme-color\"",
    $out,
);

@mkdir(__DIR__.'/public/docs', 0755, true);
file_put_contents(__DIR__.'/public/index.html', $out);
file_put_contents(__DIR__.'/public/llms.txt', $llms);
file_put_contents(__DIR__.'/public/llms-full.txt', $full);
@mkdir(__DIR__.'/public/node/docs', 0755, true);
file_put_contents(__DIR__.'/public/node/llms-full.txt', $nodeFull);
file_put_contents(__DIR__.'/public/robots.txt', $robots);
file_put_contents(__DIR__.'/public/sitemap.xml', $sitemap);
file_put_contents(__DIR__.'/public/.nojekyll', '');

foreach ($docs as $path => $text) {
    @mkdir(dirname(__DIR__.'/public/'.$path), 0755, true);
    file_put_contents(__DIR__.'/public/'.$path, $text."\n");
}

echo "Built public for {$siteUrl}: ".count($faqEntities).' FAQ entries, '.count($gateways).' Laravel and '.count($nodeGateways).' Node.js gateway docs, '.count($htmlDocUrls)." HTML doc pages, llms.txt\n";
