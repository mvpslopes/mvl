import { ArrowDownCircle, ArrowUpCircle } from 'lucide-react';
import type { Lancamento } from '../../types/financeiro';
import { formatBRL, lancamentoConcluido, valorRealizadoLancamento } from '../../lib/financeFormat';
import { LancamentoRow } from './LancamentoRow';
import LancamentoCard from './LancamentoCard';

function lancamentoKey(l: Lancamento): string {
  return l.id != null ? `id-${l.id}` : `rec-${l.recorrencia_id}`;
}

function subtotalSecao(itens: Lancamento[]) {
  let previsto = 0;
  let realizado = 0;
  for (const l of itens) {
    if (l.status === 'cancelada') continue;
    previsto += l.valor;
    if (lancamentoConcluido(l.status)) realizado += valorRealizadoLancamento(l);
  }
  return { previsto, realizado };
}

type Props = {
  tipo: 'receita' | 'despesa';
  itens: Lancamento[];
  selecionados: Set<string>;
  onToggleSecao: (itens: Lancamento[]) => void;
  onSelect: (l: Lancamento) => void;
  onPagarReceber: (l: Lancamento) => void;
  onReverter: (l: Lancamento) => void;
  onCancel: (l: Lancamento) => void;
  onEdit: (l: Lancamento) => void;
};

export default function FinMesSecao({
  tipo,
  itens,
  selecionados,
  onToggleSecao,
  onSelect,
  onPagarReceber,
  onReverter,
  onCancel,
  onEdit,
}: Props) {
  const isReceita = tipo === 'receita';
  const titulo = isReceita ? 'Receitas' : 'Despesas';
  const Icon = isReceita ? ArrowUpCircle : ArrowDownCircle;
  const { previsto, realizado } = subtotalSecao(itens);
  const todosMarcados = itens.length > 0 && itens.every((l) => selecionados.has(lancamentoKey(l)));

  const border = isReceita ? 'border-emerald-200' : 'border-red-200';
  const headerBg = isReceita ? 'bg-emerald-50/80' : 'bg-red-50/80';
  const accent = isReceita ? 'text-emerald-700' : 'text-red-700';
  const iconColor = isReceita ? 'text-emerald-600' : 'text-red-600';

  return (
    <section className={`panel-card overflow-hidden !p-0 border ${border}`}>
      <div className={`px-4 sm:px-5 py-3 sm:py-4 border-b ${border} ${headerBg} flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-2 sm:gap-3`}>
        <div className="flex items-center gap-3">
          <Icon size={22} className={iconColor} strokeWidth={1.75} />
          <div>
            <h3 className={`font-semibold ${accent}`}>{titulo}</h3>
            <p className="text-xs text-slate-500">
              {itens.length} {itens.length === 1 ? 'lançamento' : 'lançamentos'}
            </p>
          </div>
        </div>
        <div className="flex flex-col sm:flex-row flex-wrap gap-1 sm:gap-x-5 gap-y-1 text-xs sm:text-sm">
          <span className="text-slate-600">
            Previsto: <strong className={accent}>{formatBRL(previsto)}</strong>
          </span>
          <span className="text-slate-600">
            Realizado: <strong className={accent}>{formatBRL(realizado)}</strong>
          </span>
        </div>
      </div>

      {itens.length === 0 ? (
        <p className="p-6 text-sm text-slate-500 text-center">
          Nenhuma {isReceita ? 'receita' : 'despesa'} neste mês.
        </p>
      ) : (
        <>
          <div className="md:hidden p-3 space-y-2">
            {itens.map((l, i) => (
              <LancamentoCard
                key={l.id ?? `p-${l.recorrencia_id}-${i}`}
                lancamento={l}
                selected={selecionados.has(lancamentoKey(l))}
                onSelect={onSelect}
                onPagarReceber={onPagarReceber}
                onReverter={onReverter}
                onCancel={onCancel}
                onEdit={onEdit}
              />
            ))}
          </div>
          <div className="hidden md:block overflow-x-auto">
            <table className="w-full text-sm">
            <thead>
              <tr className="bg-slate-50/80 text-left border-b border-slate-100">
                <th className="px-3 py-3 w-10">
                  <input
                    type="checkbox"
                    checked={todosMarcados}
                    onChange={() => onToggleSecao(itens)}
                    className="rounded border-slate-300 text-[#1A1D26] focus:ring-slate-400"
                    aria-label={`Selecionar todas as ${titulo.toLowerCase()}`}
                  />
                </th>
                <th className="px-4 py-3">Descrição</th>
                <th className="px-4 py-3">Vencimento / Efetivação</th>
                <th className="px-4 py-3">Previsto / Real</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody>
              {itens.map((l, i) => (
                <LancamentoRow
                  key={l.id ?? `p-${l.recorrencia_id}-${i}`}
                  lancamento={l}
                  selected={selecionados.has(lancamentoKey(l))}
                  onSelect={onSelect}
                  onPagarReceber={onPagarReceber}
                  onReverter={onReverter}
                  onCancel={onCancel}
                  onEdit={onEdit}
                />
              ))}
            </tbody>
          </table>
          </div>
        </>
      )}
    </section>
  );
}

export { lancamentoKey };
