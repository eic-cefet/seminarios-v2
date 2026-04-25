import type { ReactNode } from "react";
import { Link } from "react-router-dom";
import { ROUTES } from "@shared/config/routes";
import { getYear } from "@shared/lib/utils";

export function Footer() {
    const currentYear = getYear();

    return (
        <footer className="bg-gray-50 border-t border-gray-200 overflow-x-hidden">
            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="grid gap-10 md:grid-cols-4">
                    <div className="min-w-0 md:col-span-1">
                        <p className="text-sm font-semibold text-gray-900">
                            Seminários EIC
                        </p>
                        <p className="mt-2 text-sm text-gray-600 break-words">
                            Escola de Informática e Computação — CEFET-RJ
                        </p>
                        <p className="mt-4 inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-800">
                            <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-primary-600" />
                            Em conformidade com a LGPD
                        </p>
                    </div>

                    <FooterColumn title="Navegação">
                        <FooterLink to={ROUTES.SYSTEM.SUBJECTS}>
                            Tópicos
                        </FooterLink>
                        <FooterLink to={ROUTES.SYSTEM.PRESENTATIONS}>
                            Apresentações
                        </FooterLink>
                        <FooterLink to={ROUTES.SYSTEM.WORKSHOPS}>
                            Workshops
                        </FooterLink>
                    </FooterColumn>

                    <FooterColumn title="Legal & Privacidade">
                        <FooterLink to={ROUTES.SYSTEM.PRIVACY_POLICY}>
                            Privacidade
                        </FooterLink>
                        <FooterLink to={ROUTES.SYSTEM.TERMS}>
                            Termos
                        </FooterLink>
                        <FooterLink to={ROUTES.SYSTEM.DATA_RIGHTS}>
                            Meus Dados (LGPD)
                        </FooterLink>
                        <FooterLink to={ROUTES.SYSTEM.COOKIE_PREFERENCES}>
                            Cookies
                        </FooterLink>
                    </FooterColumn>

                    <FooterColumn title="Suporte">
                        <FooterLink to={ROUTES.SYSTEM.BUG_REPORT}>
                            Reportar Bug
                        </FooterLink>
                    </FooterColumn>
                </div>

                <div className="mt-10 border-t border-gray-200 pt-6">
                    <p className="text-center text-xs text-gray-500 md:text-left">
                        &copy; {currentYear} CEFET-RJ — Escola de Informática
                        e Computação. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </footer>
    );
}

function FooterColumn({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <nav aria-label={title} className="min-w-0">
            <h3 className="text-xs font-semibold uppercase tracking-wider text-gray-900">
                {title}
            </h3>
            <ul className="mt-3 space-y-2">{children}</ul>
        </nav>
    );
}

function FooterLink({ to, children }: { to: string; children: ReactNode }) {
    return (
        <li>
            <Link
                to={to}
                className="text-sm text-gray-600 hover:text-primary-700 hover:underline underline-offset-2"
            >
                {children}
            </Link>
        </li>
    );
}
