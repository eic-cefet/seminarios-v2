import { Helmet } from 'react-helmet-async';

interface PageTitleProps {
    title: string;
}

export function PageTitle({ title }: PageTitleProps) {
    const fullTitle = `${title} - EIC CEFET-RJ`;

    return (
        <Helmet>
            <title>{fullTitle}</title>
        </Helmet>
    );
}
