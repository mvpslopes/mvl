import { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import type { Lancamento } from '../../types/financeiro';
import { formatBRL } from '../../lib/financeFormat';

type Props = {
  itens: Lancamento[];
};

function diasAtraso(dataVencimento: string): number {
  const hoje = new Date();
  hoje.setHours(12, 0, 0, 0);
  const venc = new Date(`${dataVencimento}T12:00:00`);
  const diff = Math.floor((hoje.getTime() - venc.getTime()) / (1000 * 60 * 60 * 24));
  return Math.max(0, diff);
}

export default function FinMesVencimentos({ itens }: Props) {
  const [aberto, setAberto] = useState(false);

  if (itens.length === 0) return null;

  const vencidas = itens.filter((l) => l.alerta_vencimento === 'vencida');
  const proximas = itens.filter((l) => l.alerta_vencimento !== 'vencida');

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
          <p className="text-sm font-semibold text-amber-900">Vencimentos e atrasados</p>
          {!aberto && (
            <p className="text-xs text-amber-900/80 mt-1">
              {vencidas.length > 0 && (
                <>
                  <strong className="text-red-700">{vencidas.length} vencida{vencidas.length !== 1 ? 's' : ''}</strong>
                  {proximas.length > 0 && ' · '}
                </>
              )}
              {proximas.length > 0 && (
                <>
                  {proximas.length} nos próximos 7 dias
                </>
              )}
              {' · '}
              Saldo{' '}
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
            <span>
              Receitas: <strong>{formatBRL(rec)}</strong>
            </span>
            <span>
              Despesas: <strong>{formatBRL(desp)}</strong>
            </span>
            <span>
              Saldo:{' '}
              <strong className={saldo >= 0 ? 'text-emerald-700' : 'text-red-600'}>{formatBRL(saldo)}</strong>
            </span>
          </div>
          <ul className="space-y-1.5 text-sm">
            {itens.map((l, i) => {
              const ehVencida = l.alerta_vencimento === 'vencida';
              const atraso = ehVencida ? diasAtraso(l.data_vencimento) : 0;

              return (
                <li
                  key={l.id ?? `v-${i}`}
                  className={`flex justify-between gap-3 rounded-lg px-2 py-1.5 -mx-2 ${
                    ehVencida ? 'bg-red-50/80' : ''
                  }`}
                >
                  <span className="min-w-0">
                    <span className="inline-flex flex-wrap items-center gap-1.5">
                      <span className={ehVencida ? 'text-red-700 font-medium' : 'text-slate-500'}>
                        {new Date(l.data_vencimento + 'T12:00:00').toLocaleDateString('pt-BR')}
                      </span>
                      {ehVencida ? (
                        <span className="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-red-100 text-red-700">
                          Vencida{atraso > 0 ? ` · ${atraso}d` : ''}
                        </span>
                      ) : (
                        <span className="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800">
                          Próxima
                        </span>
                      )}
                    </span>
                    <span className="block break-words mt-0.5">{l.descricao}</span>
                    {l.mes_referencia && ehVencida && (
                      <span className="text-[10px] text-slate-400">
                        Mês ref. {l.mes_referencia.slice(5)}/{l.mes_referencia.slice(0, 4)}
                      </span>
                    )}
                  </span>
                  <span
                    className={`shrink-0 font-medium ${l.tipo === 'receita' ? 'text-emerald-700' : 'text-red-600'}`}
                  >
                    {formatBRL(l.valor)}
                  </span>
                </li>
              );
            })}
          </ul>
        </div>
      )}
    </div>
  );
}
