import { useEffect, useState } from 'react';
import AppShell from '../components/layout/AppShell';
import { apiFetch } from '../lib/api';
import { formatBRL } from '../lib/format';
import type { FinConfig } from '../types/financeiro';

export default function ConfigPage() {
  const [config, setConfig] = useState<FinConfig | null>(null);
  const [saldo, setSaldo] = useState('');
  const [data, setData] = useState('');
  const [msg, setMsg] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      try {
        const res = await apiFetch<{ config: FinConfig }>('/config.php');
        setConfig(res.config);
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
      const res = await apiFetch<{ config: FinConfig }>('/config.php', {
        method: 'PUT',
        body: {
          saldo_referencia: parseFloat(saldo),
          data_referencia: data,
        },
      });
      setConfig(res.config);
      setMsg('Salvo com sucesso!');
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao salvar.');
    }
  };

  return (
    <AppShell title="Saldo inicial">
      <p className="text-sm text-slate-500 mb-6 max-w-xl">
        Informe quanto você tem em caixa/conta na data de referência. O saldo acumulado nas projeções parte desse valor.
      </p>

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : (
        <div className="fin-card max-w-md space-y-4">
          <div>
            <label className="fin-label">Saldo na data de referência</label>
            <input
              type="number"
              step="0.01"
              className="fin-input"
              value={saldo}
              onChange={(e) => setSaldo(e.target.value)}
            />
          </div>
          <div>
            <label className="fin-label">Data de referência</label>
            <input type="date" className="fin-input" value={data} onChange={(e) => setData(e.target.value)} />
          </div>
          {config && (
            <p className="text-xs text-slate-400">
              Atual: {formatBRL(config.saldo_referencia)} em{' '}
              {new Date(config.data_referencia + 'T12:00:00').toLocaleDateString('pt-BR')}
            </p>
          )}
          {msg && (
            <p className={`text-sm ${msg.includes('sucesso') ? 'text-emerald-700' : 'text-red-600'}`}>{msg}</p>
          )}
          <button type="button" className="fin-btn w-full" onClick={salvar}>
            Salvar
          </button>
        </div>
      )}
    </AppShell>
  );
}
