import { useCallback, useEffect, useState } from 'react';
import { ideiasFetch } from '../../lib/ideiasApi';
import type { IdeiaGrupoKeyword } from '../../types/ideias';

function formatDate(iso: string): string {
  try {
    return new Date(iso.includes('T') ? iso : iso.replace(' ', 'T')).toLocaleDateString('pt-BR');
  } catch {
    return iso;
  }
}

function currentMonth(): string {
  return new Date().toISOString().slice(0, 7);
}

export default function IdeiasKeywordsPanel() {
  const [grupos, setGrupos] = useState<IdeiaGrupoKeyword[]>([]);
  const [loading, setLoading] = useState(true);
  const [mes, setMes] = useState(currentMonth());
  const [q, setQ] = useState('');
  const [msg, setMsg] = useState('');

  const carregar = useCallback(async () => {
    setLoading(true);
    setMsg('');
    try {
      const params = new URLSearchParams({ view: 'keywords' });
      if (mes) params.set('mes', mes);
      if (q.trim()) params.set('q', q.trim());
      const data = await ideiasFetch<{ grupos: IdeiaGrupoKeyword[] }>(`/notas.php?${params}`);
      setGrupos(data.grupos ?? []);
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao carregar');
    } finally {
      setLoading(false);
    }
  }, [mes, q]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  return (
    <div className="max-w-3xl space-y-4">
      <p className="text-sm text-slate-500">
        Observações agrupadas alfabeticamente por palavra-chave — o mesmo padrão do método de repertório: padrões
        aparecem quando as tags se juntam.
      </p>

      <div className="flex flex-col sm:flex-row gap-2">
        <input type="month" className="panel-input w-full sm:w-auto" value={mes} onChange={(e) => setMes(e.target.value)} />
        <button type="button" className="panel-btn-ghost text-sm" onClick={() => setMes(currentMonth())}>
          Mês atual
        </button>
        <button type="button" className="panel-btn-ghost text-sm" onClick={() => setMes('')}>
          Todos
        </button>
        <input
          className="panel-input flex-1"
          placeholder="Buscar no texto…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
      </div>

      {msg && <p className="text-sm text-red-600">{msg}</p>}

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : grupos.length === 0 ? (
        <div className="panel-card text-sm text-slate-500 text-center py-8">
          Nenhuma palavra-chave ainda. Capture ideias com tags na tela Captura.
        </div>
      ) : (
        <div className="space-y-5">
          {grupos.map((g) => (
            <section key={g.keyword.id} className="space-y-2">
              <div className="flex items-baseline gap-2 border-b border-slate-200 pb-1">
                <h2 className="text-sm font-bold uppercase tracking-wide text-slate-800">{g.keyword.nome}</h2>
                <span className="text-xs text-slate-400">{g.ideias.length}</span>
              </div>
              <ul className="space-y-2">
                {g.ideias.map((ideia) => (
                  <li key={`${g.keyword.id}-${ideia.id}`} className="panel-card !p-3">
                    <p className="text-sm text-slate-800 whitespace-pre-wrap">{ideia.texto}</p>
                    <div className="mt-2 flex flex-wrap gap-1.5 items-center">
                      {ideia.keywords
                        .filter((k) => k.id !== g.keyword.id)
                        .map((k) => (
                          <span key={k.id} className="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                            {k.nome}
                          </span>
                        ))}
                      <span className="text-[10px] text-slate-400 ml-auto">{formatDate(ideia.created_at)}</span>
                    </div>
                  </li>
                ))}
              </ul>
            </section>
          ))}
        </div>
      )}
    </div>
  );
}
