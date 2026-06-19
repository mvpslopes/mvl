import { formatBRL } from '../../lib/financeFormat';
import type { ResumoCategoriaAno } from '../../types/financeiro';

type Props = {
  titulo: string;
  itens: ResumoCategoriaAno[];
  modo: 'previsto' | 'realizado';
  corBarra: string;
};

export default function FinAnoCategorias({ titulo, itens, modo, corBarra }: Props) {
  if (itens.length === 0) {
    return (
      <div className="panel-card">
        <h3 className="font-semibold text-sm mb-2">{titulo}</h3>
        <p className="text-sm text-slate-500">Nenhum lançamento com categoria neste ano.</p>
      </div>
    );
  }

  const maxVal = Math.max(1, ...itens.map((c) => (modo === 'previsto' ? c.previsto : c.realizado)));

  return (
    <div className="panel-card">
      <h3 className="font-semibold text-sm mb-4">{titulo}</h3>
      <div className="space-y-3">
        {itens.map((c) => {
          const val = modo === 'previsto' ? c.previsto : c.realizado;
          const pct = (val / maxVal) * 100;
          return (
            <div key={`${c.categoria_id ?? 0}-${c.categoria_nome}`}>
              <div className="flex justify-between items-center gap-2 text-sm mb-1">
                <span className="flex items-center gap-2 min-w-0">
                  <span
                    className="w-2.5 h-2.5 rounded-full shrink-0"
                    style={{ backgroundColor: c.categoria_cor }}
                  />
                  <span className="truncate font-medium">{c.categoria_nome}</span>
                </span>
                <span className="font-semibold shrink-0">{formatBRL(val)}</span>
              </div>
              <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div
                  className="h-full rounded-full transition-all"
                  style={{ width: `${Math.max(pct, val > 0 ? 3 : 0)}%`, backgroundColor: corBarra }}
                />
              </div>
              {modo === 'realizado' && c.previsto > 0 && (
                <p className="text-[10px] text-slate-400 mt-0.5">
                  Previsto {formatBRL(c.previsto)}
                </p>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
