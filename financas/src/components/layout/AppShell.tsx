import type { ReactNode } from 'react';
import { Link, useLocation } from 'react-router-dom';
import {
  Calendar,
  LayoutDashboard,
  LogOut,
  Repeat,
  Settings,
  TrendingUp,
  Wallet,
} from 'lucide-react';
import { apiFetch } from '../../lib/api';
import { clearSession, getUserName } from '../../lib/auth';
import { appUrl } from '../../lib/paths';

const links = [
  { to: '/', icon: LayoutDashboard, label: 'Ano' },
  { to: '/mes', icon: Calendar, label: 'Mês' },
  { to: '/projecao', icon: TrendingUp, label: 'Projeção' },
  { to: '/recorrencias', icon: Repeat, label: 'Recorrências' },
  { to: '/config', icon: Settings, label: 'Saldo inicial' },
];

export default function AppShell({ title, children }: { title: string; children: ReactNode }) {
  const location = useLocation();

  const logout = async () => {
    try {
      await apiFetch('/logout.php', { method: 'POST' });
    } catch {
      /* ignora */
    }
    clearSession();
    window.location.href = appUrl('/login');
  };

  return (
    <div className="min-h-screen bg-page flex">
      <aside className="hidden md:flex w-60 shrink-0 flex-col bg-white border-r border-slate-200">
        <div className="p-5 border-b border-slate-100">
          <div className="flex items-center gap-2">
            <div className="w-9 h-9 rounded-xl bg-ink flex items-center justify-center">
              <Wallet className="w-4 h-4 text-white" />
            </div>
            <div>
              <p className="text-sm font-bold">Finanças</p>
              <p className="text-[11px] text-slate-500">MVLopes</p>
            </div>
          </div>
        </div>
        <nav className="flex-1 p-3 space-y-1">
          {links.map(({ to, icon: Icon, label }) => {
            const active = to === '/' ? location.pathname === '/' : location.pathname.startsWith(to);
            return (
              <Link
                key={to}
                to={to}
                className={`fin-nav ${active ? 'fin-nav-active' : ''}`}
              >
                <Icon size={18} strokeWidth={1.75} />
                {label}
              </Link>
            );
          })}
        </nav>
        <div className="p-3 border-t border-slate-100">
          <button type="button" onClick={logout} className="fin-nav w-full text-red-600 hover:bg-red-50">
            <LogOut size={18} />
            Sair
          </button>
        </div>
      </aside>

      <div className="flex-1 min-w-0 flex flex-col">
        <header className="bg-white/90 backdrop-blur border-b border-slate-200 px-4 md:px-8 py-4">
          <div className="flex items-center justify-between gap-4">
            <div>
              <h1 className="text-xl font-bold">{title}</h1>
              <p className="text-sm text-slate-500">{getUserName()}</p>
            </div>
          </div>
          <nav className="flex md:hidden gap-2 mt-3 overflow-x-auto pb-1">
            {links.map(({ to, label }) => (
              <Link
                key={to}
                to={to}
                className={`shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium ${
                  (to === '/' ? location.pathname === '/' : location.pathname.startsWith(to))
                    ? 'bg-ink text-white'
                    : 'bg-slate-100 text-slate-600'
                }`}
              >
                {label}
              </Link>
            ))}
          </nav>
        </header>
        <main className="p-4 md:p-8 flex-1">{children}</main>
      </div>
    </div>
  );
}
