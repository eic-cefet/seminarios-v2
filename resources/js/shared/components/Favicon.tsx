import { useEffect } from "react";

const FAVICON_LINKS = [
    { rel: "icon", type: "image/x-icon", href: "/favicon/favicon.ico" },
    { rel: "icon", type: "image/png", sizes: "16x16", href: "/favicon/favicon-16x16.png" },
    { rel: "icon", type: "image/png", sizes: "32x32", href: "/favicon/favicon-32x32.png" },
    { rel: "apple-touch-icon", sizes: "180x180", href: "/favicon/apple-touch-icon.png" },
    { rel: "apple-touch-icon", sizes: "57x57", href: "/favicon/apple-touch-icon-57x57.png" },
    { rel: "apple-touch-icon", sizes: "60x60", href: "/favicon/apple-touch-icon-60x60.png" },
    { rel: "apple-touch-icon", sizes: "72x72", href: "/favicon/apple-touch-icon-72x72.png" },
    { rel: "apple-touch-icon", sizes: "76x76", href: "/favicon/apple-touch-icon-76x76.png" },
    { rel: "apple-touch-icon", sizes: "114x114", href: "/favicon/apple-touch-icon-114x114.png" },
    { rel: "apple-touch-icon", sizes: "120x120", href: "/favicon/apple-touch-icon-120x120.png" },
    { rel: "apple-touch-icon", sizes: "144x144", href: "/favicon/apple-touch-icon-144x144.png" },
    { rel: "apple-touch-icon", sizes: "152x152", href: "/favicon/apple-touch-icon-152x152.png" },
    { rel: "manifest", href: "/favicon/manifest.json" },
    { rel: "mask-icon", href: "/favicon/safari-pinned-tab.svg", color: "#5bbad5" },
];

const META_TAGS = [
    { name: "msapplication-TileColor", content: "#da532c" },
    { name: "msapplication-TileImage", content: "/favicon/mstile-144x144.png" },
    { name: "msapplication-config", content: "/favicon/browserconfig.xml" },
    { name: "theme-color", content: "#ffffff" },
];

export function Favicon() {
    useEffect(() => {
        // Add link tags
        FAVICON_LINKS.forEach((linkData) => {
            const existingLink = document.querySelector(
                `link[rel="${linkData.rel}"]${linkData.sizes ? `[sizes="${linkData.sizes}"]` : ""}[href="${linkData.href}"]`
            );

            if (!existingLink) {
                const link = document.createElement("link");
                Object.entries(linkData).forEach(([key, value]) => {
                    link.setAttribute(key, value);
                });
                document.head.appendChild(link);
            }
        });

        // Add meta tags
        META_TAGS.forEach((metaData) => {
            const existingMeta = document.querySelector(
                `meta[name="${metaData.name}"]`
            );

            if (!existingMeta) {
                const meta = document.createElement("meta");
                meta.setAttribute("name", metaData.name);
                meta.setAttribute("content", metaData.content);
                document.head.appendChild(meta);
            }
        });
    }, []);

    return null;
}
