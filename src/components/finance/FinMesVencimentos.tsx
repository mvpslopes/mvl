import { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import type { Lancamento } from '../../types/financeiro';
import { formatBRL } from '../../lib/financeFormat';

type Props = {
  itens: Lancamento[];
};

export default function FinMesVencimentos({ itens }: Props) {
  const [aberto, setAberto] = useState(false);

  if (itens.length === 0) return null;

  let rec = 0;
  let desp = 0;
  for (const l of itens) {
    if (l.tipo === 'receita') rec += l.valor;
    else desp += l.valor;
  }
  const saldo = rec - desp;

  return (
    <div className="panel-card mb-4 sm:mb-6 border-amber-200 bg-amber-50/70 !p-3 sm:!p-4">
      <button
        type="button"
        onClick={() => setAberto((v) => !v)}
        className="w-full flex items-start justify-between gap-3 text-left"
        aria-expanded={aberto}
      >
        <div className="min-w-0 flex-1">
          <p className="text-sm font-semibold text-amber-900">Vencimentos nos próximos 7 dias</p>
          {!aberto && (
            <p className="text-xs text-amber-900/80 mt-1">
              {itens.length} {itens.length === 1 ? 'item' : 'itens'} · Saldo{' '}
              <strong className={saldo >= 0 ? 'text-emerald-700' : 'text-red-600'}>{formatBRL(saldo)}</strong>
            </p>
          )}
        </div>
        <ChevronDown
          size={18}
          className={`shrink-0 text-amber-800 transition-transform mt-0.5 ${aberto ? 'rotate-180' : ''}`}
        />
      </button>

      {aberto && (
        <div className="mt-3 pt-3 border-t border-amber-200/80">
          <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs sm:text-sm text-amber-900/90 mb-3">
            <span>Receitas: <strong>{formatBRL(rec)}</strong></span>
            <span>Despesas: <strong>{formatBRL(desp)}</strong></span>
            <span>
              Saldo: <strong className={saldo >= 0 ? 'text-emerald-700' : 'text-red-600'}>{formatBRL(saldo)}</strong>
            </span>
          </div>
          <ul className="space-y-1.5 text-sm">
            {itens.map((l, i) => (
              <li key={l.id ?? `v-${i}`} className="flex justify-between gap-3">
                <span className="min-w-0">
                  <span className="text-slate-500">
                    {new Date(l.data_vencimento + 'T12:00:00').toLocaleDateString('pt-BR')}
                  </span>{' '}
                  <span className="break-words">{l.descricao}</span>
                </span>
                <span
                  className={`shrink-0 font-medium ${l.tipo === 'receita' ? 'text-emerald-700' : 'text-red-600'}`}
                >
                  {formatBRL(l.valor)}
                </span>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
