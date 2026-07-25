export type BriefingStatus = 'new' | 'read' | 'quoted' | 'archived';

export type BriefingFileMeta = {
  original: string;
  stored: string;
  ext: string;
  size: number;
  path: string;
};

export type Briefing = {
  id: string;
  created_at: string;
  status: BriefingStatus;
  name: string;
  company: string;
  email: string;
  phone: string;
  project_type: string;
  goal: string;
  business: string;
  has_website: string;
  current_url: string;
  domain: string;
  pages: string[];
  refs: string[];
  refs_notes: string;
  has_logo: string;
  logo_file: BriefingFileMeta | null;
  brand_file: BriefingFileMeta | null;
  logo_url: string;
  brand_url: string;
  color_primary: string;
  color_secondary: string;
  color_accent: string;
  suggest_palette: boolean;
  font_heading: string;
  font_body: string;
  suggest_fonts: boolean;
  styles: string[];
  tone: string;
  notes: string;
  ip?: string;
  ua?: string;
};

export const BRIEFING_STATUS_LABEL: Record<BriefingStatus, string> = {
  new: 'Novo',
  read: 'Lido',
  quoted: 'Orçado',
  archived: 'Arquivado',
};
