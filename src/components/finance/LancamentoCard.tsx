import type { Lancamento } from '../../types/financeiro';
import { formatBRL, lancamentoConcluido, valorRealizadoLancamento } from '../../lib/financeFormat';
import { LancamentoActions, StatusBadge } from './LancamentoRow';

type Props = {
  lancamento: Lancamento;
  selected?: boolean;
  onSelect?: (l: Lancamento) => void;
  onPagarReceber: (l: Lancamento) => void;
  onReverter: (l: Lancamento) => void;
  onCancel: (l: Lancamento) => void;
  onEdit: (l: Lancamento) => void;
};

export default function LancamentoCard({
  lancamento: l,
  selected = false,
  onSelect,
  onPagarReceber,
  onReverter,
  onCancel,
  onEdit,
}: Props) {
  const isReceita = l.tipo === 'receita';
  const ehRecorrente = !!l.recorrencia_id;
  const concluido = lancamentoConcluido(l.status);
  const real = valorRealizadoLancamento(l);
  const difere = concluido && l.valor_realizado != null && l.valor_realizado !== l.valor;

  return (
    <article
      className={`rounded-xl border p-3 transition-colors ${
        selected
          ? 'border-sky-300 bg-sky-50 ring-1 ring-sky-200'
          : ehRecorrente
            ? 'border-violet-100 bg-violet-50/40'
            : 'border-slate-100 bg-white'
      }`}
    >
      <div className="flex gap-3">
        {onSelect && (
          <input
            type="checkbox"
            checked={selected}
            onChange={() => onSelect(l)}
            className="mt-1 rounded border-slate-300 text-[#1A1D26] focus:ring-slate-400 shrink-0"
            aria-label={`Selecionar ${l.descricao}`}
          />
        )}
        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between gap-2 mb-1">
            <p className="font-medium text-sm leading-snug">{l.descricao}</p>
            <StatusBadge status={l.status} />
          </div>

          <div className="flex flex-wrap items-center gap-1.5 mb-2">
            {ehRecorrente && <span className="text-[10px] text-violet-600 font-medium">Recorrente</span>}
            {l.categoria_nome && (
              <span
                className="inline-flex text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600"
                style={
                  l.categoria_cor ? { backgroundColor: `${l.categoria_cor}22`, color: l.categoria_cor } : undefined
                }
              >
                {l.categoria_nome}
              </span>
            )}
          </div>

          <div className="flex flex-wrap justify-between gap-x-4 gap-y-1 text-xs text-slate-500 mb-2">
            <span>
              Venc. {new Date(l.data_vencimento + 'T12:00:00').toLocaleDateString('pt-BR')}
            </span>
            {concluido && l.data_efetivacao && (
              <span className="text-emerald-700">
                Efetivado {new Date(l.data_efetivacao + 'T12:00:00').toLocaleDateString('pt-BR')}
              </span>
            )}
          </div>

          <div className="mb-3">
            {concluido ? (
              <div className="text-sm">
                <span className="text-xs text-slate-500">Prev. {formatBRL(l.valor)} · </span>
                <span
                  className={`font-semibold ${isReceita ? 'text-emerald-600' : 'text-red-600'} ${difere ? 'underline decoration-amber-400 decoration-2' : ''}`}
                >
                  Real {isReceita ? '+' : '−'} {formatBRL(real)}
                </span>
              </div>
            ) : (
              <p className={`text-sm font-semibold ${isReceita ? 'text-emerald-600' : 'text-red-600'}`}>
                {isReceita ? '+' : '−'} {formatBRL(l.valor)}
              </p>
            )}
          </div>

          <LancamentoActions
            l={l}
            concluido={concluido}
            isReceita={isReceita}
            layout="card"
            onPagarReceber={onPagarReceber}
            onReverter={onReverter}
            onCancel={onCancel}
            onEdit={onEdit}
          />
        </div>
      </div>
    </article>
  );
}
