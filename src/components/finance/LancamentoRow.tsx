import type { Lancamento } from '../../types/financeiro';
import { formatBRL, lancamentoConcluido, valorRealizadoLancamento } from '../../lib/financeFormat';

type ActionProps = {
  l: Lancamento;
  concluido: boolean;
  isReceita: boolean;
  layout?: 'row' | 'card';
  onPagarReceber: (l: Lancamento) => void;
  onReverter: (l: Lancamento) => void;
  onCancel: (l: Lancamento) => void;
  onEdit: (l: Lancamento) => void;
};

export function StatusBadge({ status }: { status: string }) {
  if (status === 'recebida' || status === 'paga') {
    return (
      <span className="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 shrink-0">
        Concluído
      </span>
    );
  }
  if (status === 'cancelada') {
    return (
      <span className="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600 shrink-0">
        Cancelada
      </span>
    );
  }
  return (
    <span className="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 shrink-0">
      Prevista
    </span>
  );
}

export function LancamentoActions({
  l,
  concluido,
  isReceita,
  layout = 'row',
  onPagarReceber,
  onReverter,
  onCancel,
  onEdit,
}: ActionProps) {
  const btnClass =
    layout === 'card'
      ? 'panel-btn-ghost text-xs py-2 px-2 flex-1 min-w-[calc(50%-4px)]'
      : 'panel-btn-ghost text-xs py-1.5 px-2';

  return (
    <div className={`flex gap-1 flex-wrap ${layout === 'card' ? '' : 'justify-end'}`}>
      {l.status !== 'cancelada' && !concluido && (
        <button
          type="button"
          className={btnClass}
          onClick={(e) => {
            e.stopPropagation();
            onPagarReceber(l);
          }}
        >
          {isReceita ? 'Receber' : 'Pagar'}
        </button>
      )}
      {concluido && l.id && (
        <button
          type="button"
          className={`${btnClass} text-amber-700`}
          onClick={(e) => {
            e.stopPropagation();
            onReverter(l);
          }}
        >
          Reverter
        </button>
      )}
      <button
        type="button"
        className={btnClass}
        onClick={(e) => {
          e.stopPropagation();
          onEdit(l);
        }}
      >
        Editar
      </button>
      {l.status !== 'cancelada' && (
        <button
          type="button"
          className={`${btnClass} text-red-600`}
          onClick={(e) => {
            e.stopPropagation();
            onCancel(l);
          }}
        >
          Cancelar
        </button>
      )}
    </div>
  );
}

type Props = {
  lancamento: Lancamento;
  selected?: boolean;
  onSelect?: (l: Lancamento) => void;
  onPagarReceber: (l: Lancamento) => void;
  onReverter: (l: Lancamento) => void;
  onCancel: (l: Lancamento) => void;
  onEdit: (l: Lancamento) => void;
};

export function LancamentoRow({
  lancamento,
  selected = false,
  onSelect,
  onPagarReceber,
  onReverter,
  onCancel,
  onEdit,
}: Props) {
  const l = lancamento;
  const isReceita = l.tipo === 'receita';
  const ehRecorrente = !!l.recorrencia_id;
  const concluido = lancamentoConcluido(l.status);
  const real = valorRealizadoLancamento(l);
  const difere = concluido && l.valor_realizado != null && l.valor_realizado !== l.valor;

  return (
    <tr
      className={`border-t border-slate-100 transition-colors ${
        selected ? 'bg-sky-50 ring-1 ring-inset ring-sky-200' : ehRecorrente ? 'bg-violet-50/40' : ''
      }`}
    >
      <td className="px-3 py-3 w-10">
        {onSelect && (
          <input
            type="checkbox"
            checked={selected}
            onChange={() => onSelect(l)}
            className="rounded border-slate-300 text-[#1A1D26] focus:ring-slate-400"
            aria-label={`Selecionar ${l.descricao}`}
          />
        )}
      </td>
      <td className="px-4 py-3">
        <p className="font-medium text-sm">{l.descricao}</p>
        <div className="flex flex-wrap items-center gap-1.5 mt-0.5">
          {ehRecorrente && <span className="text-[11px] text-violet-600">Recorrente</span>}
          {l.categoria_nome && (
            <span
              className="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600"
              style={l.categoria_cor ? { backgroundColor: `${l.categoria_cor}22`, color: l.categoria_cor } : undefined}
            >
              {l.categoria_nome}
            </span>
          )}
        </div>
      </td>
      <td className="px-4 py-3 text-sm text-slate-600">
        <p>{new Date(l.data_vencimento + 'T12:00:00').toLocaleDateString('pt-BR')}</p>
        {concluido && l.data_efetivacao && (
          <p className="text-[11px] text-emerald-700">
            Efetivado {new Date(l.data_efetivacao + 'T12:00:00').toLocaleDateString('pt-BR')}
          </p>
        )}
      </td>
      <td className="px-4 py-3 text-sm">
        {concluido ? (
          <div>
            <p className="text-xs text-slate-500">Prev. {formatBRL(l.valor)}</p>
            <p
              className={`font-semibold ${isReceita ? 'text-emerald-600' : 'text-red-600'} ${difere ? 'underline decoration-amber-400 decoration-2' : ''}`}
            >
              Real {isReceita ? '+' : '−'} {formatBRL(real)}
            </p>
          </div>
        ) : (
          <p className={`font-semibold ${isReceita ? 'text-emerald-600' : 'text-red-600'}`}>
            {isReceita ? '+' : '−'} {formatBRL(l.valor)}
          </p>
        )}
      </td>
      <td className="px-4 py-3">
        <StatusBadge status={l.status} />
      </td>
      <td className="px-4 py-3">
        <LancamentoActions
          l={l}
          concluido={concluido}
          isReceita={isReceita}
          onPagarReceber={onPagarReceber}
          onReverter={onReverter}
          onCancel={onCancel}
          onEdit={onEdit}
        />
      </td>
    </tr>
  );
}
