import { useCallback, useEffect, useMemo, useState } from 'react';
import { BrainCircuit, Check, HelpCircle, RefreshCw, Save, Shuffle } from 'lucide-react';
import { ideiasFetch } from '../../lib/ideiasApi';
import type { Ideia, IdeiaKeyword } from '../../types/ideias';

type InsightIA = {
  titulo: string;
  texto: string;
  por_que: string;
  tags: string[];
};

const PROMPTS = [
  'O que essas observações têm em comum que você não tinha notado?',
  'Se uma fosse o problema e a outra a solução, que ideia nasce?',
  'Qual paradoxo aparece quando você junta esses inputs?',
  'Como isso viraria um produto, post ou pitch em uma frase?',
  'Que história ou metáfora conecta esses repertórios?',
  'O que acontece se você levar o oposto de cada observação a sério?',
  'Qual público sentiria isso ao mesmo tempo?',
  'Que pergunta nova essas combinações te forçam a fazer?',
];

function pickRandom<T>(arr: T[], n: number): T[] {
  const copy = [...arr];
  for (let i = copy.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [copy[i], copy[j]] = [copy[j], copy[i]];
  }
  return copy.slice(0, Math.min(n, copy.length));
}

function promptAleatorio(exclude?: string): string {
  const pool = exclude ? PROMPTS.filter((p) => p !== exclude) : PROMPTS;
  return pool[Math.floor(Math.random() * pool.length)] ?? PROMPTS[0];
}

export default function IdeiasCombinarPanel() {
  const [todas, setTodas] = useState<Ideia[]>([]);
  const [keywords, setKeywords] = useState<IdeiaKeyword[]>([]);
  const [loading, setLoading] = useState(true);
  const [msg, setMsg] = useState('');
  const [info, setInfo] = useState('');
  const [selecionadas, setSelecionadas] = useState<Ideia[]>([]);
  const [tagsCruzadas, setTagsCruzadas] = useState<string[]>([]);
  const [prompt, setPrompt] = useState(() => promptAleatorio());
  const [insight, setInsight] = useState('');
  const [saving, setSaving] = useState(false);
  const [generating, setGenerating] = useState(false);
  const [insightsIA, setInsightsIA] = useState<InsightIA[]>([]);
  const [perguntaIA, setPerguntaIA] = useState('');
  const [tagsIASelecionadas, setTagsIASelecionadas] = useState<string[]>([]);

  const carregar = useCallback(async () => {
    setLoading(true);
    setMsg('');
    try {
      const [data, kw] = await Promise.all([
        ideiasFetch<{ ideias: Ideia[] }>('/notas.php'),
        ideiasFetch<{ keywords: IdeiaKeyword[] }>('/keywords.php'),
      ]);
      setTodas(data.ideias ?? []);
      setKeywords(kw.keywords ?? []);
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao carregar');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const keywordsComIdeias = useMemo(
    () => keywords.filter((k) => (k.count ?? 0) > 0 || todas.some((i) => i.keywords.some((ik) => ik.id === k.id))),
    [keywords, todas]
  );

  const sortear = (qtd = 3) => {
    setInfo('');
    setMsg('');
    if (todas.length < 2) {
      setMsg('Cadastre pelo menos 2 ideias para combinar.');
      return;
    }

    // Preferir ideias de keywords diferentes (força repertório cruzado)
    const byKw = new Map<number, Ideia[]>();
    for (const ideia of todas) {
      if (ideia.keywords.length === 0) {
        const orphan = byKw.get(0) ?? [];
        orphan.push(ideia);
        byKw.set(0, orphan);
        continue;
      }
      for (const k of ideia.keywords) {
        const list = byKw.get(k.id) ?? [];
        list.push(ideia);
        byKw.set(k.id, list);
      }
    }

    const kwIds = [...byKw.keys()].filter((id) => (byKw.get(id)?.length ?? 0) > 0);
    const pickedKw = pickRandom(kwIds, Math.min(qtd, kwIds.length));
    const chosen: Ideia[] = [];
    const usedIds = new Set<number>();

    for (const kid of pickedKw) {
      const pool = (byKw.get(kid) ?? []).filter((i) => !usedIds.has(i.id));
      if (pool.length === 0) continue;
      const one = pickRandom(pool, 1)[0];
      if (one) {
        chosen.push(one);
        usedIds.add(one.id);
      }
    }

    // Completa se faltou
    if (chosen.length < Math.min(qtd, todas.length)) {
      const rest = todas.filter((i) => !usedIds.has(i.id));
      chosen.push(...pickRandom(rest, Math.min(qtd, todas.length) - chosen.length));
    }

    setSelecionadas(chosen);
    setTagsCruzadas([]);
    setPrompt(promptAleatorio(prompt));
    setInsightsIA([]);
    setPerguntaIA('');
    setTagsIASelecionadas([]);
  };

  const toggleTagCruzada = (nome: string) => {
    setInsightsIA([]);
    setPerguntaIA('');
    setTagsIASelecionadas([]);
    setTagsCruzadas((prev) =>
      prev.some((t) => t.toLowerCase() === nome.toLowerCase())
        ? prev.filter((t) => t.toLowerCase() !== nome.toLowerCase())
        : [...prev, nome]
    );
  };

  const ideiasDoCruze = useMemo(() => {
    if (tagsCruzadas.length === 0) return [];
    const wanted = new Set(tagsCruzadas.map((t) => t.toLowerCase()));
    return todas.filter((ideia) => ideia.keywords.some((k) => wanted.has(k.nome.toLowerCase())));
  }, [todas, tagsCruzadas]);

  const colunasCruze = useMemo(() => {
    return tagsCruzadas.map((tag) => ({
      tag,
      ideias: todas.filter((ideia) => ideia.keywords.some((k) => k.nome.toLowerCase() === tag.toLowerCase())),
    }));
  }, [todas, tagsCruzadas]);

  const cardsAtivos = tagsCruzadas.length > 0 ? ideiasDoCruze.slice(0, 12) : selecionadas;

  const tagsDoInsight = useMemo(() => {
    const set = new Map<string, string>();
    for (const ideia of cardsAtivos) {
      for (const k of ideia.keywords) {
        set.set(k.nome.toLowerCase(), k.nome);
      }
    }
    for (const t of tagsCruzadas) {
      set.set(t.toLowerCase(), t);
    }
    for (const t of tagsIASelecionadas) {
      set.set(t.toLowerCase(), t);
    }
    set.set('síntese', 'síntese');
    return [...set.values()];
  }, [cardsAtivos, tagsCruzadas, tagsIASelecionadas]);

  const salvarInsight = async () => {
    const texto = insight.trim();
    if (!texto) {
      setMsg('Escreva o insight antes de salvar.');
      return;
    }
    setSaving(true);
    setMsg('');
    setInfo('');
    try {
      await ideiasFetch('/notas.php', {
        method: 'POST',
        body: {
          texto,
          keywords: tagsDoInsight,
          fonte: 'combinar',
        },
      });
      setInsight('');
      setTagsIASelecionadas([]);
      setInfo('Insight salvo na Captura (tag “síntese”).');
      await carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const gerarComIA = async () => {
    if (cardsAtivos.length < 2) {
      setMsg('Selecione ou sorteie pelo menos duas ideias.');
      return;
    }
    setGenerating(true);
    setMsg('');
    setInfo('');
    setInsightsIA([]);
    setPerguntaIA('');
    try {
      const data = await ideiasFetch<{
        insights: InsightIA[];
        pergunta: string;
      }>('/insights.php', {
        method: 'POST',
        body: {
          ideia_ids: cardsAtivos.slice(0, 12).map((ideia) => ideia.id),
          pergunta_guia: prompt,
        },
      });
      setInsightsIA(data.insights ?? []);
      setPerguntaIA(data.pergunta ?? '');
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao gerar insights com IA');
    } finally {
      setGenerating(false);
    }
  };

  useEffect(() => {
    if (!loading && todas.length >= 2 && selecionadas.length === 0 && tagsCruzadas.length === 0) {
      sortear(3);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps -- só na carga inicial
  }, [loading, todas.length]);

  if (loading) {
    return <p className="text-slate-500">Carregando repertório…</p>;
  }

  return (
    <div className="max-w-4xl space-y-4">
      <p className="text-sm text-slate-500">
        Nada se cria, tudo se combina. Cruze tags ou sorteie observações de áreas diferentes e registre o insight que
        nascer.
      </p>

      <div className="panel-card space-y-3">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <p className="text-sm font-semibold text-slate-800">Motor de combinação</p>
          <div className="flex flex-wrap gap-2">
            <button type="button" className="panel-btn-primary text-sm" onClick={() => sortear(2)}>
              <Shuffle size={15} /> Sortear 2
            </button>
            <button type="button" className="panel-btn-ghost text-sm" onClick={() => sortear(3)}>
              <Shuffle size={15} /> Sortear 3
            </button>
            <button
              type="button"
              className="panel-btn-ghost text-sm"
              onClick={() => {
                setPrompt(promptAleatorio(prompt));
              }}
            >
              <RefreshCw size={15} /> Novo prompt
            </button>
          </div>
        </div>

        <div>
          <p className="text-xs font-medium text-slate-500 mb-2">Ou cruze tags manualmente</p>
          <div className="flex flex-wrap gap-1.5 max-h-28 overflow-y-auto">
            {keywordsComIdeias.map((k) => {
              const on = tagsCruzadas.some((t) => t.toLowerCase() === k.nome.toLowerCase());
              return (
                <button
                  key={k.id}
                  type="button"
                  onClick={() => {
                    toggleTagCruzada(k.nome);
                    if (!on) setSelecionadas([]);
                  }}
                  className={`text-[11px] px-2 py-0.5 rounded-full border transition-colors ${
                    on
                      ? 'bg-violet-100 border-violet-300 text-violet-900'
                      : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  {k.nome}
                  {typeof k.count === 'number' ? ` (${k.count})` : ''}
                </button>
              );
            })}
            {keywordsComIdeias.length === 0 && (
              <span className="text-xs text-slate-400">Cadastre ideias com tags para cruzar.</span>
            )}
          </div>
          {tagsCruzadas.length > 0 && (
            <button type="button" className="text-xs text-slate-500 mt-2 hover:text-slate-800" onClick={() => setTagsCruzadas([])}>
              Limpar cruzamento
            </button>
          )}
        </div>
      </div>

      {msg && <p className="text-sm text-red-600">{msg}</p>}
      {info && <p className="text-sm text-emerald-700">{info}</p>}

      {todas.length < 2 ? (
        <div className="panel-card text-sm text-slate-500 text-center py-8">
          Capture pelo menos 2 observações com palavras-chave diferentes para começar a combinar.
        </div>
      ) : (
        <>
          {tagsCruzadas.length > 0 ? (
            <div className="grid md:grid-cols-2 gap-3">
              {colunasCruze.map((col) => (
                <section key={col.tag} className="space-y-2">
                  <h3 className="text-xs font-bold uppercase tracking-wide text-slate-700 border-b border-slate-200 pb-1">
                    {col.tag} <span className="text-slate-400 font-normal">({col.ideias.length})</span>
                  </h3>
                  <ul className="space-y-2 max-h-72 overflow-y-auto">
                    {col.ideias.map((ideia) => (
                      <li key={`${col.tag}-${ideia.id}`} className="panel-card !p-3 !shadow-none border border-slate-100">
                        <p className="text-sm text-slate-800 whitespace-pre-wrap">{ideia.texto}</p>
                      </li>
                    ))}
                    {col.ideias.length === 0 && <li className="text-xs text-slate-400">Sem ideias nesta tag.</li>}
                  </ul>
                </section>
              ))}
            </div>
          ) : (
            <div className={`grid gap-3 ${selecionadas.length === 2 ? 'md:grid-cols-2' : 'md:grid-cols-3'}`}>
              {selecionadas.map((ideia, idx) => (
                <article key={ideia.id} className="panel-card space-y-2 relative overflow-hidden">
                  <span className="absolute top-2 right-2 text-[10px] font-bold text-slate-300">#{idx + 1}</span>
                  <p className="text-sm text-slate-800 whitespace-pre-wrap pr-6">{ideia.texto}</p>
                  <div className="flex flex-wrap gap-1">
                    {ideia.keywords.map((k) => (
                      <span key={k.id} className="text-[10px] px-1.5 py-0.5 rounded bg-violet-50 text-violet-800">
                        {k.nome}
                      </span>
                    ))}
                  </div>
                </article>
              ))}
            </div>
          )}

          <section className="panel-card space-y-3 border border-violet-100 bg-violet-50/30">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div className="flex items-start gap-2">
                <BrainCircuit size={19} strokeWidth={1.75} className="text-violet-700 shrink-0 mt-0.5" />
                <div>
                  <p className="text-sm font-semibold text-slate-800">Análise com IA</p>
                  <p className="text-xs text-slate-500">
                    O Groq analisa os inputs selecionados e propõe três conexões diferentes.
                  </p>
                </div>
              </div>
              <button
                type="button"
                className="panel-btn-primary shrink-0"
                disabled={generating || cardsAtivos.length < 2}
                onClick={gerarComIA}
              >
                <BrainCircuit size={16} />
                {generating ? 'Analisando…' : insightsIA.length > 0 ? 'Gerar novamente' : 'Gerar insights'}
              </button>
            </div>

            {insightsIA.length > 0 && (
              <div className="space-y-3 pt-1">
                <div className="grid md:grid-cols-3 gap-3">
                  {insightsIA.map((item, index) => (
                    <article key={`${item.titulo}-${index}`} className="rounded-xl border border-slate-200 bg-white p-3 space-y-2">
                      <div className="flex items-start gap-2">
                        <span className="text-[10px] font-bold text-violet-700 bg-violet-50 rounded px-1.5 py-0.5">
                          {index + 1}
                        </span>
                        <h3 className="text-sm font-semibold text-slate-800">{item.titulo}</h3>
                      </div>
                      <p className="text-sm text-slate-700 whitespace-pre-wrap">{item.texto}</p>
                      {item.por_que && <p className="text-xs text-slate-500">{item.por_que}</p>}
                      {item.tags.length > 0 && (
                        <div className="flex flex-wrap gap-1">
                          {item.tags.map((tag) => (
                            <span key={tag} className="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                              {tag}
                            </span>
                          ))}
                        </div>
                      )}
                      <button
                        type="button"
                        className="panel-btn-ghost text-xs w-full justify-center"
                        onClick={() => {
                          setInsight(item.texto);
                          setTagsIASelecionadas(item.tags);
                        }}
                      >
                        <Check size={14} /> Usar este insight
                      </button>
                    </article>
                  ))}
                </div>
                {perguntaIA && (
                  <p className="text-xs text-violet-800 border-l-2 border-violet-300 pl-2">
                    Próxima reflexão: {perguntaIA}
                  </p>
                )}
              </div>
            )}
          </section>

          <div className="panel-card space-y-3 border border-slate-200">
            <div className="flex items-start gap-2">
              <HelpCircle size={18} strokeWidth={1.75} className="text-slate-500 shrink-0 mt-0.5" />
              <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Pergunta-guia</p>
                <p className="text-sm text-slate-800">{prompt}</p>
              </div>
            </div>
            <textarea
              className="panel-input w-full min-h-[100px] bg-white"
              placeholder="Escreva o insight / combinação que surgiu…"
              value={insight}
              onChange={(e) => setInsight(e.target.value)}
              maxLength={500}
            />
            <div className="flex flex-wrap gap-1.5">
              {tagsDoInsight.map((t) => (
                <span key={t} className="text-[10px] px-1.5 py-0.5 rounded-full bg-white border border-slate-200 text-slate-600">
                  {t}
                </span>
              ))}
            </div>
            <div className="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2">
              <span className="text-xs text-slate-400">{insight.length}/500</span>
              <button type="button" className="panel-btn-primary" disabled={saving} onClick={salvarInsight}>
                <Save size={16} /> {saving ? 'Salvando…' : 'Salvar insight'}
              </button>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
