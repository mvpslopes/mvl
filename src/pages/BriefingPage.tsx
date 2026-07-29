import { FormEvent, useMemo, useState } from 'react';
import { ArrowLeft, ArrowRight, Check, Globe, MessageCircle, Plus, Send } from 'lucide-react';
import { Link } from 'react-router-dom';
import logoMvlBranco from '../../logo/logo_mvl-2_branco.png';
import { briefingSubmit } from '../lib/briefingApi';
import './BriefingPage.css';

const STEPS = [
  { id: 1, label: 'Contato' },
  { id: 2, label: 'Projeto' },
  { id: 3, label: 'Identidade' },
  { id: 4, label: 'Extras' },
];

const PAGE_OPTIONS = [
  'Home',
  'Sobre',
  'Serviços',
  'Portfólio',
  'Depoimentos',
  'Blog',
  'Contato',
  'Área do cliente',
];

const FONT_OPTIONS = [
  { id: 'moderna', label: 'Moderna', preview: 'Aa Bb Cc', style: { fontFamily: 'system-ui, sans-serif', fontWeight: 700 } },
  { id: 'elegante', label: 'Elegante', preview: 'Aa Bb Cc', style: { fontFamily: 'Georgia, serif', fontWeight: 500 } },
  { id: 'tecnica', label: 'Técnica', preview: 'Aa Bb Cc', style: { fontFamily: 'ui-monospace, monospace', fontWeight: 600 } },
  { id: 'amigavel', label: 'Amigável', preview: 'Aa Bb Cc', style: { fontFamily: 'Trebuchet MS, sans-serif', fontWeight: 600 } },
];

const STYLE_OPTIONS = [
  { id: 'Minimalista', label: 'Minimalista', className: 'brief-style-minimal' },
  { id: 'Corporativo', label: 'Corporativo', className: 'brief-style-corp' },
  { id: 'Criativo', label: 'Criativo', className: 'brief-style-creative' },
  { id: 'Luxo', label: 'Luxo', className: 'brief-style-luxury' },
  { id: 'Orgânico', label: 'Orgânico', className: 'brief-style-organic' },
];

const MAX_UPLOAD = 20 * 1024 * 1024;

type FormState = {
  name: string;
  phone: string;
  email: string;
  company: string;
  project_type: string;
  goal: string;
  goal_other: string;
  business: string;
  has_website: string;
  current_url: string;
  domain: string;
  pages: string[];
  has_logo: string;
  logo_url: string;
  brand_url: string;
  photos_url: string;
  color_primary: string;
  color_secondary: string;
  color_accent: string;
  suggest_palette: boolean;
  font_heading: string;
  font_body: string;
  suggest_fonts: boolean;
  styles: string[];
  tone: string;
  ref1: string;
  ref2: string;
  ref3: string;
  refs_notes: string;
  notes: string;
  website: string; // honeypot
};

const initial: FormState = {
  name: '',
  phone: '',
  email: '',
  company: '',
  project_type: '',
  goal: '',
  goal_other: '',
  business: '',
  has_website: '',
  current_url: '',
  domain: '',
  pages: ['Home', 'Contato'],
  has_logo: '',
  logo_url: '',
  brand_url: '',
  photos_url: '',
  color_primary: '#1052E0',
  color_secondary: '#1A1D26',
  color_accent: '#10B981',
  suggest_palette: false,
  font_heading: '',
  font_body: '',
  suggest_fonts: false,
  styles: [],
  tone: '',
  ref1: '',
  ref2: '',
  ref3: '',
  refs_notes: '',
  notes: '',
  website: '',
};

function validateFile(file: File | null): string | null {
  if (!file) return null;
  if (file.size > MAX_UPLOAD) return 'Arquivo maior que 20 MB.';
  const ext = file.name.split('.').pop()?.toLowerCase() ?? '';
  if (!['png', 'jpg', 'jpeg', 'webp', 'svg', 'pdf'].includes(ext)) {
    return 'Use png, jpg, webp, svg ou pdf.';
  }
  return null;
}

export default function BriefingPage() {
  const [step, setStep] = useState(1);
  const [form, setForm] = useState<FormState>(initial);
  const [customPage, setCustomPage] = useState('');
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [brandFile, setBrandFile] = useState<File | null>(null);
  const [error, setError] = useState('');
  const [sending, setSending] = useState(false);
  const [done, setDone] = useState(false);

  const progress = useMemo(() => ((step - 1) / (STEPS.length - 1)) * 100, [step]);

  const setField = <K extends keyof FormState>(key: K, value: FormState[K]) => {
    setForm((f) => ({ ...f, [key]: value }));
  };

  const togglePage = (page: string) => {
    setForm((f) => ({
      ...f,
      pages: f.pages.includes(page) ? f.pages.filter((p) => p !== page) : [...f.pages, page],
    }));
  };

  const addCustomPage = () => {
    const name = customPage.trim();
    if (!name) return;
    setForm((f) => ({
      ...f,
      pages: f.pages.includes(name) ? f.pages : [...f.pages, name],
    }));
    setCustomPage('');
  };

  const toggleStyle = (style: string) => {
    setForm((f) => ({
      ...f,
      styles: f.styles.includes(style) ? f.styles.filter((s) => s !== style) : [...f.styles, style],
    }));
  };

  const validateStep = (n: number): string | null => {
    if (n === 1) {
      if (!form.name.trim()) return 'Informe seu nome.';
      if (!form.phone.trim()) return 'Informe o WhatsApp.';
    }
    if (n === 2) {
      if (!form.project_type) return 'Selecione o tipo de projeto.';
      if (!form.goal) return 'Selecione o objetivo.';
      if (form.goal === 'Outro' && !form.goal_other.trim()) return 'Descreva o objetivo.';
    }
    if (n === 3) {
      const errLogo = validateFile(logoFile);
      if (errLogo) return errLogo;
      const errBrand = validateFile(brandFile);
      if (errBrand) return errBrand;
    }
    return null;
  };

  const goNext = () => {
    const err = validateStep(step);
    if (err) {
      setError(err);
      return;
    }
    setError('');
    setStep((s) => Math.min(4, s + 1));
  };

  const goBack = () => {
    setError('');
    setStep((s) => Math.max(1, s - 1));
  };

  const enviarBriefing = async () => {
    for (let i = 1; i <= 4; i++) {
      const err = validateStep(i);
      if (err) {
        setStep(i);
        setError(err);
        return;
      }
    }
    setSending(true);
    setError('');
    try {
      const fd = new FormData();
      Object.entries(form).forEach(([k, v]) => {
        if (k === 'pages' || k === 'styles') return;
        if (typeof v === 'boolean') {
          if (v) fd.append(k, '1');
        } else {
          fd.append(k, String(v));
        }
      });
      form.pages.forEach((p) => fd.append('pages[]', p));
      form.styles.forEach((s) => fd.append('styles[]', s));
      if (logoFile) fd.append('logo_file', logoFile);
      if (brandFile) fd.append('brand_file', brandFile);

      await briefingSubmit(fd);
      setDone(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao enviar.');
    } finally {
      setSending(false);
    }
  };

  const reiniciarBriefing = () => {
    setForm(initial);
    setCustomPage('');
    setLogoFile(null);
    setBrandFile(null);
    setError('');
    setSending(false);
    setStep(1);
    setDone(false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const whatsappResumoUrl = useMemo(() => {
    const linhas = [
      `Olá! Acabei de preencher o briefing da MVL.`,
      '',
      `Nome: ${form.name}`,
      form.company ? `Empresa: ${form.company}` : null,
      form.phone ? `WhatsApp: ${form.phone}` : null,
      form.project_type ? `Projeto: ${form.project_type}` : null,
      form.goal ? `Objetivo: ${form.goal === 'Outro' ? `Outro: ${form.goal_other}` : form.goal}` : null,
      form.domain ? `Domínio desejado: ${form.domain}` : null,
      '',
      'Já enviei pelo site — podem confirmar o recebimento?',
    ].filter((l) => l !== null);
    return `https://wa.me/5531982304737?text=${encodeURIComponent(linhas.join('\n'))}`;
  }, [form]);

  const bloquearSubmitForm = (e: FormEvent) => {
    e.preventDefault();
  };

  if (done) {
    return (
      <div className="briefing-page">
        <div className="briefing-shell">
          <div className="briefing-success">
            <div className="briefing-success-icon">
              <Check size={32} />
            </div>
            <h1>Briefing enviado</h1>
            <p>
              Obrigado, {form.name.split(' ')[0]}! Vamos analisar seu projeto
              {form.domain ? (
                <>
                  {' '}
                  e verificar a disponibilidade de <strong>{form.domain}</strong>
                </>
              ) : null}
              . Em breve entraremos em contato pelo WhatsApp.
            </p>
            <div className="briefing-success-actions">
              <a href={whatsappResumoUrl} target="_blank" rel="noreferrer" className="briefing-btn-primary">
                <MessageCircle size={18} /> Compartilhar no WhatsApp
              </a>
              <button type="button" className="briefing-btn-ghost" onClick={reiniciarBriefing}>
                Enviar outro briefing
              </button>
              <Link to="/" className="briefing-btn-ghost">
                Voltar ao site
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="briefing-page">
      <div className="briefing-shell">
        <header className="briefing-header">
          <Link to="/" className="briefing-brand" aria-label="MVLopes — início">
            <img src={logoMvlBranco} alt="MVLopes" className="briefing-logo" />
          </Link>
          <p className="briefing-kicker">Briefing de projeto</p>
          <h1>Conte o projeto. A gente cuida do resto.</h1>

          <nav className="briefing-stepper" aria-label="Progresso do formulário">
            <div className="briefing-stepper-track" aria-hidden="true">
              <div className="briefing-stepper-fill" style={{ width: `${progress}%` }} />
            </div>
            <ol className="briefing-steps-nav">
              {STEPS.map((s) => (
                <li
                  key={s.id}
                  className={step === s.id ? 'is-active' : step > s.id ? 'is-done' : ''}
                >
                  <span className="briefing-step-num">{s.id}</span>
                  <span className="briefing-step-label">{s.label}</span>
                </li>
              ))}
            </ol>
          </nav>
        </header>

        <form
          className="briefing-form"
          onSubmit={bloquearSubmitForm}
          encType="multipart/form-data"
          noValidate
        >
          {/* honeypot */}
          <input
            type="text"
            name="website"
            value={form.website}
            onChange={(e) => setField('website', e.target.value)}
            className="briefing-hp"
            tabIndex={-1}
            autoComplete="off"
            aria-hidden="true"
          />

          {step === 1 && (
            <section className="briefing-step is-active" data-step="1">
              <h2>Contato</h2>
              <div className="briefing-grid">
                <label className="briefing-field">
                  <span>Nome *</span>
                  <input value={form.name} onChange={(e) => setField('name', e.target.value)} required />
                </label>
                <label className="briefing-field">
                  <span>WhatsApp *</span>
                  <input
                    value={form.phone}
                    onChange={(e) => setField('phone', e.target.value)}
                    placeholder="(31) 99999-9999"
                    required
                  />
                </label>
                <label className="briefing-field">
                  <span>E-mail</span>
                  <input type="email" value={form.email} onChange={(e) => setField('email', e.target.value)} />
                </label>
                <label className="briefing-field">
                  <span>Empresa</span>
                  <input value={form.company} onChange={(e) => setField('company', e.target.value)} />
                </label>
              </div>
            </section>
          )}

          {step === 2 && (
            <section className="briefing-step is-active" data-step="2">
              <h2>Projeto</h2>
              <div className="briefing-grid">
                <label className="briefing-field">
                  <span>Tipo de projeto *</span>
                  <select value={form.project_type} onChange={(e) => setField('project_type', e.target.value)}>
                    <option value="">Selecione…</option>
                    <option value="Site institucional">Site institucional</option>
                    <option value="Landing page">Landing page</option>
                    <option value="E-commerce">E-commerce</option>
                    <option value="Sistema / painel">Sistema / painel</option>
                    <option value="Redesign">Redesign</option>
                  </select>
                </label>
                <label className="briefing-field">
                  <span>Objetivo principal *</span>
                  <select value={form.goal} onChange={(e) => setField('goal', e.target.value)}>
                    <option value="">Selecione…</option>
                    <option value="Gerar contatos">Gerar contatos</option>
                    <option value="Vender online">Vender online</option>
                    <option value="Fortalecer marca">Fortalecer marca</option>
                    <option value="Automatizar processos">Automatizar processos</option>
                    <option value="Outro">Outro</option>
                  </select>
                </label>
                {form.goal === 'Outro' && (
                  <label className="briefing-field briefing-span-2">
                    <span>Descreva o objetivo *</span>
                    <input
                      id="goalOther"
                      value={form.goal_other}
                      onChange={(e) => setField('goal_other', e.target.value)}
                    />
                  </label>
                )}
                <label className="briefing-field briefing-span-2">
                  <span>Sobre o negócio</span>
                  <textarea
                    rows={3}
                    value={form.business}
                    onChange={(e) => setField('business', e.target.value)}
                    placeholder="O que você faz, para quem, diferencial…"
                  />
                </label>
                <label className="briefing-field">
                  <span>Já tem site?</span>
                  <select value={form.has_website} onChange={(e) => setField('has_website', e.target.value)}>
                    <option value="">Selecione…</option>
                    <option value="Sim">Sim</option>
                    <option value="Não">Não</option>
                  </select>
                </label>
                <label className="briefing-field">
                  <span>URL atual</span>
                  <input
                    value={form.current_url}
                    onChange={(e) => setField('current_url', e.target.value)}
                    placeholder="https://…"
                    disabled={form.has_website === 'Não'}
                  />
                </label>
                <label className="briefing-field briefing-span-2">
                  <span className="inline-flex items-center gap-2">
                    <Globe size={14} /> Domínio desejado
                  </span>
                  <input
                    value={form.domain}
                    onChange={(e) => setField('domain', e.target.value)}
                    placeholder="ex.: minhamarca.com.br"
                  />
                  <small className="briefing-hint">
                    Usamos este domínio para verificar disponibilidade e registrar se estiver livre.
                  </small>
                </label>
              </div>

              <div className="briefing-block">
                <p className="briefing-block-title">Páginas desejadas</p>
                <div className="briefing-chips">
                  {PAGE_OPTIONS.map((p) => (
                    <label key={p} className={`briefing-chip ${form.pages.includes(p) ? 'is-on' : ''}`}>
                      <input type="checkbox" checked={form.pages.includes(p)} onChange={() => togglePage(p)} />
                      {p}
                    </label>
                  ))}
                  {form.pages
                    .filter((p) => !PAGE_OPTIONS.includes(p))
                    .map((p) => (
                      <label key={p} className="briefing-chip is-on">
                        <input type="checkbox" checked onChange={() => togglePage(p)} />
                        {p}
                      </label>
                    ))}
                </div>
                <div className="briefing-add-row">
                  <input
                    value={customPage}
                    onChange={(e) => setCustomPage(e.target.value)}
                    placeholder="Outra página…"
                    onKeyDown={(e) => {
                      if (e.key === 'Enter') {
                        e.preventDefault();
                        addCustomPage();
                      }
                    }}
                  />
                  <button type="button" className="briefing-add-btn" onClick={addCustomPage}>
                    <Plus size={16} /> Adicionar
                  </button>
                </div>
              </div>
            </section>
          )}

          {step === 3 && (
            <section className="briefing-step is-active" data-step="3">
              <h2>Identidade</h2>
              <div className="briefing-grid">
                <label className="briefing-field">
                  <span>Já tem logo?</span>
                  <select value={form.has_logo} onChange={(e) => setField('has_logo', e.target.value)}>
                    <option value="">Selecione…</option>
                    <option value="Sim">Sim</option>
                    <option value="Não">Não</option>
                    <option value="Em desenvolvimento">Em desenvolvimento</option>
                  </select>
                </label>
                <label className="briefing-field">
                  <span>Upload do logo</span>
                  <input
                    type="file"
                    accept=".png,.jpg,.jpeg,.webp,.svg,.pdf"
                    onChange={(e) => setLogoFile(e.target.files?.[0] ?? null)}
                  />
                  <small className="briefing-hint">Até 20 MB · png, jpg, webp, svg, pdf</small>
                </label>
                <label className="briefing-field briefing-span-2">
                  <span>Ou link do logo</span>
                  <input
                    type="url"
                    value={form.logo_url}
                    onChange={(e) => setField('logo_url', e.target.value)}
                    placeholder="https://drive.google.com/… ou link da imagem"
                  />
                </label>
                <label className="briefing-field">
                  <span>Material de marca (arquivo)</span>
                  <input
                    type="file"
                    accept=".png,.jpg,.jpeg,.webp,.svg,.pdf"
                    onChange={(e) => setBrandFile(e.target.files?.[0] ?? null)}
                  />
                  <small className="briefing-hint">Brandbook, manual, PDF…</small>
                </label>
                <label className="briefing-field">
                  <span>Ou link do material</span>
                  <input
                    type="url"
                    value={form.brand_url}
                    onChange={(e) => setField('brand_url', e.target.value)}
                    placeholder="https://… (Drive, Figma, Dropbox…)"
                  />
                </label>
                <label className="briefing-field briefing-span-2">
                  <span>Link com fotos, referências ou arquivos do projeto</span>
                  <input
                    type="url"
                    value={form.photos_url}
                    onChange={(e) => setField('photos_url', e.target.value)}
                    placeholder="https://… (Drive, WeTransfer, Dropbox, pasta compartilhada…)"
                  />
                  <small className="briefing-hint">
                    Se tiver fotos do espaço, produtos, equipe ou referências, envie aqui um link compartilhado.
                    Se não tiver link agora, você pode mandar os arquivos depois pelo WhatsApp.
                  </small>
                </label>
              </div>

              <div className="briefing-block">
                <p className="briefing-block-title">Cores</p>
                <div className="briefing-colors">
                  {(
                    [
                      ['color_primary', 'Primária'],
                      ['color_secondary', 'Secundária'],
                      ['color_accent', 'Destaque'],
                    ] as const
                  ).map(([key, label]) => (
                    <label key={key} className="briefing-color">
                      <span>{label}</span>
                      <div className="briefing-color-row">
                        <input
                          type="color"
                          value={form[key]}
                          onChange={(e) => setField(key, e.target.value.toUpperCase())}
                        />
                        <input
                          value={form[key]}
                          onChange={(e) => {
                            const v = e.target.value;
                            if (/^#[0-9A-Fa-f]{0,6}$/.test(v)) setField(key, v.toUpperCase());
                          }}
                        />
                      </div>
                    </label>
                  ))}
                </div>
                <label className="briefing-check">
                  <input
                    type="checkbox"
                    checked={form.suggest_palette}
                    onChange={(e) => setField('suggest_palette', e.target.checked)}
                  />
                  Prefiro que a MVL sugira a paleta
                </label>
              </div>

              <div className="briefing-block">
                <p className="briefing-block-title">Tipografia (títulos)</p>
                <div className="briefing-cards">
                  {FONT_OPTIONS.map((f) => (
                    <label key={f.id} className={`briefing-card ${form.font_heading === f.id ? 'is-on' : ''}`}>
                      <input
                        type="radio"
                        name="font_heading"
                        checked={form.font_heading === f.id}
                        onChange={() => setField('font_heading', f.id)}
                      />
                      <span className="briefing-card-preview" style={f.style}>
                        {f.preview}
                      </span>
                      <span>{f.label}</span>
                    </label>
                  ))}
                </div>
                <p className="briefing-block-title mt">Tipografia (texto)</p>
                <div className="briefing-cards">
                  {FONT_OPTIONS.map((f) => (
                    <label key={`b-${f.id}`} className={`briefing-card ${form.font_body === f.id ? 'is-on' : ''}`}>
                      <input
                        type="radio"
                        name="font_body"
                        checked={form.font_body === f.id}
                        onChange={() => setField('font_body', f.id)}
                      />
                      <span className="briefing-card-preview" style={f.style}>
                        {f.preview}
                      </span>
                      <span>{f.label}</span>
                    </label>
                  ))}
                </div>
                <label className="briefing-check">
                  <input
                    type="checkbox"
                    checked={form.suggest_fonts}
                    onChange={(e) => setField('suggest_fonts', e.target.checked)}
                  />
                  Prefiro que a MVL sugira tipografias
                </label>
              </div>

              <div className="briefing-block">
                <p className="briefing-block-title">Estilo visual</p>
                <div className="briefing-cards">
                  {STYLE_OPTIONS.map((s) => (
                    <label
                      key={s.id}
                      className={`briefing-card briefing-style ${s.className} ${form.styles.includes(s.id) ? 'is-on' : ''}`}
                    >
                      <input
                        type="checkbox"
                        checked={form.styles.includes(s.id)}
                        onChange={() => toggleStyle(s.id)}
                      />
                      <span className="briefing-style-swatch" />
                      <span>{s.label}</span>
                    </label>
                  ))}
                </div>
                <label className="briefing-field mt">
                  <span>Tom de comunicação</span>
                  <input
                    value={form.tone}
                    onChange={(e) => setField('tone', e.target.value)}
                    placeholder="Ex.: profissional, próximo, ousado…"
                  />
                </label>
              </div>
            </section>
          )}

          {step === 4 && (
            <section className="briefing-step is-active" data-step="4">
              <h2>Extras</h2>
              <p className="briefing-section-intro">
                Indique até 3 sites que você gosta — podem ser concorrentes, marcas do seu segmento ou
                referências de layout. Usamos isso para entender o estilo e a experiência que você busca.
              </p>
              <div className="briefing-grid">
                <label className="briefing-field briefing-span-2">
                  <span>Site de referência 1</span>
                  <input value={form.ref1} onChange={(e) => setField('ref1', e.target.value)} placeholder="https://exemplo.com.br" />
                </label>
                <label className="briefing-field briefing-span-2">
                  <span>Site de referência 2</span>
                  <input value={form.ref2} onChange={(e) => setField('ref2', e.target.value)} placeholder="https://exemplo.com.br" />
                </label>
                <label className="briefing-field briefing-span-2">
                  <span>Site de referência 3</span>
                  <input value={form.ref3} onChange={(e) => setField('ref3', e.target.value)} placeholder="https://exemplo.com.br" />
                </label>
                <label className="briefing-field briefing-span-2">
                  <span>O que gostou nesses sites?</span>
                  <textarea
                    rows={3}
                    value={form.refs_notes}
                    onChange={(e) => setField('refs_notes', e.target.value)}
                    placeholder="Ex.: cores, tipografia, organização do menu, simplicidade…"
                  />
                </label>
                <label className="briefing-field briefing-span-2">
                  <span>Observações</span>
                  <textarea rows={4} value={form.notes} onChange={(e) => setField('notes', e.target.value)} />
                </label>
              </div>
            </section>
          )}

          {error && <p className="briefing-error">{error}</p>}

          <div className="briefing-actions">
            {step > 1 ? (
              <button type="button" className="briefing-btn-ghost" onClick={goBack}>
                <ArrowLeft size={18} /> Voltar
              </button>
            ) : (
              <Link to="/" className="briefing-btn-ghost">
                <ArrowLeft size={18} /> Site
              </Link>
            )}
            {step < 4 ? (
              <button type="button" className="briefing-btn-primary" onClick={goNext}>
                Continuar <ArrowRight size={18} />
              </button>
            ) : (
              <button
                type="button"
                className="briefing-btn-primary"
                disabled={sending}
                onClick={enviarBriefing}
              >
                {sending ? 'Enviando…' : 'Enviar briefing'} <Send size={18} />
              </button>
            )}
          </div>
        </form>
      </div>
    </div>
  );
}
