import {
    buildBreadcrumbs,
    buildCollectionPage,
    buildItemList,
} from "./structuredData";

beforeEach(() => {
    app.BASE_PATH = "https://seminarios.example.com";
});

describe("buildBreadcrumbs", () => {
    it("creates a BreadcrumbList with absolute URLs", () => {
        const result = buildBreadcrumbs([
            { name: "Início", path: "/" },
            { name: "Tópicos", path: "/topicos" },
        ]);

        expect(result).toEqual({
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            itemListElement: [
                {
                    "@type": "ListItem",
                    position: 1,
                    name: "Início",
                    item: "https://seminarios.example.com/",
                },
                {
                    "@type": "ListItem",
                    position: 2,
                    name: "Tópicos",
                    item: "https://seminarios.example.com/topicos",
                },
            ],
        });
    });
});

describe("buildCollectionPage", () => {
    it("creates a CollectionPage schema", () => {
        const result = buildCollectionPage({
            name: "Workshops",
            description: "All workshops",
            path: "/workshops",
        });

        expect(result["@type"]).toBe("CollectionPage");
        expect(result.url).toBe(
            "https://seminarios.example.com/workshops",
        );
    });
});

describe("buildItemList", () => {
    it("returns null for an empty list", () => {
        expect(buildItemList([])).toBeNull();
    });

    it("creates an ItemList with sequential positions", () => {
        const result = buildItemList([
            { name: "A", url: "https://example.com/a" },
            { name: "B", url: "https://example.com/b" },
        ]);

        expect(result).not.toBeNull();
        const elements = (result as Record<string, unknown>)
            .itemListElement as Array<Record<string, unknown>>;
        expect(elements[0].position).toBe(1);
        expect(elements[1].position).toBe(2);
    });

    it("respects custom positions", () => {
        const result = buildItemList([
            { name: "A", url: "https://example.com/a", position: 11 },
            { name: "B", url: "https://example.com/b", position: 12 },
        ]);

        const elements = (result as Record<string, unknown>)
            .itemListElement as Array<Record<string, unknown>>;
        expect(elements[0].position).toBe(11);
        expect(elements[1].position).toBe(12);
    });

    it("merges extra properties", () => {
        const result = buildItemList(
            [{ name: "A", url: "https://example.com/a" }],
            { numberOfItems: 50 },
        );

        expect(
            (result as Record<string, unknown>).numberOfItems,
        ).toBe(50);
    });
});
