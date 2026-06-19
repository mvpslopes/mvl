import type { ReactNode } from 'react';
import { X } from 'lucide-react';

type Props = {
  open: boolean;
  title: string;
  onClose: () => void;
  children: ReactNode;
  /** Use em modais abertos dentro de outro modal (ex.: criar categoria no formulário). */
  elevated?: boolean;
};

export default function FinModal({ open, title, onClose, children, elevated }: Props) {
  if (!open) return null;

  return (
    <div
      className={`fixed inset-0 ${elevated ? 'z-[60]' : 'z-50'} flex items-end sm:items-center justify-center sm:p-4 bg-black/40`}
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-white rounded-t-2xl sm:rounded-2xl shadow-[0_4px_24px_rgba(0,0,0,0.05)] w-full sm:max-w-lg max-h-[92vh] sm:max-h-[90vh] overflow-y-auto border border-slate-200/80"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="fin-modal-title"
      >
        <div className="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-slate-100 sticky top-0 bg-white z-10">
          <h2 id="fin-modal-title" className="text-base sm:text-lg font-semibold pr-4">
            {title}
          </h2>
          <button
            type="button"
            onClick={onClose}
            className="p-2 -mr-1 text-slate-400 hover:text-[#1A1D26] rounded-lg hover:bg-slate-50"
            aria-label="Fechar"
          >
            <X size={20} />
          </button>
        </div>
        <div className="p-4 sm:p-6 pb-[max(1rem,env(safe-area-inset-bottom))]">{children}</div>
      </div>
    </div>
  );
}
