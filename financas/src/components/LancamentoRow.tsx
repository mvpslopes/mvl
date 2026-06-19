import type { Lancamento } from '../types/financeiro';
import { formatBRL, statusLabel } from '../lib/format';

type Props = {
  lancamento: Lancamento;
  onToggleStatus: (l: Lancamento) => void;
  onCancel: (l: Lancamento) => void;
  onEdit: (l: Lancamento) => void;
};

export function StatusBadge({ status }: { status: string }) {
  if (status === 'recebida' || status === 'paga') {
    return <span className="badge-ok">{statusLabel(status, '')}</span>;
  }
  if (status === 'cancelada') {
    return <span className="badge-cancel">Cancelada</span>;
  }
  return <span className="badge-prevista">Prevista</span>;
}

export function LancamentoRow({ lancamento, onToggleStatus, onCancel, onEdit }: Props) {
  const l = lancamento;
  const isReceita = l.tipo === 'receita';

  return (
    <tr className={`border-t border-slate-100 ${l.projetado ? 'bg-violet-50/40' : ''}`}>
      <td className="px-4 py-3">
        <p className="font-medium text-sm">{l.descricao}</p>
        {l.projetado && <p className="text-[11px] text-violet-600">Recorrente (projetado)</p>}
      </td>
      <td className="px-4 py-3 text-sm text-slate-600">
        {new Date(l.data_vencimento + 'T12:00:00').toLocaleDateString('pt-BR')}
      </td>
      <td className={`px-4 py-3 text-sm font-semibold ${isReceita ? 'text-emerald-600' : 'text-red-600'}`}>
        {isReceita ? '+' : '−'} {formatBRL(l.valor)}
      </td>
      <td className="px-4 py-3">
        <StatusBadge status={l.status} />
      </td>
      <td className="px-4 py-3">
        <div className="flex justify-end gap-1">
          {l.status !== 'cancelada' && l.status !== 'recebida' && l.status !== 'paga' && (
            <button type="button" className="fin-btn-ghost text-xs py-1.5 px-2" onClick={() => onToggleStatus(l)}>
              {isReceita ? 'Receber' : 'Pagar'}
            </button>
          )}
          <button type="button" className="fin-btn-ghost text-xs py-1.5 px-2" onClick={() => onEdit(l)}>
            Editar
          </button>
          {l.status !== 'cancelada' && (
            <button
              type="button"
              className="fin-btn-ghost text-xs py-1.5 px-2 text-red-600"
              onClick={() => onCancel(l)}
            >
              Cancelar
            </button>
          )}
        </div>
      </td>
    </tr>
  );
}
