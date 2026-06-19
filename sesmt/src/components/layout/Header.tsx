import { Bell, ChevronDown } from 'lucide-react';
import { getRole, getUserName } from '../../lib/auth';

type Props = {
  title: string;
};

export default function Header({ title }: Props) {
  const roleLabel = getRole() === 'root' ? 'Root' : 'Admin';

  return (
    <header className="flex flex-wrap items-center justify-between gap-4 mb-8">
      <h1 className="text-2xl font-bold text-sesmt-forest">{title}</h1>

      <div className="flex items-center gap-4">
        <button
          type="button"
          className="p-2 text-sesmt-forest/70 hover:text-sesmt-forest transition-colors"
          aria-label="Notificações"
        >
          <Bell size={20} strokeWidth={1.75} />
        </button>

        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-full border-2 border-sesmt-accent bg-sesmt-accent-muted flex items-center justify-center text-sm font-semibold text-sesmt-forest">
            {getUserName().charAt(0).toUpperCase()}
          </div>
          <div className="hidden sm:block">
            <p className="text-sm font-semibold text-sesmt-forest leading-tight">{getUserName()}</p>
            <p className="text-xs text-sesmt-forest/55">{roleLabel}</p>
          </div>
          <ChevronDown size={16} className="text-sesmt-forest/40 hidden sm:block" />
        </div>
      </div>
    </header>
  );
}
