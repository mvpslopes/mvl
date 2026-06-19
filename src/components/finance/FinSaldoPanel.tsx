import { useEffect, useState } from 'react';
import { finFetch } from '../../lib/financeApi';
import type { FinConfig } from '../../types/financeiro';

export default function FinSaldoPanel() {
  const [saldo, setSaldo] = useState('');
  const [data, setData] = useState('');
  const [msg, setMsg] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      try {
        const res = await finFetch<{ config: FinConfig }>('/config.php');
        setSaldo(String(res.config.saldo_referencia));
        setData(res.config.data_referencia);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const salvar = async () => {
    setMsg('');
    try {
      await finFetch('/config.php', {
        method: 'PUT',
        body: { saldo_referencia: parseFloat(saldo), data_referencia: data },
      });
      setMsg('Salvo com sucesso!');
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro');
    }
  };

  if (loading) return <p className="text-slate-500">Carregando…</p>;

  return (
    <div className="panel-card max-w-md space-y-4">
      <p className="text-sm text-slate-500">Saldo de referência para calcular o acumulado nas projeções.</p>
      <div>
        <label className="block text-sm font-medium mb-1.5">Saldo (R$)</label>
        <input type="number" step="0.01" className="panel-input" value={saldo} onChange={(e) => setSaldo(e.target.value)} />
      </div>
      <div>
        <label className="block text-sm font-medium mb-1.5">Na data</label>
        <input type="date" className="panel-input" value={data} onChange={(e) => setData(e.target.value)} />
      </div>
      {msg && <p className={`text-sm ${msg.includes('sucesso') ? 'text-emerald-700' : 'text-red-600'}`}>{msg}</p>}
      <button type="button" className="panel-btn-primary w-full" onClick={salvar}>Salvar</button>
      <p className="text-xs text-slate-400">
        Primeira vez? Rode <code className="bg-slate-100 px-1 rounded">/api/financas/install.php?key=install</code>
      </p>
    </div>
  );
}
