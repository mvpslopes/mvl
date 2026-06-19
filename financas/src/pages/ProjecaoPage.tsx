import { useCallback, useEffect, useState } from 'react';
import AppShell from '../components/layout/AppShell';
import { apiFetch } from '../lib/api';
import { formatBRL, mesLabel } from '../lib/format';
import type { ResumoMes } from '../types/financeiro';

export default function ProjecaoPage() {
  const [de, setDe] = useState(new Date().toISOString().slice(0, 7));
  const [mesesQtd, setMesesQtd] = useState(6);
  const [modo, setModo] = useState<'previsto' | 'realizado'>('previsto');
  const [linhas, setLinhas] = useState<ResumoMes[]>([]);
  const [loading, setLoading] = useState(true);

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiFetch<{ projecao: { meses: ResumoMes[] } }>(
        `/projecao.php?de=${de}&meses=${mesesQtd}`
      );
      setLinhas(data.projecao.meses);
    } finally {
      setLoading(false);
    }
  }, [de, mesesQtd]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  return (
    <AppShell title="Projeção futura">
      <p className="text-sm text-slate-500 mb-6">
        Cenário mensal com saldo acumulado — o que você teria se as previsões se mantiverem.
      </p>

      <div className="flex flex-wrap gap-4 mb-6">
        <div>
          <label className="fin-label">A partir de</label>
          <input
            type="month"
            className="fin-input w-auto"
            value={de}
            onChange={(e) => setDe(e.target.value)}
          />
        </div>
        <div>
          <label className="fin-label">Meses</label>
          <select className="fin-input w-auto" value={mesesQtd} onChange={(e) => setMesesQtd(Number(e.target.value))}>
            {[3, 6, 12, 18, 24].map((n) => (
              <option key={n} value={n}>
                {n} meses
              </option>
            ))}
          </select>
        </div>
        <div className="flex items-end">
          <div className="flex rounded-xl border border-slate-200 p-1 bg-white">
            {(['previsto', 'realizado'] as const).map((m) => (
              <button
                key={m}
                type="button"
                onClick={() => setModo(m)}
                className={`px-3 py-1.5 rounded-lg text-sm font-medium ${
                  modo === m ? 'bg-ink text-white' : 'text-slate-600'
                }`}
              >
                {m === 'previsto' ? 'Previsto' : 'Realizado'}
              </button>
            ))}
          </div>
        </div>
      </div>

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : (
        <div className="fin-card overflow-hidden !p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-slate-50 text-left">
                <th className="px-4 py-3">Mês</th>
                <th className="px-4 py-3">Receitas</th>
                <th className="px-4 py-3">Despesas</th>
                <th className="px-4 py-3">Saldo mês</th>
                <th className="px-4 py-3">Saldo acumulado</th>
              </tr>
            </thead>
            <tbody>
              {linhas.map((m) => {
                const rec = modo === 'previsto' ? m.receitas_previstas : m.receitas_realizadas;
                const desp = modo === 'previsto' ? m.despesas_previstas : m.despesas_realizadas;
                const saldo = modo === 'previsto' ? m.saldo_previsto : m.saldo_realizado;
                const acum =
                  modo === 'previsto' ? m.saldo_acumulado_previsto : m.saldo_acumulado_realizado;
                return (
                  <tr key={m.mes} className="border-t border-slate-100">
                    <td className="px-4 py-3 font-medium">{mesLabel(m.mes)}</td>
                    <td className="px-4 py-3 text-emerald-700">{formatBRL(rec)}</td>
                    <td className="px-4 py-3 text-red-600">{formatBRL(desp)}</td>
                    <td className={`px-4 py-3 ${saldo >= 0 ? 'text-emerald-700' : 'text-red-600'}`}>
                      {formatBRL(saldo)}
                    </td>
                    <td
                      className={`px-4 py-3 font-semibold ${(acum ?? 0) < 0 ? 'text-red-600' : 'text-ink'}`}
                    >
                      {formatBRL(acum ?? 0)}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </AppShell>
  );
}
