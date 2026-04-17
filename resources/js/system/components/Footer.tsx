import { Link } from "react-router-dom";
import { ROUTES } from "@shared/config/routes";
import { getYear } from "@shared/lib/date";

export function Footer() {
    const currentYear = getYear();

    return (
        <footer className="bg-white border-t border-gray-200">
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="md:flex md:items-center md:justify-between">
                    <div className="flex justify-center space-x-6 md:order-2">
                        <Link
                            to={ROUTES.SYSTEM.SUBJECTS}
                            className="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Tópicos
                        </Link>
                        <Link
                            to={ROUTES.SYSTEM.PRESENTATIONS}
                            className="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Apresentações
                        </Link>
                        <Link
                            to={ROUTES.SYSTEM.WORKSHOPS}
                            className="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Workshops
                        </Link>
                        <Link
                            to={ROUTES.SYSTEM.BUG_REPORT}
                            className="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Reportar Bug
                        </Link>
                    </div>
                    <div className="mt-8 md:order-1 md:mt-0">
                        <p className="text-center text-sm text-gray-500">
                            &copy; {currentYear} CEFET-RJ - Escola de
                            Informática e Computação. Todos os direitos
                            reservados.
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    );
}
