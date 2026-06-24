import { useCallback, useEffect, useMemo, useState } from 'react';
import { Download, FileSpreadsheet } from 'lucide-react';
import { finFetch } from '../../lib/financeApi';
import { exportAnoPdf, exportAnoXlsx } from '../../lib/financeExport';
import { formatBRL, mesCurto } from '../../lib/financeFormat';
import type { ResumoAnoDashboard, ResumoMes } from '../../types/financeiro';
import FinAnoCategorias from './FinAnoCategorias';

type Props = {
  onOpenMes: (ym: string) => void;
};

export default function FinAnoPanel({ onOpenMes }: Props) {
  const ano = new Date().getFullYear();
  const [data, setData] = useState<ResumoAnoDashboard | null>(null);
  const [loading, setLoading] = useState(true);
  const [modo, setModo] = useState<'previsto' | 'realizado'>('previsto');
  const [mesCategorias, setMesCategorias] = useState(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    return `${y}-${m}`;
  });

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const res = await finFetch<{ resumo: ResumoAnoDashboard }>(`/resumo.php?ano=${ano}`);
      setData(res.resumo);
    } finally {
      setLoading(false);
    }
  }, [ano]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const meses = data?.meses ?? [];
  const totais = data?.totais_ano;
  const porCategoriaMes = useMemo(
    () => meses.find((m) => m.mes === mesCategorias)?.por_categoria,
    [meses, mesCategorias]
  );
  const maxBar = useMemo(() => {
    let m = 1;
    for (const mes of meses) {
      const saldo = modo === 'previsto' ? mes.saldo_previsto : mes.saldo_realizado;
      m = Math.max(m, Math.abs(saldo));
    }
    return m;
  }, [meses, modo]);

  return (
    <div className="max-w-5xl">
      <div className="flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-3 mb-4 sm:mb-6">
        <p className="text-sm text-slate-500">Dashboard de {ano} — totais, categorias e evolução mensal.</p>
        <div className="flex flex-wrap items-center gap-2">
          <div className="flex rounded-xl border border-slate-200 p-1 bg-white w-full sm:w-auto">
            {(['previsto', 'realizado'] as const).map((m) => (
              <button
                key={m}
                type="button"
                onClick={() => setModo(m)}
                className={`px-3 py-1.5 rounded-lg text-sm font-medium ${
                  modo === m ? 'bg-[#1A1D26] text-white' : 'text-slate-600'
                }`}
              >
                {m === 'previsto' ? 'Previsto' : 'Realizado'}
              </button>
            ))}
          </div>
          {!loading && meses.length > 0 && (
            <>
              <button type="button" className="panel-btn-ghost text-sm" onClick={() => exportAnoXlsx(ano, meses)}>
                <FileSpreadsheet size={16} /> XLSX
              </button>
              <button type="button" className="panel-btn-ghost text-sm" onClick={() => exportAnoPdf(ano, meses)}>
                <Download size={16} /> PDF
              </button>
            </>
          )}
        </div>
      </div>

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : (
        <>
          {totais && (
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
              <div className="panel-card !p-3 sm:!p-6">
                <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Receitas no ano</p>
                <p className="text-lg sm:text-xl font-bold text-emerald-600">
                  {formatBRL(modo === 'previsto' ? totais.receitas_previstas : totais.receitas_realizadas)}
                </p>
              </div>
              <div className="panel-card !p-3 sm:!p-6">
                <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Despesas no ano</p>
                <p className="text-lg sm:text-xl font-bold text-red-600">
                  {formatBRL(modo === 'previsto' ? totais.despesas_previstas : totais.despesas_realizadas)}
                </p>
              </div>
              <div className="panel-card !p-3 sm:!p-6">
                <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Saldo do ano</p>
                <p
                  className={`text-lg sm:text-xl font-bold ${
                    (modo === 'previsto' ? totais.saldo_previsto : totais.saldo_realizado) >= 0
                      ? 'text-emerald-600'
                      : 'text-red-600'
                  }`}
                >
                  {formatBRL(modo === 'previsto' ? totais.saldo_previsto : totais.saldo_realizado)}
                </p>
              </div>
              <div className="panel-card !p-3 sm:!p-6 col-span-2 lg:col-span-1">
                <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Melhor / pior mês</p>
                <p className="text-sm mt-1">
                  {data?.melhor_mes && (
                    <span className="text-emerald-700 font-medium">
                      ↑ {mesCurto(data.melhor_mes.mes)}{' '}
                      {formatBRL(modo === 'previsto' ? data.melhor_mes.saldo_previsto : data.melhor_mes.saldo_realizado)}
                    </span>
                  )}
                </p>
                <p className="text-sm">
                  {data?.pior_mes && (
                    <span className="text-red-600 font-medium">
                      ↓ {mesCurto(data.pior_mes.mes)}{' '}
                      {formatBRL(modo === 'previsto' ? data.pior_mes.saldo_previsto : data.pior_mes.saldo_realizado)}
                    </span>
                  )}
                </p>
              </div>
            </div>
          )}

          <div className="panel-card mb-6">
            <h3 className="font-semibold text-sm mb-4">Saldo mensal — {modo === 'previsto' ? 'previsto' : 'realizado'}</h3>
            <p className="text-xs text-slate-500 mb-3">Clique em um mês para ver o resumo por categoria abaixo.</p>
            <div className="space-y-2">
              {meses.map((m) => {
                const saldo = modo === 'previsto' ? m.saldo_previsto : m.saldo_realizado;
                const pct = (Math.abs(saldo) / maxBar) * 100;
                return (
                  <button
                    key={m.mes}
                    type="button"
                    onClick={() => setMesCategorias(m.mes)}
                    className={`w-full flex items-center gap-3 text-left group rounded-lg px-1 -mx-1 transition-colors ${
                      mesCategorias === m.mes ? 'bg-violet-50' : 'hover:bg-slate-50'
                    }`}
                  >
                    <span className="w-10 text-xs text-slate-500 shrink-0">{mesCurto(m.mes)}</span>
                    <div className="flex-1 h-5 bg-slate-100 rounded-md overflow-hidden relative">
                      <div
                        className={`h-full rounded-md transition-all ${
                          saldo >= 0 ? 'bg-emerald-400/80' : 'bg-red-400/80'
                        }`}
                        style={{ width: `${Math.max(pct, saldo !== 0 ? 4 : 0)}%` }}
                      />
                    </div>
                    <span
                      className={`w-20 sm:w-24 text-right text-xs sm:text-sm font-medium shrink-0 group-hover:underline ${
                        saldo >= 0 ? 'text-emerald-700' : 'text-red-600'
                      }`}
                    >
                      {formatBRL(saldo)}
                    </span>
                  </button>
                );
              })}
            </div>
          </div>

          {porCategoriaMes && (
            <div className="mb-6">
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <h3 className="font-semibold text-sm">Por categoria no mês</h3>
                <select
                  className="panel-input w-full sm:w-auto sm:min-w-[10rem] text-sm"
                  value={mesCategorias}
                  onChange={(e) => setMesCategorias(e.target.value)}
                >
                  {meses.map((m) => (
                    <option key={m.mes} value={m.mes}>
                      {mesCurto(m.mes)}/{m.mes.slice(0, 4)}
                    </option>
                  ))}
                </select>
              </div>
              <div className="grid md:grid-cols-2 gap-4 sm:gap-6">
                <FinAnoCategorias
                  titulo={`Receitas — ${mesCurto(mesCategorias)} (${modo})`}
                  itens={porCategoriaMes.receitas}
                  modo={modo}
                  corBarra="#34d399"
                />
                <FinAnoCategorias
                  titulo={`Despesas — ${mesCurto(mesCategorias)} (${modo})`}
                  itens={porCategoriaMes.despesas}
                  modo={modo}
                  corBarra="#f87171"
                />
              </div>
            </div>
          )}

          {data?.por_categoria && (
            <div className="mb-6">
              <h3 className="font-semibold text-sm mb-4">Por categoria no ano</h3>
              <div className="grid md:grid-cols-2 gap-4 sm:gap-6">
              <FinAnoCategorias
                titulo={`Receitas por categoria — ${modo}`}
                itens={data.por_categoria.receitas}
                modo={modo}
                corBarra="#34d399"
              />
              <FinAnoCategorias
                titulo={`Despesas por categoria — ${modo}`}
                itens={data.por_categoria.despesas}
                modo={modo}
                corBarra="#f87171"
              />
              </div>
            </div>
          )}

          <div className="hidden md:block panel-card overflow-hidden !p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[520px]">
                <thead>
                  <tr className="bg-slate-50 text-left">
                    <th className="px-4 py-3 font-semibold">Mês</th>
                    <th className="px-4 py-3 font-semibold text-emerald-700">Receitas</th>
                    <th className="px-4 py-3 font-semibold text-red-600">Despesas</th>
                    <th className="px-4 py-3 font-semibold">Saldo mês</th>
                    {modo === 'previsto' && <th className="px-4 py-3 font-semibold">Saldo acumulado</th>}
                  </tr>
                </thead>
                <tbody>
                  {meses.map((m: ResumoMes) => {
                    const rec = modo === 'previsto' ? m.receitas_previstas : m.receitas_realizadas;
                    const desp = modo === 'previsto' ? m.despesas_previstas : m.despesas_realizadas;
                    const saldo = modo === 'previsto' ? m.saldo_previsto : m.saldo_realizado;
                    const acum = m.saldo_acumulado_previsto;
                    return (
                      <tr key={m.mes} className="border-t border-slate-100 hover:bg-slate-50/80">
                        <td className="px-4 py-3">
                          <button
                            type="button"
                            onClick={() => onOpenMes(m.mes)}
                            className="font-medium text-violet-700 hover:underline"
                          >
                            {mesCurto(m.mes)}/{m.mes.slice(0, 4)}
                          </button>
                        </td>
                        <td className="px-4 py-3 text-emerald-700">{formatBRL(rec)}</td>
                        <td className="px-4 py-3 text-red-600">{formatBRL(desp)}</td>
                        <td className={`px-4 py-3 font-medium ${saldo >= 0 ? 'text-emerald-700' : 'text-red-600'}`}>
                          {formatBRL(saldo)}
                        </td>
                        {modo === 'previsto' && (
                          <td className={`px-4 py-3 font-semibold ${(acum ?? 0) < 0 ? 'text-red-600' : ''}`}>
                            {formatBRL(acum ?? 0)}
                          </td>
                        )}
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>

          <div className="md:hidden space-y-2">
            {meses.map((m: ResumoMes) => {
              const rec = modo === 'previsto' ? m.receitas_previstas : m.receitas_realizadas;
              const desp = modo === 'previsto' ? m.despesas_previstas : m.despesas_realizadas;
              const saldo = modo === 'previsto' ? m.saldo_previsto : m.saldo_realizado;
              return (
                <button
                  key={m.mes}
                  type="button"
                  onClick={() => onOpenMes(m.mes)}
                  className="panel-card !p-3 w-full text-left hover:border-violet-200 transition-colors"
                >
                  <div className="flex justify-between items-center mb-2">
                    <span className="font-semibold text-violet-700">
                      {mesCurto(m.mes)}/{m.mes.slice(0, 4)}
                    </span>
                    <span className={`font-bold text-sm ${saldo >= 0 ? 'text-emerald-700' : 'text-red-600'}`}>
                      {formatBRL(saldo)}
                    </span>
                  </div>
                  <div className="grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-slate-600">
                    <span className="text-emerald-700">Rec. {formatBRL(rec)}</span>
                    <span className="text-red-600">Desp. {formatBRL(desp)}</span>
                    {modo === 'previsto' && (
                      <span className="col-span-2 text-slate-500">
                        Acumulado {formatBRL(m.saldo_acumulado_previsto ?? 0)}
                      </span>
                    )}
                  </div>
                </button>
              );
            })}
          </div>
        </>
      )}
    </div>
  );
}
