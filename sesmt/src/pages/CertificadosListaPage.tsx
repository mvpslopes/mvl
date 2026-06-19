import { useCallback, useEffect, useState } from 'react';
import { Eye, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import AppShell from '../components/layout/AppShell';
import { apiFetch, apiFetchBlob } from '../lib/api';
import type { CertificadoListItem } from '../types/certificado';

export default function CertificadosListaPage() {
  const [lista, setLista] = useState<CertificadoListItem[]>([]);
  const [busca, setBusca] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const carregar = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const q = busca.trim() ? `?q=${encodeURIComponent(busca.trim())}` : '';
      const data = await apiFetch<{ certificados: CertificadoListItem[] }>(`/certificados.php${q}`);
      setLista(data.certificados);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao carregar lista.');
    } finally {
      setLoading(false);
    }
  }, [busca]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const handleExcluir = async (item: CertificadoListItem) => {
    if (!confirm(`Excluir certificado ${item.numero} de ${item.colaborador_nome}?`)) return;
    try {
      await apiFetch('/certificados.php', { method: 'DELETE', body: { id: item.id } });
      await carregar();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao excluir.');
    }
  };

  const abrirPdf = async (id: number) => {
    const blob = await apiFetchBlob(`/certificados.php?id=${id}&download=1`);
    const url = URL.createObjectURL(blob);
    window.open(url, '_blank');
    setTimeout(() => URL.revokeObjectURL(url), 60_000);
  };

  return (
    <AppShell title="Certificados">
      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <p className="text-sm text-sesmt-forest/70">
          Certificados salvos, emitidos e rascunhos. Edite e regenere o PDF quando precisar.
        </p>
        <Link to="/certificados/novo" className="sesmt-btn-primary">
          <Plus size={18} />
          Novo certificado
        </Link>
      </div>

      <div className="flex gap-3 mb-6">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-sesmt-forest/40" />
          <input
            className="sesmt-input pl-10"
            placeholder="Buscar por nome, número ou NR…"
            value={busca}
            onChange={(e) => setBusca(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && carregar()}
          />
        </div>
        <button type="button" onClick={carregar} className="sesmt-btn-ghost">
          Buscar
        </button>
      </div>

      {error && (
        <p className="mb-4 text-sm bg-amber-50 border border-amber-200/80 rounded-[10px] px-3 py-2 text-sesmt-forest">
          {error}
        </p>
      )}

      <div className="sesmt-card overflow-hidden p-0">
        {loading ? (
          <p className="p-6 text-sm text-sesmt-forest/60">Carregando…</p>
        ) : lista.length === 0 ? (
          <p className="p-6 text-sm text-sesmt-forest/60">Nenhum certificado encontrado.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-sesmt-page text-left">
                  <th className="px-4 py-3 font-semibold">Número</th>
                  <th className="px-4 py-3 font-semibold">Colaborador</th>
                  <th className="px-4 py-3 font-semibold">Treinamento</th>
                  <th className="px-4 py-3 font-semibold">Data</th>
                  <th className="px-4 py-3 font-semibold">Status</th>
                  <th className="px-4 py-3 font-semibold text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                {lista.map((c) => (
                  <tr key={c.id} className="border-t border-sesmt-forest/8 hover:bg-sesmt-forest/[0.03]">
                    <td className="px-4 py-3 font-mono text-xs">{c.numero}</td>
                    <td className="px-4 py-3">{c.colaborador_nome}</td>
                    <td className="px-4 py-3 text-sesmt-forest/80 max-w-[200px] truncate" title={c.nome_treinamento}>
                      {c.nome_treinamento}
                    </td>
                    <td className="px-4 py-3">{c.data_certificado}</td>
                    <td className="px-4 py-3">
                      <span
                        className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${
                          c.status === 'emitido'
                            ? 'bg-sesmt-accent-muted text-sesmt-accent'
                            : 'bg-amber-50 text-amber-800'
                        }`}
                      >
                        {c.status === 'emitido' ? 'Emitido' : 'Rascunho'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex justify-end gap-1">
                        <Link
                          to={`/certificados/${c.id}/editar`}
                          className="p-2 rounded-lg hover:bg-sesmt-forest/5 text-sesmt-forest"
                          title="Editar"
                        >
                          <Pencil size={16} />
                        </Link>
                        {c.has_pdf && (
                          <button
                            type="button"
                            onClick={() => abrirPdf(c.id)}
                            className="p-2 rounded-lg hover:bg-sesmt-forest/5 text-sesmt-forest"
                            title="Ver PDF"
                          >
                            <Eye size={16} />
                          </button>
                        )}
                        <button
                          type="button"
                          onClick={() => handleExcluir(c)}
                          className="p-2 rounded-lg hover:bg-sesmt-forest/10 text-sesmt-forest/60"
                          title="Excluir"
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </AppShell>
  );
}
