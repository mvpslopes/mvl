import { formatBRL } from '../../lib/financeFormat';
import { maxAbsSemana, type ModoSemana, type SemanaResumo } from '../../lib/financeSemanas';

type Props = {
  semanas: SemanaResumo[];
  modo: ModoSemana;
  onModoChange: (modo: ModoSemana) => void;
  semanaAtiva: number | null;
  onSemanaClick: (index: number | null) => void;
};

export default function FinMesSemanas({ semanas, modo, onModoChange, semanaAtiva, onSemanaClick }: Props) {
  const maxVal = maxAbsSemana(semanas);

  return (
    <div className="panel-card mb-6">
      <div className="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
          <h3 className="font-semibold text-sm">Linha do tempo — saldo por semana</h3>
          <p className="text-xs text-slate-500">
            Clique em uma semana para filtrar os lançamentos abaixo
            {semanaAtiva !== null && (
              <button type="button" className="ml-2 text-violet-700 underline" onClick={() => onSemanaClick(null)}>
                Limpar filtro
              </button>
            )}
          </p>
        </div>
        <div className="flex rounded-lg border border-slate-200 p-0.5 bg-slate-50">
          {(['previsto', 'realizado'] as const).map((m) => (
            <button
              key={m}
              type="button"
              onClick={() => onModoChange(m)}
              className={`px-3 py-1.5 rounded-md text-xs font-medium transition-colors ${
                modo === m ? 'bg-white shadow-sm text-[#1A1D26]' : 'text-slate-500'
              }`}
            >
              {m === 'previsto' ? 'Previsto' : 'Realizado'}
            </button>
          ))}
        </div>
      </div>

      <div className="flex sm:grid sm:grid-cols-3 lg:grid-cols-5 gap-2 sm:gap-3 mb-4 sm:mb-5 overflow-x-auto snap-x snap-mandatory pb-1 sm:overflow-visible sm:snap-none">
        {semanas.map((s) => {
          const ativa = semanaAtiva === s.index;
          return (
            <button
              key={s.index}
              type="button"
              onClick={() => onSemanaClick(ativa ? null : s.index)}
              className={`rounded-xl border p-3 text-left transition-all snap-start min-w-[140px] sm:min-w-0 ${
                ativa
                  ? 'border-violet-400 bg-violet-50 ring-2 ring-violet-200'
                  : 'border-slate-200 bg-slate-50/50 hover:border-slate-300'
              }`}
            >
              <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                Semana {s.index + 1}
              </p>
              <p className="text-xs text-slate-600 mb-2">Dia {s.label}</p>
              <div className="text-xs space-y-0.5">
                <p className="text-emerald-700">+ {formatBRL(s.receitas)}</p>
                <p className="text-red-600">− {formatBRL(s.despesas)}</p>
              </div>
              <div className="pt-2 border-t border-slate-200/80 mt-2">
                <p className="text-[10px] text-slate-500">Acumulado no mês</p>
                <p
                  className={`text-sm font-bold ${
                    s.saldoAcumulado >= 0 ? 'text-emerald-600' : 'text-red-600'
                  }`}
                >
                  {formatBRL(s.saldoAcumulado)}
                </p>
              </div>
            </button>
          );
        })}
      </div>

      <div className="space-y-3">
        <p className="text-xs font-medium text-slate-500 uppercase">Fluxo semanal</p>
        {semanas.map((s) => (
          <div key={`chart-${s.index}`} className="flex items-center gap-3 text-xs">
            <span className="w-14 shrink-0 text-slate-500">S{s.index + 1}</span>
            <div className="flex-1 flex items-center gap-1 h-6">
              {s.receitas > 0 && (
                <div
                  className="h-full bg-emerald-400/80 rounded-sm min-w-[2px]"
                  style={{ width: `${(s.receitas / maxVal) * 45}%` }}
                  title={`Receitas ${formatBRL(s.receitas)}`}
                />
              )}
              {s.despesas > 0 && (
                <div
                  className="h-full bg-red-400/80 rounded-sm min-w-[2px]"
                  style={{ width: `${(s.despesas / maxVal) * 45}%` }}
                  title={`Despesas ${formatBRL(s.despesas)}`}
                />
              )}
            </div>
            <span
              className={`w-20 text-right font-medium ${
                s.saldoSemana >= 0 ? 'text-emerald-700' : 'text-red-600'
              }`}
            >
              {formatBRL(s.saldoSemana)}
            </span>
          </div>
        ))}
        <div className="flex gap-4 text-[10px] text-slate-500">
          <span className="flex items-center gap-1">
            <span className="w-3 h-2 bg-emerald-400/80 rounded-sm" /> Receitas
          </span>
          <span className="flex items-center gap-1">
            <span className="w-3 h-2 bg-red-400/80 rounded-sm" /> Despesas
          </span>
        </div>
      </div>
    </div>
  );
}
