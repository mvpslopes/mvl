import { useCallback, useEffect, useState } from 'react';
import { ClipboardList, Download, ExternalLink, MessageCircle, RefreshCw, Trash2 } from 'lucide-react';
import { briefingAdminFetch, downloadBriefingFile } from '../../lib/briefingApi';
import type { Briefing, BriefingStatus } from '../../types/briefing';
import { BRIEFING_STATUS_LABEL } from '../../types/briefing';

function formatDate(iso: string): string {
  try {
    return new Date(iso).toLocaleString('pt-BR');
  } catch {
    return iso;
  }
}

function StatusBadge({ status }: { status: BriefingStatus }) {
  const colors: Record<BriefingStatus, string> = {
    new: 'bg-sky-100 text-sky-800',
    read: 'bg-slate-100 text-slate-700',
    quoted: 'bg-emerald-100 text-emerald-800',
    archived: 'bg-amber-50 text-amber-800',
  };
  return (
    <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-semibold ${colors[status]}`}>
      {BRIEFING_STATUS_LABEL[status]}
    </span>
  );
}

export default function BriefingsPanel() {
  const [lista, setLista] = useState<Briefing[]>([]);
  const [countNew, setCountNew] = useState(0);
  const [filtro, setFiltro] = useState<'all' | BriefingStatus>('all');
  const [loading, setLoading] = useState(true);
  const [selected, setSelected] = useState<Briefing | null>(null);
  const [whatsapp, setWhatsapp] = useState('');
  const [msg, setMsg] = useState('');

  const carregar = useCallback(async () => {
    setLoading(true);
    setMsg('');
    try {
      const q = filtro === 'all' ? '' : `?status=${filtro}`;
      const data = await briefingAdminFetch<{ briefings: Briefing[]; count_new: number }>(`/admin.php${q}`);
      setLista(data.briefings ?? []);
      setCountNew(data.count_new ?? 0);
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao carregar');
    } finally {
      setLoading(false);
    }
  }, [filtro]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const abrir = async (id: string) => {
    setMsg('');
    try {
      const data = await briefingAdminFetch<{ briefing: Briefing; whatsapp_url: string }>(
        `/admin.php?id=${encodeURIComponent(id)}`
      );
      setSelected(data.briefing);
      setWhatsapp(data.whatsapp_url);
      carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro');
    }
  };

  const setStatus = async (id: string, status: BriefingStatus) => {
    try {
      const data = await briefingAdminFetch<{ briefing: Briefing }>('/admin.php', {
        method: 'PUT',
        json: { id, status },
      });
      setSelected(data.briefing);
      carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao atualizar');
    }
  };

  const excluir = async (id: string) => {
    if (!confirm('Excluir este briefing? Essa ação não pode ser desfeita.')) return;
    try {
      await briefingAdminFetch('/admin.php', {
        method: 'DELETE',
        json: { id },
      });
      setSelected(null);
      carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao excluir');
    }
  };

  return (
    <div className="max-w-5xl">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 sm:mb-6">
        <div>
          <p className="text-sm text-slate-500">
            Briefings recebidos pelo formulário público{' '}
            <a href="/briefing" target="_blank" rel="noreferrer" className="text-violet-700 hover:underline inline-flex items-center gap-1">
              /briefing <ExternalLink size={12} />
            </a>
          </p>
          {countNew > 0 && (
            <p className="text-xs font-semibold text-sky-700 mt-1">{countNew} novo(s) sem leitura</p>
          )}
        </div>
        <div className="flex flex-wrap gap-2">
          <select
            className="panel-input py-2 w-auto"
            value={filtro}
            onChange={(e) => setFiltro(e.target.value as typeof filtro)}
          >
            <option value="all">Todos</option>
            <option value="new">Novos</option>
            <option value="read">Lidos</option>
            <option value="quoted">Orçados</option>
            <option value="archived">Arquivados</option>
          </select>
          <button type="button" className="panel-btn-ghost" onClick={carregar}>
            <RefreshCw size={16} /> Atualizar
          </button>
        </div>
      </div>

      {msg && <p className="text-sm text-red-600 mb-3">{msg}</p>}

      {selected ? (
        <div className="space-y-4">
          <button type="button" className="panel-btn-ghost text-sm" onClick={() => setSelected(null)}>
            ← Voltar à lista
          </button>
          <div className="panel-card space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h2 className="text-lg font-bold">{selected.name}</h2>
                <p className="text-sm text-slate-500">{formatDate(selected.created_at)}</p>
              </div>
              <StatusBadge status={selected.status} />
            </div>

            <div className="flex flex-wrap gap-2">
              {(['new', 'read', 'quoted', 'archived'] as BriefingStatus[]).map((s) => (
                <button
                  key={s}
                  type="button"
                  className={`panel-btn-ghost text-xs ${selected.status === s ? 'ring-1 ring-slate-300' : ''}`}
                  onClick={() => setStatus(selected.id, s)}
                >
                  {BRIEFING_STATUS_LABEL[s]}
                </button>
              ))}
              {whatsapp && (
                <a href={whatsapp} target="_blank" rel="noreferrer" className="panel-btn-primary text-xs">
                  <MessageCircle size={14} /> WhatsApp
                </a>
              )}
              <button type="button" className="panel-btn-ghost text-xs text-red-600" onClick={() => excluir(selected.id)}>
                <Trash2 size={14} /> Excluir
              </button>
            </div>

            <div className="grid sm:grid-cols-2 gap-3 text-sm">
              <p>
                <span className="text-slate-500">WhatsApp:</span> {selected.phone || '—'}
              </p>
              <p>
                <span className="text-slate-500">E-mail:</span> {selected.email || '—'}
              </p>
              <p>
                <span className="text-slate-500">Empresa:</span> {selected.company || '—'}
              </p>
              <p>
                <span className="text-slate-500">Tipo:</span> {selected.project_type || '—'}
              </p>
              <p className="sm:col-span-2">
                <span className="text-slate-500">Objetivo:</span> {selected.goal || '—'}
              </p>
              <p className="sm:col-span-2 rounded-xl border border-violet-100 bg-violet-50/60 px-3 py-2">
                <span className="text-violet-700 font-semibold">Domínio desejado:</span>{' '}
                {selected.domain ? (
                  <a
                    href={`https://www.registro.br/tecnologia/ferramentas/whois/?search=${encodeURIComponent(selected.domain)}`}
                    target="_blank"
                    rel="noreferrer"
                    className="font-mono text-violet-900 hover:underline"
                  >
                    {selected.domain}
                  </a>
                ) : (
                  '—'
                )}
              </p>
              <p>
                <span className="text-slate-500">Site atual:</span> {selected.current_url || selected.has_website || '—'}
              </p>
              <p>
                <span className="text-slate-500">Tom:</span> {selected.tone || '—'}
              </p>
            </div>

            {selected.business && (
              <div>
                <p className="text-xs font-semibold uppercase text-slate-500 mb-1">Negócio</p>
                <p className="text-sm whitespace-pre-wrap">{selected.business}</p>
              </div>
            )}

            {selected.pages?.length > 0 && (
              <div>
                <p className="text-xs font-semibold uppercase text-slate-500 mb-2">Páginas</p>
                <div className="flex flex-wrap gap-1.5">
                  {selected.pages.map((p) => (
                    <span key={p} className="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700">
                      {p}
                    </span>
                  ))}
                </div>
              </div>
            )}

            <div>
              <p className="text-xs font-semibold uppercase text-slate-500 mb-2">Cores</p>
              <div className="flex flex-wrap gap-3">
                {[
                  ['Primária', selected.color_primary],
                  ['Secundária', selected.color_secondary],
                  ['Destaque', selected.color_accent],
                ].map(([label, hex]) => (
                  <div key={label} className="flex items-center gap-2 text-sm">
                    <span className="w-8 h-8 rounded-lg border border-slate-200" style={{ backgroundColor: hex }} />
                    <span>
                      {label}
                      <br />
                      <span className="font-mono text-xs text-slate-500">{hex}</span>
                    </span>
                  </div>
                ))}
              </div>
              {(selected.suggest_palette || selected.suggest_fonts) && (
                <p className="text-xs text-slate-500 mt-2">
                  {selected.suggest_palette && 'Sugestão de paleta pedida. '}
                  {selected.suggest_fonts && 'Sugestão de fontes pedida.'}
                </p>
              )}
            </div>

            {(selected.font_heading || selected.font_body || selected.styles?.length > 0) && (
              <div className="grid sm:grid-cols-3 gap-3 text-sm">
                <p>
                  <span className="text-slate-500">Fonte títulos:</span> {selected.font_heading || '—'}
                </p>
                <p>
                  <span className="text-slate-500">Fonte texto:</span> {selected.font_body || '—'}
                </p>
                <p>
                  <span className="text-slate-500">Estilos:</span>{' '}
                  {selected.styles?.length ? selected.styles.join(', ') : '—'}
                </p>
              </div>
            )}

            {(selected.logo_file || selected.brand_file || selected.logo_url || selected.brand_url || selected.photos_url) && (
              <div className="flex flex-wrap gap-2">
                {selected.logo_file && (
                  <button
                    type="button"
                    className="panel-btn-ghost text-sm"
                    onClick={() =>
                      downloadBriefingFile(selected.id, 'logo', selected.logo_file!.original).catch((e) =>
                        setMsg(e.message)
                      )
                    }
                  >
                    <Download size={14} /> Logo ({selected.logo_file.original})
                  </button>
                )}
                {selected.logo_url && (
                  <a
                    href={selected.logo_url}
                    target="_blank"
                    rel="noreferrer"
                    className="panel-btn-ghost text-sm"
                  >
                    <ExternalLink size={14} /> Link do logo
                  </a>
                )}
                {selected.brand_file && (
                  <button
                    type="button"
                    className="panel-btn-ghost text-sm"
                    onClick={() =>
                      downloadBriefingFile(selected.id, 'brand', selected.brand_file!.original).catch((e) =>
                        setMsg(e.message)
                      )
                    }
                  >
                    <Download size={14} /> Brandbook ({selected.brand_file.original})
                  </button>
                )}
                {selected.brand_url && (
                  <a
                    href={selected.brand_url}
                    target="_blank"
                    rel="noreferrer"
                    className="panel-btn-ghost text-sm"
                  >
                    <ExternalLink size={14} /> Link do material
                  </a>
                )}
                {selected.photos_url && (
                  <a
                    href={selected.photos_url}
                    target="_blank"
                    rel="noreferrer"
                    className="panel-btn-ghost text-sm"
                  >
                    <ExternalLink size={14} /> Link de fotos / arquivos
                  </a>
                )}
              </div>
            )}

            {selected.refs?.length > 0 && (
              <div>
                <p className="text-xs font-semibold uppercase text-slate-500 mb-1">Referências</p>
                <ul className="text-sm space-y-1">
                  {selected.refs.map((r) => (
                    <li key={r}>
                      <a href={r} target="_blank" rel="noreferrer" className="text-violet-700 hover:underline break-all">
                        {r}
                      </a>
                    </li>
                  ))}
                </ul>
                {selected.refs_notes && (
                  <p className="text-sm text-slate-600 mt-2 whitespace-pre-wrap">{selected.refs_notes}</p>
                )}
              </div>
            )}

            {selected.notes && (
              <div>
                <p className="text-xs font-semibold uppercase text-slate-500 mb-1">Observações</p>
                <p className="text-sm whitespace-pre-wrap">{selected.notes}</p>
              </div>
            )}
          </div>
        </div>
      ) : loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : lista.length === 0 ? (
        <div className="panel-card text-center py-10 text-slate-500">
          <ClipboardList className="mx-auto mb-3 opacity-40" size={36} />
          <p>Nenhum briefing ainda.</p>
        </div>
      ) : (
        <>
          <div className="md:hidden space-y-2">
            {lista.map((b) => (
              <button
                key={b.id}
                type="button"
                className="panel-card !p-3 w-full text-left"
                onClick={() => abrir(b.id)}
              >
                <div className="flex justify-between gap-2 mb-1">
                  <span className="font-semibold text-sm truncate">{b.name}</span>
                  <StatusBadge status={b.status} />
                </div>
                <p className="text-xs text-slate-500">{b.project_type || '—'}</p>
                {b.domain && <p className="text-xs font-mono text-violet-700 mt-1">{b.domain}</p>}
                <p className="text-[10px] text-slate-400 mt-1">{formatDate(b.created_at)}</p>
              </button>
            ))}
          </div>
          <div className="hidden md:block panel-card overflow-hidden !p-0">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-slate-50 text-left">
                  <th className="px-4 py-3">Data</th>
                  <th className="px-4 py-3">Nome</th>
                  <th className="px-4 py-3">Projeto</th>
                  <th className="px-4 py-3">Domínio</th>
                  <th className="px-4 py-3">Status</th>
                </tr>
              </thead>
              <tbody>
                {lista.map((b) => (
                  <tr
                    key={b.id}
                    className="border-t border-slate-100 hover:bg-slate-50 cursor-pointer"
                    onClick={() => abrir(b.id)}
                  >
                    <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{formatDate(b.created_at)}</td>
                    <td className="px-4 py-3 font-medium">{b.name}</td>
                    <td className="px-4 py-3">{b.project_type || '—'}</td>
                    <td className="px-4 py-3 font-mono text-xs text-violet-700">{b.domain || '—'}</td>
                    <td className="px-4 py-3">
                      <StatusBadge status={b.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      )}
    </div>
  );
}
