import { PageTitle } from "@shared/components/PageTitle";
import { useAuth } from "@shared/contexts/AuthContext";
import { Loader2 } from "lucide-react";
import { Navigate } from "react-router-dom";
import { CertificatesSection } from "../components/profile/CertificatesSection";
import { PasswordSection } from "../components/profile/PasswordSection";
import { ProfileInfoSection } from "../components/profile/ProfileInfoSection";
import { RegistrationsSection } from "../components/profile/RegistrationsSection";
import { StudentDataSection } from "../components/profile/StudentDataSection";
import { Layout } from "../components/Layout";

export default function Profile() {
    const { user, isLoading: authLoading, refreshUser } = useAuth();

    if (!authLoading && !user) {
        return <Navigate to="/login" replace />;
    }

    if (authLoading) {
        return (
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-primary-600" />
                </div>
            </Layout>
        );
    }

    return (
        <>
            <PageTitle title="Perfil" />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                        <h1 className="text-2xl font-bold text-gray-900">
                            Meu Perfil
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Gerencie suas informações pessoais e configurações
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">
                    <ProfileInfoSection user={user!} onUpdate={refreshUser} />
                    <StudentDataSection user={user!} onUpdate={refreshUser} />
                    <PasswordSection />
                    <RegistrationsSection />
                    <CertificatesSection />
                </div>
            </Layout>
        </>
    );
}
