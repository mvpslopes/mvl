import { useParams } from 'react-router-dom';
import AppShell from '../components/layout/AppShell';
import CertificadoForm from '../components/CertificadoForm';

export default function CertificadosPage() {
  const { id } = useParams();
  const editId = id ? Number(id) : undefined;
  const titulo = editId ? 'Editar certificado' : 'Novo certificado';

  return (
    <AppShell title={titulo}>
      <CertificadoForm editId={editId} />
    </AppShell>
  );
}
