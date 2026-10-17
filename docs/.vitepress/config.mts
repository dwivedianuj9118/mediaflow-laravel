import { defineConfig, type HeadConfig } from 'vitepress'

const siteUrl = 'https://dwivedianuj9118.github.io/mediaflow-laravel'
const repoUrl = 'https://github.com/dwivedianuj9118/mediaflow-laravel'
const siteName = 'MediaFlow for Laravel'
const siteDescription =
  'MediaFlow is a media manager and upload toolkit for Laravel: drop-in Blade upload fields, a media library, WebP optimization, S3/R2 cloud storage presets, signed URLs and role-based access control.'
const ogImage = `${siteUrl}/og-image.png`

// Turns "guide/installation.md" into the public, extension-less page URL.
function pageUrl(relativePath: string): string {
  const path = relativePath.replace(/(^|\/)index\.md$/, '$1').replace(/\.md$/, '')
  return `${siteUrl}/${path}`
}

export default defineConfig({
  lang: 'en-US',
  title: siteName,
  titleTemplate: ':title | MediaFlow for Laravel',
  description: siteDescription,
  base: '/mediaflow-laravel/',
  cleanUrls: true,
  lastUpdated: true,
  // Light by default; readers can switch to dark and the choice is remembered.
  appearance: { initialValue: 'light' } as any,

  markdown: {
    config(md) {
      // Blade's {{ }} in inline code must not be read as a Vue interpolation.
      const render = md.renderer.rules.code_inline!
      md.renderer.rules.code_inline = (tokens, idx, options, env, self) =>
        render(tokens, idx, options, env, self).replace(/^<code/, '<code v-pre')
    },
  },

  sitemap: {
    hostname: `${siteUrl}/`,
  },

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/mediaflow-laravel/favicon.svg' }],
    ['meta', { name: 'theme-color', content: '#ffffff', media: '(prefers-color-scheme: light)' }],
    ['meta', { name: 'theme-color', content: '#17181c', media: '(prefers-color-scheme: dark)' }],
    ['meta', { name: 'author', content: 'Anuj Dwivedi' }],
    ['meta', { name: 'robots', content: 'index, follow, max-image-preview:large' }],
    [
      'meta',
      {
        name: 'keywords',
        content:
          'laravel media manager, laravel file upload, laravel media library, laravel image upload, blade upload component, laravel s3 upload, cloudflare r2 laravel, laravel webp, laravel package',
      },
    ],
    ['meta', { property: 'og:site_name', content: siteName }],
    ['meta', { property: 'og:locale', content: 'en_US' }],
    ['meta', { property: 'og:image', content: ogImage }],
    ['meta', { property: 'og:image:width', content: '1200' }],
    ['meta', { property: 'og:image:height', content: '630' }],
    ['meta', { property: 'og:image:alt', content: 'MediaFlow for Laravel documentation' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:image', content: ogImage }],
  ],

  // Per-page canonical URL, Open Graph / Twitter tags and JSON-LD structured data.
  transformHead({ pageData, siteData }) {
    const head: HeadConfig[] = []
    const url = pageUrl(pageData.relativePath)
    const isHome = pageData.frontmatter.layout === 'home'
    const title = isHome
      ? `${siteName}: Media Manager & Upload Toolkit`
      : `${pageData.title} | ${siteName}`
    const description = pageData.frontmatter.description || pageData.description || siteData.description

    head.push(['link', { rel: 'canonical', href: url }])
    head.push(['meta', { property: 'og:type', content: isHome ? 'website' : 'article' }])
    head.push(['meta', { property: 'og:url', content: url }])
    head.push(['meta', { property: 'og:title', content: title }])
    head.push(['meta', { property: 'og:description', content: description }])
    head.push(['meta', { name: 'twitter:title', content: title }])
    head.push(['meta', { name: 'twitter:description', content: description }])

    const graph: Record<string, unknown>[] = []
    if (isHome) {
      graph.push(
        {
          '@type': 'WebSite',
          '@id': `${siteUrl}/#website`,
          url: `${siteUrl}/`,
          name: siteName,
          description: siteDescription,
          inLanguage: 'en',
        },
        {
          '@type': 'SoftwareSourceCode',
          name: 'MediaFlow for Laravel',
          description: siteDescription,
          codeRepository: repoUrl,
          programmingLanguage: ['PHP', 'JavaScript'],
          runtimePlatform: 'Laravel 11, 12, 13',
          license: 'https://opensource.org/licenses/MIT',
          author: { '@type': 'Person', name: 'Anuj Dwivedi', url: 'https://anujdwivedi.online' },
        },
      )
    } else {
      const section = pageData.relativePath.split('/')[0]
      graph.push(
        {
          '@type': 'TechArticle',
          headline: pageData.title,
          description,
          url,
          inLanguage: 'en',
          isPartOf: { '@id': `${siteUrl}/#website` },
          author: { '@type': 'Person', name: 'Anuj Dwivedi' },
          ...(pageData.lastUpdated ? { dateModified: new Date(pageData.lastUpdated).toISOString() } : {}),
        },
        {
          '@type': 'BreadcrumbList',
          itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'Docs', item: `${siteUrl}/` },
            {
              '@type': 'ListItem',
              position: 2,
              name: sections[section]?.name ?? section,
              item: sections[section] ? `${siteUrl}${sections[section].link}` : url,
            },
            { '@type': 'ListItem', position: 3, name: pageData.title, item: url },
          ],
        },
      )
    }
    head.push([
      'script',
      { type: 'application/ld+json' },
      JSON.stringify({ '@context': 'https://schema.org', '@graph': graph }),
    ])
    return head
  },

  themeConfig: {
    logo: { src: '/logo.svg', width: 24, height: 24, alt: '' },
    siteTitle: 'MediaFlow',

    nav: [
      { text: 'Guide', link: '/guide/introduction', activeMatch: '/guide/' },
      { text: 'Components', link: '/components/input', activeMatch: '/components/' },
      { text: 'API', link: '/api/facade', activeMatch: '/api/' },
      { text: 'Reference', link: '/reference/rest-api', activeMatch: '/reference/' },
      { text: 'Changelog', link: '/reference/changelog' },
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Introduction', link: '/guide/introduction' },
          { text: 'Requirements', link: '/guide/requirements' },
          { text: 'Installation', link: '/guide/installation' },
          { text: 'Quickstart', link: '/guide/quickstart' },
          { text: 'How Uploads Work', link: '/guide/how-it-works' },
          { text: 'Upgrading', link: '/guide/upgrading' },
        ],
      },
      {
        text: 'Configuration',
        items: [
          { text: 'Environment Variables', link: '/config/environment' },
          { text: 'Database Tables', link: '/config/database' },
          { text: 'Upload Rules', link: '/config/uploads' },
          { text: 'Image Processing', link: '/config/images' },
          { text: 'Settings Page', link: '/config/settings-page' },
        ],
      },
      {
        text: 'Blade Components',
        items: [
          { text: 'Input Field', link: '/components/input' },
          { text: 'Uploader', link: '/components/uploader' },
          { text: 'Media Manager', link: '/components/manager' },
          { text: 'Theming & Dark Mode', link: '/components/theming' },
        ],
      },
      {
        text: 'Developer API',
        items: [
          { text: 'MediaFlow Facade', link: '/api/facade' },
          { text: 'Media Model', link: '/api/media-model' },
          { text: 'Image Optimization', link: '/api/optimization' },
          { text: 'Events', link: '/api/events' },
        ],
      },
      {
        text: 'Storage & Delivery',
        items: [
          { text: 'Cloud Providers', link: '/storage/cloud-providers' },
          { text: 'Delivery URLs', link: '/storage/delivery' },
        ],
      },
      {
        text: 'Security',
        items: [
          { text: 'Access Control', link: '/security/access-control' },
          { text: 'Per-User Scoping', link: '/security/user-scoping' },
          { text: 'Production Checklist', link: '/security/checklist' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'REST API', link: '/reference/rest-api' },
          { text: 'Artisan Commands', link: '/reference/artisan' },
          { text: 'Diagnostics', link: '/reference/diagnostics' },
          { text: 'Extending', link: '/reference/extending' },
          { text: 'Testing & Troubleshooting', link: '/reference/troubleshooting' },
          { text: 'Changelog', link: '/reference/changelog' },
          { text: 'About', link: '/reference/about' },
        ],
      },
    ],

    socialLinks: [{ icon: 'github', link: repoUrl }],

    editLink: {
      pattern: `${repoUrl}/edit/main/docs/:path`,
      text: 'Edit this page on GitHub',
    },

    search: { provider: 'local' },

    outline: { level: [2, 3], label: 'On this page' },

    lastUpdated: { text: 'Last updated', formatOptions: { dateStyle: 'medium' } },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2026 Anuj Dwivedi',
    },
  },
})

// Breadcrumb parents for structured data: each docs folder and its first page.
const sections: Record<string, { name: string; link: string }> = {
  guide: { name: 'Getting Started', link: '/guide/introduction' },
  config: { name: 'Configuration', link: '/config/environment' },
  components: { name: 'Blade Components', link: '/components/input' },
  api: { name: 'Developer API', link: '/api/facade' },
  storage: { name: 'Storage & Delivery', link: '/storage/cloud-providers' },
  security: { name: 'Security', link: '/security/access-control' },
  reference: { name: 'Reference', link: '/reference/rest-api' },
}
