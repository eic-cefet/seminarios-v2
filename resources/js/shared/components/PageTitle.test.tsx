import { render, waitFor } from '@testing-library/react';
import { HelmetProvider } from 'react-helmet-async';
import { PageTitle } from './PageTitle';

function renderWithHelmet(ui: React.ReactElement) {
    return render(<HelmetProvider>{ui}</HelmetProvider>);
}

describe('PageTitle', () => {
    beforeEach(() => {
        document.head.innerHTML = '';
        app.BASE_PATH = '';
    });

    it('renders only the title when no SEO props are provided', async () => {
        renderWithHelmet(<PageTitle title="Home" />);

        await waitFor(() => {
            expect(document.title).toBe('Home - EIC CEFET-RJ');
        });

        expect(
            document.head.querySelector('meta[name="description"]'),
        ).not.toBeInTheDocument();
    });

    it('renders canonical, robots, social tags, and structured data', async () => {
        app.BASE_PATH = 'https://seminarios.example.com';

        renderWithHelmet(
            <PageTitle
                title="Seminários EIC do CEFET-RJ"
                absoluteTitle
                description="Agenda oficial de seminários da EIC."
                canonicalPath="/apresentacoes"
                structuredData={{
                    '@context': 'https://schema.org',
                    '@type': 'CollectionPage',
                    name: 'Apresentações',
                }}
            />,
        );

        await waitFor(() => {
            expect(document.title).toBe('Seminários EIC do CEFET-RJ');
        });

        expect(
            document.head.querySelector('meta[name="description"]'),
        ).toHaveAttribute('content', 'Agenda oficial de seminários da EIC.');
        expect(
            document.head.querySelector('meta[name="robots"]'),
        ).toHaveAttribute('content', 'index, follow');
        expect(
            document.head.querySelector('link[rel="canonical"]'),
        ).toHaveAttribute(
            'href',
            'https://seminarios.example.com/apresentacoes',
        );
        expect(
            document.head.querySelector('meta[property="og:url"]'),
        ).toHaveAttribute(
            'content',
            'https://seminarios.example.com/apresentacoes',
        );
        expect(
            document.head.querySelector('meta[name="twitter:card"]'),
        ).toHaveAttribute('content', 'summary_large_image');
        expect(
            document.head.querySelector('script[type="application/ld+json"]')
                ?.textContent,
        ).toContain('"@type":"CollectionPage"');
    });

});
