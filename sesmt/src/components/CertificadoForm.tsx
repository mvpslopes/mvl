import { useCallback, useEffect, useState } from 'react';
import { FileDown, Plus, Save, Trash2 } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import CityAutocomplete from './CityAutocomplete';
import { apiFetch, apiFetchBlob } from '../lib/api';
import type { AssinaturaSlot, CertificadoDetalhe } from '../types/certificado';

type NrTipo = { id: number; codigo: string; nome: string };
type EmpresaCadastro = { id: number; nome: string; logo_url: string | null };
type ModoEmpresa = 'cadastrada' | 'manual';

async function fileToBase64(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result as string);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

const emptyAssinatura = (): AssinaturaSlot => ({
  nome: '',
  funcao: '',
  registro_tipo: 'CREA',
  registro: '',
});

type Props = {
  editId?: number;
};

export default function CertificadoForm({ editId }: Props) {
  const navigate = useNavigate();
  const isEdit = Boolean(editId);

  const [nrTipos, setNrTipos] = useState<NrTipo[]>([]);
  const [empresas, setEmpresas] = useState<EmpresaCadastro[]>([]);
  const [modoEmpresa, setModoEmpresa] = useState<ModoEmpresa>('cadastrada');
  const [empresaSelecionadaId, setEmpresaSelecionadaId] = useState('');
  const [empresaNome, setEmpresaNome] = useState('');
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [logoPreview, setLogoPreview] = useState<string | null>(null);
  const [salvarNoCadastro, setSalvarNoCadastro] = useState(false);
  const [nrTipoId, setNrTipoId] = useState('');
  const [cargaHoraria, setCargaHoraria] = useState('');
  const [colaboradorNome, setColaboradorNome] = useState('');
  const [colaboradorCpf, setColaboradorCpf] = useState('');
  const [dataCertificado, setDataCertificado] = useState('');
  const [cidade, setCidade] = useState('');
  const [conteudoMinistrado, setConteudoMinistrado] = useState('');
  const [assinaturas, setAssinaturas] = useState<AssinaturaSlot[]>([
    emptyAssinatura(),
    emptyAssinatura(),
  ]);
  const [numero, setNumero] = useState('');
  const [status, setStatus] = useState<'rascunho' | 'emitido'>('rascunho');
  const [loading, setLoading] = useState(false);
  const [loadingData, setLoadingData] = useState(isEdit);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [pdfUrl, setPdfUrl] = useState<string | null>(null);
  const [certificadoId, setCertificadoId] = useState<number | null>(editId ?? null);

  const previewLogoEmpresa = useCallback(async (id: number) => {
    try {
      const blob = await apiFetchBlob(`/empresas.php?id=${id}&logo=1`);
      setLogoPreview((prev) => {
        if (prev?.startsWith('blob:')) URL.revokeObjectURL(prev);
        return URL.createObjectURL(blob);
      });
    } catch {
      setLogoPreview(null);
    }
  }, []);

  const selecionarEmpresaCadastrada = useCallback(
    async (id: string, lista: EmpresaCadastro[]) => {
      setEmpresaSelecionadaId(id);
      const emp = lista.find((e) => String(e.id) === id);
      if (!emp) return;
      setEmpresaNome(emp.nome);
      setLogoFile(null);
      if (emp.logo_url) {
        await previewLogoEmpresa(emp.id);
      } else {
        setLogoPreview(null);
      }
    },
    [previewLogoEmpresa]
  );

  const loadCertificado = useCallback(async () => {
    if (!editId) return;
    const { certificado } = await apiFetch<{ certificado: CertificadoDetalhe }>(
      `/certificados.php?id=${editId}`
    );
    setNrTipoId(String(certificado.nr_tipo_id));
    setCargaHoraria(certificado.carga_horaria);
    setColaboradorNome(certificado.colaborador_nome === '—' ? '' : certificado.colaborador_nome);
    setColaboradorCpf(certificado.colaborador_cpf === '—' ? '' : certificado.colaborador_cpf);
    setDataCertificado(certificado.data_certificado);
    setCidade(certificado.cidade === '—' ? '' : certificado.cidade);
    setEmpresaNome(certificado.empresa_nome === '—' ? '' : certificado.empresa_nome);
    if (certificado.empresa_id) {
      setModoEmpresa('cadastrada');
      setEmpresaSelecionadaId(String(certificado.empresa_id));
      await previewLogoEmpresa(certificado.empresa_id);
    } else {
      setModoEmpresa('manual');
      setEmpresaSelecionadaId('');
    }
    setConteudoMinistrado(certificado.conteudo_ministrado ?? '');
    setNumero(certificado.numero);
    setStatus(certificado.status);
    setCertificadoId(certificado.id);
    const ass = certificado.assinaturas?.length
      ? certificado.assinaturas
      : [emptyAssinatura(), emptyAssinatura()];
    setAssinaturas(ass);
  }, [editId, previewLogoEmpresa]);

  useEffect(() => {
    (async () => {
      setError('');
      try {
        const [nr, emp] = await Promise.all([
          apiFetch<{ tipos: NrTipo[] }>('/nr-tipos.php'),
          apiFetch<{ empresas: EmpresaCadastro[] }>('/empresas.php'),
        ]);
        setNrTipos(nr.tipos);
        setEmpresas(emp.empresas);
        if (editId) {
          await loadCertificado();
        } else if (emp.empresas.length > 0) {
          setModoEmpresa('cadastrada');
          await selecionarEmpresaCadastrada(String(emp.empresas[0].id), emp.empresas);
        } else {
          setModoEmpresa('manual');
        }
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Erro ao carregar dados.');
      } finally {
        setLoadingData(false);
      }
    })();
  }, [editId, loadCertificado, selecionarEmpresaCadastrada]);

  useEffect(() => {
    return () => {
      if (pdfUrl) URL.revokeObjectURL(pdfUrl);
    };
  }, [pdfUrl]);

  const buildBody = async () => {
    const preenchidas = assinaturas.filter((a) => a.nome.trim() !== '');
    const body: Record<string, unknown> = {
      ...(certificadoId ? { id: certificadoId } : {}),
      nr_tipo_id: nrTipoId ? Number(nrTipoId) : 0,
      carga_horaria: cargaHoraria,
      colaborador_nome: colaboradorNome,
      colaborador_cpf: colaboradorCpf,
      data_certificado: dataCertificado,
      cidade,
      empresa_nome: empresaNome,
      conteudo_ministrado: conteudoMinistrado,
      assinaturas: preenchidas.map((a) => ({
        nome: a.nome,
        funcao: a.funcao,
        registro_tipo: a.registro_tipo,
        registro: a.registro,
      })),
    };

    if (modoEmpresa === 'cadastrada' && empresaSelecionadaId) {
      body.empresa_id = Number(empresaSelecionadaId);
    } else if (logoFile) {
      body.empresa_logo_base64 = await fileToBase64(logoFile);
      if (salvarNoCadastro) body.salvar_empresa_cadastro = true;
    }

    return body;
  };

  const executarSalvar = async (gerarPdf: boolean) => {
    setLoading(true);
    setError('');
    setSuccess('');
    if (pdfUrl) {
      URL.revokeObjectURL(pdfUrl);
      setPdfUrl(null);
    }

    try {
      const body = { ...(await buildBody()), gerar_pdf: gerarPdf };
      const res = await apiFetch<{
        message: string;
        certificado: CertificadoDetalhe & { id: number; numero: string; pdf_url?: string };
      }>(`/certificados.php`, {
        method: certificadoId ? 'PUT' : 'POST',
        body,
      });

      setCertificadoId(res.certificado.id);
      setNumero(res.certificado.numero);
      setStatus(res.certificado.status);
      setSuccess(res.message);

      if (gerarPdf && res.certificado.id) {
        const blob = await apiFetchBlob(`/certificados.php?id=${res.certificado.id}&download=1`);
        setPdfUrl(URL.createObjectURL(blob));
      }

      if (!certificadoId && !gerarPdf) {
        navigate(`/certificados/${res.certificado.id}/editar`, { replace: true });
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao salvar.');
    } finally {
      setLoading(false);
    }
  };

  const updateAssinatura = (index: number, field: keyof AssinaturaSlot, value: string) => {
    setAssinaturas((prev) => {
      const next = [...prev];
      next[index] = { ...next[index], [field]: value };
      return next;
    });
  };

  if (loadingData) {
    return <p className="text-sesmt-forest/60">Carregando…</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center gap-3">
        <Link to="/certificados" className="sesmt-btn-ghost text-sm">
          ← Voltar à lista
        </Link>
        {numero && (
          <span className="text-sm text-sesmt-forest/60">
            {numero} ·{' '}
            <span
              className={
                status === 'emitido' ? 'text-sesmt-accent font-medium' : 'text-amber-700 font-medium'
              }
            >
              {status === 'emitido' ? 'Emitido' : 'Rascunho'}
            </span>
          </span>
        )}
      </div>

      {error && (
        <p className="text-sm text-sesmt-forest bg-amber-50 border border-amber-200/80 rounded-[10px] px-3 py-2">
          {error}
        </p>
      )}
      {success && (
        <p className="text-sm text-sesmt-forest bg-sesmt-forest-muted border border-sesmt-forest/15 rounded-[10px] px-3 py-2">
          {success}
        </p>
      )}

      <form
        onSubmit={(e) => {
          e.preventDefault();
          executarSalvar(true);
        }}
        className="space-y-8"
      >
        {/* sections same as before - empresa, treinamento, verso, assinaturas */}
        <section className="sesmt-card space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h2 className="text-lg font-semibold text-sesmt-forest">Empresa</h2>
            <Link to="/empresas" className="text-sm text-sesmt-accent hover:underline">
              Gerenciar cadastro de empresas
            </Link>
          </div>

          <div className="flex flex-wrap gap-4">
            <label className="flex items-center gap-2 text-sm text-sesmt-forest cursor-pointer">
              <input
                type="radio"
                name="modo-empresa"
                checked={modoEmpresa === 'cadastrada'}
                onChange={() => {
                  setModoEmpresa('cadastrada');
                  setLogoFile(null);
                  setSalvarNoCadastro(false);
                  if (empresas.length > 0) {
                    void selecionarEmpresaCadastrada(
                      empresaSelecionadaId || String(empresas[0].id),
                      empresas
                    );
                  }
                }}
              />
              Empresa cadastrada
            </label>
            <label className="flex items-center gap-2 text-sm text-sesmt-forest cursor-pointer">
              <input
                type="radio"
                name="modo-empresa"
                checked={modoEmpresa === 'manual'}
                onChange={() => {
                  setModoEmpresa('manual');
                  setEmpresaSelecionadaId('');
                  setLogoFile(null);
                  if (logoPreview?.startsWith('blob:')) URL.revokeObjectURL(logoPreview);
                  setLogoPreview(null);
                }}
              />
              Informar manualmente
            </label>
          </div>

          {modoEmpresa === 'cadastrada' ? (
            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <label className="sesmt-label">Selecione a empresa</label>
                {empresas.length === 0 ? (
                  <p className="text-sm text-sesmt-forest/60 mt-1">
                    Nenhuma empresa cadastrada.{' '}
                    <Link to="/empresas" className="text-sesmt-accent underline">
                      Cadastre uma empresa
                    </Link>{' '}
                    ou use o modo manual.
                  </p>
                ) : (
                  <select
                    className="sesmt-input"
                    value={empresaSelecionadaId}
                    onChange={(e) => void selecionarEmpresaCadastrada(e.target.value, empresas)}
                  >
                    {empresas.map((e) => (
                      <option key={e.id} value={e.id}>
                        {e.nome}
                      </option>
                    ))}
                  </select>
                )}
              </div>
              <div>
                <label className="sesmt-label">Logo</label>
                {logoPreview ? (
                  <img src={logoPreview} alt="Logo da empresa" className="h-14 object-contain mt-1" />
                ) : (
                  <p className="text-sm text-sesmt-forest/50 mt-2">Esta empresa não possui logo.</p>
                )}
              </div>
            </div>
          ) : (
            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <label className="sesmt-label">Nome da empresa</label>
                <input
                  className="sesmt-input"
                  value={empresaNome}
                  onChange={(e) => setEmpresaNome(e.target.value)}
                />
              </div>
              <div>
                <label className="sesmt-label">Logo (PNG/JPG)</label>
                <input
                  type="file"
                  accept="image/png,image/jpeg"
                  className="sesmt-input py-2"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (!file) return;
                    setLogoFile(file);
                    if (logoPreview?.startsWith('blob:')) URL.revokeObjectURL(logoPreview);
                    setLogoPreview(URL.createObjectURL(file));
                  }}
                />
                {logoPreview && (
                  <img src={logoPreview} alt="Logo" className="mt-2 h-12 object-contain" />
                )}
              </div>
              <label className="md:col-span-2 flex items-center gap-2 text-sm text-sesmt-forest cursor-pointer">
                <input
                  type="checkbox"
                  checked={salvarNoCadastro}
                  onChange={(e) => setSalvarNoCadastro(e.target.checked)}
                />
                Salvar esta empresa no cadastro para uso futuro
              </label>
            </div>
          )}
        </section>

        <section className="sesmt-card space-y-4">
          <h2 className="text-lg font-semibold text-sesmt-forest">Treinamento e colaborador</h2>
          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="sesmt-label">Nome do treinamento (NR)</label>
              <select
                className="sesmt-input"
                value={nrTipoId}
                onChange={(e) => setNrTipoId(e.target.value)}
              >
                <option value="">Selecione…</option>
                {nrTipos.map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.nome}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="sesmt-label">Carga horária</label>
              <input
                className="sesmt-input"
                value={cargaHoraria}
                onChange={(e) => setCargaHoraria(e.target.value)}
                placeholder="Ex: 8"
              />
            </div>
            <div>
              <label className="sesmt-label">Nome do colaborador</label>
              <input
                className="sesmt-input"
                value={colaboradorNome}
                onChange={(e) => setColaboradorNome(e.target.value)}
              />
            </div>
            <div>
              <label className="sesmt-label">CPF do colaborador</label>
              <input
                className="sesmt-input"
                value={colaboradorCpf}
                onChange={(e) => setColaboradorCpf(e.target.value)}
                placeholder="000.000.000-00"
              />
            </div>
            <div>
              <label className="sesmt-label">Data</label>
              <input
                type="date"
                className="sesmt-input"
                value={dataCertificado}
                onChange={(e) => setDataCertificado(e.target.value)}
              />
            </div>
            <div>
              <label className="sesmt-label">Cidade (MG)</label>
              <CityAutocomplete value={cidade} onChange={setCidade} />
            </div>
          </div>
        </section>

        <section className="sesmt-card space-y-4">
          <h2 className="text-lg font-semibold text-sesmt-forest">Verso do certificado</h2>
          <div>
            <label className="sesmt-label" htmlFor="conteudo-ministrado">
              Conteúdo ministrado
            </label>
            <textarea
              id="conteudo-ministrado"
              className="sesmt-input min-h-[160px] py-3 resize-y"
              value={conteudoMinistrado}
              onChange={(e) => setConteudoMinistrado(e.target.value)}
            />
          </div>
        </section>

        <section className="sesmt-card space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-semibold text-sesmt-forest">
              Assinaturas — frente ({assinaturas.length}/4)
            </h2>
            {assinaturas.length < 4 && (
              <button type="button" onClick={() => setAssinaturas((p) => [...p, emptyAssinatura()])} className="sesmt-btn-ghost text-sm">
                <Plus size={16} /> Adicionar
              </button>
            )}
          </div>
          {assinaturas.map((a, index) => (
            <div
              key={index}
              className="grid md:grid-cols-4 gap-3 p-4 rounded-xl bg-sesmt-page border border-sesmt-forest/10"
            >
              <div className="md:col-span-4 flex justify-between items-center">
                <span className="text-sm font-medium text-sesmt-forest">Assinatura {index + 1}</span>
                {assinaturas.length > 1 && (
                  <button type="button" onClick={() => setAssinaturas((p) => p.filter((_, i) => i !== index))} className="p-1 text-sesmt-forest/50">
                    <Trash2 size={16} />
                  </button>
                )}
              </div>
              <div>
                <label className="sesmt-label">Nome</label>
                <input className="sesmt-input" value={a.nome} onChange={(e) => updateAssinatura(index, 'nome', e.target.value)} />
              </div>
              <div>
                <label className="sesmt-label">Função</label>
                <input className="sesmt-input" value={a.funcao} onChange={(e) => updateAssinatura(index, 'funcao', e.target.value)} placeholder="Instrutor" />
              </div>
              <div>
                <label className="sesmt-label">Registro</label>
                <select className="sesmt-input" value={a.registro_tipo} onChange={(e) => updateAssinatura(index, 'registro_tipo', e.target.value as 'CREA' | 'CRM')}>
                  <option value="CREA">CREA</option>
                  <option value="CRM">CRM</option>
                </select>
              </div>
              <div>
                <label className="sesmt-label">Nº CREA / CRM</label>
                <input className="sesmt-input" value={a.registro} onChange={(e) => updateAssinatura(index, 'registro', e.target.value)} />
              </div>
            </div>
          ))}
        </section>

        <div className="flex flex-wrap gap-3">
          <button
            type="button"
            disabled={loading}
            onClick={() => executarSalvar(false)}
            className="sesmt-btn-ghost border border-sesmt-forest/20"
          >
            <Save size={18} />
            {loading ? 'Salvando…' : 'Salvar rascunho'}
          </button>
          <button type="submit" disabled={loading} className="sesmt-btn-primary">
            <FileDown size={18} />
            {loading ? 'Processando…' : certificadoId ? 'Salvar e regerar PDF' : 'Gerar certificado'}
          </button>
        </div>
      </form>

      {pdfUrl && (
        <section className="sesmt-card">
          <div className="flex flex-wrap justify-between gap-4 mb-4">
            <h2 className="text-lg font-semibold text-sesmt-forest">Visualização do PDF</h2>
            <a href={pdfUrl} download={`certificado-${numero}.pdf`} className="sesmt-btn-accent">
              Baixar PDF
            </a>
          </div>
          <iframe title="PDF" src={pdfUrl} className="w-full rounded-xl border border-sesmt-forest/10" style={{ height: '520px' }} />
        </section>
      )}
    </div>
  );
}
