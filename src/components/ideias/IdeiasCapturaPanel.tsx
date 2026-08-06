import { FormEvent, KeyboardEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { ChevronDown, Pencil, Plus, Star, Tags, Trash2, X } from 'lucide-react';
import { ideiasFetch } from '../../lib/ideiasApi';
import type { Ideia, IdeiaKeyword } from '../../types/ideias';

function formatDate(iso: string): string {
  try {
    return new Date(iso.includes('T') ? iso : iso.replace(' ', 'T')).toLocaleString('pt-BR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return iso;
  }
}

function parseTags(raw: string): string[] {
  return raw
    .split(/[,;]+/)
    .map((t) => t.trim())
    .filter(Boolean);
}

function currentMonth(): string {
  return new Date().toISOString().slice(0, 7);
}

export default function IdeiasCapturaPanel() {
  const [lista, setLista] = useState<Ideia[]>([]);
  const [allKeywords, setAllKeywords] = useState<IdeiaKeyword[]>([]);
  const [loading, setLoading] = useState(true);
  const [texto, setTexto] = useState('');
  const [tagsInput, setTagsInput] = useState('');
  const [tags, setTags] = useState<string[]>([]);
  const [mes, setMes] = useState('');
  const [q, setQ] = useState('');
  const [msg, setMsg] = useState('');
  const [saving, setSaving] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [editTexto, setEditTexto] = useState('');
  const [editTags, setEditTags] = useState<string[]>([]);
  const [editTagsInput, setEditTagsInput] = useState('');
  const [tagsPickerOpen, setTagsPickerOpen] = useState(false);
  const [generatingTags, setGeneratingTags] = useState(false);

  const carregar = useCallback(async () => {
    setLoading(true);
    setMsg('');
    try {
      const params = new URLSearchParams();
      if (mes) params.set('mes', mes);
      if (q.trim()) params.set('q', q.trim());
      const qs = params.toString() ? `?${params}` : '';
      const [data, kw] = await Promise.all([
        ideiasFetch<{ ideias: Ideia[] }>(`/notas.php${qs}`),
        ideiasFetch<{ keywords: IdeiaKeyword[] }>('/keywords.php'),
      ]);
      setLista(data.ideias ?? []);
      setAllKeywords(kw.keywords ?? []);
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao carregar');
    } finally {
      setLoading(false);
    }
  }, [mes, q]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const tagsDisponiveis = useMemo(() => {
    const used = new Set(tags.map((t) => t.toLowerCase()));
    const filtro = tagsInput.trim().toLowerCase();
    return allKeywords
      .filter((k) => !used.has(k.nome.toLowerCase()))
      .filter((k) => !filtro || k.nome.toLowerCase().includes(filtro))
      .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));
  }, [allKeywords, tags, tagsInput]);

  const addTag = (nome: string) => {
    const t = nome.trim();
    if (!t) return;
    setTags((prev) => (prev.some((p) => p.toLowerCase() === t.toLowerCase()) ? prev : [...prev, t]));
    setTagsInput('');
  };

  const onTagsKey = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      addTag(tagsInput.replace(/,/g, ''));
    } else if (e.key === 'Backspace' && tagsInput === '' && tags.length) {
      setTags((prev) => prev.slice(0, -1));
    }
  };

  const gerarTags = async () => {
    const body = texto.trim();
    if (!body) {
      setMsg('Escreva a ideia antes de gerar as tags.');
      return;
    }
    setGeneratingTags(true);
    setMsg('');
    try {
      const data = await ideiasFetch<{ tags: string[] }>('/sugerir-tags.php', {
        method: 'POST',
        body: {
          texto: body,
          existentes: allKeywords.map((k) => k.nome),
        },
      });
      const sugeridas = data.tags ?? [];
      if (sugeridas.length === 0) {
        setMsg('A IA não sugeriu tags. Tente reformular a frase.');
        return;
      }
      setTags((prev) => {
        const used = new Set(prev.map((t) => t.toLowerCase()));
        const merged = [...prev];
        for (const tag of sugeridas) {
          const key = tag.toLowerCase();
          if (!used.has(key)) {
            used.add(key);
            merged.push(tag);
          }
        }
        return merged;
      });
      setTagsInput('');
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao gerar tags');
    } finally {
      setGeneratingTags(false);
    }
  };

  const capturar = async (e?: FormEvent) => {
    e?.preventDefault();
    const body = texto.trim();
    if (!body) {
      setMsg('Escreva a ideia ou observação.');
      return;
    }
    const fromInput = parseTags(tagsInput);
    const keywords = [...tags, ...fromInput];
    setSaving(true);
    setMsg('');
    try {
      await ideiasFetch('/notas.php', {
        method: 'POST',
        body: { texto: body, keywords },
      });
      setTexto('');
      setTags([]);
      setTagsInput('');
      setTagsPickerOpen(false);
      await carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const abrirEditar = (ideia: Ideia) => {
    setEditId(ideia.id);
    setEditTexto(ideia.texto);
    setEditTags(ideia.keywords.map((k) => k.nome));
    setEditTagsInput('');
  };

  const salvarEdicao = async () => {
    if (editId == null) return;
    const body = editTexto.trim();
    if (!body) {
      setMsg('Texto obrigatório.');
      return;
    }
    setSaving(true);
    try {
      await ideiasFetch('/notas.php', {
        method: 'PUT',
        body: {
          id: editId,
          texto: body,
          keywords: [...editTags, ...parseTags(editTagsInput)],
        },
      });
      setEditId(null);
      await carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao atualizar');
    } finally {
      setSaving(false);
    }
  };

  const toggleFavorito = async (ideia: Ideia) => {
    try {
      await ideiasFetch('/notas.php', {
        method: 'PATCH',
        body: { id: ideia.id, favorito: !ideia.favorito },
      });
      await carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro');
    }
  };

  const marcarUsado = async (ideia: Ideia) => {
    try {
      await ideiasFetch('/notas.php', {
        method: 'PATCH',
        body: { id: ideia.id, status: ideia.status === 'usado' ? 'raw' : 'usado' },
      });
      await carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro');
    }
  };

  const excluir = async (ideia: Ideia) => {
    if (!confirm('Excluir esta ideia?')) return;
    try {
      await ideiasFetch('/notas.php', { method: 'DELETE', body: { id: ideia.id } });
      await carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao excluir');
    }
  };

  return (
    <div className="max-w-3xl space-y-4">
      <p className="text-sm text-slate-500">
        Capture frases curtas do cotidiano e classifique com palavras-chave. Depois use a visão “Por palavra-chave”
        para ver padrões.
      </p>

      <form onSubmit={capturar} className="panel-card space-y-3">
        <textarea
          className="panel-input min-h-[88px] w-full resize-y"
          placeholder="Uma observação curta, paradoxo ou ideia…"
          value={texto}
          onChange={(e) => setTexto(e.target.value)}
          maxLength={500}
        />
        <div className="flex flex-wrap items-center gap-1.5 min-h-[32px]">
          {tags.map((t) => (
            <span
              key={t}
              className="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700"
            >
              {t}
              <button type="button" onClick={() => setTags((prev) => prev.filter((x) => x !== t))} aria-label={`Remover ${t}`}>
                <X size={12} />
              </button>
            </span>
          ))}
          <input
            className="panel-input flex-1 min-w-[10rem] py-1.5 text-sm"
            placeholder="Nova palavra-chave (Enter ou vírgula)"
            value={tagsInput}
            onChange={(e) => setTagsInput(e.target.value)}
            onKeyDown={onTagsKey}
            onBlur={() => {
              window.setTimeout(() => {
                if (tagsInput.trim()) addTag(tagsInput);
              }, 150);
            }}
          />
          <button
            type="button"
            className="panel-btn-ghost text-xs shrink-0"
            onClick={gerarTags}
            disabled={generatingTags || !texto.trim()}
            title="Sugerir tags a partir do texto com IA"
          >
            <Tags size={14} />
            {generatingTags ? 'Gerando…' : 'Gerar tags'}
          </button>
          {allKeywords.length > 0 && (
            <button
              type="button"
              className="panel-btn-ghost text-xs shrink-0"
              onClick={() => setTagsPickerOpen((o) => !o)}
              aria-expanded={tagsPickerOpen}
              title="Ver tags já criadas"
            >
              <Plus size={14} />
              Tags ({allKeywords.length})
              <ChevronDown size={14} className={`transition-transform ${tagsPickerOpen ? 'rotate-180' : ''}`} />
            </button>
          )}
        </div>
        {tagsPickerOpen && (
          <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-3 space-y-2">
            <div className="flex items-center justify-between gap-2">
              <p className="text-xs font-medium text-slate-600">
                {tagsDisponiveis.length} tag{tagsDisponiveis.length === 1 ? '' : 's'} disponível
                {tagsDisponiveis.length === 1 ? '' : 'is'}
              </p>
              <button type="button" className="text-xs text-slate-500 hover:text-slate-800" onClick={() => setTagsPickerOpen(false)}>
                Fechar
              </button>
            </div>
            {tagsDisponiveis.length === 0 ? (
              <p className="text-xs text-slate-400">Nenhuma tag disponível com esse filtro.</p>
            ) : (
              <div className="flex flex-wrap gap-1.5 max-h-40 overflow-y-auto">
                {tagsDisponiveis.map((k) => (
                  <button
                    key={k.id}
                    type="button"
                    className="text-[11px] px-2 py-0.5 rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-violet-50 hover:border-violet-200 hover:text-violet-800"
                    onMouseDown={(e) => e.preventDefault()}
                    onClick={() => addTag(k.nome)}
                  >
                    + {k.nome}
                    {typeof k.count === 'number' ? ` (${k.count})` : ''}
                  </button>
                ))}
              </div>
            )}
          </div>
        )}
        <div className="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2">
          <span className="text-xs text-slate-400">{texto.length}/500</span>
          <button type="submit" className="panel-btn-primary" disabled={saving}>
            <Plus size={16} /> {saving ? 'Salvando…' : 'Capturar'}
          </button>
        </div>
      </form>

      <div className="flex flex-col sm:flex-row gap-2">
        <input
          type="month"
          className="panel-input w-full sm:w-auto"
          value={mes}
          onChange={(e) => setMes(e.target.value)}
          title="Filtrar mês"
        />
        <button type="button" className="panel-btn-ghost text-sm" onClick={() => setMes(currentMonth())}>
          Mês atual
        </button>
        <button type="button" className="panel-btn-ghost text-sm" onClick={() => setMes('')}>
          Todos
        </button>
        <input
          className="panel-input flex-1"
          placeholder="Buscar texto…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
      </div>

      {msg && <p className="text-sm text-red-600">{msg}</p>}

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : lista.length === 0 ? (
        <div className="panel-card text-sm text-slate-500 text-center py-8">
          Nenhuma ideia ainda. Capture a primeira observação acima.
        </div>
      ) : (
        <div className="space-y-2">
          {lista.map((ideia) => (
            <div key={ideia.id} className="panel-card !p-3 space-y-2">
              {editId === ideia.id ? (
                <>
                  <textarea
                    className="panel-input w-full min-h-[72px]"
                    value={editTexto}
                    onChange={(e) => setEditTexto(e.target.value)}
                    maxLength={500}
                  />
                  <div className="flex flex-wrap gap-1.5">
                    {editTags.map((t) => (
                      <span
                        key={t}
                        className="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-slate-100"
                      >
                        {t}
                        <button type="button" onClick={() => setEditTags((p) => p.filter((x) => x !== t))}>
                          <X size={12} />
                        </button>
                      </span>
                    ))}
                    <input
                      className="panel-input flex-1 min-w-[8rem] py-1 text-sm"
                      placeholder="Nova tag…"
                      value={editTagsInput}
                      onChange={(e) => setEditTagsInput(e.target.value)}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ',') {
                          e.preventDefault();
                          const t = editTagsInput.replace(/,/g, '').trim();
                          if (t) {
                            setEditTags((p) => (p.some((x) => x.toLowerCase() === t.toLowerCase()) ? p : [...p, t]));
                            setEditTagsInput('');
                          }
                        }
                      }}
                    />
                  </div>
                  <div className="flex gap-2 justify-end">
                    <button type="button" className="panel-btn-ghost text-sm" onClick={() => setEditId(null)}>
                      Cancelar
                    </button>
                    <button type="button" className="panel-btn-primary text-sm" onClick={salvarEdicao} disabled={saving}>
                      Salvar
                    </button>
                  </div>
                </>
              ) : (
                <>
                  <div className="flex items-start gap-2">
                    <p className="flex-1 text-sm text-slate-800 whitespace-pre-wrap">{ideia.texto}</p>
                    <div className="flex gap-0.5 shrink-0">
                      <button
                        type="button"
                        className={`panel-btn-ghost p-1.5 ${ideia.favorito ? 'text-amber-500' : ''}`}
                        onClick={() => toggleFavorito(ideia)}
                        aria-label="Favorito"
                      >
                        <Star size={15} fill={ideia.favorito ? 'currentColor' : 'none'} />
                      </button>
                      <button type="button" className="panel-btn-ghost p-1.5" onClick={() => abrirEditar(ideia)}>
                        <Pencil size={15} />
                      </button>
                      <button type="button" className="panel-btn-ghost p-1.5 text-red-600" onClick={() => excluir(ideia)}>
                        <Trash2 size={15} />
                      </button>
                    </div>
                  </div>
                  <div className="flex flex-wrap items-center gap-1.5">
                    {ideia.keywords.map((k) => (
                      <span key={k.id} className="text-[11px] px-2 py-0.5 rounded-full bg-violet-50 text-violet-800">
                        {k.nome}
                      </span>
                    ))}
                    {ideia.status === 'usado' && (
                      <span className="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800">usada</span>
                    )}
                    <span className="text-[11px] text-slate-400 ml-auto">{formatDate(ideia.created_at)}</span>
                  </div>
                  <button type="button" className="text-[11px] text-slate-500 hover:text-slate-800" onClick={() => marcarUsado(ideia)}>
                    {ideia.status === 'usado' ? 'Marcar como não usada' : 'Marcar como usada'}
                  </button>
                </>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
