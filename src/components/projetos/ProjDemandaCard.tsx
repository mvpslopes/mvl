import { Check, Pencil, Trash2 } from 'lucide-react';
import type { Demanda } from '../../types/projetos';
import { projFetch } from '../../lib/projetoApi';

type Props = {
  demanda: Demanda;
  onEdit: (d: Demanda) => void;
  onChanged: () => void;
  compact?: boolean;
};

const prioridadeClass: Record<string, string> = {
  alta: 'border-l-red-500',
  media: 'border-l-amber-400',
  baixa: 'border-l-slate-300',
};

export default function ProjDemandaCard({ demanda: d, onEdit, onChanged, compact }: Props) {
  const concluida = d.status === 'concluida';
  const temChecklist = (d.checklist_total ?? 0) > 0;
  const prog = temChecklist ? `${d.checklist_concluidos ?? 0}/${d.checklist_total}` : null;

  const toggleChecklist = async (itemId: number, concluido: boolean) => {
    await projFetch('/checklist.php', { method: 'PATCH', body: { id: itemId, concluido } });
    onChanged();
  };

  const concluir = async () => {
    await projFetch('/demandas.php', { method: 'PUT', body: { id: d.id, status: 'concluida' } });
    onChanged();
  };

  const excluir = async () => {
    if (!confirm(`Excluir "${d.titulo}"?`)) return;
    await projFetch(`/demandas.php?id=${d.id}`, { method: 'DELETE', body: { id: d.id } });
    onChanged();
  };

  return (
    <article
      className={`rounded-xl border border-slate-200 bg-white border-l-4 ${prioridadeClass[d.prioridade] ?? ''} ${
        concluida ? 'opacity-60' : ''
      } ${compact ? 'p-2.5' : 'p-3'}`}
    >
      <div className="flex items-start justify-between gap-2 mb-1">
        <div className="min-w-0 flex-1">
          <p className={`font-medium text-sm leading-snug ${concluida ? 'line-through text-slate-500' : ''}`}>
            {d.titulo}
          </p>
          {d.descricao && !compact && (
            <p className="text-xs text-slate-500 mt-0.5 line-clamp-2">{d.descricao}</p>
          )}
        </div>
        <div className="flex gap-0.5 shrink-0">
          <button type="button" className="p-1.5 text-slate-400 hover:text-[#1A1D26] rounded-lg" onClick={() => onEdit(d)}>
            <Pencil size={14} />
          </button>
          <button type="button" className="p-1.5 text-slate-400 hover:text-red-600 rounded-lg" onClick={excluir}>
            <Trash2 size={14} />
          </button>
        </div>
      </div>

      <div className="flex flex-wrap items-center gap-1.5 mb-2">
        {d.tipo_nome && (
          <span
            className="text-[10px] font-medium px-1.5 py-0.5 rounded-full"
            style={{ backgroundColor: `${d.tipo_cor}22`, color: d.tipo_cor ?? '#6366f1' }}
          >
            {d.tipo_nome}
          </span>
        )}
        {prog && (
          <span className="text-[10px] text-slate-500 font-medium">{prog}</span>
        )}
      </div>

      {temChecklist && d.checklist && (
        <ul className="space-y-1 mb-2">
          {d.checklist.map((item) => (
            <li key={item.id} className="flex items-start gap-2 text-xs">
              <input
                type="checkbox"
                checked={item.concluido}
                onChange={(e) => toggleChecklist(item.id, e.target.checked)}
                className="mt-0.5 rounded border-slate-300"
              />
              <span className={item.concluido ? 'line-through text-slate-400' : 'text-slate-700'}>{item.texto}</span>
            </li>
          ))}
        </ul>
      )}

      {!concluida && !temChecklist && (
        <button type="button" className="panel-btn-ghost text-xs py-1.5 w-full" onClick={concluir}>
          <Check size={14} /> Concluir
        </button>
      )}
    </article>
  );
}
