import { buildAbsoluteUrl } from "./utils";

type JsonLd = Record<string, unknown>;

export function buildBreadcrumbs(
    crumbs: Array<{ name: string; path: string }>,
): JsonLd {
    return {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        itemListElement: crumbs.map((crumb, index) => ({
            "@type": "ListItem",
            position: index + 1,
            name: crumb.name,
            item: buildAbsoluteUrl(crumb.path),
        })),
    };
}

export function buildCollectionPage(opts: {
    name: string;
    description: string;
    path: string;
}): JsonLd {
    return {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        name: opts.name,
        description: opts.description,
        url: buildAbsoluteUrl(opts.path),
    };
}

export function buildItemList(
    items: Array<{ name: string; url: string; position?: number }>,
    extra?: Record<string, unknown>,
): JsonLd | null {
    if (items.length === 0) {
        return null;
    }

    return {
        "@context": "https://schema.org",
        "@type": "ItemList",
        ...extra,
        itemListElement: items.map((item, index) => ({
            "@type": "ListItem",
            position: item.position ?? index + 1,
            name: item.name,
            url: item.url,
        })),
    };
}
