export type AssinaturaSlot = {
  nome: string;
  funcao: string;
  registro_tipo: 'CREA' | 'CRM';
  registro: string;
};

export type CertificadoListItem = {
  id: number;
  numero: string;
  status: 'rascunho' | 'emitido';
  nome_treinamento: string;
  colaborador_nome: string;
  colaborador_cpf: string;
  data_certificado: string;
  cidade: string;
  empresa_nome: string;
  empresa_id?: number | null;
  has_pdf: boolean;
  created_at: string;
};

export type CertificadoDetalhe = CertificadoListItem & {
  nr_tipo_id: number;
  carga_horaria: string;
  conteudo_ministrado: string | null;
  assinaturas: AssinaturaSlot[];
  pdf_url?: string | null;
};
