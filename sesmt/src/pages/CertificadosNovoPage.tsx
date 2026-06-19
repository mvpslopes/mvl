import AppShell from '../components/layout/AppShell';
import CertificadoForm from '../components/CertificadoForm';

export default function CertificadosNovoPage() {
  return (
    <AppShell title="Novo certificado">
      <CertificadoForm />
    </AppShell>
  );
}
