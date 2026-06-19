import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import AppShell from '../components/layout/AppShell';
import { apiFetch } from '../lib/api';
import type { ResumoMes } from '../types/financeiro';
import { formatBRL, mesCurto } from '../lib/format';

export default function DashboardPage() {
  const ano = new Date().getFullYear();
  const [meses, setMeses] = useState<ResumoMes[]>([]);
  const [loading, setLoading] = useState(true);
  const [modo, setModo] = useState<'previsto' | 'realizado'>('previsto');

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiFetch<{ resumo: { meses: ResumoMes[] } }>(`/resumo.php?ano=${ano}`);
      setMeses(data.resumo.meses);
    } finally {
      setLoading(false);
    }
  }, [ano]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  return (
    <AppShell title={`Visão ${ano}`}>
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <p className="text-sm text-slate-500">Receitas, despesas e saldo acumulado por mês.</p>
        <div className="flex rounded-xl border border-slate-200 p-1 bg-white">
          {(['previsto', 'realizado'] as const).map((m) => (
            <button
              key={m}
              type="button"
              onClick={() => setModo(m)}
              className={`px-3 py-1.5 rounded-lg text-sm font-medium capitalize ${
                modo === m ? 'bg-ink text-white' : 'text-slate-600'
              }`}
            >
              {m === 'previsto' ? 'Previsto' : 'Realizado'}
            </button>
          ))}
        </div>
      </div>

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : (
        <div className="fin-card overflow-hidden !p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-slate-50 text-left">
                  <th className="px-4 py-3 font-semibold">Mês</th>
                  <th className="px-4 py-3 font-semibold text-emerald-700">Receitas</th>
                  <th className="px-4 py-3 font-semibold text-red-600">Despesas</th>
                  <th className="px-4 py-3 font-semibold">Saldo mês</th>
                  <th className="px-4 py-3 font-semibold">Saldo acumulado</th>
                </tr>
              </thead>
              <tbody>
                {meses.map((m) => {
                  const rec =
                    modo === 'previsto' ? m.receitas_previstas : m.receitas_realizadas;
                  const desp =
                    modo === 'previsto' ? m.despesas_previstas : m.despesas_realizadas;
                  const saldo = modo === 'previsto' ? m.saldo_previsto : m.saldo_realizado;
                  const acum =
                    modo === 'previsto'
                      ? m.saldo_acumulado_previsto
                      : m.saldo_acumulado_realizado;
                  const neg = (acum ?? 0) < 0;
                  return (
                    <tr key={m.mes} className="border-t border-slate-100 hover:bg-slate-50/80">
                      <td className="px-4 py-3">
                        <Link to={`/mes/${m.mes}`} className="font-medium text-violet-700 hover:underline">
                          {mesCurto(m.mes)}/{m.mes.slice(0, 4)}
                        </Link>
                      </td>
                      <td className="px-4 py-3 text-emerald-700">{formatBRL(rec)}</td>
                      <td className="px-4 py-3 text-red-600">{formatBRL(desp)}</td>
                      <td className={`px-4 py-3 font-medium ${saldo >= 0 ? 'text-emerald-700' : 'text-red-600'}`}>
                        {formatBRL(saldo)}
                      </td>
                      <td className={`px-4 py-3 font-semibold ${neg ? 'text-red-600' : 'text-ink'}`}>
                        {formatBRL(acum ?? 0)}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </AppShell>
  );
}
