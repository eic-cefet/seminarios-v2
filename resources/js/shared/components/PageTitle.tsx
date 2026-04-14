import { Helmet } from "react-helmet-async";
import { buildAbsoluteUrl } from "@shared/lib/utils";

const SITE_NAME = "EIC CEFET-RJ";
const DEFAULT_DESCRIPTION =
    "Acompanhe seminários, apresentações e workshops da Escola de Informática e Computação do CEFET-RJ.";
const DEFAULT_IMAGE_PATH = "/images/email-logo.png";

type StructuredData =
    | Record<string, unknown>
    | Array<Record<string, unknown>>;

interface PageTitleProps {
    title: string;
    absoluteTitle?: boolean;
    description?: string;
    canonicalPath?: string;
    imagePath?: string;
    ogType?: "website" | "article";
    robots?: string;
    structuredData?: StructuredData;
}

export function PageTitle({
    title,
    absoluteTitle = false,
    description,
    canonicalPath,
    imagePath,
    ogType = "website",
    robots = "index, follow",
    structuredData,
}: PageTitleProps) {
    const fullTitle = absoluteTitle ? title : `${title} - ${SITE_NAME}`;
    const shouldRenderSeoMeta =
        absoluteTitle ||
        description !== undefined ||
        canonicalPath !== undefined ||
        imagePath !== undefined ||
        ogType !== "website" ||
        robots !== "index, follow" ||
        structuredData !== undefined;
    const descriptionContent = description ?? DEFAULT_DESCRIPTION;
    const canonicalUrl = canonicalPath
        ? buildAbsoluteUrl(canonicalPath)
        : undefined;
    const imageUrl = buildAbsoluteUrl(imagePath ?? DEFAULT_IMAGE_PATH);
    const structuredDataItems = structuredData
        ? Array.isArray(structuredData)
            ? structuredData
            : [structuredData]
        : [];

    return (
        <Helmet>
            <title>{fullTitle}</title>
            {shouldRenderSeoMeta ? (
                <>
                    <meta name="description" content={descriptionContent} />
                    <meta name="robots" content={robots} />
                    {canonicalUrl ? (
                        <link rel="canonical" href={canonicalUrl} />
                    ) : null}

                    <meta property="og:title" content={fullTitle} />
                    <meta
                        property="og:description"
                        content={descriptionContent}
                    />
                    <meta property="og:type" content={ogType} />
                    <meta property="og:locale" content="pt_BR" />
                    <meta
                        property="og:site_name"
                        content="Seminários EIC do CEFET-RJ"
                    />
                    {canonicalUrl ? (
                        <meta property="og:url" content={canonicalUrl} />
                    ) : null}
                    <meta property="og:image" content={imageUrl} />
                    <meta property="og:image:alt" content={fullTitle} />

                    <meta name="twitter:card" content="summary_large_image" />
                    <meta name="twitter:title" content={fullTitle} />
                    <meta
                        name="twitter:description"
                        content={descriptionContent}
                    />
                    <meta name="twitter:image" content={imageUrl} />
                    <meta name="twitter:image:alt" content={fullTitle} />

                    {structuredDataItems.map((item, index) => (
                        <script
                            key={index}
                            type="application/ld+json"
                        >
                            {JSON.stringify(item)}
                        </script>
                    ))}
                </>
            ) : null}
        </Helmet>
    );
}
