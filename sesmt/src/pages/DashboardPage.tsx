import { ClipboardList, HardHat, Users } from 'lucide-react';
import AppShell from '../components/layout/AppShell';
import { getRole, getUserName } from '../lib/auth';

function StatCard({
  icon: Icon,
  value,
  label,
  variant,
}: {
  icon: typeof Users;
  value: string;
  label: string;
  variant: 'mint' | 'accent' | 'white';
}) {
  const bg =
    variant === 'mint'
      ? 'bg-sesmt-forest-muted'
      : variant === 'accent'
        ? 'bg-sesmt-accent-muted'
        : 'sesmt-card';

  return (
    <div className={`${bg} flex items-center gap-4 min-h-[100px]`}>
      <Icon className="w-7 h-7 text-sesmt-forest shrink-0" strokeWidth={1.75} />
      <div>
        <p className="text-3xl font-bold text-sesmt-forest leading-none">{value}</p>
        <p className="text-sm text-sesmt-forest/70 mt-1">{label}</p>
      </div>
    </div>
  );
}

export default function DashboardPage() {
  const role = getRole();

  return (
    <AppShell title="Dashboard">
      <p className="text-sesmt-forest/70 mb-8">
        Olá, <strong className="text-sesmt-forest">{getUserName()}</strong>. Você está conectado como{' '}
        <span className="font-medium text-sesmt-accent">{role === 'root' ? 'Root' : 'Admin'}</span>.
      </p>

      <div className="grid md:grid-cols-3 gap-6 mb-8">
        <StatCard icon={HardHat} value="—" label="Treinamentos ativos" variant="mint" />
        <StatCard icon={ClipboardList} value="—" label="Documentos SESMT" variant="accent" />
        <StatCard icon={Users} value="—" label="Colaboradores" variant="mint" />
      </div>

      <div className="sesmt-card">
        <h2 className="text-lg font-semibold text-sesmt-forest mb-2">Bem-vindo ao SESMT</h2>
        <p className="text-sm text-sesmt-forest/70 leading-relaxed">
          O módulo de gestão de segurança do trabalho está em implantação. Usuários com perfil{' '}
          <strong>Root</strong> podem gerenciar contas em <em>Usuários</em>. Perfis <strong>Admin</strong> têm
          acesso ao painel sem permissão de cadastro de usuários.
        </p>
      </div>
    </AppShell>
  );
}
