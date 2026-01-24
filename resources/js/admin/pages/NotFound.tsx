import { Link } from "react-router-dom";
import { Home } from "lucide-react";
import { PageTitle } from "@shared/components/PageTitle";
import { Button } from "../components/ui/button";

export default function NotFound() {
    return (
        <div className="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
            <PageTitle title="Página não encontrada" />
            <p className="text-sm font-semibold text-primary">404</p>
            <h1 className="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                Página não encontrada
            </h1>
            <p className="mt-4 text-muted-foreground">
                A página que você está procurando não existe ou foi movida.
            </p>
            <div className="mt-8">
                <Button asChild>
                    <Link to="/">
                        <Home className="mr-2 h-4 w-4" />
                        Voltar para o Dashboard
                    </Link>
                </Button>
            </div>
        </div>
    );
}
