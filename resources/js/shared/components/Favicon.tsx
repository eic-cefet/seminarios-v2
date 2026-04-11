import { useEffect } from "react";

const basePath = app.BASE_PATH || "";

const FAVICON_LINKS = [
    { rel: "icon", type: "image/x-icon", href: `${basePath}/favicon/favicon.ico` },
    { rel: "icon", type: "image/png", sizes: "16x16", href: `${basePath}/favicon/favicon-16x16.png` },
    { rel: "icon", type: "image/png", sizes: "32x32", href: `${basePath}/favicon/favicon-32x32.png` },
    { rel: "apple-touch-icon", sizes: "180x180", href: `${basePath}/favicon/apple-touch-icon.png` },
    { rel: "apple-touch-icon", sizes: "57x57", href: `${basePath}/favicon/apple-touch-icon-57x57.png` },
    { rel: "apple-touch-icon", sizes: "60x60", href: `${basePath}/favicon/apple-touch-icon-60x60.png` },
    { rel: "apple-touch-icon", sizes: "72x72", href: `${basePath}/favicon/apple-touch-icon-72x72.png` },
    { rel: "apple-touch-icon", sizes: "76x76", href: `${basePath}/favicon/apple-touch-icon-76x76.png` },
    { rel: "apple-touch-icon", sizes: "114x114", href: `${basePath}/favicon/apple-touch-icon-114x114.png` },
    { rel: "apple-touch-icon", sizes: "120x120", href: `${basePath}/favicon/apple-touch-icon-120x120.png` },
    { rel: "apple-touch-icon", sizes: "144x144", href: `${basePath}/favicon/apple-touch-icon-144x144.png` },
    { rel: "apple-touch-icon", sizes: "152x152", href: `${basePath}/favicon/apple-touch-icon-152x152.png` },
    { rel: "manifest", href: `${basePath}/favicon/manifest.json` },
    { rel: "mask-icon", href: `${basePath}/favicon/safari-pinned-tab.svg`, color: "#5bbad5" },
];

const META_TAGS = [
    { name: "msapplication-TileColor", content: "#da532c" },
    { name: "msapplication-TileImage", content: `${basePath}/favicon/mstile-144x144.png` },
    { name: "msapplication-config", content: `${basePath}/favicon/browserconfig.xml` },
    { name: "theme-color", content: "#ffffff" },
];

export function Favicon() {
    useEffect(() => {
        for (const linkData of FAVICON_LINKS) {
            const selector = `link[rel="${linkData.rel}"]${linkData.sizes ? `[sizes="${linkData.sizes}"]` : ""}[href="${linkData.href}"]`;
            if (!document.querySelector(selector)) {
                const link = document.createElement("link");
                for (const [key, value] of Object.entries(linkData)) {
                    link.setAttribute(key, value);
                }
                document.head.appendChild(link);
            }
        }

        for (const metaData of META_TAGS) {
            if (!document.querySelector(`meta[name="${metaData.name}"]`)) {
                const meta = document.createElement("meta");
                meta.setAttribute("name", metaData.name);
                meta.setAttribute("content", metaData.content);
                document.head.appendChild(meta);
            }
        }
    }, []);

    return null;
}
