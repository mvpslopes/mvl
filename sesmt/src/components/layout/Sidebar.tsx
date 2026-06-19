import { Award, Building2, LayoutDashboard, LogOut, Shield, Users } from 'lucide-react';
import { NavLink, useNavigate } from 'react-router-dom';
import { apiFetch } from '../../lib/api';
import { clearSession, isRoot } from '../../lib/auth';

const navClass = ({ isActive }: { isActive: boolean }) =>
  `flex items-center gap-3 px-3.5 py-2.5 rounded-[10px] text-sm font-medium transition-colors ${
    isActive
      ? 'text-sesmt-accent'
      : 'text-sesmt-forest hover:bg-sesmt-forest/5'
  }`;

export default function Sidebar() {
  const navigate = useNavigate();
  const root = isRoot();

  const handleLogout = async () => {
    try {
      await apiFetch('/logout.php', { method: 'POST' });
    } catch {
      /* ignora erro de rede no logout */
    }
    clearSession();
    navigate('/login', { replace: true });
  };

  return (
    <aside className="hidden md:flex w-60 shrink-0 flex-col bg-white px-4 py-6 min-h-screen">
      <div className="mb-8 px-2">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-lg bg-sesmt-forest flex items-center justify-center">
            <Shield className="w-4 h-4 text-white" strokeWidth={1.75} />
          </div>
          <div>
            <p className="text-base font-bold text-sesmt-forest leading-tight">SESMT</p>
            <p className="text-[11px] text-sesmt-forest/55">MVLopes</p>
          </div>
        </div>
      </div>

      <nav className="flex flex-col gap-1 flex-1">
        <NavLink to="/" end className={navClass}>
          <LayoutDashboard size={20} strokeWidth={1.75} />
          Dashboard
        </NavLink>
        <NavLink to="/certificados" className={navClass}>
          <Award size={20} strokeWidth={1.75} />
          Certificados
        </NavLink>
        <NavLink to="/empresas" className={navClass}>
          <Building2 size={20} strokeWidth={1.75} />
          Empresas
        </NavLink>
        {root && (
          <NavLink to="/usuarios" className={navClass}>
            <Users size={20} strokeWidth={1.75} />
            Usuários
          </NavLink>
        )}
      </nav>

      <button type="button" onClick={handleLogout} className={`${navClass({ isActive: false })} w-full mt-4`}>
        <LogOut size={20} strokeWidth={1.75} />
        Sair
      </button>
    </aside>
  );
}
