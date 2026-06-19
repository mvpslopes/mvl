import type { ReactNode } from 'react';
import Sidebar from './Sidebar';
import Header from './Header';

type Props = {
  title: string;
  children: ReactNode;
};

export default function AppShell({ title, children }: Props) {
  return (
    <div className="min-h-screen flex bg-sesmt-page">
      <Sidebar />
      <div className="flex-1 flex flex-col min-w-0">
        <div className="p-6 md:p-8 flex-1">
          <Header title={title} />
          <div className="max-w-6xl">{children}</div>
        </div>
        <footer className="py-4 text-center text-xs text-sesmt-forest/50">
          © {new Date().getFullYear()} SESMT — MVLopes. Todos os direitos reservados.
        </footer>
      </div>
    </div>
  );
}
